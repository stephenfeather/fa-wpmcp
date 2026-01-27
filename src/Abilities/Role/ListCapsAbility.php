<?php

/**
 * ListCapsAbility - lists capabilities for a WordPress user role.
 *
 * @package FAWpmcp\Abilities\Role
 */

declare(strict_types=1);

namespace FAWpmcp\Abilities\Role;

use FAWpmcp\Abilities\AbstractAbility;
use FAWpmcp\Exceptions\RoleNotFoundException;

/**
 * Ability to list all capabilities for a specific WordPress user role.
 *
 * Returns the role name, list of capability names, and total count.
 *
 * @package FAWpmcp\Abilities\Role
 */
final class ListCapsAbility extends AbstractAbility
{
    /**
     * Get the unique ability name.
     *
     * @return string Ability name.
     */
    public function getName(): string
    {
        return 'fa-wpmcp/list-caps';
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
        return 'List Capabilities';
    }

    /**
     * Get the ability description.
     *
     * @return string Description.
     */
    public function getDescription(): string
    {
        return 'List all capabilities for a specific WordPress user role.';
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
                'role' => array(
                    'type'        => 'string',
                    'description' => 'The role slug to list capabilities for.',
                ),
            ),
            'required'   => array( 'role' ),
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
                'role'         => array(
                    'type'        => 'string',
                    'description' => 'The role slug.',
                ),
                'capabilities' => array(
                    'type'        => 'array',
                    'items'       => array( 'type' => 'string' ),
                    'description' => 'List of capability names.',
                ),
                'total'        => array(
                    'type'        => 'integer',
                    'description' => 'Total number of capabilities.',
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
        return 'list_users';
    }

    /**
     * Execute the ability.
     *
     * @param array<string, mixed> $input Validated input data.
     * @return array<string, mixed> Role capabilities.
     * @throws RoleNotFoundException If role does not exist.
     */
    public function doExecute(array $input): array
    {
        $role_slug = (string) $input['role'];
        $role      = get_role($role_slug);

        if (null === $role) {
            throw new RoleNotFoundException(
                sprintf('Role "%s" not found.', $role_slug)
            );
        }

        $capabilities = array_keys($role->capabilities);

        return array(
            'role'         => $role->name,
            'capabilities' => $capabilities,
            'total'        => count($capabilities),
        );
    }
}
