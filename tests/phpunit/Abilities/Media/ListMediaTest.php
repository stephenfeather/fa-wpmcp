<?php

/**
 * Tests for ListMedia ability.
 *
 * @package FAWpmcp\Tests\Abilities\Media
 */

declare(strict_types=1);

namespace FAWpmcp\Tests\Abilities\Media;

use FAWpmcp\Abilities\AbstractAbility;
use FAWpmcp\Abilities\Media\ListMedia;
use FAWpmcp\Tests\TestCase\AbilityTestTrait;
use FAWpmcp\Tests\TestCase\BrainMonkeyTestCase;
use Brain\Monkey\Functions;
use Mockery;

/**
 * Test ListMedia ability functionality.
 *
 * @package FAWpmcp\Tests\Abilities\Media
 */
class ListMediaTest extends BrainMonkeyTestCase {

	use AbilityTestTrait;

	protected function getAbilityInstance(): AbstractAbility {
		return new ListMedia();
	}

	protected function getExpectedMetadata(): array {
		return [
			'name'                 => 'fa-wpmcp/list-media',
			'category'             => 'media',
			'label'                => 'List Media',
			'description_contains' => 'paginated',
			'required_capability'  => 'upload_files',
			'operation_type'       => 'read',
		];
	}

	/**
	 * Test ability returns input schema.
	 *
	 * @return void
	 */
	public function testGetInputSchema(): void {
		$ability = $this->getAbilityInstance();
		$schema  = $ability->getInputSchema();

		$this->assertIsArray( $schema );
		$this->assertArrayHasKey( 'type', $schema );
		$this->assertArrayHasKey( 'properties', $schema );
		$this->assertArrayHasKey( 'page', $schema['properties'] );
		$this->assertArrayHasKey( 'per_page', $schema['properties'] );
	}

	/**
	 * Test ability returns output schema.
	 *
	 * @return void
	 */
	public function testGetOutputSchema(): void {
		$ability = $this->getAbilityInstance();
		$schema  = $ability->getOutputSchema();

		$this->assertIsArray( $schema );
		$this->assertArrayHasKey( 'type', $schema );
		$this->assertArrayHasKey( 'properties', $schema );
		$this->assertArrayHasKey( 'media', $schema['properties'] );
	}

	/**
	 * Test execute returns media list.
	 *
	 * @return void
	 */
	public function testExecuteReturnsMediaList(): void {
		$ability = $this->getAbilityInstance();

		// Mock WP_Query.
		$mock_query              = Mockery::mock( 'WP_Query' );
		$mock_query->posts       = array( $this->createMockAttachment( 1 ) );
		$mock_query->found_posts = 1;
		$mock_query->max_num_pages = 1;

		// Mock WordPress functions.
		Functions\when( 'wp_reset_postdata' )->justReturn( null );
		Functions\when( 'wp_get_attachment_metadata' )->justReturn( array() );
		Functions\when( 'get_attached_file' )->justReturn( 'file.jpg' );
		Functions\when( 'wp_get_attachment_url' )->justReturn( 'https://example.com/file.jpg' );
		Functions\when( 'get_post_meta' )->justReturn( '' );
		Functions\when( 'get_the_author_meta' )->justReturn( 'Test Author' );

		// Use reflection to inject mock query.
		$reflection = new \ReflectionClass( $ability );
		$method     = $reflection->getMethod( 'formatResults' );

		$result = $method->invoke( $ability, $mock_query, array( 'page' => 1 ) );

		$this->assertIsArray( $result );
		$this->assertArrayHasKey( 'media', $result );
		$this->assertArrayHasKey( 'total', $result );
		$this->assertCount( 1, $result['media'] );
	}

	/**
	 * Test buildQueryArgs applies filters and caps per_page.
	 *
	 * @return void
	 */
	public function testBuildQueryArgsAppliesFiltersAndCapsPerPage(): void {
		$ability = $this->getAbilityInstance();

		$reflection = new \ReflectionClass( $ability );
		$method     = $reflection->getMethod( 'buildQueryArgs' );

		$args = $method->invoke(
			$ability,
			array(
				'page'      => 3,
				'per_page'  => 500,
				'mime_type' => 'image/jpeg',
				'search'    => 'logo',
				'orderby'   => 'title',
				'order'     => 'ASC',
			)
		);

		$this->assertSame( 3, $args['paged'] );
		$this->assertSame( 100, $args['posts_per_page'] );
		$this->assertSame( 'image/jpeg', $args['post_mime_type'] );
		$this->assertSame( 'logo', $args['s'] );
		$this->assertSame( 'title', $args['orderby'] );
		$this->assertSame( 'ASC', $args['order'] );
	}

	/**
	 * Create a mock attachment post.
	 *
	 * @param int $id Attachment ID.
	 * @return \WP_Post Mock WP_Post object.
	 */
	private function createMockAttachment( int $id ): \WP_Post {
		$post = Mockery::mock( \WP_Post::class );
		$post->ID             = $id;
		$post->post_title     = 'Test Image';
		$post->post_mime_type = 'image/jpeg';
		$post->post_date      = '2024-01-01 00:00:00';
		$post->post_modified  = '2024-01-01 00:00:00';
		$post->post_author    = 1;
		$post->post_excerpt   = 'Test caption';
		$post->post_content   = 'Test description';
		return $post;
	}
}
