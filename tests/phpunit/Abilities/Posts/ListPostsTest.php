<?php
/**
 * Tests for ListPosts ability.
 *
 * @package FAWpmcp\Tests\Abilities\Posts
 */

declare(strict_types=1);

namespace FAWpmcp\Tests\Abilities\Posts;

use FAWpmcp\Abilities\Posts\ListPosts;
use Brain\Monkey;
use Brain\Monkey\Functions;
use Mockery;
use PHPUnit\Framework\TestCase;

/**
 * Test ListPosts ability functionality.
 *
 * Tests cover:
 * - Listing posts with pagination
 * - Filtering by status, author, category, search
 * - Pure function query building
 * - Result formatting
 *
 * @package FAWpmcp\Tests\Abilities\Posts
 */
class ListPostsTest extends TestCase {
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
	public function test_get_name(): void {
		$ability = new ListPosts();
		$this->assertEquals( 'fa-wpmcp/list-posts', $ability->get_name() );
	}

	/**
	 * Test ability returns correct category.
	 *
	 * @return void
	 */
	public function test_get_category(): void {
		$ability = new ListPosts();
		$this->assertEquals( 'posts-pages', $ability->get_category() );
	}

	/**
	 * Test ability returns correct label.
	 *
	 * @return void
	 */
	public function test_get_label(): void {
		$ability = new ListPosts();
		$this->assertEquals( 'List Posts', $ability->get_label() );
	}

	/**
	 * Test ability returns correct description.
	 *
	 * @return void
	 */
	public function test_get_description(): void {
		$ability = new ListPosts();
		$this->assertStringContainsString( 'posts', strtolower( $ability->get_description() ) );
	}

	/**
	 * Test ability returns correct operation type.
	 *
	 * @return void
	 */
	public function test_get_operation_type(): void {
		$ability = new ListPosts();
		$this->assertEquals( 'read', $ability->get_operation_type() );
	}

	/**
	 * Test ability returns correct required capability.
	 *
	 * @return void
	 */
	public function test_get_required_capability(): void {
		$ability = new ListPosts();
		$this->assertEquals( 'read', $ability->get_required_capability() );
	}

	/**
	 * Test input schema supports pagination.
	 *
	 * @return void
	 */
	public function test_input_schema_supports_pagination(): void {
		$ability = new ListPosts();
		$schema  = $ability->get_input_schema();

		$this->assertEquals( 'object', $schema['type'] );
		$this->assertArrayHasKey( 'page', $schema['properties'] );
		$this->assertArrayHasKey( 'per_page', $schema['properties'] );
		$this->assertEquals( 'integer', $schema['properties']['page']['type'] );
		$this->assertEquals( 'integer', $schema['properties']['per_page']['type'] );
	}

	/**
	 * Test input schema supports filtering.
	 *
	 * @return void
	 */
	public function test_input_schema_supports_filtering(): void {
		$ability = new ListPosts();
		$schema  = $ability->get_input_schema();

		$this->assertArrayHasKey( 'status', $schema['properties'] );
		$this->assertArrayHasKey( 'author', $schema['properties'] );
		$this->assertArrayHasKey( 'category', $schema['properties'] );
		$this->assertArrayHasKey( 'search', $schema['properties'] );
	}

	/**
	 * Test output schema has expected structure.
	 *
	 * @return void
	 */
	public function test_output_schema_structure(): void {
		$ability = new ListPosts();
		$schema  = $ability->get_output_schema();

		$this->assertEquals( 'object', $schema['type'] );
		$this->assertArrayHasKey( 'posts', $schema['properties'] );
		$this->assertArrayHasKey( 'total', $schema['properties'] );
		$this->assertArrayHasKey( 'pages', $schema['properties'] );
	}

	/**
	 * Test execute returns posts array.
	 *
	 * @return void
	 */
	public function test_execute_returns_posts_array(): void {
		// phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited -- Test mock.
		$mock_post = Mockery::mock( 'WP_Post' );
		$mock_post->ID = 1;
		$mock_post->post_title = 'Test Post';
		$mock_post->post_excerpt = 'Test excerpt';
		$mock_post->post_status = 'publish';
		$mock_post->post_type = 'post';
		$mock_post->post_author = 1;
		$mock_post->post_date = '2025-01-20 12:00:00';
		$mock_post->post_modified = '2025-01-20 14:00:00';
		$mock_post->post_name = 'test-post';

		$mock_query = Mockery::mock( 'WP_Query' );
		$mock_query->posts = array( $mock_post );
		$mock_query->found_posts = 1;
		$mock_query->max_num_pages = 1;

		// format_results uses get_permalink and get_the_author_meta.
		Functions\expect( 'get_permalink' )->andReturn( 'https://example.com/test-post/' );
		Functions\expect( 'get_the_author_meta' )->andReturn( 'John Doe' );

		$ability = new ListPosts();

		// Use reflection to test with mock WP_Query.
		$result = $this->execute_with_mock_query( $ability, array(), $mock_query );

		$this->assertArrayHasKey( 'posts', $result );
		$this->assertIsArray( $result['posts'] );
		$this->assertArrayHasKey( 'total', $result );
		$this->assertArrayHasKey( 'pages', $result );
	}

	/**
	 * Test execute with default pagination.
	 *
	 * @return void
	 */
	public function test_execute_uses_default_pagination(): void {
		$mock_query = Mockery::mock( 'WP_Query' );
		$mock_query->posts = array();
		$mock_query->found_posts = 0;
		$mock_query->max_num_pages = 0;

		$ability = new ListPosts();
		$result  = $this->execute_with_mock_query( $ability, array(), $mock_query );

		$this->assertEquals( 0, $result['total'] );
		$this->assertEquals( 0, $result['pages'] );
	}

	/**
	 * Test execute enforces max per_page limit.
	 *
	 * @return void
	 */
	public function test_execute_enforces_max_per_page(): void {
		$ability = new ListPosts();

		// Test that per_page is capped at 100.
		$input = array( 'per_page' => 500 );

		// We'll verify through the build_query_args pure function.
		$reflection = new \ReflectionClass( $ability );
		$method     = $reflection->getMethod( 'build_query_args' );

		$args = $method->invoke( $ability, $input );

		$this->assertLessThanOrEqual( 100, $args['posts_per_page'] );
	}

	/**
	 * Test execute with status filter.
	 *
	 * @return void
	 */
	public function test_execute_filters_by_status(): void {
		$ability = new ListPosts();

		$reflection = new \ReflectionClass( $ability );
		$method     = $reflection->getMethod( 'build_query_args' );

		$args = $method->invoke( $ability, array( 'status' => 'draft' ) );

		$this->assertEquals( 'draft', $args['post_status'] );
	}

	/**
	 * Test execute with author filter.
	 *
	 * @return void
	 */
	public function test_execute_filters_by_author(): void {
		$ability = new ListPosts();

		$reflection = new \ReflectionClass( $ability );
		$method     = $reflection->getMethod( 'build_query_args' );

		$args = $method->invoke( $ability, array( 'author' => 5 ) );

		$this->assertEquals( 5, $args['author'] );
	}

	/**
	 * Test execute with category filter.
	 *
	 * @return void
	 */
	public function test_execute_filters_by_category(): void {
		$ability = new ListPosts();

		$reflection = new \ReflectionClass( $ability );
		$method     = $reflection->getMethod( 'build_query_args' );

		$args = $method->invoke( $ability, array( 'category' => 3 ) );

		$this->assertEquals( 3, $args['cat'] );
	}

	/**
	 * Test execute with search filter.
	 *
	 * @return void
	 */
	public function test_execute_filters_by_search(): void {
		$ability = new ListPosts();

		$reflection = new \ReflectionClass( $ability );
		$method     = $reflection->getMethod( 'build_query_args' );

		$args = $method->invoke( $ability, array( 'search' => 'test query' ) );

		$this->assertEquals( 'test query', $args['s'] );
	}

	/**
	 * Test execute returns pagination metadata.
	 *
	 * @return void
	 */
	public function test_execute_returns_pagination_metadata(): void {
		$mock_query = Mockery::mock( 'WP_Query' );
		$mock_query->posts = array();
		$mock_query->found_posts = 50;
		$mock_query->max_num_pages = 5;

		$ability = new ListPosts();
		$result  = $this->execute_with_mock_query(
			$ability,
			array(
				'page'     => 2,
				'per_page' => 10,
			),
			$mock_query
		);

		$this->assertEquals( 50, $result['total'] );
		$this->assertEquals( 5, $result['pages'] );
		$this->assertEquals( 2, $result['current_page'] );
		$this->assertEquals( 10, $result['per_page'] );
	}

	/**
	 * Test format_post_item is a pure function.
	 *
	 * @return void
	 */
	public function test_format_post_item_is_pure(): void {
		// phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited -- Test mock.
		$mock_post = Mockery::mock( 'WP_Post' );
		$mock_post->ID = 1;
		$mock_post->post_title = 'Test';
		$mock_post->post_excerpt = 'Excerpt';
		$mock_post->post_status = 'publish';
		$mock_post->post_type = 'post';
		$mock_post->post_author = 1;
		$mock_post->post_date = '2025-01-20 12:00:00';
		$mock_post->post_modified = '2025-01-20 12:00:00';
		$mock_post->post_name = 'test';

		Functions\stubs(
			array(
				'get_permalink'       => 'https://example.com/test/',
				'get_the_author_meta' => 'Author',
			)
		);

		$ability = new ListPosts();

		$reflection = new \ReflectionClass( $ability );
		$method     = $reflection->getMethod( 'format_post_item' );

		// Call twice with same input.
		$result1 = $method->invoke( $ability, $mock_post );
		$result2 = $method->invoke( $ability, $mock_post );

		// Pure function should return identical results.
		$this->assertEquals( $result1, $result2 );
	}

	/**
	 * Test build_query_args is a pure function.
	 *
	 * @return void
	 */
	public function test_build_query_args_is_pure(): void {
		$ability = new ListPosts();

		$reflection = new \ReflectionClass( $ability );
		$method     = $reflection->getMethod( 'build_query_args' );

		$input = array(
			'page'     => 2,
			'per_page' => 20,
			'status'   => 'publish',
		);

		// Call twice with same input.
		$result1 = $method->invoke( $ability, $input );
		$result2 = $method->invoke( $ability, $input );

		// Pure function should return identical results.
		$this->assertEquals( $result1, $result2 );
	}

	/**
	 * Test execute with multiple filters combined.
	 *
	 * @return void
	 */
	public function test_execute_with_combined_filters(): void {
		$ability = new ListPosts();

		$reflection = new \ReflectionClass( $ability );
		$method     = $reflection->getMethod( 'build_query_args' );

		$input = array(
			'status'   => 'draft',
			'author'   => 10,
			'category' => 5,
			'search'   => 'keyword',
			'page'     => 3,
			'per_page' => 25,
		);

		$args = $method->invoke( $ability, $input );

		$this->assertEquals( 'draft', $args['post_status'] );
		$this->assertEquals( 10, $args['author'] );
		$this->assertEquals( 5, $args['cat'] );
		$this->assertEquals( 'keyword', $args['s'] );
		$this->assertEquals( 3, $args['paged'] );
		$this->assertEquals( 25, $args['posts_per_page'] );
	}

	/**
	 * Test execute with orderby and order parameters.
	 *
	 * @return void
	 */
	public function test_execute_with_ordering(): void {
		$ability = new ListPosts();

		$reflection = new \ReflectionClass( $ability );
		$method     = $reflection->getMethod( 'build_query_args' );

		$input = array(
			'orderby' => 'title',
			'order'   => 'ASC',
		);

		$args = $method->invoke( $ability, $input );

		$this->assertEquals( 'title', $args['orderby'] );
		$this->assertEquals( 'ASC', $args['order'] );
	}

	/**
	 * Helper to execute with mock WP_Query.
	 *
	 * @param ListPosts $ability   The ability instance.
	 * @param array     $input     Input parameters.
	 * @param object    $mock_query Mock WP_Query.
	 * @return array
	 */
	private function execute_with_mock_query( ListPosts $ability, array $input, object $mock_query ): array {
		// Use reflection to call format_results directly with mock query.
		$reflection = new \ReflectionClass( $ability );
		$method     = $reflection->getMethod( 'format_results' );

		return $method->invoke( $ability, $mock_query, $input );
	}
}
