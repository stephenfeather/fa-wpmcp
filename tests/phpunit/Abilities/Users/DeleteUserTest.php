<?php
declare(strict_types=1);

namespace FAWpmcp\Tests\Abilities\Users;

use FAWpmcp\Abilities\Users\DeleteUser;
use FAWpmcp\Exceptions\UserDeletionException;
use FAWpmcp\Exceptions\UserNotFoundException;
use Brain\Monkey\Functions;
use PHPUnit\Framework\TestCase;

final class DeleteUserTest extends TestCase {
	use \Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;

	protected function setUp(): void {
		parent::setUp();
		\Brain\Monkey\setUp();

		// Define ABSPATH for require_once in doExecute.
		if ( ! defined( 'ABSPATH' ) ) {
			define( 'ABSPATH', '/var/www/html/' );
		}
	}

	protected function tearDown(): void {
		\Brain\Monkey\tearDown();
		parent::tearDown();
	}

	public function test_ability_metadata(): void {
		$ability = new DeleteUser();
		$this->assertEquals( 'fa-wpmcp/delete-user', $ability->getName() );
		$this->assertEquals( 'users', $ability->getCategory() );
		$this->assertEquals( 'Delete User', $ability->getLabel() );
		$this->assertStringContainsString( 'delete', strtolower( $ability->getDescription() ) );
		$this->assertEquals( 'delete_users', $ability->getRequiredCapability() );
	}

	public function test_operation_type_is_write(): void {
		$ability = new DeleteUser();
		$this->assertEquals( 'write', $ability->getOperationType() );
	}

	public function test_annotations_mark_destructive(): void {
		$ability     = new DeleteUser();
		$annotations = $ability->getAnnotations();

		$this->assertTrue( $annotations['destructive'] );
		$this->assertFalse( $annotations['idempotent'] );
	}

	public function test_input_schema_requires_user_id(): void {
		$ability = new DeleteUser();
		$schema  = $ability->getInputSchema();

		$this->assertEquals( 'object', $schema['type'] );
		$this->assertArrayHasKey( 'user_id', $schema['properties'] );
		$this->assertArrayHasKey( 'reassign', $schema['properties'] );
		$this->assertContains( 'user_id', $schema['required'] );
		$this->assertNotContains( 'reassign', $schema['required'] );
	}

	public function test_output_schema_structure(): void {
		$ability = new DeleteUser();
		$schema  = $ability->getOutputSchema();

		$this->assertEquals( 'object', $schema['type'] );
		$this->assertArrayHasKey( 'user_id', $schema['properties'] );
		$this->assertArrayHasKey( 'reassigned', $schema['properties'] );
		$this->assertArrayHasKey( 'action', $schema['properties'] );
		$this->assertArrayHasKey( 'success', $schema['properties'] );
	}

	public function test_deletes_user_without_reassign(): void {
		$user     = new \stdClass();
		$user->ID = 42;

		Functions\expect( 'get_userdata' )->once()->with( 42 )->andReturn( $user );
		Functions\expect( 'get_current_user_id' )->once()->andReturn( 1 );
		Functions\expect( 'wp_delete_user' )->once()->with( 42, null )->andReturn( true );

		$ability = new DeleteUser();
		$result  = $ability->doExecute( array( 'user_id' => 42 ) );

		$this->assertEquals( 42, $result['user_id'] );
		$this->assertNull( $result['reassigned'] );
		$this->assertEquals( 'deleted', $result['action'] );
		$this->assertTrue( $result['success'] );
	}

	public function test_deletes_user_with_reassign(): void {
		$user     = new \stdClass();
		$user->ID = 42;

		$reassign_user     = new \stdClass();
		$reassign_user->ID = 10;

		Functions\expect( 'get_userdata' )->once()->with( 42 )->andReturn( $user );
		Functions\expect( 'get_current_user_id' )->once()->andReturn( 1 );
		Functions\expect( 'get_userdata' )->once()->with( 10 )->andReturn( $reassign_user );
		Functions\expect( 'wp_delete_user' )->once()->with( 42, 10 )->andReturn( true );

		$ability = new DeleteUser();
		$result  = $ability->doExecute( array(
			'user_id'  => 42,
			'reassign' => 10,
		) );

		$this->assertEquals( 42, $result['user_id'] );
		$this->assertEquals( 10, $result['reassigned'] );
		$this->assertEquals( 'deleted', $result['action'] );
		$this->assertTrue( $result['success'] );
	}

	public function test_throws_exception_when_user_not_found(): void {
		$this->expectException( UserNotFoundException::class );
		$this->expectExceptionMessage( 'User 999 not found' );

		Functions\expect( 'get_userdata' )->once()->with( 999 )->andReturn( false );

		$ability = new DeleteUser();
		$ability->doExecute( array( 'user_id' => 999 ) );
	}

	public function test_throws_exception_when_deleting_self(): void {
		$this->expectException( UserDeletionException::class );
		$this->expectExceptionMessage( 'Cannot delete the currently logged-in user' );

		$user     = new \stdClass();
		$user->ID = 42;

		Functions\expect( 'get_userdata' )->once()->with( 42 )->andReturn( $user );
		Functions\expect( 'get_current_user_id' )->once()->andReturn( 42 );

		$ability = new DeleteUser();
		$ability->doExecute( array( 'user_id' => 42 ) );
	}

	public function test_throws_exception_when_reassign_user_not_found(): void {
		$this->expectException( UserNotFoundException::class );
		$this->expectExceptionMessage( 'Reassign target user 999 not found' );

		$user     = new \stdClass();
		$user->ID = 42;

		Functions\expect( 'get_userdata' )->once()->with( 42 )->andReturn( $user );
		Functions\expect( 'get_current_user_id' )->once()->andReturn( 1 );
		Functions\expect( 'get_userdata' )->once()->with( 999 )->andReturn( false );

		$ability = new DeleteUser();
		$ability->doExecute( array(
			'user_id'  => 42,
			'reassign' => 999,
		) );
	}

	public function test_throws_exception_when_delete_fails(): void {
		$this->expectException( UserDeletionException::class );
		$this->expectExceptionMessage( 'Failed to delete user 42' );

		$user     = new \stdClass();
		$user->ID = 42;

		Functions\expect( 'get_userdata' )->once()->with( 42 )->andReturn( $user );
		Functions\expect( 'get_current_user_id' )->once()->andReturn( 1 );
		Functions\expect( 'wp_delete_user' )->once()->with( 42, null )->andReturn( false );

		$ability = new DeleteUser();
		$ability->doExecute( array( 'user_id' => 42 ) );
	}
}
