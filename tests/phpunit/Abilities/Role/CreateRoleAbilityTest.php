<?php

/**
 * Tests for CreateRoleAbility.
 *
 * @package FAWpmcp\Tests\Abilities\Role
 */

declare(strict_types=1);

namespace FAWpmcp\Tests\Abilities\Role;

use FAWpmcp\Abilities\Role\CreateRoleAbility;
use FAWpmcp\Exceptions\RoleAlreadyExistsException;
use Brain\Monkey;
use Brain\Monkey\Functions;
use Mockery;
use PHPUnit\Framework\TestCase;

/**
 * Test CreateRoleAbility functionality.
 *
 * @package FAWpmcp\Tests\Abilities\Role
 */
class CreateRoleAbilityTest extends TestCase
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
        $ability = new CreateRoleAbility();
        $this->assertEquals('fa-wpmcp/create-role', $ability->getName());
    }

    /**
     * Test ability returns correct category.
     *
     * @return void
     */
    public function testGetCategory(): void
    {
        $ability = new CreateRoleAbility();
        $this->assertEquals('role', $ability->getCategory());
    }

    /**
     * Test ability returns correct label.
     *
     * @return void
     */
    public function testGetLabel(): void
    {
        $ability = new CreateRoleAbility();
        $this->assertEquals('Create Role', $ability->getLabel());
    }

    /**
     * Test ability returns correct operation type.
     *
     * @return void
     */
    public function testGetOperationType(): void
    {
        $ability = new CreateRoleAbility();
        $this->assertEquals('write', $ability->getOperationType());
    }

    /**
     * Test ability returns correct required capability.
     *
     * @return void
     */
    public function testGetRequiredCapability(): void
    {
        $ability = new CreateRoleAbility();
        $this->assertEquals('promote_users', $ability->getRequiredCapability());
    }

    /**
     * Test ability returns input schema with required fields.
     *
     * @return void
     */
    public function testGetInputSchema(): void
    {
        $ability = new CreateRoleAbility();
        $schema  = $ability->getInputSchema();

        $this->assertIsArray($schema);
        $this->assertArrayHasKey('type', $schema);
        $this->assertArrayHasKey('properties', $schema);
        $this->assertArrayHasKey('required', $schema);
        $this->assertArrayHasKey('role', $schema['properties']);
        $this->assertArrayHasKey('display_name', $schema['properties']);
        $this->assertArrayHasKey('capabilities', $schema['properties']);
        $this->assertContains('role', $schema['required']);
        $this->assertContains('display_name', $schema['required']);
    }

    /**
     * Test ability returns output schema.
     *
     * @return void
     */
    public function testGetOutputSchema(): void
    {
        $ability = new CreateRoleAbility();
        $schema  = $ability->getOutputSchema();

        $this->assertIsArray($schema);
        $this->assertArrayHasKey('type', $schema);
        $this->assertArrayHasKey('properties', $schema);
        $this->assertArrayHasKey('name', $schema['properties']);
        $this->assertArrayHasKey('display_name', $schema['properties']);
        $this->assertArrayHasKey('capabilities', $schema['properties']);
    }

    /**
     * Test execute creates role successfully.
     *
     * @return void
     */
    public function testExecuteCreatesRoleSuccessfully(): void
    {
        $ability = new CreateRoleAbility();

        // Role does not exist yet.
        Functions\when('get_role')->justReturn(null);

        $new_role = Mockery::mock('WP_Role');
        $new_role->name = 'custom_role';
        $new_role->capabilities = array( 'read' => true );

        $mock_roles = Mockery::mock('WP_Roles');
        $mock_roles->shouldReceive('add_role')
            ->once()
            ->with('custom_role', 'Custom Role', array( 'read' => true ))
            ->andReturn($new_role);

        Functions\when('wp_roles')->justReturn($mock_roles);

        $result = $ability->doExecute(
            array(
                'role'         => 'custom_role',
                'display_name' => 'Custom Role',
                'capabilities' => array( 'read' => true ),
            )
        );

        $this->assertIsArray($result);
        $this->assertEquals('custom_role', $result['name']);
        $this->assertEquals('Custom Role', $result['display_name']);
        $this->assertArrayHasKey('capabilities', $result);
    }

    /**
     * Test execute creates role without capabilities.
     *
     * @return void
     */
    public function testExecuteCreatesRoleWithoutCapabilities(): void
    {
        $ability = new CreateRoleAbility();

        Functions\when('get_role')->justReturn(null);

        $new_role = Mockery::mock('WP_Role');
        $new_role->name = 'empty_role';
        $new_role->capabilities = array();

        $mock_roles = Mockery::mock('WP_Roles');
        $mock_roles->shouldReceive('add_role')
            ->once()
            ->with('empty_role', 'Empty Role', array())
            ->andReturn($new_role);

        Functions\when('wp_roles')->justReturn($mock_roles);

        $result = $ability->doExecute(
            array(
                'role'         => 'empty_role',
                'display_name' => 'Empty Role',
            )
        );

        $this->assertEquals('empty_role', $result['name']);
        $this->assertIsArray($result['capabilities']);
    }

    /**
     * Test execute throws exception when role already exists.
     *
     * @return void
     */
    public function testExecuteThrowsExceptionWhenRoleAlreadyExists(): void
    {
        $ability = new CreateRoleAbility();

        $existing_role = Mockery::mock('WP_Role');
        $existing_role->name = 'editor';

        Functions\when('get_role')->justReturn($existing_role);

        $this->expectException(RoleAlreadyExistsException::class);
        $this->expectExceptionMessage('Role "editor" already exists.');

        $ability->doExecute(
            array(
                'role'         => 'editor',
                'display_name' => 'Editor',
            )
        );
    }

    /**
     * Test annotations indicate write operation.
     *
     * @return void
     */
    public function testGetAnnotations(): void
    {
        $ability     = new CreateRoleAbility();
        $annotations = $ability->getAnnotations();

        $this->assertFalse($annotations['readonly']);
        $this->assertFalse($annotations['destructive']);
        $this->assertTrue($annotations['idempotent']);
    }

    /**
     * Test execute with multiple capabilities.
     *
     * @return void
     */
    public function testExecuteWithMultipleCapabilities(): void
    {
        $ability = new CreateRoleAbility();

        Functions\when('get_role')->justReturn(null);

        $capabilities = array(
            'read'         => true,
            'edit_posts'   => true,
            'delete_posts' => true,
        );

        $new_role = Mockery::mock('WP_Role');
        $new_role->name = 'contributor_plus';
        $new_role->capabilities = $capabilities;

        $mock_roles = Mockery::mock('WP_Roles');
        $mock_roles->shouldReceive('add_role')
            ->once()
            ->with('contributor_plus', 'Contributor Plus', $capabilities)
            ->andReturn($new_role);

        Functions\when('wp_roles')->justReturn($mock_roles);

        $result = $ability->doExecute(
            array(
                'role'         => 'contributor_plus',
                'display_name' => 'Contributor Plus',
                'capabilities' => $capabilities,
            )
        );

        $this->assertEquals($capabilities, $result['capabilities']);
    }
}
