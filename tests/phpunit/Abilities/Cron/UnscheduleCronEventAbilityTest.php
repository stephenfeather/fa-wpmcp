<?php

/**
 * Tests for UnscheduleCronEventAbility.
 *
 * @package FAWpmcp\Tests\Abilities\Cron
 */

declare(strict_types=1);

namespace FAWpmcp\Tests\Abilities\Cron;

use FAWpmcp\Abilities\AbstractAbility;
use FAWpmcp\Abilities\Cron\UnscheduleCronEventAbility;
use FAWpmcp\Tests\TestCase\AbilityTestTrait;
use FAWpmcp\Tests\TestCase\BrainMonkeyTestCase;
use Brain\Monkey\Functions;
use Mockery;

/**
 * Test UnscheduleCronEventAbility functionality.
 *
 * @package FAWpmcp\Tests\Abilities\Cron
 */
final class UnscheduleCronEventAbilityTest extends BrainMonkeyTestCase
{
    use AbilityTestTrait;

    /**
     * Get the ability instance for testing.
     *
     * @return AbstractAbility
     */
    protected function getAbilityInstance(): AbstractAbility
    {
        return new UnscheduleCronEventAbility();
    }

    /**
     * Get expected metadata for the ability.
     *
     * @return array<string, mixed>
     */
    protected function getExpectedMetadata(): array
    {
        return [
            'name'                 => 'fa-wpmcp/unschedule-cron-event',
            'category'             => 'cron',
            'label'                => 'Unschedule Cron Event',
            'description_contains' => 'cron',
            'operation_type'       => 'write',
            'required_capability'  => 'manage_options',
        ];
    }

    /**
     * Test execute unschedules specific event by hook and timestamp.
     *
     * @return void
     */
    public function testExecuteUnschedulesSpecificEventByTimestamp(): void
    {
        $ability = $this->getAbilityInstance();

        $cron_array = array(
            1706200000 => array(
                'my_custom_hook' => array(
                    '40cd750bba9870f18aada2478b24840a' => array(
                        'schedule' => 'hourly',
                        'args'     => array(),
                    ),
                ),
            ),
        );

        Functions\when('_get_cron_array')->justReturn($cron_array);

        Functions\expect('wp_unschedule_event')
            ->once()
            ->with(1706200000, 'my_custom_hook', array())
            ->andReturn(true);

        $result = $ability->doExecute(
            array(
                'hook'      => 'my_custom_hook',
                'timestamp' => 1706200000,
            )
        );

        $this->assertIsArray($result);
        $this->assertTrue($result['success']);
        $this->assertEquals(1, $result['removed_count']);
    }

    /**
     * Test execute clears all events for hook when no timestamp provided.
     *
     * @return void
     */
    public function testExecuteClearsAllEventsForHookWhenNoTimestamp(): void
    {
        $ability = $this->getAbilityInstance();

        Functions\expect('wp_clear_scheduled_hook')
            ->once()
            ->with('my_custom_hook', array())
            ->andReturn(3);

        $result = $ability->doExecute(array( 'hook' => 'my_custom_hook' ));

        $this->assertTrue($result['success']);
        $this->assertEquals(3, $result['removed_count']);
    }

    /**
     * Test execute returns failure when wp_unschedule_event fails.
     *
     * @return void
     */
    public function testExecuteReturnsFailureWhenUnscheduleFails(): void
    {
        $ability = $this->getAbilityInstance();

        $cron_array = array(
            1706200000 => array(
                'my_custom_hook' => array(
                    '40cd750bba9870f18aada2478b24840a' => array(
                        'schedule' => 'hourly',
                        'args'     => array(),
                    ),
                ),
            ),
        );

        Functions\when('_get_cron_array')->justReturn($cron_array);

        $wp_error = Mockery::mock('WP_Error');
        $wp_error->shouldReceive('get_error_message')->andReturn('Unschedule failed');

        Functions\expect('wp_unschedule_event')
            ->once()
            ->andReturn($wp_error);

        Functions\when('is_wp_error')->alias(
            function ($thing) use ($wp_error) {
                return $thing === $wp_error;
            }
        );

        $result = $ability->doExecute(
            array(
                'hook'      => 'my_custom_hook',
                'timestamp' => 1706200000,
            )
        );

        $this->assertFalse($result['success']);
        $this->assertArrayHasKey('error', $result);
    }

    /**
     * Test execute returns zero removed when hook not found.
     *
     * @return void
     */
    public function testExecuteReturnsZeroRemovedWhenHookNotFound(): void
    {
        $ability = $this->getAbilityInstance();

        Functions\expect('wp_clear_scheduled_hook')
            ->once()
            ->with('nonexistent_hook', array())
            ->andReturn(0);

        $result = $ability->doExecute(array( 'hook' => 'nonexistent_hook' ));

        $this->assertTrue($result['success']);
        $this->assertEquals(0, $result['removed_count']);
    }

    /**
     * Test annotations indicate destructive write operation.
     *
     * @return void
     */
    public function testGetAnnotations(): void
    {
        $ability     = new UnscheduleCronEventAbility();
        $annotations = $ability->getAnnotations();

        $this->assertFalse($annotations['readonly']);
        $this->assertTrue($annotations['destructive']);
        $this->assertTrue($annotations['idempotent']);
    }

    /**
     * Test execute returns failure when wp_clear_scheduled_hook fails.
     *
     * @return void
     */
    public function testExecuteReturnsFailureWhenClearHookFails(): void
    {
        $ability = $this->getAbilityInstance();

        $wp_error = Mockery::mock('WP_Error');
        $wp_error->shouldReceive('get_error_message')->andReturn('Clear hook failed');

        Functions\expect('wp_clear_scheduled_hook')
            ->once()
            ->andReturn($wp_error);

        Functions\when('is_wp_error')->alias(
            function ($thing) use ($wp_error) {
                return $thing === $wp_error;
            }
        );

        $result = $ability->doExecute(array( 'hook' => 'my_custom_hook' ));

        $this->assertFalse($result['success']);
        $this->assertArrayHasKey('error', $result);
    }

    /**
     * Test execute handles timestamp for event not found.
     *
     * @return void
     */
    public function testExecuteHandlesTimestampForEventNotFound(): void
    {
        $ability = $this->getAbilityInstance();

        $cron_array = array(
            1706200000 => array(
                'other_hook' => array(
                    '40cd750bba9870f18aada2478b24840a' => array(
                        'schedule' => 'hourly',
                        'args'     => array(),
                    ),
                ),
            ),
        );

        Functions\when('_get_cron_array')->justReturn($cron_array);

        $result = $ability->doExecute(
            array(
                'hook'      => 'my_custom_hook',
                'timestamp' => 1706200000,
            )
        );

        $this->assertTrue($result['success']);
        $this->assertEquals(0, $result['removed_count']);
    }
}
