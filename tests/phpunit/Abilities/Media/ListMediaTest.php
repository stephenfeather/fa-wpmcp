<?php
/**
 * Tests for ListMedia ability.
 *
 * @package FAWpmcp\Tests\Abilities\Media
 */

declare(strict_types=1);

namespace FAWpmcp\Tests\Abilities\Media;

use FAWpmcp\Abilities\Media\ListMedia;
use Brain\Monkey;
use Brain\Monkey\Functions;
use Mockery;
use PHPUnit\Framework\TestCase;

/**
 * Test ListMedia ability functionality.
 *
 * @package FAWpmcp\Tests\Abilities\Media
 */
class ListMediaTest extends TestCase {
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
		$ability = new ListMedia();
		$this->assertEquals( 'fa-wpmcp/list-media', $ability->getName() );
	}

	/**
	 * Test ability returns correct category.
	 *
	 * @return void
	 */
	public function testGetCategory(): void {
		$ability = new ListMedia();
		$this->assertEquals( 'media', $ability->getCategory() );
	}

	/**
	 * Test ability returns correct label.
	 *
	 * @return void
	 */
	public function testGetLabel(): void {
		$ability = new ListMedia();
		$this->assertEquals( 'List Media', $ability->getLabel() );
	}

	/**
	 * Test ability returns correct operation type.
	 *
	 * @return void
	 */
	public function testGetOperationType(): void {
		$ability = new ListMedia();
		$this->assertEquals( 'read', $ability->getOperationType() );
	}

	/**
	 * Test ability returns correct required capability.
	 *
	 * @return void
	 */
	public function testGetRequiredCapability(): void {
		$ability = new ListMedia();
		$this->assertEquals( 'upload_files', $ability->getRequiredCapability() );
	}

	/**
	 * Test ability returns input schema.
	 *
	 * @return void
	 */
	public function testGetInputSchema(): void {
		$ability = new ListMedia();
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
		$ability = new ListMedia();
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
		$ability = new ListMedia();

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
		$method->setAccessible( true );

		$result = $method->invoke( $ability, $mock_query, array( 'page' => 1 ) );

		$this->assertIsArray( $result );
		$this->assertArrayHasKey( 'media', $result );
		$this->assertArrayHasKey( 'total', $result );
		$this->assertCount( 1, $result['media'] );
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
