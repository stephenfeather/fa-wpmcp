<?php

/**
 * Tests for ListCronSchedulesAbility.
 *
 * @package FAWpmcp\Tests\Abilities\Cron
 */

declare(strict_types=1);

namespace FAWpmcp\Tests\Abilities\Cron;

use FAWpmcp\Abilities\Cron\ListCronSchedulesAbility;
use Brain\Monkey;
use Brain\Monkey\Functions;
use Mockery;
use PHPUnit\Framework\TestCase;

/**
 * Test ListCronSchedulesAbility functionality.
 *
 * @package FAWpmcp\Tests\Abilities\Cron
 */
class ListCronSchedulesAbilityTest extends TestCase
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
        $ability = new ListCronSchedulesAbility();
        $this->assertEquals('fa-wpmcp/list-cron-schedules', $ability->getName());
    }

    /**
     * Test ability returns correct category.
     *
     * @return void
     */
    public function testGetCategory(): void
    {
        $ability = new ListCronSchedulesAbility();
        $this->assertEquals('cron', $ability->getCategory());
    }

    /**
     * Test ability returns correct label.
     *
     * @return void
     */
    public function testGetLabel(): void
    {
        $ability = new ListCronSchedulesAbility();
        $this->assertEquals('List Cron Schedules', $ability->getLabel());
    }

    /**
     * Test ability returns correct operation type.
     *
     * @return void
     */
    public function testGetOperationType(): void
    {
        $ability = new ListCronSchedulesAbility();
        $this->assertEquals('read', $ability->getOperationType());
    }

    /**
     * Test ability returns correct required capability.
     *
     * @return void
     */
    public function testGetRequiredCapability(): void
    {
        $ability = new ListCronSchedulesAbility();
        $this->assertEquals('manage_options', $ability->getRequiredCapability());
    }

    /**
     * Test ability returns input schema with no required fields.
     *
     * @return void
     */
    public function testGetInputSchema(): void
    {
        $ability = new ListCronSchedulesAbility();
        $schema  = $ability->getInputSchema();

        $this->assertIsArray($schema);
        $this->assertArrayHasKey('type', $schema);
        $this->assertArrayHasKey('properties', $schema);
        // No required fields.
        $this->assertArrayNotHasKey('required', $schema);
    }

    /**
     * Test ability returns output schema.
     *
     * @return void
     */
    public function testGetOutputSchema(): void
    {
        $ability = new ListCronSchedulesAbility();
        $schema  = $ability->getOutputSchema();

        $this->assertIsArray($schema);
        $this->assertArrayHasKey('type', $schema);
        $this->assertArrayHasKey('properties', $schema);
        $this->assertArrayHasKey('schedules', $schema['properties']);
        $this->assertArrayHasKey('total', $schema['properties']);
    }

    /**
     * Test execute returns list of cron schedules.
     *
     * @return void
     */
    public function testExecuteReturnsListOfCronSchedules(): void
    {
        $ability = new ListCronSchedulesAbility();

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
        $ability = new ListCronSchedulesAbility();

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
        $ability = new ListCronSchedulesAbility();

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
        $ability = new ListCronSchedulesAbility();

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
        $ability = new ListCronSchedulesAbility();

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
