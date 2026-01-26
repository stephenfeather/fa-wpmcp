<?php

/**
 * Tests for CreateUser ability.
 *
 * @package FAWpmcp\Tests\Abilities\Users
 */

declare(strict_types=1);

namespace FAWpmcp\Tests\Abilities\Users;

use FAWpmcp\Abilities\AbstractAbility;
use FAWpmcp\Abilities\Users\CreateUser;
use FAWpmcp\Exceptions\UserCreationException;
use FAWpmcp\Tests\TestCase\AbilityTestTrait;
use FAWpmcp\Tests\TestCase\BrainMonkeyTestCase;
use Brain\Monkey\Functions;
use Mockery;

/**
 * Test CreateUser ability functionality.
 *
 * Tests cover:
 * - Creating users with required and optional fields
 * - Input sanitization
 * - Error handling
 *
 * @package FAWpmcp\Tests\Abilities\Users
 */
class CreateUserTest extends BrainMonkeyTestCase {

	use AbilityTestTrait;

	/**
	 * Set up default mocks for RolePolicy.
	 *
	 * @return void
	 */
	protected function setUp(): void {
		parent::setUp();

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
	 * Get an instance of the ability being tested.
	 *
	 * @return AbstractAbility
	 */
	protected function getAbilityInstance(): AbstractAbility {
		return new CreateUser();
	}

	/**
	 * Get expected metadata for the ability.
	 *
	 * @return array{
	 *     name: string,
	 *     category: string,
	 *     label: string,
	 *     description_contains: string,
	 *     operation_type: string,
	 *     required_capability: string
	 * }
	 */
	protected function getExpectedMetadata(): array {
		return array(
			'name'                  => 'fa-wpmcp/create-user',
			'category'              => 'users',
			'label'                 => 'Create User',
			'description_contains'  => 'create',
			'operation_type'        => 'write',
			'required_capability'   => 'create_users',
		);
	}

	/**
	 * Test input schema requires username and email.
	 *
	 * @return void
	 */
	public function testInputSchemaRequiresUsernameAndEmail(): void {
		$ability = new CreateUser();
		$schema  = $ability->getInputSchema();

		$this->assertArrayHasKey( 'required', $schema );
		$this->assertContains( 'username', $schema['required'] );
		$this->assertContains( 'email', $schema['required'] );
	}

	/**
	 * Test input schema supports optional fields.
	 *
	 * @return void
	 */
	public function testInputSchemaSupportsOptionalFields(): void {
		$ability = new CreateUser();
		$schema  = $ability->getInputSchema();

		$this->assertArrayHasKey( 'password', $schema['properties'] );
		$this->assertArrayHasKey( 'role', $schema['properties'] );
		$this->assertArrayHasKey( 'first_name', $schema['properties'] );
		$this->assertArrayHasKey( 'last_name', $schema['properties'] );
		$this->assertArrayHasKey( 'display_name', $schema['properties'] );
	}

	/**
	 * Test build user data with minimal input.
	 *
	 * @return void
	 */
	public function testBuildUserDataWithMinimalInput(): void {
		Functions\stubs(
			array(
				'sanitize_user'  => function ( $v ) {
					return $v;
				},
				'sanitize_email' => function ( $v ) {
					return $v;
				},
			)
		);

		$ability = $this->getAbilityInstance();

		$reflection = new \ReflectionClass( $ability );
		$method     = $reflection->getMethod( 'buildUserData' );

		$input = array(
			'username' => 'testuser',
			'email'    => 'test@example.com',
		);

		$result = $method->invoke( $ability, $input );

		$this->assertEquals( 'testuser', $result['user_login'] );
		$this->assertEquals( 'test@example.com', $result['user_email'] );
		$this->assertEquals( 'subscriber', $result['role'] );
		$this->assertEquals( 'testuser', $result['display_name'] );
	}

	/**
	 * Test build user data with all fields.
	 *
	 * @return void
	 */
	public function testBuildUserDataWithAllFields(): void {
		Functions\stubs(
			array(
				'sanitize_user'           => function ( $v ) {
					return $v;
				},
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

		$ability = $this->getAbilityInstance();

		$reflection = new \ReflectionClass( $ability );
		$method     = $reflection->getMethod( 'buildUserData' );

		$input = array(
			'username'     => 'johndoe',
			'email'        => 'john@example.com',
			'password'     => 'securepass123',
			'role'         => 'editor',
			'first_name'   => 'John',
			'last_name'    => 'Doe',
			'display_name' => 'John D.',
			'website'      => 'https://johndoe.com',
			'description'  => 'Test user bio',
		);

		$result = $method->invoke( $ability, $input );

		$this->assertEquals( 'johndoe', $result['user_login'] );
		$this->assertEquals( 'john@example.com', $result['user_email'] );
		$this->assertEquals( 'securepass123', $result['user_pass'] );
		$this->assertEquals( 'editor', $result['role'] );
		$this->assertEquals( 'John', $result['first_name'] );
		$this->assertEquals( 'Doe', $result['last_name'] );
		$this->assertEquals( 'John D.', $result['display_name'] );
		$this->assertEquals( 'https://johndoe.com', $result['user_url'] );
		$this->assertEquals( 'Test user bio', $result['description'] );
	}

	/**
	 * Test validate role with valid role.
	 *
	 * @return void
	 */
	public function testValidateRoleWithValidRole(): void {
		$ability = $this->getAbilityInstance();

		$reflection = new \ReflectionClass( $ability );
		$method     = $reflection->getMethod( 'validateRole' );

		$result = $method->invoke( $ability, 'editor' );

		$this->assertEquals( 'editor', $result );
	}

	/**
	 * Test validate role with invalid role.
	 *
	 * @return void
	 */
	public function testValidateRoleWithInvalidRole(): void {
		$ability = $this->getAbilityInstance();

		$reflection = new \ReflectionClass( $ability );
		$method     = $reflection->getMethod( 'validateRole' );

		$result = $method->invoke( $ability, 'invalid_role' );

		$this->assertEquals( 'subscriber', $result );
	}

	/**
	 * Test execute with successful user creation.
	 *
	 * @return void
	 */
	public function testExecuteWithSuccessfulCreation(): void {
		Functions\stubs(
			array(
				'sanitize_user'  => function ( $v ) {
					return $v;
				},
				'sanitize_email' => function ( $v ) {
					return $v;
				},
			)
		);

		Functions\expect( 'wp_insert_user' )->andReturn( 42 );

		// phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited -- Test mock.
		$mock_user             = Mockery::mock( \WP_User::class );
		$mock_user->user_login = 'testuser';
		$mock_user->user_email = 'test@example.com';

		Functions\expect( 'get_userdata' )->with( 42 )->andReturn( $mock_user );
		Functions\expect( 'get_edit_user_link' )->with( 42 )->andReturn( 'https://example.com/wp-admin/user-edit.php?user_id=42' );

		$ability = $this->getAbilityInstance();
		$result  = $ability->doExecute(
			array(
				'username' => 'testuser',
				'email'    => 'test@example.com',
			)
		);

		$this->assertEquals( 42, $result['user_id'] );
		$this->assertEquals( 'testuser', $result['username'] );
		$this->assertEquals( 'test@example.com', $result['email'] );
	}

	/**
	 * Test execute throws exception on WP_Error.
	 *
	 * @return void
	 */
	public function testExecuteThrowsExceptionOnWpError(): void {
		Functions\stubs(
			array(
				'sanitize_user'  => function ( $v ) {
					return $v;
				},
				'sanitize_email' => function ( $v ) {
					return $v;
				},
			)
		);

		$wp_error = Mockery::mock( 'WP_Error' );
		$wp_error->shouldReceive( 'get_error_message' )->andReturn( 'Username already exists' );

		Functions\expect( 'wp_insert_user' )->andReturn( $wp_error );
		Functions\expect( 'is_wp_error' )->with( $wp_error )->andReturn( true );

		$ability = $this->getAbilityInstance();

		$this->expectException( UserCreationException::class );
		$this->expectExceptionMessage( 'Username already exists' );

		$ability->doExecute(
			array(
				'username' => 'duplicate',
				'email'    => 'duplicate@example.com',
			)
		);
	}

	/**
	 * Test build user data is a pure function.
	 *
	 * @return void
	 */
	public function testBuildUserDataIsPure(): void {
		Functions\stubs(
			array(
				'sanitize_user'  => function ( $v ) {
					return $v;
				},
				'sanitize_email' => function ( $v ) {
					return $v;
				},
			)
		);

		$ability = $this->getAbilityInstance();

		$reflection = new \ReflectionClass( $ability );
		$method     = $reflection->getMethod( 'buildUserData' );

		$input = array(
			'username' => 'testuser',
			'email'    => 'test@example.com',
			'role'     => 'author',
		);

		// Call twice with same input.
		$result1 = $method->invoke( $ability, $input );
		$result2 = $method->invoke( $ability, $input );

		// Pure function should return identical results.
		$this->assertEquals( $result1, $result2 );
	}

	/**
	 * Test build user data does not include password if empty.
	 *
	 * @return void
	 */
	public function testBuildUserDataDoesNotIncludeEmptyPassword(): void {
		Functions\stubs(
			array(
				'sanitize_user'  => function ( $v ) {
					return $v;
				},
				'sanitize_email' => function ( $v ) {
					return $v;
				},
			)
		);

		$ability = $this->getAbilityInstance();

		$reflection = new \ReflectionClass( $ability );
		$method     = $reflection->getMethod( 'buildUserData' );

		$input = array(
			'username' => 'testuser',
			'email'    => 'test@example.com',
			'password' => '',
		);

		$result = $method->invoke( $ability, $input );

		$this->assertArrayNotHasKey( 'user_pass', $result );
	}
}
