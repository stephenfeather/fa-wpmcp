<?php

/**
 * Tests for UpdateUser ability.
 *
 * @package FAWpmcp\Tests\Abilities\Users
 */

declare(strict_types=1);

namespace FAWpmcp\Tests\Abilities\Users;

use FAWpmcp\Abilities\Users\UpdateUser;
use FAWpmcp\Exceptions\UserNotFoundException;
use FAWpmcp\Exceptions\UserUpdateException;
use Brain\Monkey;
use Brain\Monkey\Functions;
use Mockery;
use PHPUnit\Framework\TestCase;

/**
 * Test UpdateUser ability functionality.
 *
 * Tests cover:
 * - Updating user fields
 * - Partial updates
 * - User not found exception
 * - Error handling
 *
 * @package FAWpmcp\Tests\Abilities\Users
 */
class UpdateUserTest extends TestCase {

	/**
	 * Set up Brain\Monkey before each test.
	 *
	 * @return void
	 */
	protected function setUp(): void {
		parent::setUp();
		Monkey\setUp();

		// Default mock for RolePolicy's get_option call.
		// Tests can override this as needed.
		Functions\when( 'get_option' )
			->alias(
				function ( $option, $default = false ) {
					if ( 'fa_wpmcp_max_api_role' === $option ) {
						return 'editor'; // Default max role for tests.
					}
					return $default;
				}
			);
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
		$ability = new UpdateUser();
		$this->assertEquals( 'fa-wpmcp/update-user', $ability->getName() );
	}

	/**
	 * Test ability returns correct category.
	 *
	 * @return void
	 */
	public function testGetCategory(): void {
		$ability = new UpdateUser();
		$this->assertEquals( 'users', $ability->getCategory() );
	}

	/**
	 * Test ability returns correct label.
	 *
	 * @return void
	 */
	public function testGetLabel(): void {
		$ability = new UpdateUser();
		$this->assertEquals( 'Update User', $ability->getLabel() );
	}

	/**
	 * Test ability returns correct operation type.
	 *
	 * @return void
	 */
	public function testGetOperationType(): void {
		$ability = new UpdateUser();
		$this->assertEquals( 'write', $ability->getOperationType() );
	}

	/**
	 * Test ability returns correct required capability.
	 *
	 * @return void
	 */
	public function testGetRequiredCapability(): void {
		$ability = new UpdateUser();
		$this->assertEquals( 'edit_users', $ability->getRequiredCapability() );
	}

	/**
	 * Test input schema requires user_id.
	 *
	 * @return void
	 */
	public function testInputSchemaRequiresUserId(): void {
		$ability = new UpdateUser();
		$schema  = $ability->getInputSchema();

		$this->assertArrayHasKey( 'required', $schema );
		$this->assertContains( 'user_id', $schema['required'] );
	}

	/**
	 * Test input schema supports update fields.
	 *
	 * @return void
	 */
	public function testInputSchemaSupportsUpdateFields(): void {
		$ability = new UpdateUser();
		$schema  = $ability->getInputSchema();

		$this->assertArrayHasKey( 'email', $schema['properties'] );
		$this->assertArrayHasKey( 'password', $schema['properties'] );
		$this->assertArrayHasKey( 'role', $schema['properties'] );
		$this->assertArrayHasKey( 'first_name', $schema['properties'] );
		$this->assertArrayHasKey( 'last_name', $schema['properties'] );
		$this->assertArrayHasKey( 'display_name', $schema['properties'] );
	}

	/**
	 * Test build update data with minimal input.
	 *
	 * @return void
	 */
	public function testBuildUpdateDataWithMinimalInput(): void {
		$ability = new UpdateUser();

		$reflection = new \ReflectionClass( $ability );
		$method     = $reflection->getMethod( 'buildUpdateData' );

		$input = array( 'user_id' => 5 );

		$result = $method->invoke( $ability, $input );

		$this->assertEmpty( $result );
	}

	/**
	 * Test build update data with email only.
	 *
	 * @return void
	 */
	public function testBuildUpdateDataWithEmailOnly(): void {
		Functions\stubs(
			array(
				'sanitize_email' => function ( $v ) {
					return $v;
				},
			)
		);

		$ability = new UpdateUser();

		$reflection = new \ReflectionClass( $ability );
		$method     = $reflection->getMethod( 'buildUpdateData' );

		$input = array(
			'user_id' => 5,
			'email'   => 'newemail@example.com',
		);

		$result = $method->invoke( $ability, $input );

		$this->assertArrayHasKey( 'user_email', $result );
		$this->assertEquals( 'newemail@example.com', $result['user_email'] );
		$this->assertCount( 1, $result );
	}

	/**
	 * Test build update data with all fields.
	 *
	 * @return void
	 */
	public function testBuildUpdateDataWithAllFields(): void {
		Functions\stubs(
			array(
				'sanitize_email'          => function ( $v ) {
					return $v;
				},
				'sanitize_text_field'     => function ( $v ) {
					return $v;
				},
				'sanitize_textarea_field' => function ( $v ) {
					return $v;
				},
				'esc_url_raw'             => function ( $v ) {
					return $v;
				},
			)
		);

		$ability = new UpdateUser();

		$reflection = new \ReflectionClass( $ability );
		$method     = $reflection->getMethod( 'buildUpdateData' );

		$input = array(
			'user_id'      => 5,
			'email'        => 'updated@example.com',
			'password'     => 'newpass123',
			'role'         => 'editor',
			'first_name'   => 'John',
			'last_name'    => 'Updated',
			'display_name' => 'John U.',
			'website'      => 'https://updated.com',
			'description'  => 'Updated bio',
		);

		$result = $method->invoke( $ability, $input );

		$this->assertEquals( 'updated@example.com', $result['user_email'] );
		$this->assertEquals( 'newpass123', $result['user_pass'] );
		$this->assertEquals( 'editor', $result['role'] );
		$this->assertEquals( 'John', $result['first_name'] );
		$this->assertEquals( 'Updated', $result['last_name'] );
		$this->assertEquals( 'John U.', $result['display_name'] );
		$this->assertEquals( 'https://updated.com', $result['user_url'] );
		$this->assertEquals( 'Updated bio', $result['description'] );
	}

	/**
	 * Test validate role with valid role within max allowed.
	 *
	 * With default max role of 'editor', roles like editor, author, etc. should be allowed.
	 *
	 * @return void
	 */
	public function testValidateRoleWithValidRole(): void {
		$ability = new UpdateUser();

		$reflection = new \ReflectionClass( $ability );
		$method     = $reflection->getMethod( 'validateRole' );

		// Editor is within the default max role limit.
		$result = $method->invoke( $ability, 'editor' );

		$this->assertEquals( 'editor', $result );
	}

	/**
	 * Test validate role allows custom roles.
	 *
	 * @return void
	 */
	public function testValidateRoleAllowsCustomRoles(): void {
		$ability = new UpdateUser();

		$reflection = new \ReflectionClass( $ability );
		$method     = $reflection->getMethod( 'validateRole' );

		$result = $method->invoke( $ability, 'custom_role' );

		$this->assertEquals( 'custom_role', $result );
	}

	/**
	 * Test execute with user not found.
	 *
	 * @return void
	 */
	public function testExecuteWithUserNotFound(): void {
		Functions\expect( 'get_userdata' )->with( 999 )->andReturn( false );

		$ability = new UpdateUser();

		$this->expectException( UserNotFoundException::class );
		$ability->doExecute( array( 'user_id' => 999 ) );
	}

	/**
	 * Test execute with user doesn't exist.
	 *
	 * @return void
	 */
	public function testExecuteWithUserDoesntExist(): void {
		// phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited -- Test mock.
		$mock_user = Mockery::mock( \WP_User::class );
		$mock_user->shouldReceive( 'exists' )->andReturn( false );

		Functions\expect( 'get_userdata' )->with( 10 )->andReturn( $mock_user );

		$ability = new UpdateUser();

		$this->expectException( UserNotFoundException::class );
		$ability->doExecute( array( 'user_id' => 10 ) );
	}

	/**
	 * Test execute with no updates returns empty.
	 *
	 * @return void
	 */
	public function testExecuteWithNoUpdatesReturnsEmpty(): void {
		// phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited -- Test mock.
		$mock_user = Mockery::mock( \WP_User::class );
		$mock_user->shouldReceive( 'exists' )->andReturn( true );

		Functions\expect( 'get_userdata' )->with( 5 )->andReturn( $mock_user );

		$ability = new UpdateUser();
		$result  = $ability->doExecute( array( 'user_id' => 5 ) );

		$this->assertEquals( 5, $result['user_id'] );
		$this->assertEmpty( $result['updated_fields'] );
	}

	/**
	 * Test execute with successful update.
	 *
	 * @return void
	 */
	public function testExecuteWithSuccessfulUpdate(): void {
		// phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited -- Test mock.
		$mock_user = Mockery::mock( \WP_User::class );
		$mock_user->shouldReceive( 'exists' )->andReturn( true );

		Functions\expect( 'get_userdata' )->with( 5 )->andReturn( $mock_user );
		Functions\stubs(
			array(
				'sanitize_email'      => function ( $v ) {
					return $v;
				},
				'sanitize_text_field' => function ( $v ) {
					return $v;
				},
			)
		);
		Functions\expect( 'wp_update_user' )->andReturn( 5 );
		Functions\expect( 'is_wp_error' )->andReturn( false );

		$ability = new UpdateUser();
		$result  = $ability->doExecute(
			array(
				'user_id'    => 5,
				'email'      => 'updated@example.com',
				'first_name' => 'Updated',
			)
		);

		$this->assertEquals( 5, $result['user_id'] );
		$this->assertContains( 'user_email', $result['updated_fields'] );
		$this->assertContains( 'first_name', $result['updated_fields'] );
	}

	/**
	 * Test execute throws exception on WP_Error.
	 *
	 * @return void
	 */
	public function testExecuteThrowsExceptionOnWpError(): void {
		// phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited -- Test mock.
		$mock_user = Mockery::mock( \WP_User::class );
		$mock_user->shouldReceive( 'exists' )->andReturn( true );

		Functions\expect( 'get_userdata' )->with( 5 )->andReturn( $mock_user );
		Functions\stubs(
			array(
				'sanitize_email' => function ( $v ) {
					return $v;
				},
			)
		);

		$wp_error = Mockery::mock( 'WP_Error' );
		$wp_error->shouldReceive( 'get_error_message' )->andReturn( 'Email already exists' );

		Functions\expect( 'wp_update_user' )->andReturn( $wp_error );
		Functions\expect( 'is_wp_error' )->with( $wp_error )->andReturn( true );

		$ability = new UpdateUser();

		$this->expectException( UserUpdateException::class );
		$this->expectExceptionMessage( 'Email already exists' );

		$ability->doExecute(
			array(
				'user_id' => 5,
				'email'   => 'duplicate@example.com',
			)
		);
	}

	/**
	 * Test build update data is a pure function.
	 *
	 * @return void
	 */
	public function testBuildUpdateDataIsPure(): void {
		Functions\stubs(
			array(
				'sanitize_email'      => function ( $v ) {
					return $v;
				},
				'sanitize_text_field' => function ( $v ) {
					return $v;
				},
			)
		);

		$ability = new UpdateUser();

		$reflection = new \ReflectionClass( $ability );
		$method     = $reflection->getMethod( 'buildUpdateData' );

		$input = array(
			'user_id'    => 5,
			'email'      => 'test@example.com',
			'first_name' => 'Test',
		);

		// Call twice with same input.
		$result1 = $method->invoke( $ability, $input );
		$result2 = $method->invoke( $ability, $input );

		// Pure function should return identical results.
		$this->assertEquals( $result1, $result2 );
	}

	/**
	 * Test build update data does not include empty password.
	 *
	 * @return void
	 */
	public function testBuildUpdateDataDoesNotIncludeEmptyPassword(): void {
		$ability = new UpdateUser();

		$reflection = new \ReflectionClass( $ability );
		$method     = $reflection->getMethod( 'buildUpdateData' );

		$input = array(
			'user_id'  => 5,
			'password' => '',
		);

		$result = $method->invoke( $ability, $input );

		$this->assertArrayNotHasKey( 'user_pass', $result );
	}

	/**
	 * Test format response excludes ID from updated fields.
	 *
	 * @return void
	 */
	public function testFormatResponseExcludesIdFromUpdatedFields(): void {
		$ability = new UpdateUser();

		$reflection = new \ReflectionClass( $ability );
		$method     = $reflection->getMethod( 'formatResponse' );

		$update_data = array(
			'ID'         => 5,
			'user_email' => 'test@example.com',
			'first_name' => 'Test',
		);

		$result = $method->invoke( $ability, 5, $update_data );

		$this->assertEquals( 5, $result['user_id'] );
		$this->assertNotContains( 'ID', $result['updated_fields'] );
		$this->assertContains( 'user_email', $result['updated_fields'] );
		$this->assertContains( 'first_name', $result['updated_fields'] );
	}
}
