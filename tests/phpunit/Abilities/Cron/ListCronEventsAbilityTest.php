<?php

/**
 * Tests for ListCronEventsAbility.
 *
 * @package FAWpmcp\Tests\Abilities\Cron
 */

declare(strict_types=1);

namespace FAWpmcp\Tests\Abilities\Cron;

use FAWpmcp\Abilities\Cron\ListCronEventsAbility;
use Brain\Monkey;
use Brain\Monkey\Functions;
use Mockery;
use PHPUnit\Framework\TestCase;

/**
 * Test ListCronEventsAbility functionality.
 *
 * @package FAWpmcp\Tests\Abilities\Cron
 */
class ListCronEventsAbilityTest extends TestCase
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
        $ability = new ListCronEventsAbility();
        $this->assertEquals('fa-wpmcp/list-cron-events', $ability->getName());
    }

    /**
     * Test ability returns correct category.
     *
     * @return void
     */
    public function testGetCategory(): void
    {
        $ability = new ListCronEventsAbility();
        $this->assertEquals('cron', $ability->getCategory());
    }

    /**
     * Test ability returns correct label.
     *
     * @return void
     */
    public function testGetLabel(): void
    {
        $ability = new ListCronEventsAbility();
        $this->assertEquals('List Cron Events', $ability->getLabel());
    }

    /**
     * Test ability returns correct operation type.
     *
     * @return void
     */
    public function testGetOperationType(): void
    {
        $ability = new ListCronEventsAbility();
        $this->assertEquals('read', $ability->getOperationType());
    }

    /**
     * Test ability returns correct required capability.
     *
     * @return void
     */
    public function testGetRequiredCapability(): void
    {
        $ability = new ListCronEventsAbility();
        $this->assertEquals('manage_options', $ability->getRequiredCapability());
    }

    /**
     * Test ability returns input schema with optional properties.
     *
     * @return void
     */
    public function testGetInputSchema(): void
    {
        $ability = new ListCronEventsAbility();
        $schema  = $ability->getInputSchema();

        $this->assertIsArray($schema);
        $this->assertArrayHasKey('type', $schema);
        $this->assertArrayHasKey('properties', $schema);
        $this->assertArrayHasKey('hook', $schema['properties']);
    }

    /**
     * Test ability returns output schema.
     *
     * @return void
     */
    public function testGetOutputSchema(): void
    {
        $ability = new ListCronEventsAbility();
        $schema  = $ability->getOutputSchema();

        $this->assertIsArray($schema);
        $this->assertArrayHasKey('type', $schema);
        $this->assertArrayHasKey('properties', $schema);
        $this->assertArrayHasKey('events', $schema['properties']);
        $this->assertArrayHasKey('total', $schema['properties']);
    }

    /**
     * Test execute returns list of cron events.
     *
     * @return void
     */
    public function testExecuteReturnsListOfCronEvents(): void
    {
        $ability = new ListCronEventsAbility();

        $cron_array = array(
            1706200000 => array(
                'wp_scheduled_delete' => array(
                    '40cd750bba9870f18aada2478b24840a' => array(
                        'schedule' => 'daily',
                        'args'     => array(),
                    ),
                ),
            ),
            1706210000 => array(
                'wp_update_plugins' => array(
                    '40cd750bba9870f18aada2478b24840a' => array(
                        'schedule' => 'twicedaily',
                        'args'     => array(),
                    ),
                ),
            ),
        );

        Functions\when('_get_cron_array')->justReturn($cron_array);

        $result = $ability->doExecute(array());

        $this->assertIsArray($result);
        $this->assertArrayHasKey('events', $result);
        $this->assertArrayHasKey('total', $result);
        $this->assertCount(2, $result['events']);
        $this->assertEquals(2, $result['total']);
    }

    /**
     * Test execute filters events by hook name pattern.
     *
     * @return void
     */
    public function testExecuteFiltersEventsByHookPattern(): void
    {
        $ability = new ListCronEventsAbility();

        $cron_array = array(
            1706200000 => array(
                'wp_scheduled_delete' => array(
                    '40cd750bba9870f18aada2478b24840a' => array(
                        'schedule' => 'daily',
                        'args'     => array(),
                    ),
                ),
            ),
            1706210000 => array(
                'wp_update_plugins' => array(
                    '40cd750bba9870f18aada2478b24840a' => array(
                        'schedule' => 'twicedaily',
                        'args'     => array(),
                    ),
                ),
            ),
        );

        Functions\when('_get_cron_array')->justReturn($cron_array);

        $result = $ability->doExecute(array( 'hook' => 'wp_scheduled' ));

        $this->assertCount(1, $result['events']);
        $this->assertEquals('wp_scheduled_delete', $result['events'][0]['hook']);
    }

    /**
     * Test execute returns empty array when no cron events exist.
     *
     * @return void
     */
    public function testExecuteReturnsEmptyArrayWhenNoCronEvents(): void
    {
        $ability = new ListCronEventsAbility();

        Functions\when('_get_cron_array')->justReturn(array());

        $result = $ability->doExecute(array());

        $this->assertIsArray($result['events']);
        $this->assertEmpty($result['events']);
        $this->assertEquals(0, $result['total']);
    }

    /**
     * Test execute returns event details with correct structure.
     *
     * @return void
     */
    public function testExecuteReturnsEventDetailsWithCorrectStructure(): void
    {
        $ability = new ListCronEventsAbility();

        $cron_array = array(
            1706200000 => array(
                'my_custom_hook' => array(
                    '40cd750bba9870f18aada2478b24840a' => array(
                        'schedule' => 'hourly',
                        'args'     => array( 'param1', 'param2' ),
                    ),
                ),
            ),
        );

        Functions\when('_get_cron_array')->justReturn($cron_array);

        $result = $ability->doExecute(array());

        $this->assertArrayHasKey('hook', $result['events'][0]);
        $this->assertArrayHasKey('timestamp', $result['events'][0]);
        $this->assertArrayHasKey('schedule', $result['events'][0]);
        $this->assertArrayHasKey('args', $result['events'][0]);
        $this->assertEquals('my_custom_hook', $result['events'][0]['hook']);
        $this->assertEquals(1706200000, $result['events'][0]['timestamp']);
        $this->assertEquals('hourly', $result['events'][0]['schedule']);
        $this->assertEquals(array( 'param1', 'param2' ), $result['events'][0]['args']);
    }

    /**
     * Test annotations are correct for read-only ability.
     *
     * @return void
     */
    public function testGetAnnotations(): void
    {
        $ability     = new ListCronEventsAbility();
        $annotations = $ability->getAnnotations();

        $this->assertTrue($annotations['readonly']);
        $this->assertFalse($annotations['destructive']);
        $this->assertTrue($annotations['idempotent']);
    }

    /**
     * Test execute handles null cron array.
     *
     * @return void
     */
    public function testExecuteHandlesNullCronArray(): void
    {
        $ability = new ListCronEventsAbility();

        Functions\when('_get_cron_array')->justReturn(false);

        $result = $ability->doExecute(array());

        $this->assertIsArray($result['events']);
        $this->assertEmpty($result['events']);
        $this->assertEquals(0, $result['total']);
    }
}
