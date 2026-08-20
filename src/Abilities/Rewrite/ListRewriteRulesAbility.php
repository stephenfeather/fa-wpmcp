<?php

/**
 * ListRewriteRulesAbility - lists all WordPress rewrite rules.
 *
 * @package FAWpmcp\Abilities\Rewrite
 */

declare(strict_types=1);

namespace FAWpmcp\Abilities\Rewrite;

use FAWpmcp\Abilities\AbstractAbility;

/**
 * Ability to list all WordPress rewrite rules.
 *
 * Returns the registered rewrite rules from the rewrite_rules option.
 *
 * @package FAWpmcp\Abilities\Rewrite
 */
final class ListRewriteRulesAbility extends AbstractAbility
{
    /**
     * Get the unique ability name.
     *
     * @return string Ability name.
     */
    public function getName(): string
    {
        return 'fa-wpmcp/list-rewrite-rules';
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
        return 'List Rewrite Rules';
    }

    /**
     * Get the ability description.
     *
     * @return string Description.
     */
    public function getDescription(): string
    {
        return 'List all WordPress rewrite rules that map URL patterns to query variables.';
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
            'properties' => new \stdClass(),
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
                'rules' => array(
                    'type'        => 'array',
                    'description' => 'List of rewrite rules.',
                    'items'       => array(
                        'type'       => 'object',
                        'properties' => array(
                            'pattern' => array( 'type' => 'string' ),
                            'target'  => array( 'type' => 'string' ),
                        ),
                    ),
                ),
                'total' => array(
                    'type'        => 'integer',
                    'description' => 'Total number of rewrite rules.',
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
     * @return array<string, mixed> List of rewrite rules.
     */
    public function doExecute(array $input): array
    {
        $wp_rules = get_option('rewrite_rules');

        if (empty($wp_rules) || ! is_array($wp_rules)) {
            return array(
                'rules' => array(),
                'total' => 0,
            );
        }

        $rules = array();

        foreach ($wp_rules as $pattern => $target) {
            $rules[] = array(
                'pattern' => $pattern,
                'target'  => $target,
            );
        }

        return array(
            'rules' => $rules,
            'total' => count($rules),
        );
    }
}
