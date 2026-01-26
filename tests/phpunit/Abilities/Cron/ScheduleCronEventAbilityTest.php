<?php

/**
 * Tests for ScheduleCronEventAbility.
 *
 * @package FAWpmcp\Tests\Abilities\Cron
 */

declare(strict_types=1);

namespace FAWpmcp\Tests\Abilities\Cron;

use FAWpmcp\Abilities\Cron\ScheduleCronEventAbility;
use Brain\Monkey;
use Brain\Monkey\Functions;
use Mockery;
use PHPUnit\Framework\TestCase;

/**
 * Test ScheduleCronEventAbility functionality.
 *
 * @package FAWpmcp\Tests\Abilities\Cron
 */
class ScheduleCronEventAbilityTest extends TestCase
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
        $ability = new ScheduleCronEventAbility();
        $this->assertEquals('fa-wpmcp/schedule-cron-event', $ability->getName());
    }

    /**
     * Test ability returns correct category.
     *
     * @return void
     */
    public function testGetCategory(): void
    {
        $ability = new ScheduleCronEventAbility();
        $this->assertEquals('cron', $ability->getCategory());
    }

    /**
     * Test ability returns correct label.
     *
     * @return void
     */
    public function testGetLabel(): void
    {
        $ability = new ScheduleCronEventAbility();
        $this->assertEquals('Schedule Cron Event', $ability->getLabel());
    }

    /**
     * Test ability returns correct operation type.
     *
     * @return void
     */
    public function testGetOperationType(): void
    {
        $ability = new ScheduleCronEventAbility();
        $this->assertEquals('write', $ability->getOperationType());
    }

    /**
     * Test ability returns correct required capability.
     *
     * @return void
     */
    public function testGetRequiredCapability(): void
    {
        $ability = new ScheduleCronEventAbility();
        $this->assertEquals('manage_options', $ability->getRequiredCapability());
    }

    /**
     * Test ability returns input schema with required fields.
     *
     * @return void
     */
    public function testGetInputSchema(): void
    {
        $ability = new ScheduleCronEventAbility();
        $schema  = $ability->getInputSchema();

        $this->assertIsArray($schema);
        $this->assertArrayHasKey('type', $schema);
        $this->assertArrayHasKey('properties', $schema);
        $this->assertArrayHasKey('required', $schema);
        $this->assertArrayHasKey('hook', $schema['properties']);
        $this->assertArrayHasKey('timestamp', $schema['properties']);
        $this->assertArrayHasKey('recurrence', $schema['properties']);
        $this->assertArrayHasKey('args', $schema['properties']);
        $this->assertContains('hook', $schema['required']);
        $this->assertContains('timestamp', $schema['required']);
    }

    /**
     * Test ability returns output schema.
     *
     * @return void
     */
    public function testGetOutputSchema(): void
    {
        $ability = new ScheduleCronEventAbility();
        $schema  = $ability->getOutputSchema();

        $this->assertIsArray($schema);
        $this->assertArrayHasKey('type', $schema);
        $this->assertArrayHasKey('properties', $schema);
        $this->assertArrayHasKey('success', $schema['properties']);
    }

    /**
     * Test execute schedules recurring cron event successfully.
     *
     * @return void
     */
    public function testExecuteSchedulesRecurringEventSuccessfully(): void
    {
        $ability = new ScheduleCronEventAbility();

        Functions\expect('wp_schedule_event')
            ->once()
            ->with(1706200000, 'hourly', 'my_custom_hook', array())
            ->andReturn(true);

        $result = $ability->doExecute(
            array(
                'hook'       => 'my_custom_hook',
                'timestamp'  => 1706200000,
                'recurrence' => 'hourly',
            )
        );

        $this->assertIsArray($result);
        $this->assertTrue($result['success']);
    }

    /**
     * Test execute schedules single event when no recurrence provided.
     *
     * @return void
     */
    public function testExecuteSchedulesSingleEventWhenNoRecurrence(): void
    {
        $ability = new ScheduleCronEventAbility();

        Functions\expect('wp_schedule_single_event')
            ->once()
            ->with(1706200000, 'my_single_event', array())
            ->andReturn(true);

        $result = $ability->doExecute(
            array(
                'hook'      => 'my_single_event',
                'timestamp' => 1706200000,
            )
        );

        $this->assertTrue($result['success']);
    }

    /**
     * Test execute returns failure when wp_schedule_event fails.
     *
     * @return void
     */
    public function testExecuteReturnsFailureWhenScheduleFails(): void
    {
        $ability = new ScheduleCronEventAbility();

        $wp_error = Mockery::mock('WP_Error');
        $wp_error->shouldReceive('get_error_message')->andReturn('Schedule failed');

        Functions\expect('wp_schedule_event')
            ->once()
            ->andReturn($wp_error);

        Functions\when('is_wp_error')->alias(
            function ($thing) use ($wp_error) {
                return $thing === $wp_error;
            }
        );

        $result = $ability->doExecute(
            array(
                'hook'       => 'my_custom_hook',
                'timestamp'  => 1706200000,
                'recurrence' => 'hourly',
            )
        );

        $this->assertFalse($result['success']);
        $this->assertArrayHasKey('error', $result);
    }

    /**
     * Test execute passes args to wp_schedule_event.
     *
     * @return void
     */
    public function testExecutePassesArgsToScheduleEvent(): void
    {
        $ability = new ScheduleCronEventAbility();

        $args = array( 'param1', 'param2' );

        Functions\expect('wp_schedule_event')
            ->once()
            ->with(1706200000, 'daily', 'my_custom_hook', $args)
            ->andReturn(true);

        $result = $ability->doExecute(
            array(
                'hook'       => 'my_custom_hook',
                'timestamp'  => 1706200000,
                'recurrence' => 'daily',
                'args'       => $args,
            )
        );

        $this->assertTrue($result['success']);
    }

    /**
     * Test annotations indicate write operation.
     *
     * @return void
     */
    public function testGetAnnotations(): void
    {
        $ability     = new ScheduleCronEventAbility();
        $annotations = $ability->getAnnotations();

        $this->assertFalse($annotations['readonly']);
        $this->assertFalse($annotations['destructive']);
        $this->assertTrue($annotations['idempotent']);
    }

    /**
     * Test execute returns failure when wp_schedule_single_event fails.
     *
     * @return void
     */
    public function testExecuteReturnsFailureWhenSingleScheduleFails(): void
    {
        $ability = new ScheduleCronEventAbility();

        $wp_error = Mockery::mock('WP_Error');
        $wp_error->shouldReceive('get_error_message')->andReturn('Single event schedule failed');

        Functions\expect('wp_schedule_single_event')
            ->once()
            ->andReturn($wp_error);

        Functions\when('is_wp_error')->alias(
            function ($thing) use ($wp_error) {
                return $thing === $wp_error;
            }
        );

        $result = $ability->doExecute(
            array(
                'hook'      => 'my_single_event',
                'timestamp' => 1706200000,
            )
        );

        $this->assertFalse($result['success']);
        $this->assertArrayHasKey('error', $result);
    }
}
