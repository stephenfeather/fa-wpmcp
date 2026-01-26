<?php
/**
 * Tests for RolePolicy.
 *
 * @package FAWpmcp\Tests\Abilities\Users
 */

declare(strict_types=1);

namespace FAWpmcp\Tests\Abilities\Users;

use FAWpmcp\Abilities\Users\RolePolicy;
use FAWpmcp\Exceptions\RoleNotAllowedException;
use Brain\Monkey;
use Brain\Monkey\Functions;
use PHPUnit\Framework\TestCase;

/**
 * Test RolePolicy functionality.
 *
 * Tests cover:
 * - Role hierarchy enforcement
 * - Maximum role configuration
 * - Role validation and exceptions
 * - Custom role handling
 *
 * @package FAWpmcp\Tests\Abilities\Users
 */
class RolePolicyTest extends TestCase {

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
		parent::tearDown();
	}

	/**
	 * Test getMaxRole returns default when option not set.
	 *
	 * @return void
	 */
	public function testGetMaxRoleReturnsDefaultWhenNotSet(): void {
		Functions\when( 'get_option' )
			->justReturn( false );

		$policy = new RolePolicy();

		$this->assertEquals( 'editor', $policy->getMaxRole() );
	}

	/**
	 * Test getMaxRole returns configured value.
	 *
	 * @return void
	 */
	public function testGetMaxRoleReturnsConfiguredValue(): void {
		Functions\when( 'get_option' )
			->justReturn( 'administrator' );

		$policy = new RolePolicy();

		$this->assertEquals( 'administrator', $policy->getMaxRole() );
	}

	/**
	 * Test getMaxRole falls back to default for invalid option value.
	 *
	 * @return void
	 */
	public function testGetMaxRoleFallsBackForInvalidValue(): void {
		Functions\when( 'get_option' )
			->justReturn( 'invalid_role' );

		$policy = new RolePolicy();

		$this->assertEquals( 'editor', $policy->getMaxRole() );
	}

	/**
	 * Test getAllowedRoles with default max role (editor).
	 *
	 * @return void
	 */
	public function testGetAllowedRolesWithEditorMax(): void {
		Functions\when( 'get_option' )
			->justReturn( 'editor' );

		$policy  = new RolePolicy();
		$allowed = $policy->getAllowedRoles();

		$this->assertContains( 'editor', $allowed );
		$this->assertContains( 'author', $allowed );
		$this->assertContains( 'contributor', $allowed );
		$this->assertContains( 'subscriber', $allowed );
		$this->assertNotContains( 'administrator', $allowed );
	}

	/**
	 * Test getAllowedRoles with administrator max role.
	 *
	 * @return void
	 */
	public function testGetAllowedRolesWithAdministratorMax(): void {
		Functions\when( 'get_option' )
			->justReturn( 'administrator' );

		$policy  = new RolePolicy();
		$allowed = $policy->getAllowedRoles();

		$this->assertCount( 5, $allowed );
		$this->assertContains( 'administrator', $allowed );
		$this->assertContains( 'editor', $allowed );
		$this->assertContains( 'subscriber', $allowed );
	}

	/**
	 * Test getAllowedRoles with subscriber max role.
	 *
	 * @return void
	 */
	public function testGetAllowedRolesWithSubscriberMax(): void {
		Functions\when( 'get_option' )
			->justReturn( 'subscriber' );

		$policy  = new RolePolicy();
		$allowed = $policy->getAllowedRoles();

		$this->assertCount( 1, $allowed );
		$this->assertContains( 'subscriber', $allowed );
		$this->assertNotContains( 'contributor', $allowed );
	}

	/**
	 * Test isRoleAllowed returns true for allowed roles.
	 *
	 * @return void
	 */
	public function testIsRoleAllowedReturnsTrueForAllowedRoles(): void {
		Functions\when( 'get_option' )
			->justReturn( 'editor' );

		$policy = new RolePolicy();

		$this->assertTrue( $policy->isRoleAllowed( 'editor' ) );
		$this->assertTrue( $policy->isRoleAllowed( 'author' ) );
		$this->assertTrue( $policy->isRoleAllowed( 'subscriber' ) );
	}

	/**
	 * Test isRoleAllowed returns false for disallowed roles.
	 *
	 * @return void
	 */
	public function testIsRoleAllowedReturnsFalseForDisallowedRoles(): void {
		Functions\when( 'get_option' )
			->justReturn( 'editor' );

		$policy = new RolePolicy();

		$this->assertFalse( $policy->isRoleAllowed( 'administrator' ) );
	}

	/**
	 * Test isRoleAllowed returns true for custom roles.
	 *
	 * Custom roles (not in hierarchy) are always allowed.
	 *
	 * @return void
	 */
	public function testIsRoleAllowedReturnsTrueForCustomRoles(): void {
		Functions\when( 'get_option' )
			->justReturn( 'subscriber' );

		$policy = new RolePolicy();

		// Custom roles are always allowed (not in hierarchy).
		$this->assertTrue( $policy->isRoleAllowed( 'shop_manager' ) );
		$this->assertTrue( $policy->isRoleAllowed( 'custom_role' ) );
	}

	/**
	 * Test validateRole returns role when allowed.
	 *
	 * @return void
	 */
	public function testValidateRoleReturnsRoleWhenAllowed(): void {
		Functions\when( 'get_option' )
			->justReturn( 'editor' );

		$policy = new RolePolicy();

		$this->assertEquals( 'editor', $policy->validateRole( 'editor' ) );
		$this->assertEquals( 'author', $policy->validateRole( 'author' ) );
	}

	/**
	 * Test validateRole throws exception when role exceeds max.
	 *
	 * @return void
	 */
	public function testValidateRoleThrowsExceptionWhenRoleExceedsMax(): void {
		Functions\when( 'get_option' )
			->justReturn( 'editor' );

		$policy = new RolePolicy();

		$this->expectException( RoleNotAllowedException::class );
		$this->expectExceptionMessage( 'Role "administrator" cannot be assigned via API' );

		$policy->validateRole( 'administrator' );
	}

	/**
	 * Test validateRole allows custom roles.
	 *
	 * @return void
	 */
	public function testValidateRoleAllowsCustomRoles(): void {
		Functions\when( 'get_option' )
			->justReturn( 'subscriber' );

		$policy = new RolePolicy();

		// Custom roles pass validation.
		$this->assertEquals( 'shop_manager', $policy->validateRole( 'shop_manager' ) );
	}

	/**
	 * Test getRoleLevel returns correct levels.
	 *
	 * @return void
	 */
	public function testGetRoleLevelReturnsCorrectLevels(): void {
		$policy = new RolePolicy();

		$this->assertEquals( 5, $policy->getRoleLevel( 'administrator' ) );
		$this->assertEquals( 4, $policy->getRoleLevel( 'editor' ) );
		$this->assertEquals( 3, $policy->getRoleLevel( 'author' ) );
		$this->assertEquals( 2, $policy->getRoleLevel( 'contributor' ) );
		$this->assertEquals( 1, $policy->getRoleLevel( 'subscriber' ) );
		$this->assertEquals( 0, $policy->getRoleLevel( 'custom_role' ) );
	}

	/**
	 * Test isStandardRole identifies standard roles.
	 *
	 * @return void
	 */
	public function testIsStandardRoleIdentifiesStandardRoles(): void {
		$policy = new RolePolicy();

		$this->assertTrue( $policy->isStandardRole( 'administrator' ) );
		$this->assertTrue( $policy->isStandardRole( 'editor' ) );
		$this->assertTrue( $policy->isStandardRole( 'subscriber' ) );
		$this->assertFalse( $policy->isStandardRole( 'shop_manager' ) );
		$this->assertFalse( $policy->isStandardRole( 'custom_role' ) );
	}

	/**
	 * Test OPTION_NAME constant value.
	 *
	 * @return void
	 */
	public function testOptionNameConstant(): void {
		$this->assertEquals( 'fa_wpmcp_max_api_role', RolePolicy::OPTION_NAME );
	}

	/**
	 * Test DEFAULT_MAX_ROLE constant value.
	 *
	 * @return void
	 */
	public function testDefaultMaxRoleConstant(): void {
		$this->assertEquals( 'editor', RolePolicy::DEFAULT_MAX_ROLE );
	}

	/**
	 * Test ALL_ROLES constant contains all standard roles.
	 *
	 * @return void
	 */
	public function testAllRolesConstant(): void {
		$expected = array( 'administrator', 'editor', 'author', 'contributor', 'subscriber' );
		$this->assertEquals( $expected, RolePolicy::ALL_ROLES );
	}
}
