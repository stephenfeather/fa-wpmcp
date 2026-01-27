<?php

/**
 * Tests for CreateRoleAbility.
 *
 * @package FAWpmcp\Tests\Abilities\Role
 */

declare(strict_types=1);

namespace FAWpmcp\Tests\Abilities\Role;

use FAWpmcp\Abilities\AbstractAbility;
use FAWpmcp\Abilities\Role\CreateRoleAbility;
use FAWpmcp\Exceptions\RoleAlreadyExistsException;
use FAWpmcp\Tests\TestCase\AbilityTestTrait;
use FAWpmcp\Tests\TestCase\BrainMonkeyTestCase;
use Brain\Monkey\Functions;
use Mockery;

/**
 * Test CreateRoleAbility functionality.
 *
 * @package FAWpmcp\Tests\Abilities\Role
 */
class CreateRoleAbilityTest extends BrainMonkeyTestCase
{
    use AbilityTestTrait;

    /**
     * Get the ability instance to test.
     *
     * @return AbstractAbility
     */
    protected function getAbilityInstance(): AbstractAbility
    {
        return new CreateRoleAbility();
    }

    /**
     * Get the expected metadata for this ability.
     *
     * @return array<string, mixed>
     */
    protected function getExpectedMetadata(): array
    {
        return array(
            'name'                 => 'fa-wpmcp/create-role',
            'category'             => 'role',
            'label'                => 'Create Role',
            'description_contains' => 'create',
            'operation_type'       => 'write',
            'required_capability'  => 'promote_users',
        );
    }

    /**
     * Test ability returns input schema with required fields.
     *
     * @return void
     */
    public function testGetInputSchema(): void
    {
        $ability = $this->getAbilityInstance();
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
        $ability = $this->getAbilityInstance();
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
        $ability = $this->getAbilityInstance();

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
        $ability = $this->getAbilityInstance();

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
        $ability = $this->getAbilityInstance();

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
        $this->assertFalse($annotations['idempotent']);
    }

    /**
     * Test execute with multiple capabilities.
     *
     * @return void
     */
    public function testExecuteWithMultipleCapabilities(): void
    {
        $ability = $this->getAbilityInstance();

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
