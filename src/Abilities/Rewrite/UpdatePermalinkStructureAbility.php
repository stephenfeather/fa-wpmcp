<?php

/**
 * UpdatePermalinkStructureAbility - updates the permalink structure.
 *
 * @package FAWpmcp\Abilities\Rewrite
 */

declare(strict_types=1);

namespace FAWpmcp\Abilities\Rewrite;

use FAWpmcp\Abilities\AbstractAbility;

/**
 * Ability to update the WordPress permalink structure.
 *
 * Updates the permalink_structure option and flushes rewrite rules.
 *
 * @package FAWpmcp\Abilities\Rewrite
 */
final class UpdatePermalinkStructureAbility extends AbstractAbility
{
    /**
     * Get the unique ability name.
     *
     * @return string Ability name.
     */
    public function getName(): string
    {
        return 'fa-wpmcp/update-permalink-structure';
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
        return 'Update Permalink Structure';
    }

    /**
     * Get the ability description.
     *
     * @return string Description.
     */
    public function getDescription(): string
    {
        return 'Update the WordPress permalink structure. Automatically flushes rewrite rules after updating.';
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
                'structure' => array(
                    'type'        => 'string',
                    'description' => 'The new permalink structure (e.g., "/%postname%/", "/%year%/%monthnum%/%postname%/", or "" for plain).',
                ),
            ),
            'required'   => array( 'structure' ),
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
                'updated'   => array(
                    'type'        => 'boolean',
                    'description' => 'Whether the permalink structure was updated.',
                ),
                'structure' => array(
                    'type'        => 'string',
                    'description' => 'The new permalink structure.',
                ),
                'flushed'   => array(
                    'type'        => 'boolean',
                    'description' => 'Whether rewrite rules were flushed.',
                ),
                'message'   => array(
                    'type'        => 'string',
                    'description' => 'A message describing the result.',
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
     * @return string Operation type ('read' or 'write').
     */
    public function getOperationType(): string
    {
        return 'write';
    }

    /**
     * Get ability annotations.
     *
     * Marks this ability as idempotent (setting the same structure multiple times is safe)
     * and destructive (modifies existing data).
     *
     * @return array<string, mixed> Annotations array.
     */
    public function getAnnotations(): array
    {
        $annotations                = parent::getAnnotations();
        $annotations['mcp.public']  = true;
        $annotations['idempotent']  = true;
        $annotations['destructive'] = true;
        return $annotations;
    }

    /**
     * Execute the ability.
     *
     * @param array<string, mixed> $input Validated input data.
     * @return array<string, mixed> Update result.
     */
    public function doExecute(array $input): array
    {
        $structure = $input['structure'];

        // Update the permalink structure option
        $updated = update_option('permalink_structure', $structure);

        // Always flush rewrite rules after updating permalink structure
        flush_rewrite_rules(true);

        $message = $updated
            ? 'Permalink structure updated successfully.'
            : 'Permalink structure unchanged (already set to this value).';

        return array(
            'updated'   => $updated,
            'structure' => $structure,
            'flushed'   => true,
            'message'   => $message,
        );
    }
}
