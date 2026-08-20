<?php

/**
 * ListProducts ability - retrieves a list of WooCommerce products.
 *
 * @package FAWpmcp\Abilities\WooCommerce\Products
 */

declare(strict_types=1);

namespace FAWpmcp\Abilities\WooCommerce\Products;

use FAWpmcp\Abilities\WooCommerce\AbstractWooCommerceAbility;

/**
 * Ability to retrieve a list of WooCommerce products.
 *
 * Supports filtering by:
 * - Status (publish, draft, pending, etc.)
 * - Type (simple, variable, grouped, external)
 * - Category
 * - Tag
 * - Stock status
 * - On sale
 *
 * @package FAWpmcp\Abilities\WooCommerce\Products
 */
final class ListProducts extends AbstractWooCommerceAbility
{
    /**
     * Default number of products per page.
     */
    private const DEFAULT_PER_PAGE = 10;

    /**
     * Maximum number of products per page.
     */
    private const MAX_PER_PAGE = 100;

    /**
     * Returns the ability identifier.
     *
     * @return string Ability name.
     */
    public function getName(): string
    {
        return 'fa-wpmcp/wc-list-products';
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
        return 'List Products';
    }

    /**
     * Returns the ability description.
     *
     * @return string Description.
     */
    public function getDescription(): string
    {
        return 'Retrieve a list of WooCommerce products with optional filtering by status, type, category, tag, stock_status, on_sale. Supports pagination with page and per_page parameters.';
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
                'status' => array(
                    'type'        => 'string',
                    'description' => 'Filter by product status.',
                    'enum'        => ['any', 'publish', 'draft', 'pending', 'private', 'trash'],
                    'default'     => 'any',
                ),
                'type' => array(
                    'type'        => 'string',
                    'description' => 'Filter by product type.',
                    'enum'        => ['simple', 'variable', 'grouped', 'external'],
                ),
                'category' => array(
                    'type'        => 'string',
                    'description' => 'Filter by category slug.',
                ),
                'tag' => array(
                    'type'        => 'string',
                    'description' => 'Filter by tag slug.',
                ),
                'stock_status' => array(
                    'type'        => 'string',
                    'description' => 'Filter by stock status.',
                    'enum'        => ['instock', 'outofstock', 'onbackorder'],
                ),
                'on_sale' => array(
                    'type'        => 'boolean',
                    'description' => 'Filter to only products on sale.',
                ),
                'search' => array(
                    'type'        => 'string',
                    'description' => 'Search products by name or SKU.',
                ),
                'page' => array(
                    'type'        => 'integer',
                    'description' => 'Page number for pagination.',
                    'minimum'     => 1,
                    'default'     => 1,
                ),
                'per_page' => array(
                    'type'        => 'integer',
                    'description' => 'Number of products per page (max 100).',
                    'minimum'     => 1,
                    'maximum'     => 100,
                    'default'     => 10,
                ),
                'orderby' => array(
                    'type'        => 'string',
                    'description' => 'Field to order by.',
                    'enum'        => ['date', 'id', 'title', 'price', 'popularity', 'rating'],
                    'default'     => 'date',
                ),
                'order' => array(
                    'type'        => 'string',
                    'description' => 'Sort order.',
                    'enum'        => ['asc', 'desc'],
                    'default'     => 'desc',
                ),
            ),
            'required'   => array(),
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
                'products' => array(
                    'type'  => 'array',
                    'items' => array(
                        'type'       => 'object',
                        'properties' => array(
                            'id'             => array('type' => 'integer'),
                            'name'           => array('type' => 'string'),
                            'slug'           => array('type' => 'string'),
                            'type'           => array('type' => 'string'),
                            'status'         => array('type' => 'string'),
                            'sku'            => array('type' => 'string'),
                            'price'          => array('type' => 'string'),
                            'regular_price'  => array('type' => 'string'),
                            'sale_price'     => array('type' => 'string'),
                            'on_sale'        => array('type' => 'boolean'),
                            'stock_quantity' => array('type' => ['integer', 'null']),
                            'stock_status'   => array('type' => 'string'),
                        ),
                    ),
                ),
                'total'    => array('type' => 'integer'),
                'page'     => array('type' => 'integer'),
                'per_page' => array('type' => 'integer'),
                'pages'    => array('type' => 'integer'),
            ),
        );
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
     * @return array<string, mixed> Products list with pagination info.
     */
    protected function doWooCommerceExecute(array $input): array
    {
        $page = (int) ($input['page'] ?? 1);
        $per_page = min((int) ($input['per_page'] ?? self::DEFAULT_PER_PAGE), self::MAX_PER_PAGE);

        $args = $this->buildQueryArgs($input, $page, $per_page);

        // Get products.
        $products = wc_get_products($args);

        // Get total count for pagination.
        $count_args = $args;
        $count_args['limit'] = -1;
        $count_args['return'] = 'ids';
        $total = count(wc_get_products($count_args));

        $formatted = array_map(
            fn($product) => $this->formatProductSummary($product),
            $products
        );

        return array(
            'products' => $formatted,
            'total'    => $total,
            'page'     => $page,
            'per_page' => $per_page,
            'pages'    => (int) ceil($total / $per_page),
        );
    }

    /**
     * Build query arguments from input.
     *
     * @param array<string, mixed> $input    Input parameters.
     * @param int                  $page     Current page.
     * @param int                  $per_page Items per page.
     * @return array<string, mixed> Query arguments.
     */
    private function buildQueryArgs(array $input, int $page, int $per_page): array
    {
        $args = array(
            'limit'  => $per_page,
            'offset' => ($page - 1) * $per_page,
            'status' => $input['status'] ?? 'any',
        );

        if (!empty($input['type'])) {
            $args['type'] = $input['type'];
        }

        if (!empty($input['category'])) {
            $args['category'] = array($input['category']);
        }

        if (!empty($input['tag'])) {
            $args['tag'] = array($input['tag']);
        }

        if (!empty($input['stock_status'])) {
            $args['stock_status'] = $input['stock_status'];
        }

        if (isset($input['on_sale']) && $input['on_sale']) {
            $args['on_sale'] = true;
        }

        if (!empty($input['search'])) {
            $args['s'] = $input['search'];
        }

        if (!empty($input['orderby'])) {
            $args['orderby'] = $input['orderby'];
        }

        if (!empty($input['order'])) {
            $args['order'] = strtoupper($input['order']);
        }

        return $args;
    }

    /**
     * Format a product for list output (summary view).
     *
     * @param \WC_Product $product The product object.
     * @return array<string, mixed> Formatted product summary.
     */
    private function formatProductSummary(\WC_Product $product): array
    {
        return array(
            'id'             => $product->get_id(),
            'name'           => $product->get_name(),
            'slug'           => $product->get_slug(),
            'type'           => $product->get_type(),
            'status'         => $product->get_status(),
            'sku'            => $product->get_sku(),
            'price'          => $product->get_price(),
            'regular_price'  => $product->get_regular_price(),
            'sale_price'     => $product->get_sale_price(),
            'on_sale'        => $product->is_on_sale(),
            'stock_quantity' => $product->get_stock_quantity(),
            'stock_status'   => $product->get_stock_status(),
        );
    }
}
