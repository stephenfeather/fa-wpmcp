<?php

/**
 * Tests for GetOption ability.
 *
 * @package FAWpmcp\Tests\Abilities\Settings
 */

declare(strict_types=1);

namespace FAWpmcp\Tests\Abilities\Settings;

use FAWpmcp\Abilities\Settings\GetOption;
use Brain\Monkey;
use Brain\Monkey\Functions;
use Mockery;
use PHPUnit\Framework\TestCase;

/**
 * Test GetOption ability functionality.
 *
 * Tests cover:
 * - Get option that exists
 * - Get option that doesn't exist (returns default)
 * - Result formatting
 *
 * @package FAWpmcp\Tests\Abilities\Settings
 */
class GetOptionTest extends TestCase
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
        $ability = new GetOption();
        $this->assertEquals('fa-wpmcp/get-option', $ability->getName());
    }

    /**
     * Test ability returns correct category.
     *
     * @return void
     */
    public function testGetCategory(): void
    {
        $ability = new GetOption();
        $this->assertEquals('settings', $ability->getCategory());
    }

    /**
     * Test ability returns correct label.
     *
     * @return void
     */
    public function testGetLabel(): void
    {
        $ability = new GetOption();
        $this->assertEquals('Get Option', $ability->getLabel());
    }

    /**
     * Test ability returns correct description.
     *
     * @return void
     */
    public function testGetDescription(): void
    {
        $ability = new GetOption();
        $this->assertStringContainsString('option', strtolower($ability->getDescription()));
    }

    /**
     * Test ability returns correct operation type.
     *
     * @return void
     */
    public function testGetOperationType(): void
    {
        $ability = new GetOption();
        $this->assertEquals('read', $ability->getOperationType());
    }

    /**
     * Test ability returns correct required capability.
     *
     * @return void
     */
    public function testGetRequiredCapability(): void
    {
        $ability = new GetOption();
        $this->assertEquals('manage_options', $ability->getRequiredCapability());
    }

    /**
     * Test ability has valid input schema.
     *
     * @return void
     */
    public function testGetInputSchema(): void
    {
        $ability = new GetOption();
        $schema  = $ability->getInputSchema();

        $this->assertIsArray($schema);
        $this->assertEquals('object', $schema['type']);
        $this->assertArrayHasKey('option_name', $schema['properties']);
        $this->assertEquals('string', $schema['properties']['option_name']['type']);
    }

    /**
     * Test ability has valid output schema.
     *
     * @return void
     */
    public function testGetOutputSchema(): void
    {
        $ability = new GetOption();
        $schema  = $ability->getOutputSchema();

        $this->assertIsArray($schema);
        $this->assertEquals('object', $schema['type']);
        $this->assertArrayHasKey('option_name', $schema['properties']);
        $this->assertArrayHasKey('value', $schema['properties']);
        $this->assertArrayHasKey('exists', $schema['properties']);
    }

    /**
     * Test execute retrieves existing option.
     *
     * @return void
     */
    public function testExecuteRetrievesExistingOption(): void
    {
        $ability = new GetOption();

        Functions\when('sanitize_key')->returnArg();

        Functions\expect('get_option')
            ->once()
            ->with('test_option', Mockery::type('stdClass'))
            ->andReturn('test_value');

        $result = $ability->doExecute(array( 'option_name' => 'test_option' ));

        $this->assertEquals('test_option', $result['option_name']);
        $this->assertEquals('test_value', $result['value']);
        $this->assertTrue($result['exists']);
    }

    /**
     * Test execute returns default for non-existent option.
     *
     * @return void
     */
    public function testExecuteReturnsDefaultForNonExistentOption(): void
    {
        $ability = new GetOption();

        Functions\when('sanitize_key')->returnArg();

        Functions\expect('get_option')
            ->once()
            ->with('missing_option', Mockery::type('stdClass'))
            ->andReturnUsing(
                function ($name, $sentinel) {
                    return $sentinel;
                }
            );

        $result = $ability->doExecute(
            array(
                'option_name' => 'missing_option',
                'default'     => 'default_value',
            )
        );

        $this->assertEquals('missing_option', $result['option_name']);
        $this->assertEquals('default_value', $result['value']);
        $this->assertFalse($result['exists']);
    }

    /**
     * Test execute handles array option values.
     *
     * @return void
     */
    public function testExecuteHandlesArrayOptionValues(): void
    {
        $ability      = new GetOption();
        $option_value = array(
            'key1' => 'value1',
            'key2' => 'value2',
        );

        Functions\when('sanitize_key')->returnArg();

        Functions\expect('get_option')
            ->once()
            ->with('array_option', Mockery::type('stdClass'))
            ->andReturn($option_value);

        $result = $ability->doExecute(array( 'option_name' => 'array_option' ));

        $this->assertEquals('array_option', $result['option_name']);
        $this->assertEquals($option_value, $result['value']);
        $this->assertTrue($result['exists']);
    }

    /**
     * Test execute blocks protected options.
     *
     * @return void
     */
    public function testExecuteBlocksProtectedOption(): void
    {
        $ability = new GetOption();

        Functions\when('sanitize_key')->returnArg();

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('protected');

        $ability->doExecute(array( 'option_name' => 'admin_email' ));
    }
}
