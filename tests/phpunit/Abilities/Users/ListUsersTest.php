<?php

/**
 * Tests for ListUsers ability.
 *
 * @package FAWpmcp\Tests\Abilities\Users
 */

declare(strict_types=1);

namespace FAWpmcp\Tests\Abilities\Users;

use FAWpmcp\Abilities\AbstractAbility;
use FAWpmcp\Abilities\Users\ListUsers;
use FAWpmcp\Tests\TestCase\AbilityTestTrait;
use FAWpmcp\Tests\TestCase\BrainMonkeyTestCase;
use Brain\Monkey\Functions;
use Mockery;

/**
 * Test ListUsers ability functionality.
 *
 * Tests cover:
 * - Listing users with pagination
 * - Filtering by role, search
 * - Pure function query building
 * - Result formatting
 *
 * @package FAWpmcp\Tests\Abilities\Users
 */
class ListUsersTest extends BrainMonkeyTestCase
{
    use AbilityTestTrait;

    /**
     * Get an instance of the ability being tested.
     *
     * @return AbstractAbility
     */
    protected function getAbilityInstance(): AbstractAbility
    {
        return new ListUsers();
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
    protected function getExpectedMetadata(): array
    {
        return array(
            'name'                  => 'fa-wpmcp/list-users',
            'category'              => 'users',
            'label'                 => 'List Users',
            'description_contains'  => 'users',
            'operation_type'        => 'read',
            'required_capability'   => 'list_users',
        );
    }

    /**
     * Test input schema supports pagination.
     *
     * @return void
     */
    public function testInputSchemaSupportsPagination(): void
    {
        $ability = $this->getAbilityInstance();
        $schema  = $ability->getInputSchema();

        $this->assertEquals('object', $schema['type']);
        $this->assertArrayHasKey('page', $schema['properties']);
        $this->assertArrayHasKey('per_page', $schema['properties']);
        $this->assertEquals('integer', $schema['properties']['page']['type']);
        $this->assertEquals('integer', $schema['properties']['per_page']['type']);
    }

    /**
     * Test input schema supports filtering.
     *
     * @return void
     */
    public function testInputSchemaSupportsFiltering(): void
    {
        $ability = $this->getAbilityInstance();
        $schema  = $ability->getInputSchema();

        $this->assertArrayHasKey('role', $schema['properties']);
        $this->assertArrayHasKey('search', $schema['properties']);
    }

    /**
     * Test output schema has expected structure.
     *
     * @return void
     */
    public function testOutputSchemaStructure(): void
    {
        $ability = $this->getAbilityInstance();
        $schema  = $ability->getOutputSchema();

        $this->assertEquals('object', $schema['type']);
        $this->assertArrayHasKey('users', $schema['properties']);
        $this->assertArrayHasKey('total', $schema['properties']);
        $this->assertArrayHasKey('pages', $schema['properties']);
    }

    /**
     * Test build query args with default pagination.
     *
     * @return void
     */
    public function testBuildQueryArgsDefaultPagination(): void
    {
        $ability = $this->getAbilityInstance();

        $reflection = new \ReflectionClass($ability);
        $method     = $reflection->getMethod('buildQueryArgs');

        $args = $method->invoke($ability, array());

        $this->assertEquals(10, $args['number']);
        $this->assertEquals(0, $args['offset']);
    }

    /**
     * Test build query args enforces max per_page limit.
     *
     * @return void
     */
    public function testBuildQueryArgsEnforcesMaxPerPage(): void
    {
        $ability = $this->getAbilityInstance();

        $reflection = new \ReflectionClass($ability);
        $method     = $reflection->getMethod('buildQueryArgs');

        $args = $method->invoke($ability, array( 'per_page' => 500 ));

        $this->assertLessThanOrEqual(100, $args['number']);
    }

    /**
     * Test build query args with role filter.
     *
     * @return void
     */
    public function testBuildQueryArgsWithRoleFilter(): void
    {
        $ability = $this->getAbilityInstance();

        $reflection = new \ReflectionClass($ability);
        $method     = $reflection->getMethod('buildQueryArgs');

        $args = $method->invoke($ability, array( 'role' => 'editor' ));

        $this->assertEquals('editor', $args['role']);
    }

    /**
     * Test build query args with search filter.
     *
     * @return void
     */
    public function testBuildQueryArgsWithSearchFilter(): void
    {
        $ability = $this->getAbilityInstance();

        $reflection = new \ReflectionClass($ability);
        $method     = $reflection->getMethod('buildQueryArgs');

        $args = $method->invoke($ability, array( 'search' => 'john' ));

        $this->assertEquals('*john*', $args['search']);
        $this->assertArrayHasKey('search_columns', $args);
    }

    /**
     * Test build query args with ordering.
     *
     * @return void
     */
    public function testBuildQueryArgsWithOrdering(): void
    {
        $ability = $this->getAbilityInstance();

        $reflection = new \ReflectionClass($ability);
        $method     = $reflection->getMethod('buildQueryArgs');

        $args = $method->invoke(
            $ability,
            array(
                'orderby' => 'display_name',
                'order'   => 'DESC',
            )
        );

        $this->assertEquals('display_name', $args['orderby']);
        $this->assertEquals('DESC', $args['order']);
    }

    /**
     * Test build query args with page 2.
     *
     * @return void
     */
    public function testBuildQueryArgsWithPage2(): void
    {
        $ability = $this->getAbilityInstance();

        $reflection = new \ReflectionClass($ability);
        $method     = $reflection->getMethod('buildQueryArgs');

        $args = $method->invoke(
            $ability,
            array(
                'page'     => 2,
                'per_page' => 10,
            )
        );

        $this->assertEquals(10, $args['number']);
        $this->assertEquals(10, $args['offset']);
    }

    /**
     * Test format user item is a pure function.
     *
     * @return void
     */
    public function testFormatUserItemIsPure(): void
    {
        // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited -- Test mock.
        $mock_user                  = Mockery::mock(\WP_User::class);
        $mock_user->ID              = 1;
        $mock_user->user_login      = 'johndoe';
        $mock_user->user_email      = 'john@example.com';
        $mock_user->display_name    = 'John Doe';
        $mock_user->first_name      = 'John';
        $mock_user->last_name       = 'Doe';
        $mock_user->roles           = array( 'editor' );
        $mock_user->user_registered = '2025-01-01 00:00:00';

        Functions\stubs(
            array(
                'get_avatar_url' => 'https://example.com/avatar.jpg',
            )
        );

        $ability = $this->getAbilityInstance();

        $reflection = new \ReflectionClass($ability);
        $method     = $reflection->getMethod('formatUserItem');

        // Call twice with same input.
        $result1 = $method->invoke($ability, $mock_user);
        $result2 = $method->invoke($ability, $mock_user);

        // Pure function should return identical results.
        $this->assertEquals($result1, $result2);
        $this->assertEquals(1, $result1['id']);
        $this->assertEquals('johndoe', $result1['username']);
        $this->assertEquals('john@example.com', $result1['email']);
    }

    /**
     * Test build query args is a pure function.
     *
     * @return void
     */
    public function testBuildQueryArgsIsPure(): void
    {
        $ability = $this->getAbilityInstance();

        $reflection = new \ReflectionClass($ability);
        $method     = $reflection->getMethod('buildQueryArgs');

        $input = array(
            'page'     => 2,
            'per_page' => 20,
            'role'     => 'author',
        );

        // Call twice with same input.
        $result1 = $method->invoke($ability, $input);
        $result2 = $method->invoke($ability, $input);

        // Pure function should return identical results.
        $this->assertEquals($result1, $result2);
    }

    /**
     * Test format results with users array.
     *
     * @return void
     */
    public function testFormatResultsWithUsers(): void
    {
        // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited -- Test mock.
        $mock_user                  = Mockery::mock(\WP_User::class);
        $mock_user->ID              = 1;
        $mock_user->user_login      = 'testuser';
        $mock_user->user_email      = 'test@example.com';
        $mock_user->display_name    = 'Test User';
        $mock_user->first_name      = 'Test';
        $mock_user->last_name       = 'User';
        $mock_user->roles           = array( 'subscriber' );
        $mock_user->user_registered = '2025-01-01 00:00:00';

        Functions\stubs(
            array(
                'get_avatar_url' => 'https://example.com/avatar.jpg',
            )
        );

        $ability = $this->getAbilityInstance();

        $reflection = new \ReflectionClass($ability);
        $method     = $reflection->getMethod('formatResults');

        $result = $method->invoke(
            $ability,
            array( $mock_user ),
            1,
            array(
                'page'     => 1,
                'per_page' => 10,
            )
        );

        $this->assertArrayHasKey('users', $result);
        $this->assertIsArray($result['users']);
        $this->assertCount(1, $result['users']);
        $this->assertEquals(1, $result['total']);
        $this->assertEquals(1, $result['pages']);
        $this->assertEquals(1, $result['current_page']);
        $this->assertEquals(10, $result['per_page']);
    }

    /**
     * Test get total users with no filters.
     *
     * @return void
     */
    public function testGetTotalUsersWithNoFilters(): void
    {
        $ability = $this->getAbilityInstance();

        $reflection = new \ReflectionClass($ability);
        $method     = $reflection->getMethod('getTotalUsers');

        $count_result = array(
            'total_users' => 42,
            'avail_roles' => array(
                'administrator' => 1,
                'editor'        => 5,
                'subscriber'    => 36,
            ),
        );

        $total = $method->invoke($ability, array(), $count_result);

        $this->assertEquals(42, $total);
    }

    /**
     * Test get total users with role filter.
     *
     * @return void
     */
    public function testGetTotalUsersWithRoleFilter(): void
    {
        $ability = $this->getAbilityInstance();

        $reflection = new \ReflectionClass($ability);
        $method     = $reflection->getMethod('getTotalUsers');

        $count_result = array(
            'total_users' => 42,
            'avail_roles' => array(
                'administrator' => 1,
                'editor'        => 5,
                'subscriber'    => 36,
            ),
        );

        $total = $method->invoke($ability, array( 'role' => 'editor' ), $count_result);

        $this->assertEquals(5, $total);
    }

    /**
     * Test get total users with search filter.
     *
     * @return void
     */
    public function testGetTotalUsersWithSearchFilter(): void
    {
        $ability = $this->getAbilityInstance();

        $reflection = new \ReflectionClass($ability);
        $method     = $reflection->getMethod('getTotalUsers');

        // Mock get_users to return 3 matching users.
        $mock_users = array(
            Mockery::mock(\WP_User::class),
            Mockery::mock(\WP_User::class),
            Mockery::mock(\WP_User::class),
        );

        Functions\expect('get_users')->andReturn($mock_users);

        $count_result = array(
            'total_users' => 42,
            'avail_roles' => array(),
        );

        $total = $method->invoke($ability, array( 'search' => 'john' ), $count_result);

        $this->assertEquals(3, $total);
    }
}
