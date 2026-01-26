<?php

/**
 * Tests for GetPost ability.
 *
 * @package FAWpmcp\Tests\Abilities\Posts
 */

declare(strict_types=1);

namespace FAWpmcp\Tests\Abilities\Posts;

use FAWpmcp\Abilities\AbstractAbility;
use FAWpmcp\Abilities\Posts\GetPost;
use FAWpmcp\Exceptions\PostNotFoundException;
use FAWpmcp\Exceptions\PostTypeMismatchException;
use FAWpmcp\Tests\TestCase\AbilityTestTrait;
use FAWpmcp\Tests\TestCase\BrainMonkeyTestCase;
use Brain\Monkey\Functions;
use Mockery;

/**
 * Test GetPost ability functionality.
 *
 * Tests cover:
 * - Retrieving a post by ID
 * - Returning full post data with meta, categories, tags
 * - Error handling for non-existent posts
 * - Metadata structure and formatting
 *
 * @package FAWpmcp\Tests\Abilities\Posts
 */
class GetPostTest extends BrainMonkeyTestCase {

	use AbilityTestTrait;

	/**
	 * Get an instance of the ability being tested.
	 *
	 * @return AbstractAbility
	 */
	protected function getAbilityInstance(): AbstractAbility {
		return new GetPost();
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
			'name'                  => 'fa-wpmcp/get-post',
			'category'              => 'posts-pages',
			'label'                 => 'Get Post',
			'description_contains'  => 'post',
			'operation_type'        => 'read',
			'required_capability'   => 'read',
		);
	}

	/**
	 * Test input schema requires post_id.
	 *
	 * @return void
	 */
	public function test_input_schema_requires_post_id(): void {
		$ability = $this->getAbilityInstance();
		$schema  = $ability->getInputSchema();

		$this->assertEquals( 'object', $schema['type'] );
		$this->assertArrayHasKey( 'post_id', $schema['properties'] );
		$this->assertEquals( 'integer', $schema['properties']['post_id']['type'] );
		$this->assertContains( 'post_id', $schema['required'] );
	}

	/**
	 * Test input schema supports optional post_type parameter.
	 *
	 * @return void
	 */
	public function test_input_schema_supports_post_type(): void {
		$ability = $this->getAbilityInstance();
		$schema  = $ability->getInputSchema();

		$this->assertArrayHasKey( 'post_type', $schema['properties'] );
		$this->assertEquals( 'string', $schema['properties']['post_type']['type'] );
		$this->assertNotContains( 'post_type', $schema['required'] ?? array() );
	}

	/**
	 * Test output schema has expected structure.
	 *
	 * @return void
	 */
	public function test_output_schema_structure(): void {
		$ability = $this->getAbilityInstance();
		$schema  = $ability->getOutputSchema();

		$this->assertEquals( 'object', $schema['type'] );
		$this->assertArrayHasKey( 'post', $schema['properties'] );
	}

	/**
	 * Test execute returns post data for existing post.
	 *
	 * @return void
	 */
	public function test_execute_returns_post_data(): void {
		// phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited -- Test mock.
		$mock_post                = Mockery::mock( 'WP_Post' );
		$mock_post->ID            = 42;
		$mock_post->post_title    = 'Test Post Title';
		$mock_post->post_content  = '<p>Test content</p>';
		$mock_post->post_excerpt  = 'Test excerpt';
		$mock_post->post_status   = 'publish';
		$mock_post->post_type     = 'post';
		$mock_post->post_author   = 1;
		$mock_post->post_date     = '2025-01-20 12:00:00';
		$mock_post->post_modified = '2025-01-20 14:00:00';
		$mock_post->post_name     = 'test-post-title';

		Functions\expect( 'get_post' )
			->once()
			->with( 42 )
			->andReturn( $mock_post );

		Functions\expect( 'get_permalink' )
			->once()
			->with( 42 )
			->andReturn( 'https://example.com/test-post-title/' );

		Functions\expect( 'get_edit_post_link' )
			->once()
			->with( 42, 'raw' )
			->andReturn( 'https://example.com/wp-admin/post.php?post=42&action=edit' );

		Functions\expect( 'get_post_meta' )
			->once()
			->with( 42, '', true )
			->andReturn( array( '_thumbnail_id' => array( '100' ) ) );

		Functions\expect( 'get_the_post_thumbnail_url' )
			->once()
			->with( 42, 'full' )
			->andReturn( 'https://example.com/image.jpg' );

		Functions\expect( 'wp_get_post_categories' )
			->once()
			->with( 42, array( 'fields' => 'all' ) )
			->andReturn(
				array(
					(object) array(
						'term_id' => 1,
						'name'    => 'Uncategorized',
						'slug'    => 'uncategorized',
					),
				)
			);

		Functions\expect( 'wp_get_post_tags' )
			->once()
			->with( 42, array( 'fields' => 'all' ) )
			->andReturn(
				array(
					(object) array(
						'term_id' => 2,
						'name'    => 'test-tag',
						'slug'    => 'test-tag',
					),
				)
			);

		Functions\expect( 'get_the_author_meta' )
			->once()
			->with( 'display_name', 1 )
			->andReturn( 'John Doe' );

		$ability = $this->getAbilityInstance();
		$result  = $ability->doExecute( array( 'post_id' => 42 ) );

		$this->assertArrayHasKey( 'post', $result );
		$this->assertEquals( 42, $result['post']['id'] );
		$this->assertEquals( 'Test Post Title', $result['post']['title'] );
		$this->assertEquals( '<p>Test content</p>', $result['post']['content'] );
		$this->assertEquals( 'publish', $result['post']['status'] );
		$this->assertEquals( 'https://example.com/test-post-title/', $result['post']['permalink'] );
	}

	/**
	 * Test execute throws exception for non-existent post.
	 *
	 * @return void
	 */
	public function test_execute_throws_for_nonexistent_post(): void {
		Functions\expect( 'get_post' )
			->once()
			->with( 9999 )
			->andReturn( null );

		$this->expectException( PostNotFoundException::class );
		$this->expectExceptionMessage( 'Post not found' );

		$ability = $this->getAbilityInstance();
		$ability->doExecute( array( 'post_id' => 9999 ) );
	}

	/**
	 * Test execute returns categories.
	 *
	 * @return void
	 */
	public function test_execute_returns_categories(): void {
		// phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited -- Test mock.
		$mock_post                = Mockery::mock( 'WP_Post' );
		$mock_post->ID            = 1;
		$mock_post->post_title    = 'Test';
		$mock_post->post_content  = 'Content';
		$mock_post->post_excerpt  = '';
		$mock_post->post_status   = 'publish';
		$mock_post->post_type     = 'post';
		$mock_post->post_author   = 1;
		$mock_post->post_date     = '2025-01-20 12:00:00';
		$mock_post->post_modified = '2025-01-20 12:00:00';
		$mock_post->post_name     = 'test';

		Functions\expect( 'get_post' )->andReturn( $mock_post );
		Functions\expect( 'get_permalink' )->andReturn( 'https://example.com/test/' );
		Functions\expect( 'get_edit_post_link' )->andReturn( 'https://example.com/wp-admin/post.php?post=1&action=edit' );
		Functions\expect( 'get_post_meta' )->andReturn( array() );
		Functions\expect( 'get_the_post_thumbnail_url' )->andReturn( '' );
		Functions\expect( 'get_the_author_meta' )->andReturn( 'Author' );

		Functions\expect( 'wp_get_post_categories' )
			->once()
			->andReturn(
				array(
					(object) array(
						'term_id' => 1,
						'name'    => 'News',
						'slug'    => 'news',
					),
					(object) array(
						'term_id' => 2,
						'name'    => 'Tech',
						'slug'    => 'tech',
					),
				)
			);

		Functions\expect( 'wp_get_post_tags' )->andReturn( array() );

		$ability = $this->getAbilityInstance();
		$result  = $ability->doExecute( array( 'post_id' => 1 ) );

		$this->assertArrayHasKey( 'categories', $result['post'] );
		$this->assertCount( 2, $result['post']['categories'] );
		$this->assertEquals( 'News', $result['post']['categories'][0]['name'] );
		$this->assertEquals( 'Tech', $result['post']['categories'][1]['name'] );
	}

	/**
	 * Test execute returns tags.
	 *
	 * @return void
	 */
	public function test_execute_returns_tags(): void {
		// phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited -- Test mock.
		$mock_post                = Mockery::mock( 'WP_Post' );
		$mock_post->ID            = 1;
		$mock_post->post_title    = 'Test';
		$mock_post->post_content  = 'Content';
		$mock_post->post_excerpt  = '';
		$mock_post->post_status   = 'publish';
		$mock_post->post_type     = 'post';
		$mock_post->post_author   = 1;
		$mock_post->post_date     = '2025-01-20 12:00:00';
		$mock_post->post_modified = '2025-01-20 12:00:00';
		$mock_post->post_name     = 'test';

		Functions\expect( 'get_post' )->andReturn( $mock_post );
		Functions\expect( 'get_permalink' )->andReturn( 'https://example.com/test/' );
		Functions\expect( 'get_edit_post_link' )->andReturn( 'https://example.com/wp-admin/post.php?post=1&action=edit' );
		Functions\expect( 'get_post_meta' )->andReturn( array() );
		Functions\expect( 'get_the_post_thumbnail_url' )->andReturn( '' );
		Functions\expect( 'get_the_author_meta' )->andReturn( 'Author' );
		Functions\expect( 'wp_get_post_categories' )->andReturn( array() );

		Functions\expect( 'wp_get_post_tags' )
			->once()
			->andReturn(
				array(
					(object) array(
						'term_id' => 10,
						'name'    => 'WordPress',
						'slug'    => 'wordpress',
					),
					(object) array(
						'term_id' => 11,
						'name'    => 'PHP',
						'slug'    => 'php',
					),
				)
			);

		$ability = $this->getAbilityInstance();
		$result  = $ability->doExecute( array( 'post_id' => 1 ) );

		$this->assertArrayHasKey( 'tags', $result['post'] );
		$this->assertCount( 2, $result['post']['tags'] );
		$this->assertEquals( 'WordPress', $result['post']['tags'][0]['name'] );
		$this->assertEquals( 'PHP', $result['post']['tags'][1]['name'] );
	}

	/**
	 * Test execute returns featured image URL.
	 *
	 * @return void
	 */
	public function test_execute_returns_featured_image(): void {
		// phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited -- Test mock.
		$mock_post                = Mockery::mock( 'WP_Post' );
		$mock_post->ID            = 1;
		$mock_post->post_title    = 'Test';
		$mock_post->post_content  = 'Content';
		$mock_post->post_excerpt  = '';
		$mock_post->post_status   = 'publish';
		$mock_post->post_type     = 'post';
		$mock_post->post_author   = 1;
		$mock_post->post_date     = '2025-01-20 12:00:00';
		$mock_post->post_modified = '2025-01-20 12:00:00';
		$mock_post->post_name     = 'test';

		Functions\expect( 'get_post' )->andReturn( $mock_post );
		Functions\expect( 'get_permalink' )->andReturn( 'https://example.com/test/' );
		Functions\expect( 'get_edit_post_link' )->andReturn( 'https://example.com/wp-admin/post.php?post=1&action=edit' );
		Functions\expect( 'get_post_meta' )->andReturn( array( '_thumbnail_id' => array( '50' ) ) );
		Functions\expect( 'wp_get_post_categories' )->andReturn( array() );
		Functions\expect( 'wp_get_post_tags' )->andReturn( array() );
		Functions\expect( 'get_the_author_meta' )->andReturn( 'Author' );

		Functions\expect( 'get_the_post_thumbnail_url' )
			->once()
			->with( 1, 'full' )
			->andReturn( 'https://example.com/uploads/featured-image.jpg' );

		$ability = $this->getAbilityInstance();
		$result  = $ability->doExecute( array( 'post_id' => 1 ) );

		$this->assertEquals( 'https://example.com/uploads/featured-image.jpg', $result['post']['featured_image'] );
	}

	/**
	 * Test execute returns author information.
	 *
	 * @return void
	 */
	public function test_execute_returns_author_info(): void {
		// phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited -- Test mock.
		$mock_post                = Mockery::mock( 'WP_Post' );
		$mock_post->ID            = 1;
		$mock_post->post_title    = 'Test';
		$mock_post->post_content  = 'Content';
		$mock_post->post_excerpt  = '';
		$mock_post->post_status   = 'publish';
		$mock_post->post_type     = 'post';
		$mock_post->post_author   = 5;
		$mock_post->post_date     = '2025-01-20 12:00:00';
		$mock_post->post_modified = '2025-01-20 12:00:00';
		$mock_post->post_name     = 'test';

		Functions\expect( 'get_post' )->andReturn( $mock_post );
		Functions\expect( 'get_permalink' )->andReturn( 'https://example.com/test/' );
		Functions\expect( 'get_edit_post_link' )->andReturn( 'https://example.com/wp-admin/post.php?post=1&action=edit' );
		Functions\expect( 'get_post_meta' )->andReturn( array() );
		Functions\expect( 'get_the_post_thumbnail_url' )->andReturn( '' );
		Functions\expect( 'wp_get_post_categories' )->andReturn( array() );
		Functions\expect( 'wp_get_post_tags' )->andReturn( array() );

		Functions\expect( 'get_the_author_meta' )
			->once()
			->with( 'display_name', 5 )
			->andReturn( 'Jane Smith' );

		$ability = $this->getAbilityInstance();
		$result  = $ability->doExecute( array( 'post_id' => 1 ) );

		$this->assertEquals( 5, $result['post']['author']['id'] );
		$this->assertEquals( 'Jane Smith', $result['post']['author']['name'] );
	}

	/**
	 * Test execute accepts page post type.
	 *
	 * @return void
	 */
	public function test_execute_accepts_page_post_type(): void {
		// phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited -- Test mock.
		$mock_post                = Mockery::mock( 'WP_Post' );
		$mock_post->ID            = 10;
		$mock_post->post_title    = 'Test Page';
		$mock_post->post_content  = 'Page content';
		$mock_post->post_excerpt  = '';
		$mock_post->post_status   = 'publish';
		$mock_post->post_type     = 'page';
		$mock_post->post_author   = 1;
		$mock_post->post_date     = '2025-01-20 12:00:00';
		$mock_post->post_modified = '2025-01-20 12:00:00';
		$mock_post->post_name     = 'test-page';

		Functions\expect( 'get_post' )->andReturn( $mock_post );
		Functions\expect( 'get_permalink' )->andReturn( 'https://example.com/test-page/' );
		Functions\expect( 'get_edit_post_link' )->andReturn( 'https://example.com/wp-admin/post.php?post=10&action=edit' );
		Functions\expect( 'get_post_meta' )->andReturn( array() );
		Functions\expect( 'get_the_post_thumbnail_url' )->andReturn( '' );
		Functions\expect( 'wp_get_post_categories' )->andReturn( array() );
		Functions\expect( 'wp_get_post_tags' )->andReturn( array() );
		Functions\expect( 'get_the_author_meta' )->andReturn( 'Author' );

		$ability = $this->getAbilityInstance();
		$result  = $ability->doExecute(
			array(
				'post_id'   => 10,
				'post_type' => 'page',
			)
		);

		$this->assertEquals( 10, $result['post']['id'] );
		$this->assertEquals( 'page', $result['post']['type'] );
	}

	/**
	 * Test execute throws exception when post_type mismatch.
	 *
	 * @return void
	 */
	public function test_execute_throws_when_post_type_mismatch(): void {
		// phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited -- Test mock.
		$mock_post            = Mockery::mock( 'WP_Post' );
		$mock_post->ID        = 10;
		$mock_post->post_type = 'page';

		Functions\expect( 'get_post' )->andReturn( $mock_post );

		$this->expectException( PostTypeMismatchException::class );
		$this->expectExceptionMessage( 'Post type mismatch' );

		$ability = $this->getAbilityInstance();
		$ability->doExecute(
			array(
				'post_id'   => 10,
				'post_type' => 'post',
			)
		);
	}
}
