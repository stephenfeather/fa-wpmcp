<?php

/**
 * Tests for UpdateOption ability.
 *
 * @package FAWpmcp\Tests\Abilities\Settings
 */

declare(strict_types=1);

namespace FAWpmcp\Tests\Abilities\Settings;

use FAWpmcp\Abilities\AbstractAbility;
use FAWpmcp\Abilities\Settings\UpdateOption;
use FAWpmcp\Tests\TestCase\AbilityTestTrait;
use FAWpmcp\Tests\TestCase\BrainMonkeyTestCase;
use Brain\Monkey\Functions;

/**
 * Test UpdateOption ability functionality.
 *
 * Tests cover:
 * - Update existing option
 * - Create new option
 * - Update with autoload setting
 *
 * @package FAWpmcp\Tests\Abilities\Settings
 */
class UpdateOptionTest extends BrainMonkeyTestCase
{
    use AbilityTestTrait;

    /**
     * Get an instance of the ability being tested.
     *
     * @return AbstractAbility
     */
    protected function getAbilityInstance(): AbstractAbility
    {
        return new UpdateOption();
    }

    /**
     * Get expected metadata for the ability.
     *
     * @return array{
     *     name: string,
     *     category: string,
     *     label: string,
     *     description_contains: string,
     *     operation_type: string,
     *     required_capability: string
     * }
     */
    protected function getExpectedMetadata(): array
    {
        return array(
            'name'                  => 'fa-wpmcp/update-option',
            'category'              => 'settings',
            'label'                 => 'Update Option',
            'description_contains'  => 'update',
            'operation_type'        => 'write',
            'required_capability'   => 'manage_options',
        );
    }

    /**
     * Test execute updates existing option successfully.
     *
     * @return void
     */
    public function testExecuteUpdatesOption(): void
    {
        $ability = $this->getAbilityInstance();

        Functions\when('sanitize_key')->returnArg();

        Functions\expect('update_option')
            ->once()
            ->with('test_option', 'new_value', null)
            ->andReturn(true);

        $result = $ability->doExecute(
            array(
                'option_name' => 'test_option',
                'value'       => 'new_value',
            )
        );

        $this->assertEquals('test_option', $result['option_name']);
        $this->assertTrue($result['updated']);
    }

    /**
     * Test execute updates option with autoload setting.
     *
     * @return void
     */
    public function testExecuteUpdatesOptionWithAutoload(): void
    {
        $ability = $this->getAbilityInstance();

        Functions\when('sanitize_key')->returnArg();

        Functions\expect('update_option')
            ->once()
            ->with('test_option', 'value', 'yes')
            ->andReturn(true);

        $result = $ability->doExecute(
            array(
                'option_name' => 'test_option',
                'value'       => 'value',
                'autoload'    => 'yes',
            )
        );

        $this->assertEquals('test_option', $result['option_name']);
        $this->assertTrue($result['updated']);
    }

    /**
     * Test execute handles update failure.
     *
     * @return void
     */
    public function testExecuteHandlesUpdateFailure(): void
    {
        $ability = $this->getAbilityInstance();

        Functions\when('sanitize_key')->returnArg();

        Functions\expect('update_option')
            ->once()
            ->with('test_option', 'value', null)
            ->andReturn(false);

        $result = $ability->doExecute(
            array(
                'option_name' => 'test_option',
                'value'       => 'value',
            )
        );

        $this->assertEquals('test_option', $result['option_name']);
        $this->assertFalse($result['updated']);
    }

    /**
     * Test execute handles array values.
     *
     * @return void
     */
    public function testExecuteHandlesArrayValues(): void
    {
        $ability = $this->getAbilityInstance();
        $value   = array(
            'key1' => 'value1',
            'key2' => 'value2',
        );

        Functions\when('sanitize_key')->returnArg();

        Functions\expect('update_option')
            ->once()
            ->with('test_option', $value, null)
            ->andReturn(true);

        $result = $ability->doExecute(
            array(
                'option_name' => 'test_option',
                'value'       => $value,
            )
        );

        $this->assertTrue($result['updated']);
    }

    /**
     * Test execute blocks protected options.
     *
     * @return void
     */
    public function testExecuteBlocksProtectedOption(): void
    {
        $ability = $this->getAbilityInstance();

        Functions\when('sanitize_key')->returnArg();

        Functions\expect('update_option')
            ->never();

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('protected');

        $ability->doExecute(
            array(
                'option_name' => 'admin_email',
                'value'       => 'hacker@example.com',
            )
        );
    }
}
