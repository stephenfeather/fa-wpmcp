<?php
/**
 * Tests for AbilityRegistry.
 *
 * @package FAWpmcp\Tests\Abilities
 */

declare(strict_types=1);

namespace FAWpmcp\Tests\Abilities;

use FAWpmcp\Abilities\AbilityRegistry;
use FAWpmcp\Abilities\AbstractAbility;
use Mockery;
use PHPUnit\Framework\TestCase;

/**
 * Test AbilityRegistry central registration.
 *
 * Tests the registry pattern for:
 * - Registering abilities
 * - Retrieving abilities by name
 * - Listing all abilities
 * - Listing abilities by category
 * - Preventing duplicate registrations
 *
 * @package FAWpmcp\Tests\Abilities
 */
class AbilityRegistryTest extends TestCase {

	/**
	 * Tear down Mockery after each test.
	 *
	 * @return void
	 */
	protected function tearDown(): void {
		Mockery::close();
		parent::tearDown();
	}

	/**
	 * Create a mock ability for testing.
	 *
	 * @param string $name     Ability name.
	 * @param string $category Ability category.
	 * @return AbstractAbility
	 */
	private function create_mock_ability( string $name, string $category = 'test-category' ): AbstractAbility {
		$ability = Mockery::mock( AbstractAbility::class );
		$ability->shouldReceive( 'getName' )->andReturn( $name );
		$ability->shouldReceive( 'getCategory' )->andReturn( $category );
		$ability->shouldReceive( 'getLabel' )->andReturn( 'Test Ability' );
		$ability->shouldReceive( 'getDescription' )->andReturn( 'Test description' );
		$ability->shouldReceive( 'getOperationType' )->andReturn( 'read' );
		$ability->shouldReceive( 'getRequiredCapability' )->andReturn( 'read' );
		$ability->shouldReceive( 'getInputSchema' )->andReturn( array() );
		$ability->shouldReceive( 'getOutputSchema' )->andReturn( array() );
		return $ability;
	}

	/**
	 * Test register ability adds to registry.
	 *
	 * @return void
	 */
	public function test_register_ability(): void {
		$registry = new AbilityRegistry();
		$ability  = $this->create_mock_ability( 'fa-wpmcp/list-posts', 'posts-pages' );

		$registry->register( $ability );

		$this->assertTrue( $registry->has( 'fa-wpmcp/list-posts' ) );
	}

	/**
	 * Test get ability by name.
	 *
	 * @return void
	 */
	public function test_get_ability_by_name(): void {
		$registry = new AbilityRegistry();
		$ability  = $this->create_mock_ability( 'fa-wpmcp/list-posts', 'posts-pages' );

		$registry->register( $ability );

		$retrieved = $registry->get( 'fa-wpmcp/list-posts' );

		$this->assertSame( $ability, $retrieved );
	}

	/**
	 * Test get returns null for non-existent ability.
	 *
	 * @return void
	 */
	public function test_get_returns_null_for_non_existent(): void {
		$registry = new AbilityRegistry();

		$result = $registry->get( 'fa-wpmcp/non-existent' );

		$this->assertNull( $result );
	}

	/**
	 * Test list all abilities.
	 *
	 * @return void
	 */
	public function test_list_all_abilities(): void {
		$registry = new AbilityRegistry();

		$ability1 = $this->create_mock_ability( 'fa-wpmcp/list-posts', 'posts-pages' );
		$ability2 = $this->create_mock_ability( 'fa-wpmcp/create-post', 'posts-pages' );
		$ability3 = $this->create_mock_ability( 'fa-wpmcp/list-users', 'users' );

		$registry->register( $ability1 );
		$registry->register( $ability2 );
		$registry->register( $ability3 );

		$all = $registry->all();

		$this->assertCount( 3, $all );
		$this->assertArrayHasKey( 'fa-wpmcp/list-posts', $all );
		$this->assertArrayHasKey( 'fa-wpmcp/create-post', $all );
		$this->assertArrayHasKey( 'fa-wpmcp/list-users', $all );
	}

	/**
	 * Test list abilities by category.
	 *
	 * @return void
	 */
	public function test_list_abilities_by_category(): void {
		$registry = new AbilityRegistry();

		$ability1 = $this->create_mock_ability( 'fa-wpmcp/list-posts', 'posts-pages' );
		$ability2 = $this->create_mock_ability( 'fa-wpmcp/create-post', 'posts-pages' );
		$ability3 = $this->create_mock_ability( 'fa-wpmcp/list-users', 'users' );
		$ability4 = $this->create_mock_ability( 'fa-wpmcp/create-user', 'users' );

		$registry->register( $ability1 );
		$registry->register( $ability2 );
		$registry->register( $ability3 );
		$registry->register( $ability4 );

		$posts_abilities = $registry->byCategory( 'posts-pages' );
		$user_abilities  = $registry->byCategory( 'users' );

		$this->assertCount( 2, $posts_abilities );
		$this->assertArrayHasKey( 'fa-wpmcp/list-posts', $posts_abilities );
		$this->assertArrayHasKey( 'fa-wpmcp/create-post', $posts_abilities );

		$this->assertCount( 2, $user_abilities );
		$this->assertArrayHasKey( 'fa-wpmcp/list-users', $user_abilities );
		$this->assertArrayHasKey( 'fa-wpmcp/create-user', $user_abilities );
	}

	/**
	 * Test list abilities returns empty for non-existent category.
	 *
	 * @return void
	 */
	public function test_list_abilities_returns_empty_for_non_existent_category(): void {
		$registry = new AbilityRegistry();

		$ability = $this->create_mock_ability( 'fa-wpmcp/list-posts', 'posts-pages' );
		$registry->register( $ability );

		$result = $registry->byCategory( 'non-existent' );

		$this->assertIsArray( $result );
		$this->assertEmpty( $result );
	}

	/**
	 * Test prevent duplicate registration throws exception.
	 *
	 * @return void
	 */
	public function test_prevent_duplicate_registration(): void {
		$registry = new AbilityRegistry();

		$ability1 = $this->create_mock_ability( 'fa-wpmcp/list-posts', 'posts-pages' );
		$ability2 = $this->create_mock_ability( 'fa-wpmcp/list-posts', 'posts-pages' );

		$registry->register( $ability1 );

		$this->expectException( \InvalidArgumentException::class );
		$this->expectExceptionMessage( 'already registered' );

		$registry->register( $ability2 );
	}

	/**
	 * Test has returns false for non-existent ability.
	 *
	 * @return void
	 */
	public function test_has_returns_false_for_non_existent(): void {
		$registry = new AbilityRegistry();

		$this->assertFalse( $registry->has( 'fa-wpmcp/non-existent' ) );
	}

	/**
	 * Test count returns number of registered abilities.
	 *
	 * @return void
	 */
	public function test_count_returns_registered_count(): void {
		$registry = new AbilityRegistry();

		$this->assertEquals( 0, $registry->count() );

		$registry->register( $this->create_mock_ability( 'fa-wpmcp/ability1' ) );
		$this->assertEquals( 1, $registry->count() );

		$registry->register( $this->create_mock_ability( 'fa-wpmcp/ability2' ) );
		$this->assertEquals( 2, $registry->count() );

		$registry->register( $this->create_mock_ability( 'fa-wpmcp/ability3' ) );
		$this->assertEquals( 3, $registry->count() );
	}

	/**
	 * Test get categories returns unique categories.
	 *
	 * @return void
	 */
	public function test_get_categories(): void {
		$registry = new AbilityRegistry();

		$registry->register( $this->create_mock_ability( 'fa-wpmcp/list-posts', 'posts-pages' ) );
		$registry->register( $this->create_mock_ability( 'fa-wpmcp/create-post', 'posts-pages' ) );
		$registry->register( $this->create_mock_ability( 'fa-wpmcp/list-users', 'users' ) );
		$registry->register( $this->create_mock_ability( 'fa-wpmcp/list-media', 'media' ) );

		$categories = $registry->categories();

		$this->assertCount( 3, $categories );
		$this->assertContains( 'posts-pages', $categories );
		$this->assertContains( 'users', $categories );
		$this->assertContains( 'media', $categories );
	}

	/**
	 * Test empty registry has no categories.
	 *
	 * @return void
	 */
	public function test_empty_registry_has_no_categories(): void {
		$registry = new AbilityRegistry();

		$categories = $registry->categories();

		$this->assertIsArray( $categories );
		$this->assertEmpty( $categories );
	}

	/**
	 * Test registry returns abilities in registration order.
	 *
	 * @return void
	 */
	public function test_registry_maintains_order(): void {
		$registry = new AbilityRegistry();

		$registry->register( $this->create_mock_ability( 'fa-wpmcp/z-last' ) );
		$registry->register( $this->create_mock_ability( 'fa-wpmcp/a-first' ) );
		$registry->register( $this->create_mock_ability( 'fa-wpmcp/m-middle' ) );

		$all  = $registry->all();
		$keys = array_keys( $all );

		$this->assertEquals( 'fa-wpmcp/z-last', $keys[0] );
		$this->assertEquals( 'fa-wpmcp/a-first', $keys[1] );
		$this->assertEquals( 'fa-wpmcp/m-middle', $keys[2] );
	}

	/**
	 * Test unregister removes ability.
	 *
	 * @return void
	 */
	public function test_unregister_removes_ability(): void {
		$registry = new AbilityRegistry();

		$ability = $this->create_mock_ability( 'fa-wpmcp/list-posts' );
		$registry->register( $ability );

		$this->assertTrue( $registry->has( 'fa-wpmcp/list-posts' ) );

		$registry->unregister( 'fa-wpmcp/list-posts' );

		$this->assertFalse( $registry->has( 'fa-wpmcp/list-posts' ) );
		$this->assertNull( $registry->get( 'fa-wpmcp/list-posts' ) );
	}

	/**
	 * Test unregister updates category list.
	 *
	 * @return void
	 */
	public function test_unregister_updates_category_list(): void {
		$registry = new AbilityRegistry();

		$registry->register( $this->create_mock_ability( 'fa-wpmcp/list-posts', 'posts-pages' ) );
		$registry->register( $this->create_mock_ability( 'fa-wpmcp/list-users', 'users' ) );

		$this->assertCount( 2, $registry->categories() );

		$registry->unregister( 'fa-wpmcp/list-posts' );

		$categories = $registry->categories();
		$this->assertCount( 1, $categories );
		$this->assertNotContains( 'posts-pages', $categories );
		$this->assertContains( 'users', $categories );
	}

	/**
	 * Test unregister non-existent returns false.
	 *
	 * @return void
	 */
	public function test_unregister_non_existent_returns_false(): void {
		$registry = new AbilityRegistry();

		$result = $registry->unregister( 'fa-wpmcp/non-existent' );

		$this->assertFalse( $result );
	}

	/**
	 * Test clear removes all abilities.
	 *
	 * @return void
	 */
	public function test_clear_removes_all(): void {
		$registry = new AbilityRegistry();

		$registry->register( $this->create_mock_ability( 'fa-wpmcp/ability1' ) );
		$registry->register( $this->create_mock_ability( 'fa-wpmcp/ability2' ) );
		$registry->register( $this->create_mock_ability( 'fa-wpmcp/ability3' ) );

		$this->assertEquals( 3, $registry->count() );

		$registry->clear();

		$this->assertEquals( 0, $registry->count() );
		$this->assertEmpty( $registry->all() );
		$this->assertEmpty( $registry->categories() );
	}

	/**
	 * Test get names returns array of ability names.
	 *
	 * @return void
	 */
	public function test_get_names(): void {
		$registry = new AbilityRegistry();

		$registry->register( $this->create_mock_ability( 'fa-wpmcp/list-posts' ) );
		$registry->register( $this->create_mock_ability( 'fa-wpmcp/create-post' ) );

		$names = $registry->names();

		$this->assertCount( 2, $names );
		$this->assertContains( 'fa-wpmcp/list-posts', $names );
		$this->assertContains( 'fa-wpmcp/create-post', $names );
	}

	/**
	 * Test filter abilities by operation type.
	 *
	 * @return void
	 */
	public function test_filter_by_operation_type(): void {
		$registry = new AbilityRegistry();

		$read_ability = Mockery::mock( AbstractAbility::class );
		$read_ability->shouldReceive( 'getName' )->andReturn( 'fa-wpmcp/list-posts' );
		$read_ability->shouldReceive( 'getCategory' )->andReturn( 'posts-pages' );
		$read_ability->shouldReceive( 'getOperationType' )->andReturn( 'read' );

		$write_ability = Mockery::mock( AbstractAbility::class );
		$write_ability->shouldReceive( 'getName' )->andReturn( 'fa-wpmcp/create-post' );
		$write_ability->shouldReceive( 'getCategory' )->andReturn( 'posts-pages' );
		$write_ability->shouldReceive( 'getOperationType' )->andReturn( 'write' );

		$registry->register( $read_ability );
		$registry->register( $write_ability );

		$read_abilities  = $registry->byOperation( 'read' );
		$write_abilities = $registry->byOperation( 'write' );

		$this->assertCount( 1, $read_abilities );
		$this->assertArrayHasKey( 'fa-wpmcp/list-posts', $read_abilities );

		$this->assertCount( 1, $write_abilities );
		$this->assertArrayHasKey( 'fa-wpmcp/create-post', $write_abilities );
	}

	/**
	 * Test to_array returns ability registration arrays.
	 *
	 * @return void
	 */
	public function test_to_array_returns_registration_arrays(): void {
		$registry = new AbilityRegistry();

		$ability = Mockery::mock( AbstractAbility::class );
		$ability->shouldReceive( 'getName' )->andReturn( 'fa-wpmcp/list-posts' );
		$ability->shouldReceive( 'getCategory' )->andReturn( 'posts-pages' );
		$ability->shouldReceive( 'toRegistrationArray' )->andReturn(
			array(
				'name'        => 'fa-wpmcp/list-posts',
				'category'    => 'posts-pages',
				'label'       => 'List Posts',
				'description' => 'List all posts',
			)
		);

		$registry->register( $ability );

		$arrays = $registry->toArray();

		$this->assertCount( 1, $arrays );
		$this->assertEquals( 'fa-wpmcp/list-posts', $arrays[0]['name'] );
		$this->assertEquals( 'posts-pages', $arrays[0]['category'] );
	}
}
