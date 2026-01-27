<?php

/**
 * Tests for ListEnvVarsAbility.
 *
 * @package FAWpmcp\Tests\Abilities\Dotenv
 */

declare(strict_types=1);

namespace FAWpmcp\Tests\Abilities\Dotenv;

use FAWpmcp\Abilities\AbstractAbility;
use FAWpmcp\Abilities\Dotenv\EnvAccessPolicy;
use FAWpmcp\Abilities\Dotenv\EnvFileLocator;
use FAWpmcp\Abilities\Dotenv\EnvFileParser;
use FAWpmcp\Abilities\Dotenv\ListEnvVarsAbility;
use FAWpmcp\Exceptions\DotenvException;
use FAWpmcp\Tests\TestCase\AbilityTestTrait;
use FAWpmcp\Tests\TestCase\BrainMonkeyTestCase;

/**
 * Test ListEnvVarsAbility functionality.
 *
 * @package FAWpmcp\Tests\Abilities\Dotenv
 */
class ListEnvVarsAbilityTest extends BrainMonkeyTestCase
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
        return new ListEnvVarsAbility();
    }

    /**
     * Get expected metadata for the ability.
     *
     * @return array<string, string>
     */
    protected function getExpectedMetadata(): array
    {
        return array(
            'name'                 => 'fa-wpmcp/list-env-vars',
            'category'             => 'dotenv',
            'label'                => 'List Environment Variables',
            'description_contains' => 'environment variables',
            'operation_type'       => 'read',
            'required_capability'  => 'manage_options',
        );
    }

    /**
     * Test ability returns input schema with show_sensitive property.
     *
     * @return void
     */
    public function testGetInputSchemaHasShowSensitiveProperty(): void
    {
        $ability = $this->getAbilityInstance();
        $schema = $ability->getInputSchema();

        $this->assertArrayHasKey('properties', $schema);
        $this->assertArrayHasKey('show_sensitive', $schema['properties']);
        $this->assertEquals('boolean', $schema['properties']['show_sensitive']['type']);
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
        $this->assertArrayHasKey('variables', $schema['properties']);
        $this->assertArrayHasKey('total', $schema['properties']);
        $this->assertArrayHasKey('file_path', $schema['properties']);
    }

    /**
     * Test execute returns list of variables.
     *
     * @return void
     */
    public function testExecuteReturnsListOfVariables(): void
    {
        file_put_contents($this->tempEnvFile, "WP_ENV=development\nDEBUG=true\n");

        $locator = $this->createMock(EnvFileLocator::class);
        $locator->method('locate')->willReturn($this->tempEnvFile);

        $ability = new ListEnvVarsAbility($locator);
        $result = $ability->doExecute(array());

        $this->assertArrayHasKey('variables', $result);
        $this->assertArrayHasKey('total', $result);
        $this->assertArrayHasKey('file_path', $result);
        $this->assertEquals(2, $result['total']);
        $this->assertEquals($this->tempEnvFile, $result['file_path']);
    }

    /**
     * Test execute redacts sensitive values by default.
     *
     * @return void
     */
    public function testExecuteRedactsSensitiveValuesByDefault(): void
    {
        file_put_contents($this->tempEnvFile, "DB_PASSWORD=secret123\nWP_ENV=development\n");

        $locator = $this->createMock(EnvFileLocator::class);
        $locator->method('locate')->willReturn($this->tempEnvFile);

        $ability = new ListEnvVarsAbility($locator);
        $result = $ability->doExecute(array());

        $variables = $result['variables'];
        $password_var = array_filter($variables, fn($v) => $v['key'] === 'DB_PASSWORD');
        $password_var = reset($password_var);

        $this->assertEquals('[REDACTED]', $password_var['value']);
        $this->assertTrue($password_var['sensitive']);
    }

    /**
     * Test execute shows sensitive values when requested.
     *
     * @return void
     */
    public function testExecuteShowsSensitiveValuesWhenRequested(): void
    {
        file_put_contents($this->tempEnvFile, "DB_PASSWORD=secret123\n");

        $locator = $this->createMock(EnvFileLocator::class);
        $locator->method('locate')->willReturn($this->tempEnvFile);

        $ability = new ListEnvVarsAbility($locator);
        $result = $ability->doExecute(array('show_sensitive' => true));

        $variables = $result['variables'];
        $password_var = array_filter($variables, fn($v) => $v['key'] === 'DB_PASSWORD');
        $password_var = reset($password_var);

        $this->assertEquals('secret123', $password_var['value']);
        $this->assertTrue($password_var['sensitive']);
    }

    /**
     * Test execute marks non-sensitive variables correctly.
     *
     * @return void
     */
    public function testExecuteMarksNonSensitiveVariablesCorrectly(): void
    {
        file_put_contents($this->tempEnvFile, "WP_ENV=development\n");

        $locator = $this->createMock(EnvFileLocator::class);
        $locator->method('locate')->willReturn($this->tempEnvFile);

        $ability = new ListEnvVarsAbility($locator);
        $result = $ability->doExecute(array());

        $variables = $result['variables'];
        $env_var = array_filter($variables, fn($v) => $v['key'] === 'WP_ENV');
        $env_var = reset($env_var);

        $this->assertEquals('development', $env_var['value']);
        $this->assertFalse($env_var['sensitive']);
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

        $ability = new ListEnvVarsAbility($locator);

        $this->expectException(DotenvException::class);
        $ability->doExecute(array());
    }

    /**
     * Test annotations are correct for read-only ability.
     *
     * @return void
     */
    public function testGetAnnotations(): void
    {
        $ability = new ListEnvVarsAbility();
        $annotations = $ability->getAnnotations();

        $this->assertTrue($annotations['readonly']);
        $this->assertFalse($annotations['destructive']);
        $this->assertTrue($annotations['idempotent']);
    }
}
