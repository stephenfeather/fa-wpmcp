<?php
/**
 * Tests for DeleteRoleAbility.
 *
 * @package FAWpmcp\Tests\Abilities\Role
 */

declare(strict_types=1);

namespace FAWpmcp\Tests\Abilities\Role;

use FAWpmcp\Abilities\AbstractAbility;
use FAWpmcp\Abilities\Role\DeleteRoleAbility;
use FAWpmcp\Exceptions\RoleNotFoundException;
use FAWpmcp\Exceptions\RoleDeletionException;
use FAWpmcp\Tests\TestCase\AbilityTestTrait;
use FAWpmcp\Tests\TestCase\BrainMonkeyTestCase;
use Brain\Monkey\Functions;
use Mockery;

/**
 * Test DeleteRoleAbility functionality.
 *
 * @package FAWpmcp\Tests\Abilities\Role
 */
class DeleteRoleAbilityTest extends BrainMonkeyTestCase {

	use AbilityTestTrait;

	/**
	 * Get the ability instance to test.
	 *
	 * @return AbstractAbility
	 */
	protected function getAbilityInstance(): AbstractAbility {
		return new DeleteRoleAbility();
	}

	/**
	 * Get the expected metadata for this ability.
	 *
	 * @return array<string, mixed>
	 */
	protected function getExpectedMetadata(): array {
		return array(
			'name'                 => 'fa-wpmcp/delete-role',
			'category'             => 'role',
			'label'                => 'Delete Role',
			'description_contains' => 'delete',
			'operation_type'       => 'write',
			'required_capability'  => 'delete_users',
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
		$this->assertArrayHasKey( 'deleted', $schema['properties'] );
		$this->assertArrayHasKey( 'role', $schema['properties'] );
	}

	/**
	 * Test execute deletes custom role successfully.
	 *
	 * @return void
	 */
	public function testExecuteDeletesCustomRoleSuccessfully(): void {
		$ability = $this->getAbilityInstance();

		$mock_role = Mockery::mock( 'WP_Role' );
		$mock_role->name = 'custom_role';

		Functions\when( 'get_role' )->justReturn( $mock_role );

		$mock_roles = Mockery::mock( 'WP_Roles' );
		$mock_roles->shouldReceive( 'remove_role' )
			->once()
			->with( 'custom_role' );

		Functions\when( 'wp_roles' )->justReturn( $mock_roles );

		$result = $ability->doExecute( array( 'role' => 'custom_role' ) );

		$this->assertIsArray( $result );
		$this->assertTrue( $result['deleted'] );
		$this->assertEquals( 'custom_role', $result['role'] );
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
	 * Test execute throws exception when trying to delete administrator role.
	 *
	 * @return void
	 */
	public function testExecuteThrowsExceptionWhenDeletingAdministratorRole(): void {
		$ability = $this->getAbilityInstance();

		$mock_role = Mockery::mock( 'WP_Role' );
		$mock_role->name = 'administrator';

		Functions\when( 'get_role' )->justReturn( $mock_role );

		$this->expectException( RoleDeletionException::class );
		$this->expectExceptionMessage( 'Cannot delete default WordPress role "administrator".' );

		$ability->doExecute( array( 'role' => 'administrator' ) );
	}

	/**
	 * Test execute throws exception when trying to delete editor role.
	 *
	 * @return void
	 */
	public function testExecuteThrowsExceptionWhenDeletingEditorRole(): void {
		$ability = $this->getAbilityInstance();

		$mock_role = Mockery::mock( 'WP_Role' );
		$mock_role->name = 'editor';

		Functions\when( 'get_role' )->justReturn( $mock_role );

		$this->expectException( RoleDeletionException::class );
		$this->expectExceptionMessage( 'Cannot delete default WordPress role "editor".' );

		$ability->doExecute( array( 'role' => 'editor' ) );
	}

	/**
	 * Test execute throws exception when trying to delete author role.
	 *
	 * @return void
	 */
	public function testExecuteThrowsExceptionWhenDeletingAuthorRole(): void {
		$ability = $this->getAbilityInstance();

		$mock_role = Mockery::mock( 'WP_Role' );
		$mock_role->name = 'author';

		Functions\when( 'get_role' )->justReturn( $mock_role );

		$this->expectException( RoleDeletionException::class );
		$this->expectExceptionMessage( 'Cannot delete default WordPress role "author".' );

		$ability->doExecute( array( 'role' => 'author' ) );
	}

	/**
	 * Test execute throws exception when trying to delete contributor role.
	 *
	 * @return void
	 */
	public function testExecuteThrowsExceptionWhenDeletingContributorRole(): void {
		$ability = $this->getAbilityInstance();

		$mock_role = Mockery::mock( 'WP_Role' );
		$mock_role->name = 'contributor';

		Functions\when( 'get_role' )->justReturn( $mock_role );

		$this->expectException( RoleDeletionException::class );
		$this->expectExceptionMessage( 'Cannot delete default WordPress role "contributor".' );

		$ability->doExecute( array( 'role' => 'contributor' ) );
	}

	/**
	 * Test execute throws exception when trying to delete subscriber role.
	 *
	 * @return void
	 */
	public function testExecuteThrowsExceptionWhenDeletingSubscriberRole(): void {
		$ability = $this->getAbilityInstance();

		$mock_role = Mockery::mock( 'WP_Role' );
		$mock_role->name = 'subscriber';

		Functions\when( 'get_role' )->justReturn( $mock_role );

		$this->expectException( RoleDeletionException::class );
		$this->expectExceptionMessage( 'Cannot delete default WordPress role "subscriber".' );

		$ability->doExecute( array( 'role' => 'subscriber' ) );
	}

	/**
	 * Test annotations indicate destructive write operation.
	 *
	 * @return void
	 */
	public function testGetAnnotations(): void {
		$ability     = new DeleteRoleAbility();
		$annotations = $ability->getAnnotations();

		$this->assertFalse( $annotations['readonly'] );
		$this->assertTrue( $annotations['destructive'] );
		$this->assertFalse( $annotations['idempotent'] );
	}
}
