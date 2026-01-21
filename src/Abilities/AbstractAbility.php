<?php
/**
 * Abstract base class for all abilities.
 *
 * @package FAWpmcp\Abilities
 */

declare(strict_types=1);

namespace FAWpmcp\Abilities;

/**
 * Abstract base class for MCP abilities.
 *
 * Provides the contract for ability implementations with:
 * - Abstract methods for required metadata
 * - Default implementations for optional behavior
 * - Method for API registration format
 *
 * @package FAWpmcp\Abilities
 */
abstract class AbstractAbility {
    /**
     * Get the unique ability name.
     *
     * Format: "namespace/ability-name" (e.g., "fa-wpmcp/list-posts").
     *
     * @return string Ability name.
     */
    abstract public function get_name(): string;

    /**
     * Get the ability category.
     *
     * Categories group related abilities (e.g., "posts-pages", "users").
     *
     * @return string Category name.
     */
    abstract public function get_category(): string;

    /**
     * Get the human-readable label.
     *
     * @return string Ability label for display.
     */
    abstract public function get_label(): string;

    /**
     * Get the ability description.
     *
     * @return string Description explaining what the ability does.
     */
    abstract public function get_description(): string;

    /**
     * Get the input schema.
     *
     * JSON Schema format for ability input validation.
     *
     * @return array<string, mixed> JSON Schema array.
     */
    abstract public function get_input_schema(): array;

    /**
     * Get the output schema.
     *
     * JSON Schema format for ability output validation.
     *
     * @return array<string, mixed> JSON Schema array.
     */
    abstract public function get_output_schema(): array;

    /**
     * Get the required WordPress capability.
     *
     * @return string WordPress capability name.
     */
    abstract public function get_required_capability(): string;

    /**
     * Execute the ability.
     *
     * Implement this method to perform the actual ability logic.
     * Receives validated input and should return the output data.
     *
     * @param array<string, mixed> $input Validated input data.
     * @return array<string, mixed> Output data.
     * @throws \Exception If execution fails.
     */
    abstract public function do_execute( array $input ): array;

    /**
     * Get the operation type.
     *
     * Default is 'read'. Override for write operations.
     *
     * @return string 'read' or 'write'.
     */
    public function get_operation_type(): string {
        return 'read';
    }

    /**
     * Get ability annotations.
     *
     * Annotations provide hints about ability behavior.
     *
     * @return array<string, mixed> Annotations array.
     */
    public function get_annotations(): array {
        return [
            'readonly'     => 'read' === $this->get_operation_type(),
            'destructive'  => false,
            'idempotent'   => true,
            'instructions' => $this->get_description(),
        ];
    }

    /**
     * Convert ability to MCP registration array format.
     *
     * This is the format required by the Abilities API.
     *
     * @return array<string, mixed> Registration array.
     */
    public function to_registration_array(): array {
        return [
            'name'               => $this->get_name(),
            'category'           => $this->get_category(),
            'label'              => $this->get_label(),
            'description'        => $this->get_description(),
            'inputSchema'        => $this->get_input_schema(),
            'outputSchema'       => $this->get_output_schema(),
            'requiredCapability' => $this->get_required_capability(),
            'operationType'      => $this->get_operation_type(),
            'annotations'        => $this->get_annotations(),
        ];
    }
}
