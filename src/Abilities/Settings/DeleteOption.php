<?php

/**
 * DeleteOption ability - deletes a WordPress option.
 *
 * @package FAWpmcp\Abilities\Settings
 */

declare(strict_types=1);

namespace FAWpmcp\Abilities\Settings;

use FAWpmcp\Abilities\AbstractAbility;
use FAWpmcp\Abilities\Settings\OptionAccessPolicy;
use FAWpmcp\Exceptions\OptionException;

/**
 * Ability to delete a WordPress option.
 *
 * Removes the option from the database.
 *
 * @package FAWpmcp\Abilities\Settings
 */
final class DeleteOption extends AbstractAbility
{
    /**
     * Returns the ability identifier.
     *
     * @return string Ability name.
     */
    public function getName(): string
    {
        return 'fa-wpmcp/delete-option';
    }

    /**
     * Returns the ability category.
     *
     * @return string Category name.
     */
    public function getCategory(): string
    {
        return 'settings';
    }

    /**
     * Returns the display label.
     *
     * @return string Ability label.
     */
    public function getLabel(): string
    {
        return 'Delete Option';
    }

    /**
     * Returns the ability description.
     *
     * @return string Description.
     */
    public function getDescription(): string
    {
        return 'Delete a WordPress option from the database.';
    }

    /**
     * Returns the operation type.
     *
     * @return string Operation type.
     */
    public function getOperationType(): string
    {
        return 'write';
    }

    /**
     * Returns the JSON Schema for input validation.
     *
     * @return array<string, mixed> JSON Schema array.
     */
    public function getInputSchema(): array
    {
        return array(
            'type'       => 'object',
            'properties' => array(
                'option_name' => array(
                    'type'        => 'string',
                    'description' => 'The name of the option to delete.',
                ),
            ),
            'required'   => array( 'option_name' ),
        );
    }

    /**
     * Returns the JSON Schema for output.
     *
     * @return array<string, mixed> JSON Schema array.
     */
    public function getOutputSchema(): array
    {
        return array(
            'type'       => 'object',
            'properties' => array(
                'option_name' => array(
                    'type'        => 'string',
                    'description' => 'The name of the option.',
                ),
                'deleted'     => array(
                    'type'        => 'boolean',
                    'description' => 'Whether the option was successfully deleted.',
                ),
            ),
        );
    }

    /**
     * Returns the WordPress capability required.
     *
     * @return string WordPress capability name.
     */
    public function getRequiredCapability(): string
    {
        return 'manage_options';
    }

    /**
     * Get ability annotations.
     *
     * Marks this ability as destructive and non-idempotent.
     *
     * @return array<string, mixed> Annotations array.
     */
    public function getAnnotations(): array
    {
        $annotations                = parent::getAnnotations();
        $annotations['destructive'] = true;
        $annotations['idempotent']  = true;
        return $annotations;
    }

    /**
     * Executes the ability.
     *
     * @param array<string, mixed> $input Validated input data.
     * @return array<string, mixed> Deletion result.
     * @throws OptionException If option name is invalid or protected.
     */
    public function doExecute(array $input): array
    {
        $option_name = sanitize_key($input['option_name']);

        // Validate option name length (MySQL utf8mb4 index limit).
        if (strlen($option_name) > 191) {
            throw new OptionException('Option name exceeds maximum length of 191 characters.');
        }

        OptionAccessPolicy::assertAllowed($option_name);
        $deleted = delete_option($option_name);

        return array(
            'option_name' => $option_name,
            'deleted'     => $deleted,
        );
    }
}
