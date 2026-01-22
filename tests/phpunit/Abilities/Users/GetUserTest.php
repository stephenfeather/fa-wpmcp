<?php
/**
 * Tests for GetUser ability.
 *
 * @package FAWpmcp\Tests\Abilities\Users
 */

declare(strict_types=1);

namespace FAWpmcp\Tests\Abilities\Users;

use FAWpmcp\Abilities\Users\GetUser;
use FAWpmcp\Exceptions\UserNotFoundException;
use Brain\Monkey;
use Brain\Monkey\Functions;
use Mockery;
use PHPUnit\Framework\TestCase;

/**
 * Test GetUser ability functionality.
 *
 * Tests cover:
 * - Get user by ID, username, email
 * - User not found exception
 * - Result formatting
 *
 * @package FAWpmcp\Tests\Abilities\Users
 */
class GetUserTest extends TestCase {
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
		$ability = new GetUser();
		$this->assertEquals( 'fa-wpmcp/get-user', $ability->getName() );
	}

	/**
	 * Test ability returns correct category.
	 *
	 * @return void
	 */
	public function testGetCategory(): void {
		$ability = new GetUser();
		$this->assertEquals( 'users', $ability->getCategory() );
	}

	/**
	 * Test ability returns correct label.
	 *
	 * @return void
	 */
	public function testGetLabel(): void {
		$ability = new GetUser();
		$this->assertEquals( 'Get User', $ability->getLabel() );
	}

	/**
	 * Test ability returns correct description.
	 *
	 * @return void
	 */
	public function testGetDescription(): void {
		$ability = new GetUser();
		$this->assertStringContainsString( 'user', strtolower( $ability->getDescription() ) );
	}

	/**
	 * Test ability returns correct operation type.
	 *
	 * @return void
	 */
	public function testGetOperationType(): void {
		$ability = new GetUser();
		$this->assertEquals( 'read', $ability->getOperationType() );
	}

	/**
	 * Test ability returns correct required capability.
	 *
	 * @return void
	 */
	public function testGetRequiredCapability(): void {
		$ability = new GetUser();
		$this->assertEquals( 'list_users', $ability->getRequiredCapability() );
	}

	/**
	 * Test input schema supports user_id.
	 *
	 * @return void
	 */
	public function testInputSchemaSupportsUserId(): void {
		$ability = new GetUser();
		$schema  = $ability->getInputSchema();

		$this->assertArrayHasKey( 'user_id', $schema['properties'] );
		$this->assertEquals( 'integer', $schema['properties']['user_id']['type'] );
	}

	/**
	 * Test input schema supports username.
	 *
	 * @return void
	 */
	public function testInputSchemaSupportsUsername(): void {
		$ability = new GetUser();
		$schema  = $ability->getInputSchema();

		$this->assertArrayHasKey( 'username', $schema['properties'] );
		$this->assertEquals( 'string', $schema['properties']['username']['type'] );
	}

	/**
	 * Test input schema supports email.
	 *
	 * @return void
	 */
	public function testInputSchemaSupportsEmail(): void {
		$ability = new GetUser();
		$schema  = $ability->getInputSchema();

		$this->assertArrayHasKey( 'email', $schema['properties'] );
		$this->assertEquals( 'string', $schema['properties']['email']['type'] );
	}

	/**
	 * Test output schema has expected structure.
	 *
	 * @return void
	 */
	public function testOutputSchemaStructure(): void {
		$ability = new GetUser();
		$schema  = $ability->getOutputSchema();

		$this->assertEquals( 'object', $schema['type'] );
		$this->assertArrayHasKey( 'id', $schema['properties'] );
		$this->assertArrayHasKey( 'username', $schema['properties'] );
		$this->assertArrayHasKey( 'email', $schema['properties'] );
		$this->assertArrayHasKey( 'roles', $schema['properties'] );
	}

	/**
	 * Test execute with valid user ID.
	 *
	 * @return void
	 */
	public function testExecuteWithValidUserId(): void {
		// phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited -- Test mock.
		$mock_user                  = Mockery::mock( \WP_User::class );
		$mock_user->ID              = 5;
		$mock_user->user_login      = 'testuser';
		$mock_user->user_email      = 'test@example.com';
		$mock_user->display_name    = 'Test User';
		$mock_user->first_name      = 'Test';
		$mock_user->last_name       = 'User';
		$mock_user->nickname        = 'tester';
		$mock_user->description     = 'Test description';
		$mock_user->roles           = array( 'editor' );
		$mock_user->user_registered = '2025-01-01 00:00:00';
		$mock_user->user_url        = 'https://example.com';

		$mock_user->shouldReceive( 'exists' )->andReturn( true );

		Functions\expect( 'get_userdata' )->with( 5 )->andReturn( $mock_user );
		Functions\expect( 'get_avatar_url' )->with( 5 )->andReturn( 'https://example.com/avatar.jpg' );

		$ability = new GetUser();
		$result  = $ability->doExecute( array( 'user_id' => 5 ) );

		$this->assertEquals( 5, $result['id'] );
		$this->assertEquals( 'testuser', $result['username'] );
		$this->assertEquals( 'test@example.com', $result['email'] );
	}

	/**
	 * Test execute with valid username.
	 *
	 * @return void
	 */
	public function testExecuteWithValidUsername(): void {
		// phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited -- Test mock.
		$mock_user                  = Mockery::mock( \WP_User::class );
		$mock_user->ID              = 5;
		$mock_user->user_login      = 'johndoe';
		$mock_user->user_email      = 'john@example.com';
		$mock_user->display_name    = 'John Doe';
		$mock_user->first_name      = 'John';
		$mock_user->last_name       = 'Doe';
		$mock_user->nickname        = 'john';
		$mock_user->description     = '';
		$mock_user->roles           = array( 'subscriber' );
		$mock_user->user_registered = '2025-01-01 00:00:00';
		$mock_user->user_url        = '';

		$mock_user->shouldReceive( 'exists' )->andReturn( true );

		Functions\expect( 'get_user_by' )->with( 'login', 'johndoe' )->andReturn( $mock_user );
		Functions\expect( 'get_avatar_url' )->with( 5 )->andReturn( 'https://example.com/avatar.jpg' );

		$ability = new GetUser();
		$result  = $ability->doExecute( array( 'username' => 'johndoe' ) );

		$this->assertEquals( 5, $result['id'] );
		$this->assertEquals( 'johndoe', $result['username'] );
	}

	/**
	 * Test execute with valid email.
	 *
	 * @return void
	 */
	public function testExecuteWithValidEmail(): void {
		// phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited -- Test mock.
		$mock_user                  = Mockery::mock( \WP_User::class );
		$mock_user->ID              = 5;
		$mock_user->user_login      = 'janedoe';
		$mock_user->user_email      = 'jane@example.com';
		$mock_user->display_name    = 'Jane Doe';
		$mock_user->first_name      = 'Jane';
		$mock_user->last_name       = 'Doe';
		$mock_user->nickname        = 'jane';
		$mock_user->description     = 'Bio';
		$mock_user->roles           = array( 'author' );
		$mock_user->user_registered = '2025-01-01 00:00:00';
		$mock_user->user_url        = 'https://jane.example.com';

		$mock_user->shouldReceive( 'exists' )->andReturn( true );

		Functions\expect( 'get_user_by' )->with( 'email', 'jane@example.com' )->andReturn( $mock_user );
		Functions\expect( 'get_avatar_url' )->with( 5 )->andReturn( 'https://example.com/avatar.jpg' );

		$ability = new GetUser();
		$result  = $ability->doExecute( array( 'email' => 'jane@example.com' ) );

		$this->assertEquals( 5, $result['id'] );
		$this->assertEquals( 'jane@example.com', $result['email'] );
	}

	/**
	 * Test execute throws exception when user not found.
	 *
	 * @return void
	 */
	public function testExecuteThrowsExceptionWhenUserNotFound(): void {
		Functions\expect( 'get_userdata' )->with( 999 )->andReturn( false );

		$ability = new GetUser();

		$this->expectException( UserNotFoundException::class );
		$ability->doExecute( array( 'user_id' => 999 ) );
	}

	/**
	 * Test execute throws exception when user doesn't exist.
	 *
	 * @return void
	 */
	public function testExecuteThrowsExceptionWhenUserDoesntExist(): void {
		// phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited -- Test mock.
		$mock_user = Mockery::mock( \WP_User::class );
		$mock_user->shouldReceive( 'exists' )->andReturn( false );

		Functions\expect( 'get_userdata' )->with( 10 )->andReturn( $mock_user );

		$ability = new GetUser();

		$this->expectException( UserNotFoundException::class );
		$ability->doExecute( array( 'user_id' => 10 ) );
	}

	/**
	 * Test format user data is a pure function.
	 *
	 * @return void
	 */
	public function testFormatUserDataIsPure(): void {
		// phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited -- Test mock.
		$mock_user                  = Mockery::mock( \WP_User::class );
		$mock_user->ID              = 1;
		$mock_user->user_login      = 'testuser';
		$mock_user->user_email      = 'test@example.com';
		$mock_user->display_name    = 'Test User';
		$mock_user->first_name      = 'Test';
		$mock_user->last_name       = 'User';
		$mock_user->nickname        = 'tester';
		$mock_user->description     = 'Description';
		$mock_user->roles           = array( 'editor' );
		$mock_user->user_registered = '2025-01-01 00:00:00';
		$mock_user->user_url        = 'https://example.com';

		Functions\stubs(
			array(
				'get_avatar_url' => 'https://example.com/avatar.jpg',
			)
		);

		$ability = new GetUser();

		$reflection = new \ReflectionClass( $ability );
		$method     = $reflection->getMethod( 'formatUserData' );

		// Call twice with same input.
		$result1 = $method->invoke( $ability, $mock_user );
		$result2 = $method->invoke( $ability, $mock_user );

		// Pure function should return identical results.
		$this->assertEquals( $result1, $result2 );
		$this->assertEquals( 1, $result1['id'] );
		$this->assertEquals( 'testuser', $result1['username'] );
	}

	/**
	 * Test fetch user prioritizes user_id over username.
	 *
	 * @return void
	 */
	public function testFetchUserPrioritizesUserId(): void {
		// phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited -- Test mock.
		$mock_user = Mockery::mock( \WP_User::class );

		Functions\expect( 'get_userdata' )->with( 5 )->andReturn( $mock_user );

		$ability = new GetUser();

		$reflection = new \ReflectionClass( $ability );
		$method     = $reflection->getMethod( 'fetchUser' );

		$result = $method->invoke(
			$ability,
			array(
				'user_id'  => 5,
				'username' => 'ignored',
			)
		);

		$this->assertEquals( $mock_user, $result );
	}

	/**
	 * Test fetch user prioritizes username over email.
	 *
	 * @return void
	 */
	public function testFetchUserPrioritizesUsernameOverEmail(): void {
		// phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited -- Test mock.
		$mock_user = Mockery::mock( \WP_User::class );

		Functions\expect( 'get_user_by' )->with( 'login', 'testuser' )->andReturn( $mock_user );

		$ability = new GetUser();

		$reflection = new \ReflectionClass( $ability );
		$method     = $reflection->getMethod( 'fetchUser' );

		$result = $method->invoke(
			$ability,
			array(
				'username' => 'testuser',
				'email'    => 'ignored@example.com',
			)
		);

		$this->assertEquals( $mock_user, $result );
	}
}
