<?php

/**
 * GetConfigConstantAbility - retrieves a specific WordPress configuration constant.
 *
 * @package FAWpmcp\Abilities\Config
 */

declare(strict_types=1);

namespace FAWpmcp\Abilities\Config;

use FAWpmcp\Abilities\AbstractAbility;
use FAWpmcp\Exceptions\ConfigConstantException;

/**
 * Ability to get a specific WordPress configuration constant.
 *
 * Returns the value of a specific configuration constant.
 * Sensitive constants (passwords, keys, salts) cannot be retrieved.
 *
 * @package FAWpmcp\Abilities\Config
 */
final class GetConfigConstantAbility extends AbstractAbility
{
    use ConfigValueFormatterTrait;
    /**
     * Constants that contain sensitive data and cannot be retrieved.
     *
     * @var array<string>
     */
    private const SENSITIVE_CONSTANTS = array(
        'DB_PASSWORD',
        'AUTH_KEY',
        'SECURE_AUTH_KEY',
        'LOGGED_IN_KEY',
        'NONCE_KEY',
        'AUTH_SALT',
        'SECURE_AUTH_SALT',
        'LOGGED_IN_SALT',
        'NONCE_SALT',
        'WP_CACHE_KEY_SALT',
        'JWT_AUTH_SECRET_KEY',
        'WPMS_MAILER',
        'WPMS_SMTP_PASS',
        'AWS_ACCESS_KEY_ID',
        'AWS_SECRET_ACCESS_KEY',
        'STRIPE_SECRET_KEY',
        'STRIPE_WEBHOOK_SECRET',
    );

    /**
     * Get the unique ability name.
     *
     * @return string Ability name.
     */
    public function getName(): string
    {
        return 'fa-wpmcp/get-config-constant';
    }

    /**
     * Get the ability category.
     *
     * @return string Category name.
     */
    public function getCategory(): string
    {
        return 'config';
    }

    /**
     * Get the human-readable label.
     *
     * @return string Ability label.
     */
    public function getLabel(): string
    {
        return 'Get Config Constant';
    }

    /**
     * Get the ability description.
     *
     * @return string Description.
     */
    public function getDescription(): string
    {
        return 'Get the value of a specific WordPress configuration constant. Sensitive constants (passwords, keys, salts) cannot be retrieved.';
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
                'name' => array(
                    'type'        => 'string',
                    'description' => 'The constant name to retrieve (e.g., WP_DEBUG, ABSPATH).',
                ),
            ),
            'required'   => array('name'),
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
                'name'    => array(
                    'type'        => 'string',
                    'description' => 'Constant name.',
                ),
                'value'   => array(
                    'type'        => 'string',
                    'description' => 'Constant value (as string).',
                ),
                'type'    => array(
                    'type'        => 'string',
                    'description' => 'PHP type of the value.',
                ),
                'defined' => array(
                    'type'        => 'boolean',
                    'description' => 'Whether the constant is defined.',
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
     * @return array<string, mixed> Constant information.
     * @throws ConfigConstantException If constant name is invalid or sensitive.
     */
    public function doExecute(array $input): array
    {
        $name = $input['name'] ?? '';

        // Validate constant name format.
        if (empty($name) || ! preg_match('/^[A-Z][A-Z0-9_]*$/', $name)) {
            throw new ConfigConstantException(
                'Invalid constant name. Must be uppercase with underscores (e.g., WP_DEBUG).'
            );
        }

        // Check if this is a sensitive constant.
        if ($this->isSensitive($name)) {
            throw new ConfigConstantException(
                'Cannot retrieve sensitive constant: ' . $name . '. This constant may contain passwords, keys, or salts.'
            );
        }

        // Check if the constant is defined.
        if (! defined($name)) {
            return array(
                'name'    => $name,
                'value'   => '',
                'type'    => 'undefined',
                'defined' => false,
            );
        }

        $value = constant($name);

        return array(
            'name'    => $name,
            'value'   => $this->formatValue($value),
            'type'    => gettype($value),
            'defined' => true,
        );
    }

    /**
     * Check if a constant name is sensitive.
     *
     * @param string $name Constant name.
     * @return bool True if sensitive.
     */
    private function isSensitive(string $name): bool
    {
        // Direct match.
        if (in_array($name, self::SENSITIVE_CONSTANTS, true)) {
            return true;
        }

        // Pattern match for common sensitive patterns.
        $sensitivePatterns = array(
            '/PASSWORD/i',
            '/SECRET/i',
            '/API_KEY/i',
            '/PRIVATE_KEY/i',
            '/_KEY$/i',
            '/_SALT$/i',
            '/TOKEN/i',
            '/CREDENTIAL/i',
        );

        foreach ($sensitivePatterns as $pattern) {
            if (preg_match($pattern, $name)) {
                return true;
            }
        }

        return false;
    }

}
