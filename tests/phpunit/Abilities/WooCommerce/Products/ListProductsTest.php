<?php

/**
 * Tests for ListProducts WooCommerce ability.
 *
 * @package FAWpmcp\Tests\Abilities\WooCommerce\Products
 */

declare(strict_types=1);

namespace FAWpmcp\Tests\Abilities\WooCommerce\Products;

use Brain\Monkey\Functions;
use FAWpmcp\Abilities\AbstractAbility;
use FAWpmcp\Abilities\WooCommerce\Products\ListProducts;
use FAWpmcp\Tests\Factories\WooCommerceFactory;
use FAWpmcp\Tests\TestCase\AbstractWooCommerceAbilityTest;

/**
 * Tests for ListProducts ability.
 *
 * @covers \FAWpmcp\Abilities\WooCommerce\Products\ListProducts
 */
class ListProductsTest extends AbstractWooCommerceAbilityTest
{
    /**
     * Get an instance of the ability being tested.
     *
     * @return AbstractAbility
     */
    protected function getAbilityInstance(): AbstractAbility
    {
        return new ListProducts();
    }

    /**
     * Get expected metadata for the ability.
     *
     * @return array<string, string>
     */
    protected function getExpectedMetadata(): array
    {
        return [
            'name'                 => 'fa-wpmcp/wc-list-products',
            'category'             => 'woocommerce-products',
            'label'                => 'List Products',
            'description_contains' => 'products',
            'operation_type'       => 'read',
            'required_capability'  => 'edit_products',
        ];
    }

    /**
     * Test listing products returns array with pagination.
     *
     * @return void
     */
    public function testDoExecuteReturnsProductsWithPagination(): void
    {
        $products = WooCommerceFactory::products(3);

        // First call returns products, second returns IDs for count.
        Functions\when('wc_get_products')->alias(function ($args) use ($products) {
            if (isset($args['return']) && $args['return'] === 'ids') {
                return [1, 2, 3];
            }
            return $products;
        });

        $ability = new ListProducts();
        $result = $ability->doExecute([]);

        $this->assertArrayHasKey('products', $result);
        $this->assertArrayHasKey('total', $result);
        $this->assertArrayHasKey('page', $result);
        $this->assertArrayHasKey('per_page', $result);
        $this->assertArrayHasKey('pages', $result);

        $this->assertCount(3, $result['products']);
        $this->assertEquals(3, $result['total']);
        $this->assertEquals(1, $result['page']);
        $this->assertEquals(10, $result['per_page']);
        $this->assertEquals(1, $result['pages']);
    }

    /**
     * Test pagination parameters are respected.
     *
     * @return void
     */
    public function testDoExecuteRespectsPagination(): void
    {
        $products = WooCommerceFactory::products(2);

        $capturedArgs = null;
        Functions\when('wc_get_products')->alias(function ($args) use ($products, &$capturedArgs) {
            if (!isset($args['return'])) {
                $capturedArgs = $args;
            }
            if (isset($args['return']) && $args['return'] === 'ids') {
                return array_fill(0, 25, 1); // 25 total.
            }
            return $products;
        });

        $ability = new ListProducts();
        $result = $ability->doExecute(['page' => 2, 'per_page' => 5]);

        $this->assertEquals(5, $capturedArgs['limit']);
        $this->assertEquals(5, $capturedArgs['offset']); // Page 2 with 5 per page = offset 5.
        $this->assertEquals(25, $result['total']);
        $this->assertEquals(2, $result['page']);
        $this->assertEquals(5, $result['per_page']);
        $this->assertEquals(5, $result['pages']); // 25 / 5 = 5 pages.
    }

    /**
     * Test per_page is capped at 100.
     *
     * @return void
     */
    public function testDoExecuteCapsPerPageAt100(): void
    {
        $capturedArgs = null;
        Functions\when('wc_get_products')->alias(function ($args) use (&$capturedArgs) {
            if (!isset($args['return'])) {
                $capturedArgs = $args;
            }
            return [];
        });

        $ability = new ListProducts();
        $ability->doExecute(['per_page' => 500]);

        $this->assertEquals(100, $capturedArgs['limit']);
    }

    /**
     * Test filtering by status.
     *
     * @return void
     */
    public function testDoExecuteFiltersbyStatus(): void
    {
        $capturedArgs = null;
        Functions\when('wc_get_products')->alias(function ($args) use (&$capturedArgs) {
            if (!isset($args['return'])) {
                $capturedArgs = $args;
            }
            return [];
        });

        $ability = new ListProducts();
        $ability->doExecute(['status' => 'draft']);

        $this->assertEquals('draft', $capturedArgs['status']);
    }

    /**
     * Test filtering by type.
     *
     * @return void
     */
    public function testDoExecuteFiltersByType(): void
    {
        $capturedArgs = null;
        Functions\when('wc_get_products')->alias(function ($args) use (&$capturedArgs) {
            if (!isset($args['return'])) {
                $capturedArgs = $args;
            }
            return [];
        });

        $ability = new ListProducts();
        $ability->doExecute(['type' => 'variable']);

        $this->assertEquals('variable', $capturedArgs['type']);
    }

    /**
     * Test filtering by category.
     *
     * @return void
     */
    public function testDoExecuteFiltersByCategory(): void
    {
        $capturedArgs = null;
        Functions\when('wc_get_products')->alias(function ($args) use (&$capturedArgs) {
            if (!isset($args['return'])) {
                $capturedArgs = $args;
            }
            return [];
        });

        $ability = new ListProducts();
        $ability->doExecute(['category' => 'electronics']);

        $this->assertEquals(['electronics'], $capturedArgs['category']);
    }

    /**
     * Test filtering by on_sale.
     *
     * @return void
     */
    public function testDoExecuteFiltersByOnSale(): void
    {
        $capturedArgs = null;
        Functions\when('wc_get_products')->alias(function ($args) use (&$capturedArgs) {
            if (!isset($args['return'])) {
                $capturedArgs = $args;
            }
            return [];
        });

        $ability = new ListProducts();
        $ability->doExecute(['on_sale' => true]);

        $this->assertTrue($capturedArgs['on_sale']);
    }

    /**
     * Test filtering by stock_status.
     *
     * @return void
     */
    public function testDoExecuteFiltersByStockStatus(): void
    {
        $capturedArgs = null;
        Functions\when('wc_get_products')->alias(function ($args) use (&$capturedArgs) {
            if (!isset($args['return'])) {
                $capturedArgs = $args;
            }
            return [];
        });

        $ability = new ListProducts();
        $ability->doExecute(['stock_status' => 'outofstock']);

        $this->assertEquals('outofstock', $capturedArgs['stock_status']);
    }

    /**
     * Test ordering parameters.
     *
     * @return void
     */
    public function testDoExecuteAppliesOrdering(): void
    {
        $capturedArgs = null;
        Functions\when('wc_get_products')->alias(function ($args) use (&$capturedArgs) {
            if (!isset($args['return'])) {
                $capturedArgs = $args;
            }
            return [];
        });

        $ability = new ListProducts();
        $ability->doExecute(['orderby' => 'price', 'order' => 'asc']);

        $this->assertEquals('price', $capturedArgs['orderby']);
        $this->assertEquals('ASC', $capturedArgs['order']);
    }

    /**
     * Test search parameter.
     *
     * @return void
     */
    public function testDoExecuteAppliesSearch(): void
    {
        $capturedArgs = null;
        Functions\when('wc_get_products')->alias(function ($args) use (&$capturedArgs) {
            if (!isset($args['return'])) {
                $capturedArgs = $args;
            }
            return [];
        });

        $ability = new ListProducts();
        $ability->doExecute(['search' => 'widget']);

        $this->assertEquals('widget', $capturedArgs['s']);
    }

    /**
     * Test empty result returns empty array.
     *
     * @return void
     */
    public function testDoExecuteReturnsEmptyArrayWhenNoProducts(): void
    {
        Functions\when('wc_get_products')->justReturn([]);

        $ability = new ListProducts();
        $result = $ability->doExecute([]);

        $this->assertEmpty($result['products']);
        $this->assertEquals(0, $result['total']);
        $this->assertEquals(0, $result['pages']);
    }

    /**
     * Test product summary format.
     *
     * @return void
     */
    public function testDoExecuteReturnsProductSummaryFormat(): void
    {
        $product = WooCommerceFactory::product([
            'id'             => 42,
            'name'           => 'Test Widget',
            'slug'           => 'test-widget',
            'type'           => 'simple',
            'status'         => 'publish',
            'sku'            => 'WIDGET-001',
            'price'          => '29.99',
            'regular_price'  => '34.99',
            'sale_price'     => '29.99',
            'stock_quantity' => 50,
            'stock_status'   => 'instock',
        ]);

        Functions\when('wc_get_products')->alias(function ($args) use ($product) {
            if (isset($args['return']) && $args['return'] === 'ids') {
                return [42];
            }
            return [$product];
        });

        $ability = new ListProducts();
        $result = $ability->doExecute([]);

        $summary = $result['products'][0];
        $this->assertEquals(42, $summary['id']);
        $this->assertEquals('Test Widget', $summary['name']);
        $this->assertEquals('test-widget', $summary['slug']);
        $this->assertEquals('simple', $summary['type']);
        $this->assertEquals('publish', $summary['status']);
        $this->assertEquals('WIDGET-001', $summary['sku']);
        $this->assertEquals('29.99', $summary['price']);
        $this->assertTrue($summary['on_sale']);
        $this->assertEquals(50, $summary['stock_quantity']);
        $this->assertEquals('instock', $summary['stock_status']);
    }
}
