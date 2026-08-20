<?php

/**
 * CreateProduct ability - creates a new WooCommerce product.
 *
 * @package FAWpmcp\Abilities\WooCommerce\Products
 */

declare(strict_types=1);

namespace FAWpmcp\Abilities\WooCommerce\Products;

use FAWpmcp\Abilities\WooCommerce\AbstractWooCommerceAbility;

/**
 * Ability to create a new WooCommerce product.
 *
 * Creates simple products by default. For variable products,
 * use this to create the parent product, then use CreateVariation
 * to add variations.
 *
 * @package FAWpmcp\Abilities\WooCommerce\Products
 */
final class CreateProduct extends AbstractWooCommerceAbility
{
    /**
     * Returns the ability identifier.
     *
     * @return string Ability name.
     */
    public function getName(): string
    {
        return 'fa-wpmcp/wc-create-product';
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
        return 'Create Product';
    }

    /**
     * Returns the ability description.
     *
     * @return string Description.
     */
    public function getDescription(): string
    {
        return 'Create a new WooCommerce product. Required: name. Optional: type (simple/variable/grouped/external), status, sku, regular_price, sale_price, description, short_description, manage_stock, stock_quantity, stock_status, categories, tags.';
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
                'name' => array(
                    'type'        => 'string',
                    'description' => 'Product name (required).',
                    'minLength'   => 1,
                ),
                'type' => array(
                    'type'        => 'string',
                    'description' => 'Product type.',
                    'enum'        => ['simple', 'variable', 'grouped', 'external'],
                    'default'     => 'simple',
                ),
                'status' => array(
                    'type'        => 'string',
                    'description' => 'Product status.',
                    'enum'        => ['publish', 'draft', 'pending', 'private'],
                    'default'     => 'draft',
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
                    'default'     => false,
                ),
                'stock_quantity' => array(
                    'type'        => 'integer',
                    'description' => 'Stock quantity (requires manage_stock=true).',
                    'minimum'     => 0,
                ),
                'stock_status' => array(
                    'type'        => 'string',
                    'description' => 'Stock status.',
                    'enum'        => ['instock', 'outofstock', 'onbackorder'],
                    'default'     => 'instock',
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
                'virtual' => array(
                    'type'        => 'boolean',
                    'description' => 'Whether product is virtual (no shipping).',
                    'default'     => false,
                ),
                'downloadable' => array(
                    'type'        => 'boolean',
                    'description' => 'Whether product is downloadable.',
                    'default'     => false,
                ),
            ),
            'required'   => array('name'),
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
                    'description' => 'The ID of the created product.',
                ),
                'name'       => array('type' => 'string'),
                'status'     => array('type' => 'string'),
                'permalink'  => array('type' => 'string'),
            ),
        );
    }

    /**
     * Returns the operation type.
     *
     * @return string 'write' for create operations.
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
     * @return array<string, mixed> Created product info.
     * @throws \RuntimeException If product creation fails.
     */
    protected function doWooCommerceExecute(array $input): array
    {
        $type = $input['type'] ?? 'simple';
        $product = $this->createProductByType($type);

        $this->setProductProperties($product, $input);

        $product_id = $product->save();

        if (!$product_id) {
            throw new \RuntimeException('Failed to create product');
        }

        return array(
            'product_id' => $product_id,
            'name'       => $product->get_name(),
            'status'     => $product->get_status(),
            'permalink'  => get_permalink($product_id),
        );
    }

    /**
     * Create a product instance based on type.
     *
     * @param string $type Product type.
     * @return \WC_Product Product instance.
     */
    private function createProductByType(string $type): \WC_Product
    {
        switch ($type) {
            case 'variable':
                return new \WC_Product_Variable();
            case 'grouped':
                return new \WC_Product_Grouped();
            case 'external':
                return new \WC_Product_External();
            default:
                return new \WC_Product_Simple();
        }
    }

    /**
     * Set product properties from input.
     *
     * @param \WC_Product          $product Product instance.
     * @param array<string, mixed> $input   Input data.
     * @return void
     */
    private function setProductProperties(\WC_Product $product, array $input): void
    {
        $product->set_name($input['name']);

        if (isset($input['status'])) {
            $product->set_status($input['status']);
        } else {
            $product->set_status('draft');
        }

        if (!empty($input['sku'])) {
            $product->set_sku($input['sku']);
        }

        if (isset($input['regular_price'])) {
            $product->set_regular_price($input['regular_price']);
        }

        if (isset($input['sale_price'])) {
            $product->set_sale_price($input['sale_price']);
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

        if (!empty($input['categories'])) {
            $product->set_category_ids($input['categories']);
        }

        if (!empty($input['tags'])) {
            $product->set_tag_ids($input['tags']);
        }

        if (isset($input['virtual'])) {
            $product->set_virtual($input['virtual']);
        }

        if (isset($input['downloadable'])) {
            $product->set_downloadable($input['downloadable']);
        }
    }
}
