<?php

/**
 * GetPermalinkStructureAbility - retrieves the current permalink structure.
 *
 * @package FAWpmcp\Abilities\Rewrite
 */

declare(strict_types=1);

namespace FAWpmcp\Abilities\Rewrite;

use FAWpmcp\Abilities\AbstractAbility;

/**
 * Ability to get the current WordPress permalink structure.
 *
 * Returns the permalink_structure option value.
 *
 * @package FAWpmcp\Abilities\Rewrite
 */
final class GetPermalinkStructureAbility extends AbstractAbility
{
    /**
     * Get the unique ability name.
     *
     * @return string Ability name.
     */
    public function getName(): string
    {
        return 'fa-wpmcp/get-permalink-structure';
    }

    /**
     * Get the ability category.
     *
     * @return string Category name.
     */
    public function getCategory(): string
    {
        return 'rewrite';
    }

    /**
     * Get the human-readable label.
     *
     * @return string Ability label.
     */
    public function getLabel(): string
    {
        return 'Get Permalink Structure';
    }

    /**
     * Get the ability description.
     *
     * @return string Description.
     */
    public function getDescription(): string
    {
        return 'Get the current WordPress permalink structure setting.';
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
            'properties' => array(),
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
                'structure' => array(
                    'type'        => 'string',
                    'description' => 'The permalink structure string (e.g., "/%postname%/").',
                ),
                'is_plain'  => array(
                    'type'        => 'boolean',
                    'description' => 'Whether the site uses plain (default) permalinks.',
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
     * @return array<string, mixed> Permalink structure data.
     */
    public function doExecute(array $input): array
    {
        $structure = get_option('permalink_structure');

        // Ensure structure is a string (could be false if option doesn't exist)
        if (! is_string($structure)) {
            $structure = '';
        }

        return array(
            'structure' => $structure,
            'is_plain'  => '' === $structure,
        );
    }
}
