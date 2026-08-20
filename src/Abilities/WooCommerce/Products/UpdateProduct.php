<?php

/**
 * UpdateProduct ability - updates an existing WooCommerce product.
 *
 * @package FAWpmcp\Abilities\WooCommerce\Products
 */

declare(strict_types=1);

namespace FAWpmcp\Abilities\WooCommerce\Products;

use FAWpmcp\Abilities\WooCommerce\AbstractWooCommerceAbility;

/**
 * Ability to update an existing WooCommerce product.
 *
 * Updates only the fields that are provided in the input.
 * Omitted fields retain their current values.
 *
 * @package FAWpmcp\Abilities\WooCommerce\Products
 */
final class UpdateProduct extends AbstractWooCommerceAbility
{
    /**
     * Returns the ability identifier.
     *
     * @return string Ability name.
     */
    public function getName(): string
    {
        return 'fa-wpmcp/wc-update-product';
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
        return 'Update Product';
    }

    /**
     * Returns the ability description.
     *
     * @return string Description.
     */
    public function getDescription(): string
    {
        return 'Update an existing WooCommerce product. Required: product_id. Optional: name, status, sku, regular_price, sale_price, description, short_description, manage_stock, stock_quantity, stock_status, categories, tags. Only provided fields are updated.';
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
                    'description' => 'The ID of the product to update (required).',
                    'minimum'     => 1,
                ),
                'name' => array(
                    'type'        => 'string',
                    'description' => 'Product name.',
                    'minLength'   => 1,
                ),
                'status' => array(
                    'type'        => 'string',
                    'description' => 'Product status.',
                    'enum'        => ['publish', 'draft', 'pending', 'private', 'trash'],
                ),
                'sku' => array(
                    'type'        => 'string',
                    'description' => 'Stock Keeping Unit.',
                ),
                'regular_price' => array(
                    'type'        => 'string',
                    'description' => 'Regular price.',
                ),
                'sale_price' => array(
                    'type'        => 'string',
                    'description' => 'Sale price.',
                ),
                'description' => array(
                    'type'        => 'string',
                    'description' => 'Full product description.',
                ),
                'short_description' => array(
                    'type'        => 'string',
                    'description' => 'Short product description.',
                ),
                'manage_stock' => array(
                    'type'        => 'boolean',
                    'description' => 'Whether to manage stock.',
                ),
                'stock_quantity' => array(
                    'type'        => 'integer',
                    'description' => 'Stock quantity.',
                    'minimum'     => 0,
                ),
                'stock_status' => array(
                    'type'        => 'string',
                    'description' => 'Stock status.',
                    'enum'        => ['instock', 'outofstock', 'onbackorder'],
                ),
                'categories' => array(
                    'type'        => 'array',
                    'description' => 'Product category IDs.',
                    'items'       => array('type' => 'integer'),
                ),
                'tags' => array(
                    'type'        => 'array',
                    'description' => 'Product tag IDs.',
                    'items'       => array('type' => 'integer'),
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
                    'description' => 'The ID of the updated product.',
                ),
                'name'       => array('type' => 'string'),
                'status'     => array('type' => 'string'),
                'updated'    => array('type' => 'boolean'),
            ),
        );
    }

    /**
     * Returns the operation type.
     *
     * @return string 'write' for update operations.
     */
    public function getOperationType(): string
    {
        return 'write';
    }

    /**
     * Returns the WordPress capability required.
     *
     * @return string WordPress capability name.
     */
    public function getRequiredCapability(): string
    {
        return 'edit_products';
    }

    /**
     * Executes the WooCommerce-specific ability logic.
     *
     * @param array<string, mixed> $input Validated input data.
     * @return array<string, mixed> Updated product info.
     * @throws \RuntimeException If product not found or update fails.
     */
    protected function doWooCommerceExecute(array $input): array
    {
        $product_id = (int) $input['product_id'];

        $product = wc_get_product($product_id);

        if (!$product) {
            throw new \RuntimeException(
                sprintf('Product with ID %d not found', $product_id)
            );
        }

        $this->updateProductProperties($product, $input);

        $saved_id = $product->save();

        if (!$saved_id) {
            throw new \RuntimeException('Failed to update product');
        }

        return array(
            'product_id' => $saved_id,
            'name'       => $product->get_name(),
            'status'     => $product->get_status(),
            'updated'    => true,
        );
    }

    /**
     * Update product properties from input.
     *
     * Only updates properties that are explicitly provided in input.
     *
     * @param \WC_Product          $product Product instance.
     * @param array<string, mixed> $input   Input data.
     * @return void
     */
    private function updateProductProperties(\WC_Product $product, array $input): void
    {
        if (isset($input['name'])) {
            $product->set_name($input['name']);
        }

        if (isset($input['status'])) {
            $product->set_status($input['status']);
        }

        if (array_key_exists('sku', $input)) {
            $product->set_sku($input['sku'] ?? '');
        }

        if (isset($input['regular_price'])) {
            $product->set_regular_price($input['regular_price']);
        }

        if (array_key_exists('sale_price', $input)) {
            $product->set_sale_price($input['sale_price'] ?? '');
        }

        if (isset($input['description'])) {
            $product->set_description($input['description']);
        }

        if (isset($input['short_description'])) {
            $product->set_short_description($input['short_description']);
        }

        if (isset($input['manage_stock'])) {
            $product->set_manage_stock($input['manage_stock']);
        }

        if (isset($input['stock_quantity'])) {
            $product->set_stock_quantity($input['stock_quantity']);
        }

        if (isset($input['stock_status'])) {
            $product->set_stock_status($input['stock_status']);
        }

        if (isset($input['categories'])) {
            $product->set_category_ids($input['categories']);
        }

        if (isset($input['tags'])) {
            $product->set_tag_ids($input['tags']);
        }
    }
}
