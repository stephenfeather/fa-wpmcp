<?php

/**
 * GetEnvVarAbility - retrieves a specific environment variable.
 *
 * @package FAWpmcp\Abilities\Dotenv
 */

declare(strict_types=1);

namespace FAWpmcp\Abilities\Dotenv;

use FAWpmcp\Abilities\AbstractAbility;
use FAWpmcp\Exceptions\DotenvException;

/**
 * Ability to get a specific environment variable from the .env file.
 *
 * @package FAWpmcp\Abilities\Dotenv
 */
final class GetEnvVarAbility extends AbstractAbility
{
    /**
     * File locator instance.
     *
     * @var EnvFileLocator
     */
    private EnvFileLocator $locator;

    /**
     * File parser instance.
     *
     * @var EnvFileParser
     */
    private EnvFileParser $parser;

    /**
     * Access policy instance.
     *
     * @var EnvAccessPolicy
     */
    private EnvAccessPolicy $policy;

    /**
     * Constructor.
     *
     * @param EnvFileLocator|null  $locator File locator.
     * @param EnvFileParser|null   $parser  File parser.
     * @param EnvAccessPolicy|null $policy  Access policy.
     */
    public function __construct(
        ?EnvFileLocator $locator = null,
        ?EnvFileParser $parser = null,
        ?EnvAccessPolicy $policy = null
    ) {
        $this->locator = $locator ?? new EnvFileLocator();
        $this->parser = $parser ?? new EnvFileParser();
        $this->policy = $policy ?? new EnvAccessPolicy();
    }

    /**
     * Get the unique ability name.
     *
     * @return string Ability name.
     */
    public function getName(): string
    {
        return 'fa-wpmcp/get-env-var';
    }

    /**
     * Get the ability category.
     *
     * @return string Category name.
     */
    public function getCategory(): string
    {
        return 'dotenv';
    }

    /**
     * Get the human-readable label.
     *
     * @return string Ability label.
     */
    public function getLabel(): string
    {
        return 'Get Environment Variable';
    }

    /**
     * Get the ability description.
     *
     * @return string Description.
     */
    public function getDescription(): string
    {
        return 'Get a specific environment variable from the Bedrock .env file.';
    }

    /**
     * Get the input schema.
     *
     * @return array<string, mixed> JSON Schema array.
     */
    public function getInputSchema(): array
    {
        return array(
            'type'       => 'object',
            'properties' => array(
                'key' => array(
                    'type'        => 'string',
                    'description' => 'The environment variable name.',
                ),
                'show_sensitive' => array(
                    'type'        => 'boolean',
                    'description' => 'Show sensitive values instead of redacting them.',
                    'default'     => false,
                ),
            ),
            'required'   => array( 'key' ),
        );
    }

    /**
     * Get the output schema.
     *
     * @return array<string, mixed> JSON Schema array.
     */
    public function getOutputSchema(): array
    {
        return array(
            'type'       => 'object',
            'properties' => array(
                'key'       => array(
                    'type'        => 'string',
                    'description' => 'The variable name.',
                ),
                'value'     => array(
                    'type'        => 'string',
                    'description' => 'The variable value (may be redacted).',
                ),
                'sensitive' => array(
                    'type'        => 'boolean',
                    'description' => 'Whether the variable is sensitive.',
                ),
                'exists'    => array(
                    'type'        => 'boolean',
                    'description' => 'Whether the variable exists in the file.',
                ),
            ),
        );
    }

    /**
     * Get the required WordPress capability.
     *
     * @return string WordPress capability name.
     */
    public function getRequiredCapability(): string
    {
        return 'manage_options';
    }

    /**
     * Execute the ability.
     *
     * @param array<string, mixed> $input Validated input data.
     * @return array<string, mixed> Variable information.
     * @throws DotenvException If the .env file cannot be read.
     */
    public function doExecute(array $input): array
    {
        $key = (string) $input['key'];
        $show_sensitive = (bool) ($input['show_sensitive'] ?? false);

        $file_path = $this->locator->locate();
        $content = @file_get_contents($file_path);

        if ($content === false) {
            throw new DotenvException('Could not read .env file: ' . $file_path);
        }

        $value = $this->parser->getValue($content, $key);
        $exists = $value !== null;
        $is_sensitive = $this->policy->isSensitive($key);

        if ($exists && $is_sensitive && !$show_sensitive) {
            $display_value = '[REDACTED]';
        } else {
            $display_value = $value ?? '';
        }

        return array(
            'key'       => $key,
            'value'     => $display_value,
            'sensitive' => $is_sensitive,
            'exists'    => $exists,
        );
    }
}
