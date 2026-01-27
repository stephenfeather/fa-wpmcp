<?php

/**
 * ListEnvVarsAbility - lists all environment variables from .env file.
 *
 * @package FAWpmcp\Abilities\Dotenv
 */

declare(strict_types=1);

namespace FAWpmcp\Abilities\Dotenv;

use FAWpmcp\Abilities\AbstractAbility;
use FAWpmcp\Exceptions\DotenvException;

/**
 * Ability to list all environment variables from the .env file.
 *
 * Sensitive values are redacted unless explicitly requested.
 *
 * @package FAWpmcp\Abilities\Dotenv
 */
final class ListEnvVarsAbility extends AbstractAbility
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
        return 'fa-wpmcp/list-env-vars';
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
        return 'List Environment Variables';
    }

    /**
     * Get the ability description.
     *
     * @return string Description.
     */
    public function getDescription(): string
    {
        return 'List all environment variables from the Bedrock .env file. Sensitive values are redacted by default.';
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
                'show_sensitive' => array(
                    'type'        => 'boolean',
                    'description' => 'Show sensitive values instead of redacting them.',
                    'default'     => false,
                ),
            ),
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
                'variables' => array(
                    'type'        => 'array',
                    'description' => 'List of environment variables.',
                    'items'       => array(
                        'type'       => 'object',
                        'properties' => array(
                            'key'       => array( 'type' => 'string' ),
                            'value'     => array( 'type' => 'string' ),
                            'sensitive' => array( 'type' => 'boolean' ),
                        ),
                    ),
                ),
                'total'     => array(
                    'type'        => 'integer',
                    'description' => 'Total number of variables.',
                ),
                'file_path' => array(
                    'type'        => 'string',
                    'description' => 'Path to the .env file.',
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
     * @return array<string, mixed> List of environment variables.
     * @throws DotenvException If the .env file cannot be read.
     */
    public function doExecute(array $input): array
    {
        $show_sensitive = (bool) ($input['show_sensitive'] ?? false);

        $file_path = $this->locator->locate();
        $content = @file_get_contents($file_path);

        if ($content === false) {
            throw new DotenvException('Could not read .env file: ' . $file_path);
        }

        $parsed = $this->parser->parse($content);
        $variables = array();

        foreach ($parsed as $key => $value) {
            $is_sensitive = $this->policy->isSensitive($key);
            $display_value = ($is_sensitive && !$show_sensitive) ? '[REDACTED]' : $value;

            $variables[] = array(
                'key'       => $key,
                'value'     => $display_value,
                'sensitive' => $is_sensitive,
            );
        }

        return array(
            'variables' => $variables,
            'total'     => count($variables),
            'file_path' => $file_path,
        );
    }
}
