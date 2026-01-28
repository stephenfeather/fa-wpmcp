<?php

/**
 * Tests for DeleteEnvVarAbility.
 *
 * @package FAWpmcp\Tests\Abilities\Dotenv
 */

declare(strict_types=1);

namespace FAWpmcp\Tests\Abilities\Dotenv;

use FAWpmcp\Abilities\AbstractAbility;
use FAWpmcp\Abilities\Dotenv\DeleteEnvVarAbility;
use FAWpmcp\Abilities\Dotenv\EnvFileLocator;
use FAWpmcp\Exceptions\DotenvException;
use FAWpmcp\Tests\TestCase\AbilityTestTrait;
use FAWpmcp\Tests\TestCase\BrainMonkeyTestCase;

/**
 * Test DeleteEnvVarAbility functionality.
 *
 * @package FAWpmcp\Tests\Abilities\Dotenv
 */
class DeleteEnvVarAbilityTest extends BrainMonkeyTestCase
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
        return new DeleteEnvVarAbility();
    }

    /**
     * Get expected metadata for the ability.
     *
     * @return array<string, string>
     */
    protected function getExpectedMetadata(): array
    {
        return array(
            'name'                 => 'fa-wpmcp/delete-env-var',
            'category'             => 'dotenv',
            'label'                => 'Delete Environment Variable',
            'description_contains' => 'environment variable',
            'operation_type'       => 'write',
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
        $this->assertArrayHasKey('deleted', $schema['properties']);
    }

    /**
     * Test execute deletes existing variable.
     *
     * @return void
     */
    public function testExecuteDeletesExistingVariable(): void
    {
        file_put_contents($this->tempEnvFile, "MY_VAR=value\nOTHER=test\n");

        $locator = $this->createMock(EnvFileLocator::class);
        $locator->method('locate')->willReturn($this->tempEnvFile);

        $ability = new DeleteEnvVarAbility($locator);
        $result = $ability->doExecute(array('key' => 'MY_VAR'));

        $this->assertEquals('MY_VAR', $result['key']);
        $this->assertTrue($result['deleted']);

        $content = file_get_contents($this->tempEnvFile);
        $this->assertStringNotContainsString('MY_VAR', $content);
        $this->assertStringContainsString('OTHER=test', $content);
    }

    /**
     * Test execute returns deleted false for non-existent variable.
     *
     * @return void
     */
    public function testExecuteReturnsDeletedFalseForNonExistentVariable(): void
    {
        file_put_contents($this->tempEnvFile, "MY_VAR=value\n");

        $locator = $this->createMock(EnvFileLocator::class);
        $locator->method('locate')->willReturn($this->tempEnvFile);

        $ability = new DeleteEnvVarAbility($locator);
        $result = $ability->doExecute(array('key' => 'NONEXISTENT'));

        $this->assertEquals('NONEXISTENT', $result['key']);
        $this->assertFalse($result['deleted']);
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

        $ability = new DeleteEnvVarAbility($locator);

        $this->expectException(DotenvException::class);
        $this->expectExceptionMessage('Cannot delete protected variable');
        $ability->doExecute(array('key' => 'DB_NAME'));
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

        $ability = new DeleteEnvVarAbility($locator);

        $this->expectException(DotenvException::class);
        $this->expectExceptionMessage('Cannot delete protected variable');
        $ability->doExecute(array('key' => 'DB_PASSWORD'));
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

        $ability = new DeleteEnvVarAbility($locator);

        $this->expectException(DotenvException::class);
        $this->expectExceptionMessage('Cannot delete protected variable');
        $ability->doExecute(array('key' => 'WP_ENV'));
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

        $ability = new DeleteEnvVarAbility($locator);

        $this->expectException(DotenvException::class);
        $this->expectExceptionMessage('Cannot delete protected variable');
        $ability->doExecute(array('key' => 'AUTH_KEY'));
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

        $ability = new DeleteEnvVarAbility($locator);

        $this->expectException(DotenvException::class);
        $this->expectExceptionMessage('Cannot delete protected variable');
        $ability->doExecute(array('key' => 'AUTH_SALT'));
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

        $ability = new DeleteEnvVarAbility($locator);

        $this->expectException(DotenvException::class);
        $ability->doExecute(array('key' => 'MY_VAR'));
    }

    /**
     * Test annotations are correct for destructive ability.
     *
     * @return void
     */
    public function testGetAnnotations(): void
    {
        $ability = new DeleteEnvVarAbility();
        $annotations = $ability->getAnnotations();

        $this->assertFalse($annotations['readonly']);
        $this->assertTrue($annotations['destructive']);
        $this->assertFalse($annotations['idempotent']);
    }
}
