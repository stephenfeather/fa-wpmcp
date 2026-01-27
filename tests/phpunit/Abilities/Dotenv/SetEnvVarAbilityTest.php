<?php

/**
 * Tests for SetEnvVarAbility.
 *
 * @package FAWpmcp\Tests\Abilities\Dotenv
 */

declare(strict_types=1);

namespace FAWpmcp\Tests\Abilities\Dotenv;

use FAWpmcp\Abilities\AbstractAbility;
use FAWpmcp\Abilities\Dotenv\EnvFileLocator;
use FAWpmcp\Abilities\Dotenv\SetEnvVarAbility;
use FAWpmcp\Exceptions\DotenvException;
use FAWpmcp\Tests\TestCase\AbilityTestTrait;
use FAWpmcp\Tests\TestCase\BrainMonkeyTestCase;

/**
 * Test SetEnvVarAbility functionality.
 *
 * @package FAWpmcp\Tests\Abilities\Dotenv
 */
class SetEnvVarAbilityTest extends BrainMonkeyTestCase
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
        return new SetEnvVarAbility();
    }

    /**
     * Get expected metadata for the ability.
     *
     * @return array<string, string>
     */
    protected function getExpectedMetadata(): array
    {
        return array(
            'name'                 => 'fa-wpmcp/set-env-var',
            'category'             => 'dotenv',
            'label'                => 'Set Environment Variable',
            'description_contains' => 'environment variable',
            'operation_type'       => 'write',
            'required_capability'  => 'manage_options',
        );
    }

    /**
     * Test ability returns input schema with required fields.
     *
     * @return void
     */
    public function testGetInputSchemaHasRequiredFields(): void
    {
        $ability = $this->getAbilityInstance();
        $schema = $ability->getInputSchema();

        $this->assertArrayHasKey('properties', $schema);
        $this->assertArrayHasKey('key', $schema['properties']);
        $this->assertArrayHasKey('value', $schema['properties']);
        $this->assertArrayHasKey('quote', $schema['properties']);
        $this->assertArrayHasKey('required', $schema);
        $this->assertContains('key', $schema['required']);
        $this->assertContains('value', $schema['required']);
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
        $this->assertArrayHasKey('action', $schema['properties']);
    }

    /**
     * Test execute creates new variable.
     *
     * @return void
     */
    public function testExecuteCreatesNewVariable(): void
    {
        file_put_contents($this->tempEnvFile, "WP_ENV=development\n");

        $locator = $this->createMock(EnvFileLocator::class);
        $locator->method('locate')->willReturn($this->tempEnvFile);

        $ability = new SetEnvVarAbility($locator);
        $result = $ability->doExecute(array('key' => 'NEW_VAR', 'value' => 'new_value'));

        $this->assertEquals('NEW_VAR', $result['key']);
        $this->assertEquals('new_value', $result['value']);
        $this->assertEquals('created', $result['action']);

        $content = file_get_contents($this->tempEnvFile);
        $this->assertStringContainsString('NEW_VAR=new_value', $content);
    }

    /**
     * Test execute updates existing variable.
     *
     * @return void
     */
    public function testExecuteUpdatesExistingVariable(): void
    {
        file_put_contents($this->tempEnvFile, "MY_VAR=old_value\n");

        $locator = $this->createMock(EnvFileLocator::class);
        $locator->method('locate')->willReturn($this->tempEnvFile);

        $ability = new SetEnvVarAbility($locator);
        $result = $ability->doExecute(array('key' => 'MY_VAR', 'value' => 'new_value'));

        $this->assertEquals('MY_VAR', $result['key']);
        $this->assertEquals('new_value', $result['value']);
        $this->assertEquals('updated', $result['action']);

        $content = file_get_contents($this->tempEnvFile);
        $this->assertStringContainsString('MY_VAR=new_value', $content);
        $this->assertStringNotContainsString('old_value', $content);
    }

    /**
     * Test execute quotes value when requested.
     *
     * @return void
     */
    public function testExecuteQuotesValueWhenRequested(): void
    {
        file_put_contents($this->tempEnvFile, "");

        $locator = $this->createMock(EnvFileLocator::class);
        $locator->method('locate')->willReturn($this->tempEnvFile);

        $ability = new SetEnvVarAbility($locator);
        $ability->doExecute(array('key' => 'MY_VAR', 'value' => 'value with spaces', 'quote' => true));

        $content = file_get_contents($this->tempEnvFile);
        $this->assertStringContainsString('MY_VAR="value with spaces"', $content);
    }

    /**
     * Test execute rejects protected DB_NAME.
     *
     * @return void
     */
    public function testExecuteRejectsProtectedDbName(): void
    {
        file_put_contents($this->tempEnvFile, "DB_NAME=mydb\n");

        $locator = $this->createMock(EnvFileLocator::class);
        $locator->method('locate')->willReturn($this->tempEnvFile);

        $ability = new SetEnvVarAbility($locator);

        $this->expectException(DotenvException::class);
        $this->expectExceptionMessage('Cannot modify protected variable');
        $ability->doExecute(array('key' => 'DB_NAME', 'value' => 'newdb'));
    }

    /**
     * Test execute rejects protected DB_PASSWORD.
     *
     * @return void
     */
    public function testExecuteRejectsProtectedDbPassword(): void
    {
        file_put_contents($this->tempEnvFile, "DB_PASSWORD=secret\n");

        $locator = $this->createMock(EnvFileLocator::class);
        $locator->method('locate')->willReturn($this->tempEnvFile);

        $ability = new SetEnvVarAbility($locator);

        $this->expectException(DotenvException::class);
        $this->expectExceptionMessage('Cannot modify protected variable');
        $ability->doExecute(array('key' => 'DB_PASSWORD', 'value' => 'newpass'));
    }

    /**
     * Test execute rejects protected WP_ENV.
     *
     * @return void
     */
    public function testExecuteRejectsProtectedWpEnv(): void
    {
        file_put_contents($this->tempEnvFile, "WP_ENV=development\n");

        $locator = $this->createMock(EnvFileLocator::class);
        $locator->method('locate')->willReturn($this->tempEnvFile);

        $ability = new SetEnvVarAbility($locator);

        $this->expectException(DotenvException::class);
        $this->expectExceptionMessage('Cannot modify protected variable');
        $ability->doExecute(array('key' => 'WP_ENV', 'value' => 'production'));
    }

    /**
     * Test execute rejects protected KEY suffix variables.
     *
     * @return void
     */
    public function testExecuteRejectsProtectedKeySuffixVariables(): void
    {
        file_put_contents($this->tempEnvFile, "AUTH_KEY=xyz\n");

        $locator = $this->createMock(EnvFileLocator::class);
        $locator->method('locate')->willReturn($this->tempEnvFile);

        $ability = new SetEnvVarAbility($locator);

        $this->expectException(DotenvException::class);
        $this->expectExceptionMessage('Cannot modify protected variable');
        $ability->doExecute(array('key' => 'AUTH_KEY', 'value' => 'newkey'));
    }

    /**
     * Test execute rejects protected SALT suffix variables.
     *
     * @return void
     */
    public function testExecuteRejectsProtectedSaltSuffixVariables(): void
    {
        file_put_contents($this->tempEnvFile, "AUTH_SALT=xyz\n");

        $locator = $this->createMock(EnvFileLocator::class);
        $locator->method('locate')->willReturn($this->tempEnvFile);

        $ability = new SetEnvVarAbility($locator);

        $this->expectException(DotenvException::class);
        $this->expectExceptionMessage('Cannot modify protected variable');
        $ability->doExecute(array('key' => 'AUTH_SALT', 'value' => 'newsalt'));
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

        $ability = new SetEnvVarAbility($locator);

        $this->expectException(DotenvException::class);
        $ability->doExecute(array('key' => 'MY_VAR', 'value' => 'test'));
    }

    /**
     * Test annotations are correct for write ability.
     *
     * @return void
     */
    public function testGetAnnotations(): void
    {
        $ability = new SetEnvVarAbility();
        $annotations = $ability->getAnnotations();

        $this->assertFalse($annotations['readonly']);
        $this->assertFalse($annotations['destructive']);
        $this->assertTrue($annotations['idempotent']);
    }
}
