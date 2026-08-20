<?php

/**
 * Tests for DeleteProduct WooCommerce ability.
 *
 * @package FAWpmcp\Tests\Abilities\WooCommerce\Products
 */

declare(strict_types=1);

namespace FAWpmcp\Tests\Abilities\WooCommerce\Products;

use Brain\Monkey\Functions;
use FAWpmcp\Abilities\AbstractAbility;
use FAWpmcp\Abilities\WooCommerce\Products\DeleteProduct;
use FAWpmcp\Tests\Factories\WooCommerceFactory;
use FAWpmcp\Tests\TestCase\AbstractWooCommerceAbilityTest;

/**
 * Tests for DeleteProduct ability.
 *
 * @covers \FAWpmcp\Abilities\WooCommerce\Products\DeleteProduct
 */
class DeleteProductTest extends AbstractWooCommerceAbilityTest
{
    /**
     * Get an instance of the ability being tested.
     *
     * @return AbstractAbility
     */
    protected function getAbilityInstance(): AbstractAbility
    {
        return new DeleteProduct();
    }

    /**
     * Get expected metadata for the ability.
     *
     * @return array<string, string>
     */
    protected function getExpectedMetadata(): array
    {
        return [
            'name'                 => 'fa-wpmcp/wc-delete-product',
            'category'             => 'woocommerce-products',
            'label'                => 'Delete Product',
            'description_contains' => 'product',
            'operation_type'       => 'write',
            'required_capability'  => 'delete_products',
        ];
    }

    /**
     * Test soft delete (trash) by default.
     *
     * @return void
     */
    public function testDoExecuteTrashesProductByDefault(): void
    {
        $mockProduct = WooCommerceFactory::product([
            'id'   => 42,
            'name' => 'Product to Trash',
        ]);

        $mockProduct->shouldReceive('delete')->once()->with(false)->andReturn(true);

        Functions\when('wc_get_product')->justReturn($mockProduct);

        $ability = new DeleteProduct();
        $result = $ability->doExecute(['product_id' => 42]);

        $this->assertEquals(42, $result['product_id']);
        $this->assertEquals('Product to Trash', $result['name']);
        $this->assertFalse($result['deleted']);
        $this->assertTrue($result['trashed']);
    }

    /**
     * Test force delete permanently removes product.
     *
     * @return void
     */
    public function testDoExecuteForceDeletesPermanently(): void
    {
        $mockProduct = WooCommerceFactory::product([
            'id'   => 42,
            'name' => 'Product to Delete',
        ]);

        $mockProduct->shouldReceive('delete')->once()->with(true)->andReturn(true);

        Functions\when('wc_get_product')->justReturn($mockProduct);

        $ability = new DeleteProduct();
        $result = $ability->doExecute([
            'product_id' => 42,
            'force'      => true,
        ]);

        $this->assertEquals(42, $result['product_id']);
        $this->assertTrue($result['deleted']);
        $this->assertFalse($result['trashed']);
    }

    /**
     * Test product not found throws exception.
     *
     * @return void
     */
    public function testDoExecuteThrowsWhenProductNotFound(): void
    {
        Functions\when('wc_get_product')->justReturn(false);

        $ability = new DeleteProduct();

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Product with ID 999 not found');

        $ability->doExecute(['product_id' => 999]);
    }

    /**
     * Test delete failure throws exception.
     *
     * @return void
     */
    public function testDoExecuteThrowsOnDeleteFailure(): void
    {
        $mockProduct = WooCommerceFactory::product(['id' => 42]);
        $mockProduct->shouldReceive('delete')->once()->andReturn(false);

        Functions\when('wc_get_product')->justReturn($mockProduct);

        $ability = new DeleteProduct();

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Failed to delete product with ID 42');

        $ability->doExecute(['product_id' => 42]);
    }

    /**
     * Test operation type is write.
     *
     * @return void
     */
    public function testOperationTypeIsWrite(): void
    {
        $ability = new DeleteProduct();
        $this->assertEquals('write', $ability->getOperationType());
    }

    /**
     * Test annotations include destructive flag.
     *
     * @return void
     */
    public function testAnnotationsIncludeDestructive(): void
    {
        $ability = new DeleteProduct();
        $annotations = $ability->getAnnotations();

        $this->assertTrue($annotations['destructive']);
    }

    /**
     * Test input schema requires product_id.
     *
     * @return void
     */
    public function testInputSchemaRequiresProductId(): void
    {
        $ability = new DeleteProduct();
        $schema = $ability->getInputSchema();

        $this->assertContains('product_id', $schema['required']);
    }

    /**
     * Test input schema has force option defaulting to false.
     *
     * @return void
     */
    public function testInputSchemaHasForceOptionWithDefault(): void
    {
        $ability = new DeleteProduct();
        $schema = $ability->getInputSchema();

        $this->assertEquals('boolean', $schema['properties']['force']['type']);
        $this->assertFalse($schema['properties']['force']['default']);
    }

    /**
     * Test required capability is delete_products.
     *
     * @return void
     */
    public function testRequiredCapabilityIsDeleteProducts(): void
    {
        $ability = new DeleteProduct();
        $this->assertEquals('delete_products', $ability->getRequiredCapability());
    }
}
