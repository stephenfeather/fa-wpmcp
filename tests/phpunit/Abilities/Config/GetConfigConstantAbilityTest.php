<?php

/**
 * Tests for GetConfigConstantAbility.
 *
 * @package FAWpmcp\Tests\Abilities\Config
 */

declare(strict_types=1);

namespace FAWpmcp\Tests\Abilities\Config;

use FAWpmcp\Abilities\AbstractAbility;
use FAWpmcp\Abilities\Config\GetConfigConstantAbility;
use FAWpmcp\Exceptions\ConfigConstantException;
use FAWpmcp\Tests\TestCase\AbilityTestTrait;
use FAWpmcp\Tests\TestCase\BrainMonkeyTestCase;
use Brain\Monkey\Functions;

/**
 * Test GetConfigConstantAbility functionality.
 *
 * @package FAWpmcp\Tests\Abilities\Config
 */
class GetConfigConstantAbilityTest extends BrainMonkeyTestCase
{
    use AbilityTestTrait;

    /**
     * Get the ability instance to test.
     *
     * @return AbstractAbility
     */
    protected function getAbilityInstance(): AbstractAbility
    {
        return new GetConfigConstantAbility();
    }

    /**
     * Get the expected metadata for this ability.
     *
     * @return array<string, mixed>
     */
    protected function getExpectedMetadata(): array
    {
        return array(
            'name'                 => 'fa-wpmcp/get-config-constant',
            'category'             => 'config',
            'label'                => 'Get Config Constant',
            'description_contains' => 'constant',
            'operation_type'       => 'read',
            'required_capability'  => 'manage_options',
        );
    }

    /**
     * Test ability returns input schema with name property.
     *
     * @return void
     */
    public function testGetInputSchema(): void
    {
        $ability = $this->getAbilityInstance();
        $schema  = $ability->getInputSchema();

        $this->assertIsArray($schema);
        $this->assertArrayHasKey('type', $schema);
        $this->assertEquals('object', $schema['type']);
        $this->assertArrayHasKey('properties', $schema);
        $this->assertArrayHasKey('name', $schema['properties']);
        $this->assertArrayHasKey('required', $schema);
        $this->assertContains('name', $schema['required']);
    }

    /**
     * Test ability returns output schema with value properties.
     *
     * @return void
     */
    public function testGetOutputSchema(): void
    {
        $ability = $this->getAbilityInstance();
        $schema  = $ability->getOutputSchema();

        $this->assertIsArray($schema);
        $this->assertArrayHasKey('properties', $schema);
        $this->assertArrayHasKey('name', $schema['properties']);
        $this->assertArrayHasKey('value', $schema['properties']);
        $this->assertArrayHasKey('type', $schema['properties']);
        $this->assertArrayHasKey('defined', $schema['properties']);
    }

    /**
     * Test execute returns constant value when defined.
     *
     * @return void
     */
    public function testExecuteReturnsConstantValue(): void
    {
        $ability = $this->getAbilityInstance();

        // Ensure WP_DEBUG is defined.
        if (! defined('WP_DEBUG')) {
            define('WP_DEBUG', true);
        }

        // json_encode is used directly, no mock needed.

        $result = $ability->doExecute(array('name' => 'WP_DEBUG'));

        $this->assertIsArray($result);
        $this->assertArrayHasKey('name', $result);
        $this->assertArrayHasKey('value', $result);
        $this->assertArrayHasKey('type', $result);
        $this->assertArrayHasKey('defined', $result);
        $this->assertEquals('WP_DEBUG', $result['name']);
        $this->assertTrue($result['defined']);
        $this->assertEquals('boolean', $result['type']);
    }

    /**
     * Test execute returns undefined for non-existent constant.
     *
     * @return void
     */
    public function testExecuteReturnsUndefinedForNonExistent(): void
    {
        $ability = $this->getAbilityInstance();

        // json_encode is used directly, no mock needed.

        $result = $ability->doExecute(array('name' => 'NONEXISTENT_TEST_CONSTANT'));

        $this->assertIsArray($result);
        $this->assertFalse($result['defined']);
        $this->assertEquals('undefined', $result['type']);
        $this->assertEquals('', $result['value']);
    }

    /**
     * Test execute rejects sensitive constants.
     *
     * @return void
     */
    public function testExecuteRejectsSensitiveConstants(): void
    {
        $ability = $this->getAbilityInstance();

        // json_encode is used directly, no mock needed.

        $this->expectException(ConfigConstantException::class);
        $this->expectExceptionMessage('sensitive');

        $ability->doExecute(array('name' => 'DB_PASSWORD'));
    }

    /**
     * Test execute rejects constants with PASSWORD pattern.
     *
     * @return void
     */
    public function testExecuteRejectsPasswordPattern(): void
    {
        $ability = $this->getAbilityInstance();

        // json_encode is used directly, no mock needed.

        $this->expectException(ConfigConstantException::class);

        $ability->doExecute(array('name' => 'CUSTOM_PASSWORD'));
    }

    /**
     * Test execute rejects constants with SECRET pattern.
     *
     * @return void
     */
    public function testExecuteRejectsSecretPattern(): void
    {
        $ability = $this->getAbilityInstance();

        // json_encode is used directly, no mock needed.

        $this->expectException(ConfigConstantException::class);

        $ability->doExecute(array('name' => 'MY_SECRET_VALUE'));
    }

    /**
     * Test execute rejects constants with API_KEY pattern.
     *
     * @return void
     */
    public function testExecuteRejectsApiKeyPattern(): void
    {
        $ability = $this->getAbilityInstance();

        // json_encode is used directly, no mock needed.

        $this->expectException(ConfigConstantException::class);

        $ability->doExecute(array('name' => 'SOME_API_KEY'));
    }

    /**
     * Test execute rejects empty constant name.
     *
     * @return void
     */
    public function testExecuteRejectsEmptyName(): void
    {
        $ability = $this->getAbilityInstance();

        // json_encode is used directly, no mock needed.

        $this->expectException(ConfigConstantException::class);
        $this->expectExceptionMessage('Invalid constant name');

        $ability->doExecute(array('name' => ''));
    }

    /**
     * Test execute rejects lowercase constant names.
     *
     * @return void
     */
    public function testExecuteRejectsLowercaseName(): void
    {
        $ability = $this->getAbilityInstance();

        // json_encode is used directly, no mock needed.

        $this->expectException(ConfigConstantException::class);
        $this->expectExceptionMessage('Invalid constant name');

        $ability->doExecute(array('name' => 'lowercase_name'));
    }

    /**
     * Test execute rejects constant names starting with number.
     *
     * @return void
     */
    public function testExecuteRejectsNameStartingWithNumber(): void
    {
        $ability = $this->getAbilityInstance();

        // json_encode is used directly, no mock needed.

        $this->expectException(ConfigConstantException::class);
        $this->expectExceptionMessage('Invalid constant name');

        $ability->doExecute(array('name' => '123_INVALID'));
    }

    /**
     * Test formatting of different value types.
     *
     * @return void
     */
    public function testFormatsValueTypesCorrectly(): void
    {
        $ability = $this->getAbilityInstance();

        if (! defined('ABSPATH')) {
            define('ABSPATH', '/var/www/html/');
        }

        // json_encode is used directly, no mock needed.

        // Test string constant - ABSPATH may already be defined by bootstrap.
        $result = $ability->doExecute(array('name' => 'ABSPATH'));
        $this->assertIsArray($result);
        $this->assertEquals('string', $result['type']);
        $this->assertTrue($result['defined']);
        // Value should match whatever ABSPATH is defined as.
        $this->assertEquals(ABSPATH, $result['value']);
    }

    /**
     * Test annotations are correct for read-only ability.
     *
     * @return void
     */
    public function testGetAnnotations(): void
    {
        $ability     = new GetConfigConstantAbility();
        $annotations = $ability->getAnnotations();

        $this->assertTrue($annotations['readonly']);
        $this->assertFalse($annotations['destructive']);
        $this->assertTrue($annotations['idempotent']);
    }
}
