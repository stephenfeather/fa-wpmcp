<?php

/**
 * Tests for GetEnvVarAbility.
 *
 * @package FAWpmcp\Tests\Abilities\Dotenv
 */

declare(strict_types=1);

namespace FAWpmcp\Tests\Abilities\Dotenv;

use FAWpmcp\Abilities\AbstractAbility;
use FAWpmcp\Abilities\Dotenv\EnvFileLocator;
use FAWpmcp\Abilities\Dotenv\GetEnvVarAbility;
use FAWpmcp\Exceptions\DotenvException;
use FAWpmcp\Tests\TestCase\AbilityTestTrait;
use FAWpmcp\Tests\TestCase\BrainMonkeyTestCase;

/**
 * Test GetEnvVarAbility functionality.
 *
 * @package FAWpmcp\Tests\Abilities\Dotenv
 */
class GetEnvVarAbilityTest extends BrainMonkeyTestCase
{
    use AbilityTestTrait;

    /**
     * Temporary env file path.
     *
     * @var string
     */
    private string $tempEnvFile;

    /**
     * Set up test fixtures.
     *
     * @return void
     */
    protected function setUp(): void
    {
        parent::setUp();
        $this->tempEnvFile = sys_get_temp_dir() . '/.env_test_' . uniqid();
    }

    /**
     * Tear down test fixtures.
     *
     * @return void
     */
    protected function tearDown(): void
    {
        if (file_exists($this->tempEnvFile)) {
            unlink($this->tempEnvFile);
        }
        parent::tearDown();
    }

    /**
     * Get an instance of the ability being tested.
     *
     * @return AbstractAbility
     */
    protected function getAbilityInstance(): AbstractAbility
    {
        return new GetEnvVarAbility();
    }

    /**
     * Get expected metadata for the ability.
     *
     * @return array<string, string>
     */
    protected function getExpectedMetadata(): array
    {
        return array(
            'name'                 => 'fa-wpmcp/get-env-var',
            'category'             => 'dotenv',
            'label'                => 'Get Environment Variable',
            'description_contains' => 'environment variable',
            'operation_type'       => 'read',
            'required_capability'  => 'manage_options',
        );
    }

    /**
     * Test ability returns input schema with required key.
     *
     * @return void
     */
    public function testGetInputSchemaHasRequiredKey(): void
    {
        $ability = $this->getAbilityInstance();
        $schema = $ability->getInputSchema();

        $this->assertArrayHasKey('properties', $schema);
        $this->assertArrayHasKey('key', $schema['properties']);
        $this->assertArrayHasKey('required', $schema);
        $this->assertContains('key', $schema['required']);
    }

    /**
     * Test ability returns output schema.
     *
     * @return void
     */
    public function testGetOutputSchema(): void
    {
        $ability = $this->getAbilityInstance();
        $schema = $ability->getOutputSchema();

        $this->assertArrayHasKey('properties', $schema);
        $this->assertArrayHasKey('key', $schema['properties']);
        $this->assertArrayHasKey('value', $schema['properties']);
        $this->assertArrayHasKey('sensitive', $schema['properties']);
        $this->assertArrayHasKey('exists', $schema['properties']);
    }

    /**
     * Test execute returns variable value.
     *
     * @return void
     */
    public function testExecuteReturnsVariableValue(): void
    {
        file_put_contents($this->tempEnvFile, "WP_ENV=development\n");

        $locator = $this->createMock(EnvFileLocator::class);
        $locator->method('locate')->willReturn($this->tempEnvFile);

        $ability = new GetEnvVarAbility($locator);
        $result = $ability->doExecute(array('key' => 'WP_ENV'));

        $this->assertEquals('WP_ENV', $result['key']);
        $this->assertEquals('development', $result['value']);
        $this->assertTrue($result['exists']);
        $this->assertFalse($result['sensitive']);
    }

    /**
     * Test execute redacts sensitive value by default.
     *
     * @return void
     */
    public function testExecuteRedactsSensitiveValueByDefault(): void
    {
        file_put_contents($this->tempEnvFile, "DB_PASSWORD=secret123\n");

        $locator = $this->createMock(EnvFileLocator::class);
        $locator->method('locate')->willReturn($this->tempEnvFile);

        $ability = new GetEnvVarAbility($locator);
        $result = $ability->doExecute(array('key' => 'DB_PASSWORD'));

        $this->assertEquals('[REDACTED]', $result['value']);
        $this->assertTrue($result['sensitive']);
        $this->assertTrue($result['exists']);
    }

    /**
     * Test execute shows sensitive value when requested.
     *
     * @return void
     */
    public function testExecuteShowsSensitiveValueWhenRequested(): void
    {
        file_put_contents($this->tempEnvFile, "DB_PASSWORD=secret123\n");

        $locator = $this->createMock(EnvFileLocator::class);
        $locator->method('locate')->willReturn($this->tempEnvFile);

        $ability = new GetEnvVarAbility($locator);
        $result = $ability->doExecute(array('key' => 'DB_PASSWORD', 'show_sensitive' => true));

        $this->assertEquals('secret123', $result['value']);
        $this->assertTrue($result['sensitive']);
    }

    /**
     * Test execute returns exists false for non-existent variable.
     *
     * @return void
     */
    public function testExecuteReturnsExistsFalseForNonExistentVariable(): void
    {
        file_put_contents($this->tempEnvFile, "WP_ENV=development\n");

        $locator = $this->createMock(EnvFileLocator::class);
        $locator->method('locate')->willReturn($this->tempEnvFile);

        $ability = new GetEnvVarAbility($locator);
        $result = $ability->doExecute(array('key' => 'NONEXISTENT'));

        $this->assertEquals('NONEXISTENT', $result['key']);
        $this->assertEquals('', $result['value']);
        $this->assertFalse($result['exists']);
    }

    /**
     * Test execute throws exception when file cannot be read.
     *
     * @return void
     */
    public function testExecuteThrowsExceptionWhenFileCannotBeRead(): void
    {
        $locator = $this->createMock(EnvFileLocator::class);
        $locator->method('locate')->willReturn('/nonexistent/path/.env');

        $ability = new GetEnvVarAbility($locator);

        $this->expectException(DotenvException::class);
        $ability->doExecute(array('key' => 'WP_ENV'));
    }

    /**
     * Test annotations are correct for read-only ability.
     *
     * @return void
     */
    public function testGetAnnotations(): void
    {
        $ability = new GetEnvVarAbility();
        $annotations = $ability->getAnnotations();

        $this->assertTrue($annotations['readonly']);
        $this->assertFalse($annotations['destructive']);
        $this->assertTrue($annotations['idempotent']);
    }
}
