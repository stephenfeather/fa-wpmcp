<?php

/**
 * Tests for ListRolesAbility.
 *
 * @package FAWpmcp\Tests\Abilities\Role
 */

declare(strict_types=1);

namespace FAWpmcp\Tests\Abilities\Role;

use FAWpmcp\Abilities\AbstractAbility;
use FAWpmcp\Abilities\Role\ListRolesAbility;
use FAWpmcp\Tests\TestCase\AbilityTestTrait;
use FAWpmcp\Tests\TestCase\BrainMonkeyTestCase;
use Brain\Monkey\Functions;
use Mockery;

/**
 * Test ListRolesAbility functionality.
 *
 * @package FAWpmcp\Tests\Abilities\Role
 */
class ListRolesAbilityTest extends BrainMonkeyTestCase {

	use AbilityTestTrait;

	/**
	 * Get the ability instance to test.
	 *
	 * @return AbstractAbility
	 */
	protected function getAbilityInstance(): AbstractAbility {
		return new ListRolesAbility();
	}

	/**
	 * Get the expected metadata for this ability.
	 *
	 * @return array<string, mixed>
	 */
	protected function getExpectedMetadata(): array {
		return array(
			'name'                 => 'fa-wpmcp/list-roles',
			'category'             => 'role',
			'label'                => 'List Roles',
			'description_contains' => 'list',
			'operation_type'       => 'read',
			'required_capability'  => 'list_users',
		);
	}

	/**
	 * Test ability returns input schema.
	 *
	 * @return void
	 */
	public function testGetInputSchema(): void {
		$ability = $this->getAbilityInstance();
		$schema  = $ability->getInputSchema();

		$this->assertIsArray( $schema );
		$this->assertArrayHasKey( 'type', $schema );
		$this->assertEquals( 'object', $schema['type'] );
	}

	/**
	 * Test ability returns output schema.
	 *
	 * @return void
	 */
	public function testGetOutputSchema(): void {
		$ability = $this->getAbilityInstance();
		$schema  = $ability->getOutputSchema();

		$this->assertIsArray( $schema );
		$this->assertArrayHasKey( 'type', $schema );
		$this->assertArrayHasKey( 'properties', $schema );
		$this->assertArrayHasKey( 'roles', $schema['properties'] );
		$this->assertArrayHasKey( 'total', $schema['properties'] );
	}

	/**
	 * Test execute returns list of roles.
	 *
	 * @return void
	 */
	public function testExecuteReturnsListOfRoles(): void {
		$ability = $this->getAbilityInstance();

		// Create mock WP_Role objects.
		$admin_role = Mockery::mock( 'WP_Role' );
		$admin_role->name = 'administrator';
		$admin_role->capabilities = array(
			'manage_options' => true,
			'edit_posts'     => true,
		);

		$editor_role = Mockery::mock( 'WP_Role' );
		$editor_role->name = 'editor';
		$editor_role->capabilities = array(
			'edit_posts'         => true,
			'edit_others_posts'  => true,
		);

		// Create mock WP_Roles.
		$mock_roles = Mockery::mock( 'WP_Roles' );
		$mock_roles->shouldReceive( 'get_names' )->andReturn(
			array(
				'administrator' => 'Administrator',
				'editor'        => 'Editor',
			)
		);
		$mock_roles->role_objects = array(
			'administrator' => $admin_role,
			'editor'        => $editor_role,
		);

		Functions\when( 'wp_roles' )->justReturn( $mock_roles );

		$result = $ability->doExecute( array() );

		$this->assertIsArray( $result );
		$this->assertArrayHasKey( 'roles', $result );
		$this->assertArrayHasKey( 'total', $result );
		$this->assertCount( 2, $result['roles'] );
		$this->assertEquals( 2, $result['total'] );
	}

	/**
	 * Test execute returns role structure with expected fields.
	 *
	 * @return void
	 */
	public function testExecuteReturnsRoleStructureWithExpectedFields(): void {
		$ability = $this->getAbilityInstance();

		$admin_role = Mockery::mock( 'WP_Role' );
		$admin_role->name = 'administrator';
		$admin_role->capabilities = array(
			'manage_options' => true,
			'edit_posts'     => true,
		);

		$mock_roles = Mockery::mock( 'WP_Roles' );
		$mock_roles->shouldReceive( 'get_names' )->andReturn(
			array(
				'administrator' => 'Administrator',
			)
		);
		$mock_roles->role_objects = array(
			'administrator' => $admin_role,
		);

		Functions\when( 'wp_roles' )->justReturn( $mock_roles );

		$result = $ability->doExecute( array() );

		$role = $result['roles'][0];
		$this->assertArrayHasKey( 'name', $role );
		$this->assertArrayHasKey( 'display_name', $role );
		$this->assertArrayHasKey( 'capabilities', $role );
		$this->assertEquals( 'administrator', $role['name'] );
		$this->assertEquals( 'Administrator', $role['display_name'] );
		$this->assertIsArray( $role['capabilities'] );
	}

	/**
	 * Test execute returns empty array when no roles exist.
	 *
	 * @return void
	 */
	public function testExecuteReturnsEmptyArrayWhenNoRoles(): void {
		$ability = $this->getAbilityInstance();

		$mock_roles = Mockery::mock( 'WP_Roles' );
		$mock_roles->shouldReceive( 'get_names' )->andReturn( array() );
		$mock_roles->role_objects = array();

		Functions\when( 'wp_roles' )->justReturn( $mock_roles );

		$result = $ability->doExecute( array() );

		$this->assertIsArray( $result['roles'] );
		$this->assertEmpty( $result['roles'] );
		$this->assertEquals( 0, $result['total'] );
	}

	/**
	 * Test annotations are correct for read-only ability.
	 *
	 * @return void
	 */
	public function testGetAnnotations(): void {
		$ability     = new ListRolesAbility();
		$annotations = $ability->getAnnotations();

		$this->assertTrue( $annotations['readonly'] );
		$this->assertFalse( $annotations['destructive'] );
		$this->assertTrue( $annotations['idempotent'] );
	}
}
