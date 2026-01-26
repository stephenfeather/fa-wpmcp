<?php

/**
 * Tests for ListPosts ability.
 *
 * @package FAWpmcp\Tests\Abilities\Posts
 */

declare(strict_types=1);

namespace FAWpmcp\Tests\Abilities\Posts;

use FAWpmcp\Abilities\AbstractAbility;
use FAWpmcp\Abilities\Posts\ListPosts;
use FAWpmcp\Tests\TestCase\AbilityTestTrait;
use FAWpmcp\Tests\TestCase\BrainMonkeyTestCase;
use Brain\Monkey\Functions;
use Mockery;

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
class ListPostsTest extends BrainMonkeyTestCase
{
    use AbilityTestTrait;

    /**
     * Get an instance of the ability being tested.
     *
     * @return AbstractAbility
     */
    protected function getAbilityInstance(): AbstractAbility
    {
        return new ListPosts();
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
            'name'                  => 'fa-wpmcp/list-posts',
            'category'              => 'posts-pages',
            'label'                 => 'List Posts',
            'description_contains'  => 'posts',
            'operation_type'        => 'read',
            'required_capability'   => 'read',
        );
    }

    /**
     * Test input schema supports pagination.
     *
     * @return void
     */
    public function test_input_schema_supports_pagination(): void
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
    public function test_input_schema_supports_filtering(): void
    {
        $ability = $this->getAbilityInstance();
        $schema  = $ability->getInputSchema();

        $this->assertArrayHasKey('status', $schema['properties']);
        $this->assertArrayHasKey('author', $schema['properties']);
        $this->assertArrayHasKey('category', $schema['properties']);
        $this->assertArrayHasKey('search', $schema['properties']);
    }

    /**
     * Test input schema supports post_type parameter.
     *
     * @return void
     */
    public function test_input_schema_supports_post_type(): void
    {
        $ability = $this->getAbilityInstance();
        $schema  = $ability->getInputSchema();

        $this->assertArrayHasKey('post_type', $schema['properties']);
        $this->assertEquals('string', $schema['properties']['post_type']['type']);
        $this->assertEquals('post', $schema['properties']['post_type']['default']);
    }

    /**
     * Test output schema has expected structure.
     *
     * @return void
     */
    public function test_output_schema_structure(): void
    {
        $ability = $this->getAbilityInstance();
        $schema  = $ability->getOutputSchema();

        $this->assertEquals('object', $schema['type']);
        $this->assertArrayHasKey('posts', $schema['properties']);
        $this->assertArrayHasKey('total', $schema['properties']);
        $this->assertArrayHasKey('pages', $schema['properties']);
    }

    /**
     * Test execute returns posts array.
     *
     * @return void
     */
    public function test_execute_returns_posts_array(): void
    {
        // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited -- Test mock.
        $mock_post                = Mockery::mock('WP_Post');
        $mock_post->ID            = 1;
        $mock_post->post_title    = 'Test Post';
        $mock_post->post_excerpt  = 'Test excerpt';
        $mock_post->post_status   = 'publish';
        $mock_post->post_type     = 'post';
        $mock_post->post_author   = 1;
        $mock_post->post_date     = '2025-01-20 12:00:00';
        $mock_post->post_modified = '2025-01-20 14:00:00';
        $mock_post->post_name     = 'test-post';

        $mock_query                = Mockery::mock('WP_Query');
        $mock_query->posts         = array( $mock_post );
        $mock_query->found_posts   = 1;
        $mock_query->max_num_pages = 1;

        // format_results uses get_permalink and get_the_author_meta.
        Functions\expect('get_permalink')->andReturn('https://example.com/test-post/');
        Functions\expect('get_the_author_meta')->andReturn('John Doe');

        $ability = $this->getAbilityInstance();

        // Use reflection to test with mock WP_Query.
        $result = $this->execute_with_mock_query($ability, array(), $mock_query);

        $this->assertArrayHasKey('posts', $result);
        $this->assertIsArray($result['posts']);
        $this->assertArrayHasKey('total', $result);
        $this->assertArrayHasKey('pages', $result);
    }

    /**
     * Test execute with default pagination.
     *
     * @return void
     */
    public function test_execute_uses_default_pagination(): void
    {
        $mock_query                = Mockery::mock('WP_Query');
        $mock_query->posts         = array();
        $mock_query->found_posts   = 0;
        $mock_query->max_num_pages = 0;

        $ability = $this->getAbilityInstance();
        $result  = $this->execute_with_mock_query($ability, array(), $mock_query);

        $this->assertEquals(0, $result['total']);
        $this->assertEquals(0, $result['pages']);
    }

    /**
     * Test execute enforces max per_page limit.
     *
     * @return void
     */
    public function test_execute_enforces_max_per_page(): void
    {
        $ability = $this->getAbilityInstance();

        // Test that per_page is capped at 100.
        $input = array( 'per_page' => 500 );

        // We'll verify through the build_query_args pure function.
        $reflection = new \ReflectionClass($ability);
        $method     = $reflection->getMethod('buildQueryArgs');

        $args = $method->invoke($ability, $input);

        $this->assertLessThanOrEqual(100, $args['posts_per_page']);
    }

    /**
     * Test execute with status filter.
     *
     * @return void
     */
    public function test_execute_filters_by_status(): void
    {
        $ability = $this->getAbilityInstance();

        $reflection = new \ReflectionClass($ability);
        $method     = $reflection->getMethod('buildQueryArgs');

        $args = $method->invoke($ability, array( 'status' => 'draft' ));

        $this->assertEquals('draft', $args['post_status']);
    }

    /**
     * Test execute with author filter.
     *
     * @return void
     */
    public function test_execute_filters_by_author(): void
    {
        $ability = $this->getAbilityInstance();

        $reflection = new \ReflectionClass($ability);
        $method     = $reflection->getMethod('buildQueryArgs');

        $args = $method->invoke($ability, array( 'author' => 5 ));

        $this->assertEquals(5, $args['author']);
    }

    /**
     * Test execute with category filter.
     *
     * @return void
     */
    public function test_execute_filters_by_category(): void
    {
        $ability = $this->getAbilityInstance();

        $reflection = new \ReflectionClass($ability);
        $method     = $reflection->getMethod('buildQueryArgs');

        $args = $method->invoke($ability, array( 'category' => 3 ));

        $this->assertEquals(3, $args['cat']);
    }

    /**
     * Test execute with search filter.
     *
     * @return void
     */
    public function test_execute_filters_by_search(): void
    {
        $ability = $this->getAbilityInstance();

        $reflection = new \ReflectionClass($ability);
        $method     = $reflection->getMethod('buildQueryArgs');

        $args = $method->invoke($ability, array( 'search' => 'test query' ));

        $this->assertEquals('test query', $args['s']);
    }

    /**
     * Test execute returns pagination metadata.
     *
     * @return void
     */
    public function test_execute_returns_pagination_metadata(): void
    {
        $mock_query                = Mockery::mock('WP_Query');
        $mock_query->posts         = array();
        $mock_query->found_posts   = 50;
        $mock_query->max_num_pages = 5;

        $ability = $this->getAbilityInstance();
        $result  = $this->execute_with_mock_query(
            $ability,
            array(
                'page'     => 2,
                'per_page' => 10,
            ),
            $mock_query
        );

        $this->assertEquals(50, $result['total']);
        $this->assertEquals(5, $result['pages']);
        $this->assertEquals(2, $result['current_page']);
        $this->assertEquals(10, $result['per_page']);
    }

    /**
     * Test format_post_item is a pure function.
     *
     * @return void
     */
    public function test_format_post_item_is_pure(): void
    {
        // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited -- Test mock.
        $mock_post                = Mockery::mock('WP_Post');
        $mock_post->ID            = 1;
        $mock_post->post_title    = 'Test';
        $mock_post->post_excerpt  = 'Excerpt';
        $mock_post->post_status   = 'publish';
        $mock_post->post_type     = 'post';
        $mock_post->post_author   = 1;
        $mock_post->post_date     = '2025-01-20 12:00:00';
        $mock_post->post_modified = '2025-01-20 12:00:00';
        $mock_post->post_name     = 'test';

        Functions\stubs(
            array(
                'get_permalink'       => 'https://example.com/test/',
                'get_the_author_meta' => 'Author',
            )
        );

        $ability = $this->getAbilityInstance();

        $reflection = new \ReflectionClass($ability);
        $method     = $reflection->getMethod('formatPostItem');

        // Call twice with same input.
        $result1 = $method->invoke($ability, $mock_post);
        $result2 = $method->invoke($ability, $mock_post);

        // Pure function should return identical results.
        $this->assertEquals($result1, $result2);
    }

    /**
     * Test build_query_args is a pure function.
     *
     * @return void
     */
    public function test_build_query_args_is_pure(): void
    {
        $ability = $this->getAbilityInstance();

        $reflection = new \ReflectionClass($ability);
        $method     = $reflection->getMethod('buildQueryArgs');

        $input = array(
            'page'     => 2,
            'per_page' => 20,
            'status'   => 'publish',
        );

        // Call twice with same input.
        $result1 = $method->invoke($ability, $input);
        $result2 = $method->invoke($ability, $input);

        // Pure function should return identical results.
        $this->assertEquals($result1, $result2);
    }

    /**
     * Test execute with multiple filters combined.
     *
     * @return void
     */
    public function test_execute_with_combined_filters(): void
    {
        $ability = $this->getAbilityInstance();

        $reflection = new \ReflectionClass($ability);
        $method     = $reflection->getMethod('buildQueryArgs');

        $input = array(
            'status'   => 'draft',
            'author'   => 10,
            'category' => 5,
            'search'   => 'keyword',
            'page'     => 3,
            'per_page' => 25,
        );

        $args = $method->invoke($ability, $input);

        $this->assertEquals('draft', $args['post_status']);
        $this->assertEquals(10, $args['author']);
        $this->assertEquals(5, $args['cat']);
        $this->assertEquals('keyword', $args['s']);
        $this->assertEquals(3, $args['paged']);
        $this->assertEquals(25, $args['posts_per_page']);
    }

    /**
     * Test execute with orderby and order parameters.
     *
     * @return void
     */
    public function test_execute_with_ordering(): void
    {
        $ability = $this->getAbilityInstance();

        $reflection = new \ReflectionClass($ability);
        $method     = $reflection->getMethod('buildQueryArgs');

        $input = array(
            'orderby' => 'title',
            'order'   => 'ASC',
        );

        $args = $method->invoke($ability, $input);

        $this->assertEquals('title', $args['orderby']);
        $this->assertEquals('ASC', $args['order']);
    }

    /**
     * Test execute defaults to post type.
     *
     * @return void
     */
    public function test_execute_defaults_to_post_type(): void
    {
        $ability = $this->getAbilityInstance();

        $reflection = new \ReflectionClass($ability);
        $method     = $reflection->getMethod('buildQueryArgs');

        $args = $method->invoke($ability, array());

        $this->assertEquals('post', $args['post_type']);
    }

    /**
     * Test execute filters by post_type page.
     *
     * @return void
     */
    public function test_execute_filters_by_page_type(): void
    {
        $ability = $this->getAbilityInstance();

        $reflection = new \ReflectionClass($ability);
        $method     = $reflection->getMethod('buildQueryArgs');

        $args = $method->invoke($ability, array( 'post_type' => 'page' ));

        $this->assertEquals('page', $args['post_type']);
    }

    /**
     * Test execute supports custom post types.
     *
     * @return void
     */
    public function test_execute_supports_custom_post_types(): void
    {
        $ability = $this->getAbilityInstance();

        $reflection = new \ReflectionClass($ability);
        $method     = $reflection->getMethod('buildQueryArgs');

        $args = $method->invoke($ability, array( 'post_type' => 'custom_type' ));

        $this->assertEquals('custom_type', $args['post_type']);
    }

    /**
     * Helper to execute with mock WP_Query.
     *
     * @param ListPosts $ability   The ability instance.
     * @param array     $input     Input parameters.
     * @param object    $mock_query Mock WP_Query.
     * @return array
     */
    private function execute_with_mock_query(ListPosts $ability, array $input, object $mock_query): array
    {
        // Use reflection to call format_results directly with mock query.
        $reflection = new \ReflectionClass($ability);
        $method     = $reflection->getMethod('formatResults');

        return $method->invoke($ability, $mock_query, $input);
    }
}
