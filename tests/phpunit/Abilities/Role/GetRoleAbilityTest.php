<?php
/**
 * Tests for GetRoleAbility.
 *
 * @package FAWpmcp\Tests\Abilities\Role
 */

declare(strict_types=1);

namespace FAWpmcp\Tests\Abilities\Role;

use FAWpmcp\Abilities\AbstractAbility;
use FAWpmcp\Abilities\Role\GetRoleAbility;
use FAWpmcp\Exceptions\RoleNotFoundException;
use FAWpmcp\Tests\TestCase\AbilityTestTrait;
use FAWpmcp\Tests\TestCase\BrainMonkeyTestCase;
use Brain\Monkey\Functions;
use Mockery;

/**
 * Test GetRoleAbility functionality.
 *
 * @package FAWpmcp\Tests\Abilities\Role
 */
class GetRoleAbilityTest extends BrainMonkeyTestCase {

	use AbilityTestTrait;

	/**
	 * Get the ability instance to test.
	 *
	 * @return AbstractAbility
	 */
	protected function getAbilityInstance(): AbstractAbility {
		return new GetRoleAbility();
	}

	/**
	 * Get the expected metadata for this ability.
	 *
	 * @return array<string, mixed>
	 */
	protected function getExpectedMetadata(): array {
		return array(
			'name'                 => 'fa-wpmcp/get-role',
			'category'             => 'role',
			'label'                => 'Get Role',
			'description_contains' => 'details',
			'operation_type'       => 'read',
			'required_capability'  => 'list_users',
		);
	}

	/**
	 * Test ability returns input schema with required role field.
	 *
	 * @return void
	 */
	public function testGetInputSchema(): void {
		$ability = $this->getAbilityInstance();
		$schema  = $ability->getInputSchema();

		$this->assertIsArray( $schema );
		$this->assertArrayHasKey( 'type', $schema );
		$this->assertArrayHasKey( 'properties', $schema );
		$this->assertArrayHasKey( 'required', $schema );
		$this->assertArrayHasKey( 'role', $schema['properties'] );
		$this->assertContains( 'role', $schema['required'] );
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
		$this->assertArrayHasKey( 'name', $schema['properties'] );
		$this->assertArrayHasKey( 'display_name', $schema['properties'] );
		$this->assertArrayHasKey( 'capabilities', $schema['properties'] );
		$this->assertArrayHasKey( 'capabilities_count', $schema['properties'] );
	}

	/**
	 * Test execute returns role details.
	 *
	 * @return void
	 */
	public function testExecuteReturnsRoleDetails(): void {
		$ability = $this->getAbilityInstance();

		$mock_role = Mockery::mock( 'WP_Role' );
		$mock_role->name = 'editor';
		$mock_role->capabilities = array(
			'edit_posts'        => true,
			'edit_others_posts' => true,
			'publish_posts'     => true,
		);

		$mock_roles = Mockery::mock( 'WP_Roles' );
		$mock_roles->shouldReceive( 'get_names' )->andReturn(
			array(
				'editor' => 'Editor',
			)
		);

		Functions\when( 'get_role' )->justReturn( $mock_role );
		Functions\when( 'wp_roles' )->justReturn( $mock_roles );

		$result = $ability->doExecute( array( 'role' => 'editor' ) );

		$this->assertIsArray( $result );
		$this->assertArrayHasKey( 'name', $result );
		$this->assertArrayHasKey( 'display_name', $result );
		$this->assertArrayHasKey( 'capabilities', $result );
		$this->assertArrayHasKey( 'capabilities_count', $result );
		$this->assertEquals( 'editor', $result['name'] );
		$this->assertEquals( 'Editor', $result['display_name'] );
		$this->assertEquals( 3, $result['capabilities_count'] );
	}

	/**
	 * Test execute throws exception when role not found.
	 *
	 * @return void
	 */
	public function testExecuteThrowsExceptionWhenRoleNotFound(): void {
		$ability = $this->getAbilityInstance();

		Functions\when( 'get_role' )->justReturn( null );

		$this->expectException( RoleNotFoundException::class );
		$this->expectExceptionMessage( 'Role "nonexistent" not found.' );

		$ability->doExecute( array( 'role' => 'nonexistent' ) );
	}

	/**
	 * Test annotations are correct for read-only ability.
	 *
	 * @return void
	 */
	public function testGetAnnotations(): void {
		$ability     = new GetRoleAbility();
		$annotations = $ability->getAnnotations();

		$this->assertTrue( $annotations['readonly'] );
		$this->assertFalse( $annotations['destructive'] );
		$this->assertTrue( $annotations['idempotent'] );
	}

	/**
	 * Test execute returns capabilities as array.
	 *
	 * @return void
	 */
	public function testExecuteReturnsCapabilitiesAsArray(): void {
		$ability = $this->getAbilityInstance();

		$mock_role = Mockery::mock( 'WP_Role' );
		$mock_role->name = 'author';
		$mock_role->capabilities = array(
			'edit_posts'    => true,
			'publish_posts' => true,
			'upload_files'  => true,
		);

		$mock_roles = Mockery::mock( 'WP_Roles' );
		$mock_roles->shouldReceive( 'get_names' )->andReturn(
			array(
				'author' => 'Author',
			)
		);

		Functions\when( 'get_role' )->justReturn( $mock_role );
		Functions\when( 'wp_roles' )->justReturn( $mock_roles );

		$result = $ability->doExecute( array( 'role' => 'author' ) );

		$this->assertIsArray( $result['capabilities'] );
		$this->assertArrayHasKey( 'edit_posts', $result['capabilities'] );
		$this->assertTrue( $result['capabilities']['edit_posts'] );
	}
}
