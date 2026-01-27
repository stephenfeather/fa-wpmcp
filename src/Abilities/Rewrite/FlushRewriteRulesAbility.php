<?php

/**
 * FlushRewriteRulesAbility - regenerates WordPress rewrite rules.
 *
 * @package FAWpmcp\Abilities\Rewrite
 */

declare(strict_types=1);

namespace FAWpmcp\Abilities\Rewrite;

use FAWpmcp\Abilities\AbstractAbility;

/**
 * Ability to flush and regenerate WordPress rewrite rules.
 *
 * Calls flush_rewrite_rules() to regenerate the rewrite rules.
 * Can perform either a hard flush (regenerates .htaccess) or soft flush.
 *
 * @package FAWpmcp\Abilities\Rewrite
 */
final class FlushRewriteRulesAbility extends AbstractAbility
{
    /**
     * Get the unique ability name.
     *
     * @return string Ability name.
     */
    public function getName(): string
    {
        return 'fa-wpmcp/flush-rewrite-rules';
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
        return 'Flush Rewrite Rules';
    }

    /**
     * Get the ability description.
     *
     * @return string Description.
     */
    public function getDescription(): string
    {
        return 'Flush and regenerate WordPress rewrite rules. Use after adding custom rewrite rules or changing permalink structure.';
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
                'hard' => array(
                    'type'        => 'boolean',
                    'description' => 'Whether to perform a hard flush (regenerate .htaccess). Defaults to true.',
                    'default'     => true,
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
                'flushed' => array(
                    'type'        => 'boolean',
                    'description' => 'Whether the rewrite rules were flushed.',
                ),
                'hard'    => array(
                    'type'        => 'boolean',
                    'description' => 'Whether a hard flush was performed.',
                ),
                'message' => array(
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
     * Marks this ability as idempotent (can be called multiple times safely).
     *
     * @return array<string, mixed> Annotations array.
     */
    public function getAnnotations(): array
    {
        $annotations               = parent::getAnnotations();
        $annotations['mcp.public'] = true;
        $annotations['idempotent'] = true;
        return $annotations;
    }

    /**
     * Execute the ability.
     *
     * @param array<string, mixed> $input Validated input data.
     * @return array<string, mixed> Flush result.
     */
    public function doExecute(array $input): array
    {
        $hard = $input['hard'] ?? true;

        flush_rewrite_rules($hard);

        $flush_type = $hard ? 'hard' : 'soft';

        return array(
            'flushed' => true,
            'hard'    => $hard,
            'message' => "Rewrite rules flushed successfully ({$flush_type} flush).",
        );
    }
}
