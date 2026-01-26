<?php

/**
 * Tests for ListCronSchedulesAbility.
 *
 * @package FAWpmcp\Tests\Abilities\Cron
 */

declare(strict_types=1);

namespace FAWpmcp\Tests\Abilities\Cron;

use FAWpmcp\Abilities\AbstractAbility;
use FAWpmcp\Abilities\Cron\ListCronSchedulesAbility;
use FAWpmcp\Tests\TestCase\AbilityTestTrait;
use FAWpmcp\Tests\TestCase\BrainMonkeyTestCase;
use Brain\Monkey\Functions;

/**
 * Test ListCronSchedulesAbility functionality.
 *
 * @package FAWpmcp\Tests\Abilities\Cron
 */
final class ListCronSchedulesAbilityTest extends BrainMonkeyTestCase
{
    use AbilityTestTrait;

    /**
     * Get the ability instance for testing.
     *
     * @return AbstractAbility
     */
    protected function getAbilityInstance(): AbstractAbility
    {
        return new ListCronSchedulesAbility();
    }

    /**
     * Get expected metadata for the ability.
     *
     * @return array<string, mixed>
     */
    protected function getExpectedMetadata(): array
    {
        return [
            'name'                 => 'fa-wpmcp/list-cron-schedules',
            'category'             => 'cron',
            'label'                => 'List Cron Schedules',
            'description_contains' => 'cron',
            'operation_type'       => 'read',
            'required_capability'  => 'manage_options',
        ];
    }

    /**
     * Test execute returns list of cron schedules.
     *
     * @return void
     */
    public function testExecuteReturnsListOfCronSchedules(): void
    {
        $ability = $this->getAbilityInstance();

        $schedules = array(
            'hourly'     => array(
                'interval' => 3600,
                'display'  => 'Once Hourly',
            ),
            'twicedaily' => array(
                'interval' => 43200,
                'display'  => 'Twice Daily',
            ),
            'daily'      => array(
                'interval' => 86400,
                'display'  => 'Once Daily',
            ),
        );

        Functions\when('wp_get_schedules')->justReturn($schedules);

        $result = $ability->doExecute(array());

        $this->assertIsArray($result);
        $this->assertArrayHasKey('schedules', $result);
        $this->assertArrayHasKey('total', $result);
        $this->assertCount(3, $result['schedules']);
        $this->assertEquals(3, $result['total']);
    }

    /**
     * Test execute returns schedules with correct structure.
     *
     * @return void
     */
    public function testExecuteReturnsSchedulesWithCorrectStructure(): void
    {
        $ability = $this->getAbilityInstance();

        $schedules = array(
            'hourly' => array(
                'interval' => 3600,
                'display'  => 'Once Hourly',
            ),
        );

        Functions\when('wp_get_schedules')->justReturn($schedules);

        $result = $ability->doExecute(array());

        $this->assertArrayHasKey('name', $result['schedules'][0]);
        $this->assertArrayHasKey('interval', $result['schedules'][0]);
        $this->assertArrayHasKey('display', $result['schedules'][0]);
        $this->assertEquals('hourly', $result['schedules'][0]['name']);
        $this->assertEquals(3600, $result['schedules'][0]['interval']);
        $this->assertEquals('Once Hourly', $result['schedules'][0]['display']);
    }

    /**
     * Test execute returns empty array when no schedules exist.
     *
     * @return void
     */
    public function testExecuteReturnsEmptyArrayWhenNoSchedules(): void
    {
        $ability = $this->getAbilityInstance();

        Functions\when('wp_get_schedules')->justReturn(array());

        $result = $ability->doExecute(array());

        $this->assertIsArray($result['schedules']);
        $this->assertEmpty($result['schedules']);
        $this->assertEquals(0, $result['total']);
    }

    /**
     * Test annotations are correct for read-only ability.
     *
     * @return void
     */
    public function testGetAnnotations(): void
    {
        $ability     = new ListCronSchedulesAbility();
        $annotations = $ability->getAnnotations();

        $this->assertTrue($annotations['readonly']);
        $this->assertFalse($annotations['destructive']);
        $this->assertTrue($annotations['idempotent']);
    }

    /**
     * Test execute includes custom plugin schedules.
     *
     * @return void
     */
    public function testExecuteIncludesCustomPluginSchedules(): void
    {
        $ability = $this->getAbilityInstance();

        $schedules = array(
            'hourly'        => array(
                'interval' => 3600,
                'display'  => 'Once Hourly',
            ),
            'weekly'        => array(
                'interval' => 604800,
                'display'  => 'Once Weekly',
            ),
            'custom_5min'   => array(
                'interval' => 300,
                'display'  => 'Every 5 Minutes',
            ),
        );

        Functions\when('wp_get_schedules')->justReturn($schedules);

        $result = $ability->doExecute(array());

        $this->assertCount(3, $result['schedules']);
        $schedule_names = array_column($result['schedules'], 'name');
        $this->assertContains('custom_5min', $schedule_names);
    }

    /**
     * Test execute sorts schedules by interval.
     *
     * @return void
     */
    public function testExecuteSortsSchedulesByInterval(): void
    {
        $ability = $this->getAbilityInstance();

        $schedules = array(
            'daily'      => array(
                'interval' => 86400,
                'display'  => 'Once Daily',
            ),
            'hourly'     => array(
                'interval' => 3600,
                'display'  => 'Once Hourly',
            ),
            'twicedaily' => array(
                'interval' => 43200,
                'display'  => 'Twice Daily',
            ),
        );

        Functions\when('wp_get_schedules')->justReturn($schedules);

        $result = $ability->doExecute(array());

        // Should be sorted by interval ascending.
        $this->assertEquals('hourly', $result['schedules'][0]['name']);
        $this->assertEquals('twicedaily', $result['schedules'][1]['name']);
        $this->assertEquals('daily', $result['schedules'][2]['name']);
    }
}
