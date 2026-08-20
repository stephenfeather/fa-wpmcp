<?php

/**
 * Tests for GetProduct WooCommerce ability.
 *
 * @package FAWpmcp\Tests\Abilities\WooCommerce\Products
 */

declare(strict_types=1);

namespace FAWpmcp\Tests\Abilities\WooCommerce\Products;

use Brain\Monkey\Functions;
use FAWpmcp\Abilities\AbstractAbility;
use FAWpmcp\Abilities\WooCommerce\Products\GetProduct;
use FAWpmcp\Exceptions\WooCommerceNotActiveException;
use FAWpmcp\Tests\Factories\WooCommerceFactory;
use FAWpmcp\Tests\TestCase\AbstractWooCommerceAbilityTest;

/**
 * Tests for GetProduct ability.
 *
 * @covers \FAWpmcp\Abilities\WooCommerce\Products\GetProduct
 */
class GetProductTest extends AbstractWooCommerceAbilityTest
{
    /**
     * Get an instance of the ability being tested.
     *
     * @return AbstractAbility
     */
    protected function getAbilityInstance(): AbstractAbility
    {
        return new GetProduct();
    }

    /**
     * Get expected metadata for the ability.
     *
     * @return array<string, string>
     */
    protected function getExpectedMetadata(): array
    {
        return [
            'name'                 => 'fa-wpmcp/wc-get-product',
            'category'             => 'woocommerce-products',
            'label'                => 'Get Product',
            'description_contains' => 'product',
            'operation_type'       => 'read',
            'required_capability'  => 'edit_products',
        ];
    }

    /**
     * Test successful product retrieval.
     *
     * @return void
     */
    public function testDoExecuteReturnsProductData(): void
    {
        $mockProduct = WooCommerceFactory::product([
            'id'                => 42,
            'name'              => 'Test Widget',
            'slug'              => 'test-widget',
            'type'              => 'simple',
            'status'            => 'publish',
            'sku'               => 'WIDGET-001',
            'price'             => '29.99',
            'regular_price'     => '34.99',
            'sale_price'        => '29.99',
            'stock_quantity'    => 50,
            'stock_status'      => 'instock',
            'manage_stock'      => true,
            'description'       => 'A wonderful widget.',
            'short_description' => 'Widget for testing.',
        ]);

        // Mock additional methods needed for formatProduct.
        $mockProduct->shouldReceive('get_image_id')->andReturn(0);
        $mockProduct->shouldReceive('get_gallery_image_ids')->andReturn([]);

        Functions\when('wc_get_product')->justReturn($mockProduct);
        Functions\when('get_the_terms')->justReturn(false);

        $ability = new GetProduct();
        $result = $ability->doExecute(['product_id' => 42]);

        $this->assertArrayHasKey('product', $result);
        $this->assertEquals(42, $result['product']['id']);
        $this->assertEquals('Test Widget', $result['product']['name']);
        $this->assertEquals('test-widget', $result['product']['slug']);
        $this->assertEquals('simple', $result['product']['type']);
        $this->assertEquals('WIDGET-001', $result['product']['sku']);
        $this->assertEquals('29.99', $result['product']['price']);
        $this->assertEquals('instock', $result['product']['stock_status']);
        $this->assertTrue($result['product']['on_sale']);
    }

    /**
     * Test product not found throws exception.
     *
     * @return void
     */
    public function testDoExecuteThrowsWhenProductNotFound(): void
    {
        Functions\when('wc_get_product')->justReturn(false);

        $ability = new GetProduct();

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Product with ID 999 not found');

        $ability->doExecute(['product_id' => 999]);
    }

    /**
     * Test product with categories and tags.
     *
     * @return void
     */
    public function testDoExecuteIncludesCategoriesAndTags(): void
    {
        $mockProduct = WooCommerceFactory::product(['id' => 1]);
        $mockProduct->shouldReceive('get_image_id')->andReturn(0);
        $mockProduct->shouldReceive('get_gallery_image_ids')->andReturn([]);

        Functions\when('wc_get_product')->justReturn($mockProduct);

        // Mock categories.
        $category = (object) [
            'term_id' => 5,
            'name'    => 'Electronics',
            'slug'    => 'electronics',
        ];

        // Mock tags.
        $tag = (object) [
            'term_id' => 10,
            'name'    => 'Featured',
            'slug'    => 'featured',
        ];

        Functions\when('get_the_terms')->alias(function ($id, $taxonomy) use ($category, $tag) {
            if ($taxonomy === 'product_cat') {
                return [$category];
            }
            if ($taxonomy === 'product_tag') {
                return [$tag];
            }
            return false;
        });

        $ability = new GetProduct();
        $result = $ability->doExecute(['product_id' => 1]);

        $this->assertCount(1, $result['product']['categories']);
        $this->assertEquals('Electronics', $result['product']['categories'][0]['name']);

        $this->assertCount(1, $result['product']['tags']);
        $this->assertEquals('Featured', $result['product']['tags'][0]['name']);
    }

    /**
     * Test product with images.
     *
     * @return void
     */
    public function testDoExecuteIncludesImages(): void
    {
        $mockProduct = WooCommerceFactory::product(['id' => 1]);
        $mockProduct->shouldReceive('get_image_id')->andReturn(100);
        $mockProduct->shouldReceive('get_gallery_image_ids')->andReturn([101, 102]);

        Functions\when('wc_get_product')->justReturn($mockProduct);
        Functions\when('get_the_terms')->justReturn(false);
        Functions\when('wp_get_attachment_url')->alias(function ($id) {
            return "https://example.com/image-{$id}.jpg";
        });
        Functions\when('get_post_meta')->alias(function ($id, $key, $single) {
            if ($key === '_wp_attachment_image_alt') {
                return "Alt text for image {$id}";
            }
            return '';
        });

        $ability = new GetProduct();
        $result = $ability->doExecute(['product_id' => 1]);

        $this->assertCount(3, $result['product']['images']);
        $this->assertEquals(100, $result['product']['images'][0]['id']);
        $this->assertEquals('https://example.com/image-100.jpg', $result['product']['images'][0]['src']);
        $this->assertEquals('Alt text for image 100', $result['product']['images'][0]['alt']);
    }

    /**
     * Test that WooCommerce not active throws exception.
     *
     * @return void
     */
    public function testDoExecuteThrowsWhenWooCommerceNotActive(): void
    {
        $this->mockWooCommerceInactive();

        $ability = new GetProduct();

        $this->expectException(WooCommerceNotActiveException::class);

        $ability->doExecute(['product_id' => 1]);
    }

    /**
     * Test input schema has required product_id.
     *
     * @return void
     */
    public function testInputSchemaRequiresProductId(): void
    {
        $ability = new GetProduct();
        $schema = $ability->getInputSchema();

        $this->assertContains('product_id', $schema['required']);
        $this->assertEquals('integer', $schema['properties']['product_id']['type']);
        $this->assertEquals(1, $schema['properties']['product_id']['minimum']);
    }
}
