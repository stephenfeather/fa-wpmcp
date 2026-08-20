<?php

/**
 * Tests for CreateProduct WooCommerce ability.
 *
 * @package FAWpmcp\Tests\Abilities\WooCommerce\Products
 */

declare(strict_types=1);

namespace FAWpmcp\Tests\Abilities\WooCommerce\Products;

use Brain\Monkey\Functions;
use FAWpmcp\Abilities\AbstractAbility;
use FAWpmcp\Abilities\WooCommerce\Products\CreateProduct;
use FAWpmcp\Tests\TestCase\AbstractWooCommerceAbilityTest;

/**
 * Tests for CreateProduct ability.
 *
 * @covers \FAWpmcp\Abilities\WooCommerce\Products\CreateProduct
 */
class CreateProductTest extends AbstractWooCommerceAbilityTest
{
    /**
     * Set up test with product class reset.
     *
     * @return void
     */
    protected function setUp(): void
    {
        parent::setUp();
        // Reset the product ID counter for test isolation.
        \WC_Product::resetIdCounter();
    }

    /**
     * Get an instance of the ability being tested.
     *
     * @return AbstractAbility
     */
    protected function getAbilityInstance(): AbstractAbility
    {
        return new CreateProduct();
    }

    /**
     * Get expected metadata for the ability.
     *
     * @return array<string, string>
     */
    protected function getExpectedMetadata(): array
    {
        return [
            'name'                 => 'fa-wpmcp/wc-create-product',
            'category'             => 'woocommerce-products',
            'label'                => 'Create Product',
            'description_contains' => 'product',
            'operation_type'       => 'write',
            'required_capability'  => 'edit_products',
        ];
    }

    /**
     * Test successful product creation with minimal input.
     *
     * @return void
     */
    public function testDoExecuteCreatesProductWithMinimalInput(): void
    {
        Functions\when('get_permalink')->justReturn('https://example.com/product/test-product/');

        $ability = new CreateProduct();
        $result = $ability->doExecute(['name' => 'Test Product']);

        $this->assertArrayHasKey('product_id', $result);
        $this->assertGreaterThan(0, $result['product_id']);
        $this->assertEquals('Test Product', $result['name']);
        $this->assertEquals('draft', $result['status']);
        $this->assertEquals('https://example.com/product/test-product/', $result['permalink']);
    }

    /**
     * Test product creation with custom status.
     *
     * @return void
     */
    public function testDoExecuteCreatesProductWithCustomStatus(): void
    {
        Functions\when('get_permalink')->justReturn('https://example.com/product/published/');

        $ability = new CreateProduct();
        $result = $ability->doExecute([
            'name'   => 'Published Product',
            'status' => 'publish',
        ]);

        $this->assertEquals('publish', $result['status']);
    }

    /**
     * Test product creation with all properties.
     *
     * @return void
     */
    public function testDoExecuteCreatesProductWithAllProperties(): void
    {
        Functions\when('get_permalink')->justReturn('https://example.com/product/full/');

        $ability = new CreateProduct();
        $result = $ability->doExecute([
            'name'              => 'Full Product',
            'status'            => 'publish',
            'sku'               => 'TEST-SKU',
            'regular_price'     => '99.99',
            'sale_price'        => '79.99',
            'description'       => 'Full description',
            'short_description' => 'Short desc',
            'manage_stock'      => true,
            'stock_quantity'    => 100,
            'stock_status'      => 'instock',
            'categories'        => [1, 2],
            'tags'              => [3, 4],
            'virtual'           => false,
            'downloadable'      => false,
        ]);

        $this->assertGreaterThan(0, $result['product_id']);
        $this->assertEquals('Full Product', $result['name']);
        $this->assertEquals('publish', $result['status']);
    }

    /**
     * Test creation defaults to simple product type.
     *
     * @return void
     */
    public function testDoExecuteDefaultsToSimpleProduct(): void
    {
        Functions\when('get_permalink')->justReturn('https://example.com/product/simple/');

        $ability = new CreateProduct();
        $result = $ability->doExecute(['name' => 'Simple Product']);

        // Product was created successfully (stub class WC_Product_Simple was used).
        $this->assertGreaterThan(0, $result['product_id']);
    }

    /**
     * Test creation of variable product.
     *
     * @return void
     */
    public function testDoExecuteCreatesVariableProduct(): void
    {
        Functions\when('get_permalink')->justReturn('https://example.com/product/variable/');

        $ability = new CreateProduct();
        $result = $ability->doExecute([
            'name' => 'Variable Product',
            'type' => 'variable',
        ]);

        $this->assertGreaterThan(0, $result['product_id']);
    }

    /**
     * Test creation of grouped product.
     *
     * @return void
     */
    public function testDoExecuteCreatesGroupedProduct(): void
    {
        Functions\when('get_permalink')->justReturn('https://example.com/product/grouped/');

        $ability = new CreateProduct();
        $result = $ability->doExecute([
            'name' => 'Grouped Product',
            'type' => 'grouped',
        ]);

        $this->assertGreaterThan(0, $result['product_id']);
    }

    /**
     * Test creation of external product.
     *
     * @return void
     */
    public function testDoExecuteCreatesExternalProduct(): void
    {
        Functions\when('get_permalink')->justReturn('https://example.com/product/external/');

        $ability = new CreateProduct();
        $result = $ability->doExecute([
            'name' => 'External Product',
            'type' => 'external',
        ]);

        $this->assertGreaterThan(0, $result['product_id']);
    }

    /**
     * Test multiple products get unique IDs.
     *
     * @return void
     */
    public function testDoExecuteAssignsUniqueIds(): void
    {
        Functions\when('get_permalink')->justReturn('https://example.com/product/test/');

        $ability = new CreateProduct();

        $result1 = $ability->doExecute(['name' => 'Product 1']);
        $result2 = $ability->doExecute(['name' => 'Product 2']);
        $result3 = $ability->doExecute(['name' => 'Product 3']);

        $this->assertNotEquals($result1['product_id'], $result2['product_id']);
        $this->assertNotEquals($result2['product_id'], $result3['product_id']);
        $this->assertNotEquals($result1['product_id'], $result3['product_id']);
    }

    /**
     * Test operation type is write.
     *
     * @return void
     */
    public function testOperationTypeIsWrite(): void
    {
        $ability = new CreateProduct();
        $this->assertEquals('write', $ability->getOperationType());
    }

    /**
     * Test input schema requires name.
     *
     * @return void
     */
    public function testInputSchemaRequiresName(): void
    {
        $ability = new CreateProduct();
        $schema = $ability->getInputSchema();

        $this->assertContains('name', $schema['required']);
    }

    /**
     * Test input schema has correct type options.
     *
     * @return void
     */
    public function testInputSchemaHasTypeOptions(): void
    {
        $ability = new CreateProduct();
        $schema = $ability->getInputSchema();

        $this->assertEquals(
            ['simple', 'variable', 'grouped', 'external'],
            $schema['properties']['type']['enum']
        );
    }

    /**
     * Test input schema has correct status options.
     *
     * @return void
     */
    public function testInputSchemaHasStatusOptions(): void
    {
        $ability = new CreateProduct();
        $schema = $ability->getInputSchema();

        $this->assertEquals(
            ['publish', 'draft', 'pending', 'private'],
            $schema['properties']['status']['enum']
        );
    }
}
