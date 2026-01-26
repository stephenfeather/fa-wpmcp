<?php

/**
 * Tests for GetCronEventAbility.
 *
 * @package FAWpmcp\Tests\Abilities\Cron
 */

declare(strict_types=1);

namespace FAWpmcp\Tests\Abilities\Cron;

use FAWpmcp\Abilities\Cron\GetCronEventAbility;
use Brain\Monkey;
use Brain\Monkey\Functions;
use Mockery;
use PHPUnit\Framework\TestCase;

/**
 * Test GetCronEventAbility functionality.
 *
 * @package FAWpmcp\Tests\Abilities\Cron
 */
class GetCronEventAbilityTest extends TestCase
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
        $ability = new GetCronEventAbility();
        $this->assertEquals('fa-wpmcp/get-cron-event', $ability->getName());
    }

    /**
     * Test ability returns correct category.
     *
     * @return void
     */
    public function testGetCategory(): void
    {
        $ability = new GetCronEventAbility();
        $this->assertEquals('cron', $ability->getCategory());
    }

    /**
     * Test ability returns correct label.
     *
     * @return void
     */
    public function testGetLabel(): void
    {
        $ability = new GetCronEventAbility();
        $this->assertEquals('Get Cron Event', $ability->getLabel());
    }

    /**
     * Test ability returns correct operation type.
     *
     * @return void
     */
    public function testGetOperationType(): void
    {
        $ability = new GetCronEventAbility();
        $this->assertEquals('read', $ability->getOperationType());
    }

    /**
     * Test ability returns correct required capability.
     *
     * @return void
     */
    public function testGetRequiredCapability(): void
    {
        $ability = new GetCronEventAbility();
        $this->assertEquals('manage_options', $ability->getRequiredCapability());
    }

    /**
     * Test ability returns input schema with required hook field.
     *
     * @return void
     */
    public function testGetInputSchema(): void
    {
        $ability = new GetCronEventAbility();
        $schema  = $ability->getInputSchema();

        $this->assertIsArray($schema);
        $this->assertArrayHasKey('type', $schema);
        $this->assertArrayHasKey('properties', $schema);
        $this->assertArrayHasKey('required', $schema);
        $this->assertArrayHasKey('hook', $schema['properties']);
        $this->assertContains('hook', $schema['required']);
    }

    /**
     * Test ability returns output schema.
     *
     * @return void
     */
    public function testGetOutputSchema(): void
    {
        $ability = new GetCronEventAbility();
        $schema  = $ability->getOutputSchema();

        $this->assertIsArray($schema);
        $this->assertArrayHasKey('type', $schema);
        $this->assertArrayHasKey('properties', $schema);
        $this->assertArrayHasKey('found', $schema['properties']);
        $this->assertArrayHasKey('events', $schema['properties']);
    }

    /**
     * Test execute returns cron event when it exists.
     *
     * @return void
     */
    public function testExecuteReturnsCronEventWhenExists(): void
    {
        $ability = new GetCronEventAbility();

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

        $result = $ability->doExecute(array( 'hook' => 'my_custom_hook' ));

        $this->assertIsArray($result);
        $this->assertTrue($result['found']);
        $this->assertCount(1, $result['events']);
        $this->assertEquals('my_custom_hook', $result['events'][0]['hook']);
    }

    /**
     * Test execute returns found false when event does not exist.
     *
     * @return void
     */
    public function testExecuteReturnsFoundFalseWhenEventNotFound(): void
    {
        $ability = new GetCronEventAbility();

        $cron_array = array(
            1706200000 => array(
                'wp_scheduled_delete' => array(
                    '40cd750bba9870f18aada2478b24840a' => array(
                        'schedule' => 'daily',
                        'args'     => array(),
                    ),
                ),
            ),
        );

        Functions\when('_get_cron_array')->justReturn($cron_array);

        $result = $ability->doExecute(array( 'hook' => 'nonexistent_hook' ));

        $this->assertIsArray($result);
        $this->assertFalse($result['found']);
        $this->assertEmpty($result['events']);
    }

    /**
     * Test execute returns multiple instances of the same hook.
     *
     * @return void
     */
    public function testExecuteReturnsMultipleInstancesOfSameHook(): void
    {
        $ability = new GetCronEventAbility();

        $cron_array = array(
            1706200000 => array(
                'my_custom_hook' => array(
                    '40cd750bba9870f18aada2478b24840a' => array(
                        'schedule' => 'hourly',
                        'args'     => array( 'first' ),
                    ),
                ),
            ),
            1706203600 => array(
                'my_custom_hook' => array(
                    '9a0364b9e99bb480dd25e1f0284c8555' => array(
                        'schedule' => 'hourly',
                        'args'     => array( 'second' ),
                    ),
                ),
            ),
        );

        Functions\when('_get_cron_array')->justReturn($cron_array);

        $result = $ability->doExecute(array( 'hook' => 'my_custom_hook' ));

        $this->assertTrue($result['found']);
        $this->assertCount(2, $result['events']);
    }

    /**
     * Test execute returns event details with correct structure.
     *
     * @return void
     */
    public function testExecuteReturnsEventDetailsWithCorrectStructure(): void
    {
        $ability = new GetCronEventAbility();

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

        $result = $ability->doExecute(array( 'hook' => 'my_custom_hook' ));

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
        $ability     = new GetCronEventAbility();
        $annotations = $ability->getAnnotations();

        $this->assertTrue($annotations['readonly']);
        $this->assertFalse($annotations['destructive']);
        $this->assertTrue($annotations['idempotent']);
    }

    /**
     * Test execute handles empty cron array.
     *
     * @return void
     */
    public function testExecuteHandlesEmptyCronArray(): void
    {
        $ability = new GetCronEventAbility();

        Functions\when('_get_cron_array')->justReturn(array());

        $result = $ability->doExecute(array( 'hook' => 'any_hook' ));

        $this->assertFalse($result['found']);
        $this->assertEmpty($result['events']);
    }
}
