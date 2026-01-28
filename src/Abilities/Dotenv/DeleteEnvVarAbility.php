<?php

/**
 * DeleteEnvVarAbility - deletes an environment variable.
 *
 * @package FAWpmcp\Abilities\Dotenv
 */

declare(strict_types=1);

namespace FAWpmcp\Abilities\Dotenv;

use FAWpmcp\Abilities\AbstractAbility;
use FAWpmcp\Exceptions\DotenvException;

/**
 * Ability to delete an environment variable from the .env file.
 *
 * Protected variables cannot be deleted.
 *
 * @package FAWpmcp\Abilities\Dotenv
 */
final class DeleteEnvVarAbility extends AbstractAbility
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
        return 'fa-wpmcp/delete-env-var';
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
        return 'Delete Environment Variable';
    }

    /**
     * Get the ability description.
     *
     * @return string Description.
     */
    public function getDescription(): string
    {
        return 'Delete an environment variable from the Bedrock .env file. Protected variables cannot be deleted.';
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
                    'description' => 'The environment variable name to delete.',
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
                'key'     => array(
                    'type'        => 'string',
                    'description' => 'The variable name.',
                ),
                'deleted' => array(
                    'type'        => 'boolean',
                    'description' => 'Whether the variable was deleted.',
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
     * Get the operation type.
     *
     * @return string Operation type: 'read' or 'write'.
     */
    public function getOperationType(): string
    {
        return 'write';
    }

    /**
     * Get annotations for this ability.
     *
     * @return array<string, mixed> Annotations array.
     */
    public function getAnnotations(): array
    {
        $annotations = parent::getAnnotations();
        $annotations['readonly'] = false;
        $annotations['destructive'] = true;
        $annotations['idempotent'] = false;
        return $annotations;
    }

    /**
     * Execute the ability.
     *
     * @param array<string, mixed> $input Validated input data.
     * @return array<string, mixed> Result of the operation.
     * @throws DotenvException If the operation fails.
     */
    public function doExecute(array $input): array
    {
        $key = (string) $input['key'];

        // Check if variable is protected.
        if ($this->policy->isProtected($key)) {
            throw new DotenvException(
                'Cannot delete protected variable: ' . $key . '. Protected variables include database credentials, WP_ENV, and security keys/salts.'
            );
        }

        $file_path = $this->locator->locate();
        $content = @file_get_contents($file_path);

        if ($content === false) {
            throw new DotenvException('Could not read .env file: ' . $file_path);
        }

        $result = $this->parser->deleteValue($content, $key);

        if ($result['deleted']) {
            // Write atomically using temp file and rename.
            $this->atomicWrite($file_path, $result['content']);
        }

        return array(
            'key'     => $key,
            'deleted' => $result['deleted'],
        );
    }

    /**
     * Write content to file atomically.
     *
     * @param string $file_path Target file path.
     * @param string $content   Content to write.
     * @throws DotenvException If write fails.
     */
    private function atomicWrite(string $file_path, string $content): void
    {
        $temp_path = $file_path . '.tmp.' . uniqid('', true);

        $written = file_put_contents($temp_path, $content, LOCK_EX);
        if ($written === false) {
            throw new DotenvException('Could not write to temporary file: ' . $temp_path);
        }

        // Preserve original file permissions.
        $perms = fileperms($file_path);
        if ($perms !== false) {
            chmod($temp_path, $perms);
        }

        // Atomic rename.
        if (!rename($temp_path, $file_path)) {
            unlink($temp_path);
            throw new DotenvException('Could not rename temporary file to: ' . $file_path);
        }
    }
}
