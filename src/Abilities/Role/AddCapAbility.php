<?php

/**
 * AddCapAbility - adds capabilities to a WordPress user role.
 *
 * @package FAWpmcp\Abilities\Role
 */

declare(strict_types=1);

namespace FAWpmcp\Abilities\Role;

use FAWpmcp\Abilities\AbstractAbility;
use FAWpmcp\Exceptions\RoleNotFoundException;

/**
 * Ability to add one or more capabilities to a WordPress user role.
 *
 * Adds specified capabilities to an existing role.
 *
 * @package FAWpmcp\Abilities\Role
 */
final class AddCapAbility extends AbstractAbility
{
    /**
     * Get the unique ability name.
     *
     * @return string Ability name.
     */
    public function getName(): string
    {
        return 'fa-wpmcp/add-cap';
    }

    /**
     * Get the ability category.
     *
     * @return string Category name.
     */
    public function getCategory(): string
    {
        return 'role';
    }

    /**
     * Get the human-readable label.
     *
     * @return string Ability label.
     */
    public function getLabel(): string
    {
        return 'Add Capability';
    }

    /**
     * Get the ability description.
     *
     * @return string Description.
     */
    public function getDescription(): string
    {
        return 'Add one or more capabilities to a WordPress user role.';
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
                'role'         => array(
                    'type'        => 'string',
                    'description' => 'The role slug to add capabilities to.',
                ),
                'capabilities' => array(
                    'type'        => 'array',
                    'items'       => array( 'type' => 'string' ),
                    'description' => 'Capabilities to add.',
                ),
            ),
            'required'   => array( 'role', 'capabilities' ),
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
                'role'               => array(
                    'type'        => 'string',
                    'description' => 'The role slug.',
                ),
                'added'              => array(
                    'type'        => 'array',
                    'items'       => array( 'type' => 'string' ),
                    'description' => 'Capabilities that were added.',
                ),
                'total_capabilities' => array(
                    'type'        => 'integer',
                    'description' => 'Total capabilities after adding.',
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
        return 'promote_users';
    }

    /**
     * Get the operation type.
     *
     * @return string 'write' for this ability.
     */
    public function getOperationType(): string
    {
        return 'write';
    }

    /**
     * Execute the ability.
     *
     * @param array<string, mixed> $input Validated input data.
     * @return array<string, mixed> Result with added capabilities.
     * @throws RoleNotFoundException If role does not exist.
     */
    public function doExecute(array $input): array
    {
        $role_slug    = (string) $input['role'];
        $capabilities = $input['capabilities'] ?? array();

        $role = get_role($role_slug);
        if (null === $role) {
            throw new RoleNotFoundException(
                sprintf('Role "%s" not found.', $role_slug)
            );
        }

        $added = array();
        foreach ($capabilities as $cap) {
            $cap_string = (string) $cap;
            $role->add_cap($cap_string, true);
            $added[] = $cap_string;
        }

        return array(
            'role'               => $role->name,
            'added'              => $added,
            'total_capabilities' => count($role->capabilities),
        );
    }
}
