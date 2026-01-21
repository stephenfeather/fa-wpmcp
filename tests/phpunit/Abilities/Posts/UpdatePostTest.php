<?php
/**
 * Tests for UpdatePost ability.
 *
 * @package FAWpmcp\Tests\Abilities\Posts
 */

declare(strict_types=1);

namespace FAWpmcp\Tests\Abilities\Posts;

use FAWpmcp\Abilities\Posts\UpdatePost;
use FAWpmcp\Exceptions\PostNotFoundException;
use FAWpmcp\Exceptions\PostTypeMismatchException;
use FAWpmcp\Exceptions\PostUpdateException;
use Brain\Monkey;
use Brain\Monkey\Functions;
use Mockery;
use PHPUnit\Framework\TestCase;

/**
 * Test UpdatePost ability functionality.
 *
 * Tests cover:
 * - Updating existing posts
 * - Partial updates (only provided fields)
 * - Error handling for non-existent posts
 * - Input sanitization
 * - Category and tag updates
 *
 * @package FAWpmcp\Tests\Abilities\Posts
 */
class UpdatePostTest extends TestCase {
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
		$ability = new UpdatePost();
		$this->assertEquals( 'fa-wpmcp/update-post', $ability->getName() );
	}

	/**
	 * Test ability returns correct category.
	 *
	 * @return void
	 */
	public function test_get_category(): void {
		$ability = new UpdatePost();
		$this->assertEquals( 'posts-pages', $ability->getCategory() );
	}

	/**
	 * Test ability returns correct label.
	 *
	 * @return void
	 */
	public function test_get_label(): void {
		$ability = new UpdatePost();
		$this->assertEquals( 'Update Post', $ability->getLabel() );
	}

	/**
	 * Test ability returns correct description.
	 *
	 * @return void
	 */
	public function test_get_description(): void {
		$ability = new UpdatePost();
		$this->assertStringContainsString( 'update', strtolower( $ability->getDescription() ) );
	}

	/**
	 * Test ability returns write operation type.
	 *
	 * @return void
	 */
	public function test_get_operation_type(): void {
		$ability = new UpdatePost();
		$this->assertEquals( 'write', $ability->getOperationType() );
	}

	/**
	 * Test ability returns correct required capability.
	 *
	 * @return void
	 */
	public function test_get_required_capability(): void {
		$ability = new UpdatePost();
		$this->assertEquals( 'edit_posts', $ability->getRequiredCapability() );
	}

	/**
	 * Test input schema requires post_id.
	 *
	 * @return void
	 */
	public function test_input_schema_requires_post_id(): void {
		$ability = new UpdatePost();
		$schema  = $ability->getInputSchema();

		$this->assertEquals( 'object', $schema['type'] );
		$this->assertArrayHasKey( 'post_id', $schema['properties'] );
		$this->assertEquals( 'integer', $schema['properties']['post_id']['type'] );
		$this->assertContains( 'post_id', $schema['required'] );
	}

	/**
	 * Test input schema supports updatable fields.
	 *
	 * @return void
	 */
	public function test_input_schema_supports_updatable_fields(): void {
		$ability = new UpdatePost();
		$schema  = $ability->getInputSchema();

		$this->assertArrayHasKey( 'title', $schema['properties'] );
		$this->assertArrayHasKey( 'content', $schema['properties'] );
		$this->assertArrayHasKey( 'status', $schema['properties'] );
		$this->assertArrayHasKey( 'excerpt', $schema['properties'] );
		$this->assertArrayHasKey( 'categories', $schema['properties'] );
		$this->assertArrayHasKey( 'tags', $schema['properties'] );
	}

	/**
	 * Test input schema supports optional post_type parameter.
	 *
	 * @return void
	 */
	public function test_input_schema_supports_post_type(): void {
		$ability = new UpdatePost();
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
		$ability = new UpdatePost();
		$schema  = $ability->getOutputSchema();

		$this->assertEquals( 'object', $schema['type'] );
		$this->assertArrayHasKey( 'post_id', $schema['properties'] );
		$this->assertArrayHasKey( 'permalink', $schema['properties'] );
		$this->assertArrayHasKey( 'status', $schema['properties'] );
		$this->assertArrayHasKey( 'updated', $schema['properties'] );
	}

	/**
	 * Test execute verifies post exists before updating.
	 *
	 * @return void
	 */
	public function test_execute_verifies_post_exists(): void {
		Functions\expect( 'get_post' )
			->once()
			->with( 999 )
			->andReturn( null );

		$this->expectException( PostNotFoundException::class );
		$this->expectExceptionMessage( 'Post not found' );

		$ability = new UpdatePost();
		$ability->doExecute(
			array(
				'post_id' => 999,
				'title'   => 'Updated Title',
			)
		);
	}

	/**
	 * Test execute updates post successfully.
	 *
	 * @return void
	 */
	public function test_execute_updates_post(): void {
		// phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited -- Test mock.
		$mock_post = Mockery::mock( 'WP_Post' );
		$mock_post->ID = 42;
		$mock_post->post_title = 'Original Title';
		$mock_post->post_content = 'Original content';
		$mock_post->post_status = 'draft';

		Functions\expect( 'get_post' )
			->once()
			->with( 42 )
			->andReturn( $mock_post );

		Functions\expect( 'sanitize_text_field' )
			->once()
			->with( 'Updated Title' )
			->andReturnFirstArg();

		Functions\expect( 'wp_kses_post' )
			->once()
			->with( '<p>Updated content</p>' )
			->andReturnFirstArg();

		Functions\expect( 'wp_update_post' )
			->once()
			->with(
				Mockery::on(
					function ( $args ) {
						return 42 === $args['ID']
							&& 'Updated Title' === $args['post_title']
							&& '<p>Updated content</p>' === $args['post_content'];
					}
				),
				true
			)
			->andReturn( 42 );

		Functions\expect( 'is_wp_error' )
			->once()
			->with( 42 )
			->andReturn( false );

		Functions\expect( 'get_permalink' )
			->once()
			->with( 42 )
			->andReturn( 'https://example.com/updated-title/' );

		Functions\expect( 'get_post_status' )
			->once()
			->with( 42 )
			->andReturn( 'draft' );

		Functions\expect( 'get_edit_post_link' )
			->once()
			->with( 42, 'raw' )
			->andReturn( 'https://example.com/wp-admin/post.php?post=42&action=edit' );

		$ability = new UpdatePost();
		$result  = $ability->doExecute(
			array(
				'post_id' => 42,
				'title'   => 'Updated Title',
				'content' => '<p>Updated content</p>',
			)
		);

		$this->assertEquals( 42, $result['post_id'] );
		$this->assertTrue( $result['updated'] );
		$this->assertEquals( 'https://example.com/updated-title/', $result['permalink'] );
	}

	/**
	 * Test execute allows partial updates.
	 *
	 * @return void
	 */
	public function test_execute_allows_partial_updates(): void {
		// phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited -- Test mock.
		$mock_post = Mockery::mock( 'WP_Post' );
		$mock_post->ID = 1;
		$mock_post->post_title = 'Original Title';
		$mock_post->post_status = 'draft';

		Functions\expect( 'get_post' )->andReturn( $mock_post );
		Functions\expect( 'sanitize_text_field' )->andReturnFirstArg();

		// Only title should be updated.
		Functions\expect( 'wp_update_post' )
			->once()
			->with(
				Mockery::on(
					function ( $args ) {
						// Only ID and title should be in args, no content.
						return 1 === $args['ID']
							&& 'New Title' === $args['post_title']
							&& ! isset( $args['post_content'] );
					}
				),
				true
			)
			->andReturn( 1 );

		Functions\expect( 'is_wp_error' )->andReturn( false );
		Functions\expect( 'get_permalink' )->andReturn( 'https://example.com/new-title/' );
		Functions\expect( 'get_post_status' )->andReturn( 'draft' );
		Functions\expect( 'get_edit_post_link' )->andReturn( 'https://example.com/wp-admin/post.php?post=1&action=edit' );

		$ability = new UpdatePost();
		$result  = $ability->doExecute(
			array(
				'post_id' => 1,
				'title'   => 'New Title',
			)
		);

		$this->assertTrue( $result['updated'] );
	}

	/**
	 * Test execute updates status.
	 *
	 * @return void
	 */
	public function test_execute_updates_status(): void {
		// phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited -- Test mock.
		$mock_post = Mockery::mock( 'WP_Post' );
		$mock_post->ID = 1;
		$mock_post->post_status = 'draft';

		Functions\expect( 'get_post' )->andReturn( $mock_post );

		Functions\expect( 'wp_update_post' )
			->once()
			->with(
				Mockery::on(
					function ( $args ) {
						return 'publish' === $args['post_status'];
					}
				),
				true
			)
			->andReturn( 1 );

		Functions\expect( 'is_wp_error' )->andReturn( false );
		Functions\expect( 'get_permalink' )->andReturn( 'https://example.com/post/' );
		Functions\expect( 'get_post_status' )->andReturn( 'publish' );
		Functions\expect( 'get_edit_post_link' )->andReturn( 'https://example.com/wp-admin/post.php?post=1&action=edit' );

		$ability = new UpdatePost();
		$result  = $ability->doExecute(
			array(
				'post_id' => 1,
				'status'  => 'publish',
			)
		);

		$this->assertEquals( 'publish', $result['status'] );
	}

	/**
	 * Test execute sanitizes title.
	 *
	 * @return void
	 */
	public function test_execute_sanitizes_title(): void {
		// phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited -- Test mock.
		$mock_post = Mockery::mock( 'WP_Post' );
		$mock_post->ID = 1;

		Functions\expect( 'get_post' )->andReturn( $mock_post );

		Functions\expect( 'sanitize_text_field' )
			->once()
			->with( '<script>alert("xss")</script>Safe Title' )
			->andReturn( 'Safe Title' );

		Functions\expect( 'wp_update_post' )
			->once()
			->with(
				Mockery::on(
					function ( $args ) {
						return 'Safe Title' === $args['post_title'];
					}
				),
				true
			)
			->andReturn( 1 );

		Functions\expect( 'is_wp_error' )->andReturn( false );
		Functions\expect( 'get_permalink' )->andReturn( 'https://example.com/safe-title/' );
		Functions\expect( 'get_post_status' )->andReturn( 'draft' );
		Functions\expect( 'get_edit_post_link' )->andReturn( 'https://example.com/wp-admin/post.php?post=1&action=edit' );

		$ability = new UpdatePost();
		$result  = $ability->doExecute(
			array(
				'post_id' => 1,
				'title'   => '<script>alert("xss")</script>Safe Title',
			)
		);

		$this->assertTrue( $result['updated'] );
	}

	/**
	 * Test execute sanitizes content.
	 *
	 * @return void
	 */
	public function test_execute_sanitizes_content(): void {
		// phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited -- Test mock.
		$mock_post = Mockery::mock( 'WP_Post' );
		$mock_post->ID = 1;

		Functions\expect( 'get_post' )->andReturn( $mock_post );

		Functions\expect( 'wp_kses_post' )
			->once()
			->with( '<script>evil()</script><p>Safe</p>' )
			->andReturn( '<p>Safe</p>' );

		Functions\expect( 'wp_update_post' )
			->once()
			->with(
				Mockery::on(
					function ( $args ) {
						return '<p>Safe</p>' === $args['post_content'];
					}
				),
				true
			)
			->andReturn( 1 );

		Functions\expect( 'is_wp_error' )->andReturn( false );
		Functions\expect( 'get_permalink' )->andReturn( 'https://example.com/post/' );
		Functions\expect( 'get_post_status' )->andReturn( 'draft' );
		Functions\expect( 'get_edit_post_link' )->andReturn( 'https://example.com/wp-admin/post.php?post=1&action=edit' );

		$ability = new UpdatePost();
		$result  = $ability->doExecute(
			array(
				'post_id' => 1,
				'content' => '<script>evil()</script><p>Safe</p>',
			)
		);

		$this->assertTrue( $result['updated'] );
	}

	/**
	 * Test execute throws on wp_update_post error.
	 *
	 * @return void
	 */
	public function test_execute_throws_on_update_error(): void {
		// phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited -- Test mock.
		$mock_post = Mockery::mock( 'WP_Post' );
		$mock_post->ID = 1;

		Functions\expect( 'get_post' )->andReturn( $mock_post );
		Functions\expect( 'sanitize_text_field' )->andReturnFirstArg();

		$wp_error = Mockery::mock( 'WP_Error' );
		$wp_error->shouldReceive( 'get_error_message' )
			->once()
			->andReturn( 'Database error' );

		Functions\expect( 'wp_update_post' )
			->once()
			->andReturn( $wp_error );

		Functions\expect( 'is_wp_error' )
			->once()
			->with( $wp_error )
			->andReturn( true );

		$this->expectException( PostUpdateException::class );
		$this->expectExceptionMessage( 'Failed to update post' );

		$ability = new UpdatePost();
		$ability->doExecute(
			array(
				'post_id' => 1,
				'title'   => 'Updated',
			)
		);
	}

	/**
	 * Test execute updates categories.
	 *
	 * @return void
	 */
	public function test_execute_updates_categories(): void {
		// phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited -- Test mock.
		$mock_post = Mockery::mock( 'WP_Post' );
		$mock_post->ID = 1;

		Functions\expect( 'get_post' )->andReturn( $mock_post );

		Functions\expect( 'wp_update_post' )
			->once()
			->with(
				Mockery::on(
					function ( $args ) {
						return array( 5, 10, 15 ) === $args['post_category'];
					}
				),
				true
			)
			->andReturn( 1 );

		Functions\expect( 'is_wp_error' )->andReturn( false );
		Functions\expect( 'get_permalink' )->andReturn( 'https://example.com/post/' );
		Functions\expect( 'get_post_status' )->andReturn( 'draft' );
		Functions\expect( 'get_edit_post_link' )->andReturn( 'https://example.com/wp-admin/post.php?post=1&action=edit' );

		$ability = new UpdatePost();
		$result  = $ability->doExecute(
			array(
				'post_id'    => 1,
				'categories' => array( 5, 10, 15 ),
			)
		);

		$this->assertTrue( $result['updated'] );
	}

	/**
	 * Test execute updates tags.
	 *
	 * @return void
	 */
	public function test_execute_updates_tags(): void {
		// phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited -- Test mock.
		$mock_post = Mockery::mock( 'WP_Post' );
		$mock_post->ID = 1;

		Functions\expect( 'get_post' )->andReturn( $mock_post );

		Functions\expect( 'wp_update_post' )
			->once()
			->with(
				Mockery::on(
					function ( $args ) {
						return array( 'new-tag', 'updated' ) === $args['tags_input'];
					}
				),
				true
			)
			->andReturn( 1 );

		Functions\expect( 'is_wp_error' )->andReturn( false );
		Functions\expect( 'get_permalink' )->andReturn( 'https://example.com/post/' );
		Functions\expect( 'get_post_status' )->andReturn( 'draft' );
		Functions\expect( 'get_edit_post_link' )->andReturn( 'https://example.com/wp-admin/post.php?post=1&action=edit' );

		$ability = new UpdatePost();
		$result  = $ability->doExecute(
			array(
				'post_id' => 1,
				'tags'    => array( 'new-tag', 'updated' ),
			)
		);

		$this->assertTrue( $result['updated'] );
	}

	/**
	 * Test execute updates excerpt.
	 *
	 * @return void
	 */
	public function test_execute_updates_excerpt(): void {
		// phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited -- Test mock.
		$mock_post = Mockery::mock( 'WP_Post' );
		$mock_post->ID = 1;

		Functions\expect( 'get_post' )->andReturn( $mock_post );

		Functions\expect( 'sanitize_textarea_field' )
			->once()
			->with( 'New excerpt text' )
			->andReturnFirstArg();

		Functions\expect( 'wp_update_post' )
			->once()
			->with(
				Mockery::on(
					function ( $args ) {
						return 'New excerpt text' === $args['post_excerpt'];
					}
				),
				true
			)
			->andReturn( 1 );

		Functions\expect( 'is_wp_error' )->andReturn( false );
		Functions\expect( 'get_permalink' )->andReturn( 'https://example.com/post/' );
		Functions\expect( 'get_post_status' )->andReturn( 'draft' );
		Functions\expect( 'get_edit_post_link' )->andReturn( 'https://example.com/wp-admin/post.php?post=1&action=edit' );

		$ability = new UpdatePost();
		$result  = $ability->doExecute(
			array(
				'post_id' => 1,
				'excerpt' => 'New excerpt text',
			)
		);

		$this->assertTrue( $result['updated'] );
	}

	/**
	 * Test build_update_data is a pure function.
	 *
	 * @return void
	 */
	public function test_build_update_data_is_pure(): void {
		Functions\stubs(
			array(
				'sanitize_text_field' => fn( $v ) => $v,
				'wp_kses_post'        => fn( $v ) => $v,
			)
		);

		$ability = new UpdatePost();

		$reflection = new \ReflectionClass( $ability );
		$method     = $reflection->getMethod( 'buildUpdateData' );

		$input = array(
			'post_id' => 1,
			'title'   => 'Test',
			'content' => 'Content',
		);

		$result1 = $method->invoke( $ability, $input );
		$result2 = $method->invoke( $ability, $input );

		// Pure function should return identical results.
		$this->assertEquals( $result1['ID'], $result2['ID'] );
		$this->assertEquals( $result1['post_title'], $result2['post_title'] );
	}

	/**
	 * Test execute returns edit URL.
	 *
	 * @return void
	 */
	public function test_execute_returns_edit_url(): void {
		// phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited -- Test mock.
		$mock_post = Mockery::mock( 'WP_Post' );
		$mock_post->ID = 50;

		Functions\expect( 'get_post' )->andReturn( $mock_post );
		Functions\expect( 'sanitize_text_field' )->andReturnFirstArg();
		Functions\expect( 'wp_update_post' )->andReturn( 50 );
		Functions\expect( 'is_wp_error' )->andReturn( false );
		Functions\expect( 'get_permalink' )->andReturn( 'https://example.com/post/' );
		Functions\expect( 'get_post_status' )->andReturn( 'publish' );

		Functions\expect( 'get_edit_post_link' )
			->once()
			->with( 50, 'raw' )
			->andReturn( 'https://example.com/wp-admin/post.php?post=50&action=edit' );

		$ability = new UpdatePost();
		$result  = $ability->doExecute(
			array(
				'post_id' => 50,
				'title'   => 'Updated',
			)
		);

		$this->assertEquals( 'https://example.com/wp-admin/post.php?post=50&action=edit', $result['edit_url'] );
	}

	/**
	 * Test execute validates status values.
	 *
	 * @return void
	 */
	public function test_execute_validates_status(): void {
		// phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited -- Test mock.
		$mock_post = Mockery::mock( 'WP_Post' );
		$mock_post->ID = 1;
		$mock_post->post_status = 'draft';

		Functions\expect( 'get_post' )->andReturn( $mock_post );

		// Invalid status should be ignored, not included in update.
		Functions\expect( 'wp_update_post' )
			->once()
			->with(
				Mockery::on(
					function ( $args ) {
						// Invalid status should not be passed through.
						return ! isset( $args['post_status'] ) || in_array( $args['post_status'], array( 'publish', 'draft', 'pending', 'private', 'future', 'trash' ), true );
					}
				),
				true
			)
			->andReturn( 1 );

		Functions\expect( 'is_wp_error' )->andReturn( false );
		Functions\expect( 'get_permalink' )->andReturn( 'https://example.com/post/' );
		Functions\expect( 'get_post_status' )->andReturn( 'draft' );
		Functions\expect( 'get_edit_post_link' )->andReturn( 'https://example.com/wp-admin/post.php?post=1&action=edit' );

		$ability = new UpdatePost();
		$result  = $ability->doExecute(
			array(
				'post_id' => 1,
				'status'  => 'invalid_status',
			)
		);

		$this->assertTrue( $result['updated'] );
	}

	/**
	 * Test execute accepts page post type.
	 *
	 * @return void
	 */
	public function test_execute_accepts_page_post_type(): void {
		// phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited -- Test mock.
		$mock_post = Mockery::mock( 'WP_Post' );
		$mock_post->ID = 10;
		$mock_post->post_type = 'page';

		Functions\expect( 'get_post' )->andReturn( $mock_post );
		Functions\expect( 'sanitize_text_field' )->andReturnFirstArg();
		Functions\expect( 'wp_update_post' )->andReturn( 10 );
		Functions\expect( 'is_wp_error' )->andReturn( false );
		Functions\expect( 'get_permalink' )->andReturn( 'https://example.com/test-page/' );
		Functions\expect( 'get_post_status' )->andReturn( 'publish' );
		Functions\expect( 'get_edit_post_link' )->andReturn( 'https://example.com/wp-admin/post.php?post=10&action=edit' );

		$ability = new UpdatePost();
		$result  = $ability->doExecute(
			array(
				'post_id'   => 10,
				'title'     => 'Updated Page',
				'post_type' => 'page',
			)
		);

		$this->assertTrue( $result['updated'] );
	}

	/**
	 * Test execute throws exception when post_type mismatch.
	 *
	 * @return void
	 */
	public function test_execute_throws_when_post_type_mismatch(): void {
		// phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited -- Test mock.
		$mock_post = Mockery::mock( 'WP_Post' );
		$mock_post->ID = 10;
		$mock_post->post_type = 'page';

		Functions\expect( 'get_post' )->andReturn( $mock_post );

		$this->expectException( PostTypeMismatchException::class );
		$this->expectExceptionMessage( 'Post type mismatch' );

		$ability = new UpdatePost();
		$ability->doExecute(
			array(
				'post_id'   => 10,
				'title'     => 'Updated',
				'post_type' => 'post',
			)
		);
	}
}
