<?php

/**
 * Tests for UpdateProduct WooCommerce ability.
 *
 * @package FAWpmcp\Tests\Abilities\WooCommerce\Products
 */

declare(strict_types=1);

namespace FAWpmcp\Tests\Abilities\WooCommerce\Products;

use Brain\Monkey\Functions;
use FAWpmcp\Abilities\AbstractAbility;
use FAWpmcp\Abilities\WooCommerce\Products\UpdateProduct;
use FAWpmcp\Tests\Factories\WooCommerceFactory;
use FAWpmcp\Tests\TestCase\AbstractWooCommerceAbilityTest;

/**
 * Tests for UpdateProduct ability.
 *
 * @covers \FAWpmcp\Abilities\WooCommerce\Products\UpdateProduct
 */
class UpdateProductTest extends AbstractWooCommerceAbilityTest
{
    /**
     * Get an instance of the ability being tested.
     *
     * @return AbstractAbility
     */
    protected function getAbilityInstance(): AbstractAbility
    {
        return new UpdateProduct();
    }

    /**
     * Get expected metadata for the ability.
     *
     * @return array<string, string>
     */
    protected function getExpectedMetadata(): array
    {
        return [
            'name'                 => 'fa-wpmcp/wc-update-product',
            'category'             => 'woocommerce-products',
            'label'                => 'Update Product',
            'description_contains' => 'product',
            'operation_type'       => 'write',
            'required_capability'  => 'edit_products',
        ];
    }

    /**
     * Test successful product update with name change.
     *
     * @return void
     */
    public function testDoExecuteUpdatesProductName(): void
    {
        $mockProduct = WooCommerceFactory::product([
            'id'     => 42,
            'name'   => 'Original Name',
            'status' => 'publish',
        ]);

        $mockProduct->shouldReceive('set_name')->once()->with('New Name');
        $mockProduct->shouldReceive('save')->once()->andReturn(42);

        Functions\when('wc_get_product')->justReturn($mockProduct);

        $ability = new UpdateProduct();
        $result = $ability->doExecute([
            'product_id' => 42,
            'name'       => 'New Name',
        ]);

        $this->assertEquals(42, $result['product_id']);
        $this->assertTrue($result['updated']);
    }

    /**
     * Test product update with status change.
     *
     * @return void
     */
    public function testDoExecuteUpdatesProductStatus(): void
    {
        $mockProduct = WooCommerceFactory::product([
            'id'     => 42,
            'name'   => 'Test Product',
            'status' => 'draft',
        ]);

        $mockProduct->shouldReceive('set_status')->once()->with('publish');
        $mockProduct->shouldReceive('save')->once()->andReturn(42);

        Functions\when('wc_get_product')->justReturn($mockProduct);

        $ability = new UpdateProduct();
        $result = $ability->doExecute([
            'product_id' => 42,
            'status'     => 'publish',
        ]);

        $this->assertTrue($result['updated']);
    }

    /**
     * Test product update with multiple fields.
     *
     * @return void
     */
    public function testDoExecuteUpdatesMultipleFields(): void
    {
        $mockProduct = WooCommerceFactory::product(['id' => 42]);

        $mockProduct->shouldReceive('set_name')->once()->with('Updated Name');
        $mockProduct->shouldReceive('set_sku')->once()->with('NEW-SKU');
        $mockProduct->shouldReceive('set_regular_price')->once()->with('49.99');
        $mockProduct->shouldReceive('set_sale_price')->once()->with('39.99');
        $mockProduct->shouldReceive('set_description')->once()->with('New description');
        $mockProduct->shouldReceive('set_short_description')->once()->with('New short');
        $mockProduct->shouldReceive('set_manage_stock')->once()->with(true);
        $mockProduct->shouldReceive('set_stock_quantity')->once()->with(200);
        $mockProduct->shouldReceive('set_stock_status')->once()->with('instock');
        $mockProduct->shouldReceive('set_category_ids')->once()->with([5, 6]);
        $mockProduct->shouldReceive('set_tag_ids')->once()->with([7, 8]);
        $mockProduct->shouldReceive('save')->once()->andReturn(42);

        Functions\when('wc_get_product')->justReturn($mockProduct);

        $ability = new UpdateProduct();
        $result = $ability->doExecute([
            'product_id'        => 42,
            'name'              => 'Updated Name',
            'sku'               => 'NEW-SKU',
            'regular_price'     => '49.99',
            'sale_price'        => '39.99',
            'description'       => 'New description',
            'short_description' => 'New short',
            'manage_stock'      => true,
            'stock_quantity'    => 200,
            'stock_status'      => 'instock',
            'categories'        => [5, 6],
            'tags'              => [7, 8],
        ]);

        $this->assertTrue($result['updated']);
    }

    /**
     * Test product not found throws exception.
     *
     * @return void
     */
    public function testDoExecuteThrowsWhenProductNotFound(): void
    {
        Functions\when('wc_get_product')->justReturn(false);

        $ability = new UpdateProduct();

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Product with ID 999 not found');

        $ability->doExecute([
            'product_id' => 999,
            'name'       => 'New Name',
        ]);
    }

    /**
     * Test that only provided fields are updated.
     *
     * @return void
     */
    public function testDoExecuteOnlyUpdatesProvidedFields(): void
    {
        $mockProduct = WooCommerceFactory::product(['id' => 42]);

        // Only name should be set, not other fields.
        $mockProduct->shouldReceive('set_name')->once()->with('Only Name');
        $mockProduct->shouldReceive('save')->once()->andReturn(42);

        // These should NOT be called.
        $mockProduct->shouldNotReceive('set_status');
        $mockProduct->shouldNotReceive('set_sku');
        $mockProduct->shouldNotReceive('set_regular_price');

        Functions\when('wc_get_product')->justReturn($mockProduct);

        $ability = new UpdateProduct();
        $result = $ability->doExecute([
            'product_id' => 42,
            'name'       => 'Only Name',
        ]);

        $this->assertTrue($result['updated']);
    }

    /**
     * Test operation type is write.
     *
     * @return void
     */
    public function testOperationTypeIsWrite(): void
    {
        $ability = new UpdateProduct();
        $this->assertEquals('write', $ability->getOperationType());
    }

    /**
     * Test input schema requires product_id.
     *
     * @return void
     */
    public function testInputSchemaRequiresProductId(): void
    {
        $ability = new UpdateProduct();
        $schema = $ability->getInputSchema();

        $this->assertContains('product_id', $schema['required']);
        $this->assertEquals('integer', $schema['properties']['product_id']['type']);
    }

    /**
     * Test input schema has status with trash option.
     *
     * @return void
     */
    public function testInputSchemaIncludesTrashStatus(): void
    {
        $ability = new UpdateProduct();
        $schema = $ability->getInputSchema();

        $this->assertContains('trash', $schema['properties']['status']['enum']);
    }
}
