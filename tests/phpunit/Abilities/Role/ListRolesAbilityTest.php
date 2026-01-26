<?php
/**
 * Tests for ListRolesAbility.
 *
 * @package FAWpmcp\Tests\Abilities\Role
 */

declare(strict_types=1);

namespace FAWpmcp\Tests\Abilities\Role;

use FAWpmcp\Abilities\Role\ListRolesAbility;
use Brain\Monkey;
use Brain\Monkey\Functions;
use Mockery;
use PHPUnit\Framework\TestCase;

/**
 * Test ListRolesAbility functionality.
 *
 * @package FAWpmcp\Tests\Abilities\Role
 */
class ListRolesAbilityTest extends TestCase {
	/**
	 * Set up Brain\Monkey before each test.
	 *
	 * @return void
	 */
	protected function setUp(): void {
		parent::setUp();
		Monkey\setUp();
	}

	/**
	 * Tear down Brain\Monkey after each test.
	 *
	 * @return void
	 */
	protected function tearDown(): void {
		Monkey\tearDown();
		Mockery::close();
		parent::tearDown();
	}

	/**
	 * Test ability returns correct name.
	 *
	 * @return void
	 */
	public function testGetName(): void {
		$ability = new ListRolesAbility();
		$this->assertEquals( 'fa-wpmcp/list-roles', $ability->getName() );
	}

	/**
	 * Test ability returns correct category.
	 *
	 * @return void
	 */
	public function testGetCategory(): void {
		$ability = new ListRolesAbility();
		$this->assertEquals( 'role', $ability->getCategory() );
	}

	/**
	 * Test ability returns correct label.
	 *
	 * @return void
	 */
	public function testGetLabel(): void {
		$ability = new ListRolesAbility();
		$this->assertEquals( 'List Roles', $ability->getLabel() );
	}

	/**
	 * Test ability returns correct operation type.
	 *
	 * @return void
	 */
	public function testGetOperationType(): void {
		$ability = new ListRolesAbility();
		$this->assertEquals( 'read', $ability->getOperationType() );
	}

	/**
	 * Test ability returns correct required capability.
	 *
	 * @return void
	 */
	public function testGetRequiredCapability(): void {
		$ability = new ListRolesAbility();
		$this->assertEquals( 'list_users', $ability->getRequiredCapability() );
	}

	/**
	 * Test ability returns input schema.
	 *
	 * @return void
	 */
	public function testGetInputSchema(): void {
		$ability = new ListRolesAbility();
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
		$ability = new ListRolesAbility();
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
		$ability = new ListRolesAbility();

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
		$ability = new ListRolesAbility();

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
		$ability = new ListRolesAbility();

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
