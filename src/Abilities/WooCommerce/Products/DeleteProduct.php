<?php

/**
 * DeleteProduct ability - deletes a WooCommerce product.
 *
 * @package FAWpmcp\Abilities\WooCommerce\Products
 */

declare(strict_types=1);

namespace FAWpmcp\Abilities\WooCommerce\Products;

use FAWpmcp\Abilities\WooCommerce\AbstractWooCommerceAbility;

/**
 * Ability to delete a WooCommerce product.
 *
 * By default, moves product to trash (soft delete).
 * Set force=true to permanently delete.
 *
 * @package FAWpmcp\Abilities\WooCommerce\Products
 */
final class DeleteProduct extends AbstractWooCommerceAbility
{
    /**
     * Returns the ability identifier.
     *
     * @return string Ability name.
     */
    public function getName(): string
    {
        return 'fa-wpmcp/wc-delete-product';
    }

    /**
     * Returns the ability category.
     *
     * @return string Category name.
     */
    public function getCategory(): string
    {
        return 'woocommerce-products';
    }

    /**
     * Returns the display label.
     *
     * @return string Ability label.
     */
    public function getLabel(): string
    {
        return 'Delete Product';
    }

    /**
     * Returns the ability description.
     *
     * @return string Description.
     */
    public function getDescription(): string
    {
        return 'Delete a WooCommerce product. By default moves to trash (soft delete). Set force=true to permanently delete. Returns the deleted product info.';
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
                'product_id' => array(
                    'type'        => 'integer',
                    'description' => 'The ID of the product to delete (required).',
                    'minimum'     => 1,
                ),
                'force' => array(
                    'type'        => 'boolean',
                    'description' => 'If true, permanently delete. If false (default), move to trash.',
                    'default'     => false,
                ),
            ),
            'required'   => array('product_id'),
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
                'product_id' => array(
                    'type'        => 'integer',
                    'description' => 'The ID of the deleted product.',
                ),
                'name'       => array('type' => 'string'),
                'deleted'    => array('type' => 'boolean'),
                'trashed'    => array('type' => 'boolean'),
            ),
        );
    }

    /**
     * Returns the operation type.
     *
     * @return string 'write' for delete operations.
     */
    public function getOperationType(): string
    {
        return 'write';
    }

    /**
     * Get ability annotations.
     *
     * @return array<string, mixed> Annotations array.
     */
    public function getAnnotations(): array
    {
        $annotations = parent::getAnnotations();
        $annotations['destructive'] = true;
        return $annotations;
    }

    /**
     * Returns the WordPress capability required.
     *
     * @return string WordPress capability name.
     */
    public function getRequiredCapability(): string
    {
        return 'delete_products';
    }

    /**
     * Executes the WooCommerce-specific ability logic.
     *
     * @param array<string, mixed> $input Validated input data.
     * @return array<string, mixed> Deleted product info.
     * @throws \RuntimeException If product not found or deletion fails.
     */
    protected function doWooCommerceExecute(array $input): array
    {
        $product_id = (int) $input['product_id'];
        $force = (bool) ($input['force'] ?? false);

        $product = wc_get_product($product_id);

        if (!$product) {
            throw new \RuntimeException(
                sprintf('Product with ID %d not found', $product_id)
            );
        }

        $name = $product->get_name();

        // Delete the product.
        $result = $product->delete($force);

        if (!$result) {
            throw new \RuntimeException(
                sprintf('Failed to delete product with ID %d', $product_id)
            );
        }

        return array(
            'product_id' => $product_id,
            'name'       => $name,
            'deleted'    => $force,
            'trashed'    => !$force,
        );
    }
}
