<?php

/**
 * Tests for GetTransient ability.
 *
 * @package FAWpmcp\Tests\Abilities\Transients
 */

declare(strict_types=1);

namespace FAWpmcp\Tests\Abilities\Transients;

use FAWpmcp\Abilities\Transients\GetTransient;
use Brain\Monkey;
use Brain\Monkey\Functions;
use Mockery;
use PHPUnit\Framework\TestCase;

/**
 * Test GetTransient ability functionality.
 *
 * @package FAWpmcp\Tests\Abilities\Transients
 */
class GetTransientTest extends TestCase
{
    /**
     * Set up Brain\Monkey before each test.
     *
     * @return void
     */
    protected function setUp(): void
    {
        parent::setUp();
        Monkey\setUp();
    }

    /**
     * Tear down Brain\Monkey after each test.
     *
     * @return void
     */
    protected function tearDown(): void
    {
        Monkey\tearDown();
        Mockery::close();
        parent::tearDown();
    }

    /**
     * Test ability returns correct name.
     *
     * @return void
     */
    public function testGetName(): void
    {
        $ability = new GetTransient();
        $this->assertEquals('fa-wpmcp/get-transient', $ability->getName());
    }

    /**
     * Test ability returns correct category.
     *
     * @return void
     */
    public function testGetCategory(): void
    {
        $ability = new GetTransient();
        $this->assertEquals('transients', $ability->getCategory());
    }

    /**
     * Test ability returns correct label.
     *
     * @return void
     */
    public function testGetLabel(): void
    {
        $ability = new GetTransient();
        $this->assertEquals('Get Transient', $ability->getLabel());
    }

    /**
     * Test ability returns correct operation type.
     *
     * @return void
     */
    public function testGetOperationType(): void
    {
        $ability = new GetTransient();
        $this->assertEquals('read', $ability->getOperationType());
    }

    /**
     * Test ability returns correct required capability.
     *
     * @return void
     */
    public function testGetRequiredCapability(): void
    {
        $ability = new GetTransient();
        $this->assertEquals('manage_options', $ability->getRequiredCapability());
    }

    /**
     * Test ability returns input schema with required key field.
     *
     * @return void
     */
    public function testGetInputSchema(): void
    {
        $ability = new GetTransient();
        $schema  = $ability->getInputSchema();

        $this->assertIsArray($schema);
        $this->assertArrayHasKey('type', $schema);
        $this->assertArrayHasKey('properties', $schema);
        $this->assertArrayHasKey('required', $schema);
        $this->assertArrayHasKey('key', $schema['properties']);
        $this->assertContains('key', $schema['required']);
    }

    /**
     * Test input schema includes optional network parameter.
     *
     * @return void
     */
    public function testGetInputSchemaHasNetworkParameter(): void
    {
        $ability = new GetTransient();
        $schema  = $ability->getInputSchema();

        $this->assertArrayHasKey('network', $schema['properties']);
        $this->assertEquals('boolean', $schema['properties']['network']['type']);
    }

    /**
     * Test ability returns output schema.
     *
     * @return void
     */
    public function testGetOutputSchema(): void
    {
        $ability = new GetTransient();
        $schema  = $ability->getOutputSchema();

        $this->assertIsArray($schema);
        $this->assertArrayHasKey('type', $schema);
        $this->assertArrayHasKey('properties', $schema);
        $this->assertArrayHasKey('value', $schema['properties']);
        $this->assertArrayHasKey('exists', $schema['properties']);
    }

    /**
     * Test execute returns transient value when it exists.
     *
     * @return void
     */
    public function testExecuteReturnsTransientValue(): void
    {
        $ability = new GetTransient();

        Functions\when('get_transient')->justReturn('cached_value');

        $result = $ability->doExecute(array( 'key' => 'my_transient' ));

        $this->assertIsArray($result);
        $this->assertTrue($result['exists']);
        $this->assertEquals('cached_value', $result['value']);
    }

    /**
     * Test execute returns exists false when transient does not exist.
     *
     * @return void
     */
    public function testExecuteReturnsExistsFalseWhenNotFound(): void
    {
        $ability = new GetTransient();

        Functions\when('get_transient')->justReturn(false);

        $result = $ability->doExecute(array( 'key' => 'nonexistent_transient' ));

        $this->assertIsArray($result);
        $this->assertFalse($result['exists']);
        $this->assertNull($result['value']);
    }

    /**
     * Test execute uses get_site_transient for network transients.
     *
     * @return void
     */
    public function testExecuteUsesGetSiteTransientForNetwork(): void
    {
        $ability = new GetTransient();

        Functions\expect('get_site_transient')
            ->once()
            ->with('network_transient')
            ->andReturn('network_value');

        $result = $ability->doExecute(
            array(
                'key'     => 'network_transient',
                'network' => true,
            )
        );

        $this->assertTrue($result['exists']);
        $this->assertEquals('network_value', $result['value']);
    }

    /**
     * Test execute handles array transient values.
     *
     * @return void
     */
    public function testExecuteHandlesArrayValues(): void
    {
        $ability = new GetTransient();

        $array_value = array(
            'key1' => 'value1',
            'key2' => 'value2',
        );
        Functions\when('get_transient')->justReturn($array_value);

        $result = $ability->doExecute(array( 'key' => 'array_transient' ));

        $this->assertTrue($result['exists']);
        $this->assertEquals($array_value, $result['value']);
    }

    /**
     * Test execute handles object transient values.
     *
     * @return void
     */
    public function testExecuteHandlesObjectValues(): void
    {
        $ability = new GetTransient();

        $object_value = (object) array( 'prop' => 'value' );
        Functions\when('get_transient')->justReturn($object_value);

        $result = $ability->doExecute(array( 'key' => 'object_transient' ));

        $this->assertTrue($result['exists']);
        $this->assertEquals($object_value, $result['value']);
    }

    /**
     * Test annotations are correct for read-only ability.
     *
     * @return void
     */
    public function testGetAnnotations(): void
    {
        $ability     = new GetTransient();
        $annotations = $ability->getAnnotations();

        $this->assertTrue($annotations['readonly']);
        $this->assertFalse($annotations['destructive']);
        $this->assertTrue($annotations['idempotent']);
    }
}
