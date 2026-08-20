<?php

/**
 * GetProduct ability - retrieves a single WooCommerce product by ID.
 *
 * @package FAWpmcp\Abilities\WooCommerce\Products
 */

declare(strict_types=1);

namespace FAWpmcp\Abilities\WooCommerce\Products;

use FAWpmcp\Abilities\WooCommerce\AbstractWooCommerceAbility;

/**
 * Ability to retrieve a single WooCommerce product by ID.
 *
 * Returns complete product data including:
 * - Basic product fields (name, price, stock, etc.)
 * - Product type (simple, variable, grouped, external)
 * - Categories and tags
 * - Images and gallery
 *
 * @package FAWpmcp\Abilities\WooCommerce\Products
 */
final class GetProduct extends AbstractWooCommerceAbility
{
    /**
     * Returns the ability identifier.
     *
     * @return string Ability name.
     */
    public function getName(): string
    {
        return 'fa-wpmcp/wc-get-product';
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
        return 'Get Product';
    }

    /**
     * Returns the ability description.
     *
     * @return string Description.
     */
    public function getDescription(): string
    {
        return 'Retrieve a WooCommerce product by ID. Returns: id, name, slug, type, status, sku, price, regular_price, sale_price, stock_quantity, stock_status, description, short_description, categories, tags, images.';
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
                    'description' => 'The ID of the product to retrieve.',
                    'minimum'     => 1,
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
                'product' => array(
                    'type'       => 'object',
                    'properties' => array(
                        'id'                => array('type' => 'integer'),
                        'name'              => array('type' => 'string'),
                        'slug'              => array('type' => 'string'),
                        'type'              => array('type' => 'string'),
                        'status'            => array('type' => 'string'),
                        'sku'               => array('type' => 'string'),
                        'price'             => array('type' => 'string'),
                        'regular_price'     => array('type' => 'string'),
                        'sale_price'        => array('type' => 'string'),
                        'on_sale'           => array('type' => 'boolean'),
                        'stock_quantity'    => array('type' => ['integer', 'null']),
                        'stock_status'      => array('type' => 'string'),
                        'manage_stock'      => array('type' => 'boolean'),
                        'description'       => array('type' => 'string'),
                        'short_description' => array('type' => 'string'),
                        'categories'        => array(
                            'type'  => 'array',
                            'items' => array(
                                'type'       => 'object',
                                'properties' => array(
                                    'id'   => array('type' => 'integer'),
                                    'name' => array('type' => 'string'),
                                    'slug' => array('type' => 'string'),
                                ),
                            ),
                        ),
                        'tags'              => array(
                            'type'  => 'array',
                            'items' => array(
                                'type'       => 'object',
                                'properties' => array(
                                    'id'   => array('type' => 'integer'),
                                    'name' => array('type' => 'string'),
                                    'slug' => array('type' => 'string'),
                                ),
                            ),
                        ),
                        'images'            => array(
                            'type'  => 'array',
                            'items' => array(
                                'type'       => 'object',
                                'properties' => array(
                                    'id'  => array('type' => 'integer'),
                                    'src' => array('type' => 'string'),
                                    'alt' => array('type' => 'string'),
                                ),
                            ),
                        ),
                    ),
                ),
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
     * @return array<string, mixed> Product data.
     * @throws \RuntimeException If product not found.
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

        return array(
            'product' => $this->formatProduct($product),
        );
    }

    /**
     * Format a WC_Product object into output array.
     *
     * @param \WC_Product $product The product object.
     * @return array<string, mixed> Formatted product data.
     */
    private function formatProduct(\WC_Product $product): array
    {
        return array(
            'id'                => $product->get_id(),
            'name'              => $product->get_name(),
            'slug'              => $product->get_slug(),
            'type'              => $product->get_type(),
            'status'            => $product->get_status(),
            'sku'               => $product->get_sku(),
            'price'             => $product->get_price(),
            'regular_price'     => $product->get_regular_price(),
            'sale_price'        => $product->get_sale_price(),
            'on_sale'           => $product->is_on_sale(),
            'stock_quantity'    => $product->get_stock_quantity(),
            'stock_status'      => $product->get_stock_status(),
            'manage_stock'      => $product->get_manage_stock(),
            'description'       => $product->get_description(),
            'short_description' => $product->get_short_description(),
            'categories'        => $this->formatTerms($product, 'product_cat'),
            'tags'              => $this->formatTerms($product, 'product_tag'),
            'images'            => $this->formatImages($product),
        );
    }

    /**
     * Format product terms (categories or tags).
     *
     * @param \WC_Product $product  The product object.
     * @param string      $taxonomy Taxonomy name.
     * @return array<int, array<string, mixed>> Formatted terms.
     */
    private function formatTerms(\WC_Product $product, string $taxonomy): array
    {
        $terms = get_the_terms($product->get_id(), $taxonomy);

        if (!is_array($terms)) {
            return array();
        }

        return array_map(
            fn($term) => array(
                'id'   => $term->term_id,
                'name' => $term->name,
                'slug' => $term->slug,
            ),
            $terms
        );
    }

    /**
     * Format product images.
     *
     * @param \WC_Product $product The product object.
     * @return array<int, array<string, mixed>> Formatted images.
     */
    private function formatImages(\WC_Product $product): array
    {
        $images = array();

        // Featured image.
        $featured_id = $product->get_image_id();
        if ($featured_id) {
            $images[] = $this->formatImage((int) $featured_id);
        }

        // Gallery images.
        $gallery_ids = $product->get_gallery_image_ids();
        foreach ($gallery_ids as $image_id) {
            $images[] = $this->formatImage((int) $image_id);
        }

        return $images;
    }

    /**
     * Format a single image.
     *
     * @param int $image_id Attachment ID.
     * @return array<string, mixed> Formatted image data.
     */
    private function formatImage(int $image_id): array
    {
        return array(
            'id'  => $image_id,
            'src' => wp_get_attachment_url($image_id) ?: '',
            'alt' => get_post_meta($image_id, '_wp_attachment_image_alt', true) ?: '',
        );
    }
}
