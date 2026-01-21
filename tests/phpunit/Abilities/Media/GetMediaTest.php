<?php
/**
 * Tests for GetMedia ability.
 *
 * @package FAWpmcp\Tests\Abilities\Media
 */

declare(strict_types=1);

namespace FAWpmcp\Tests\Abilities\Media;

use FAWpmcp\Abilities\Media\GetMedia;
use FAWpmcp\Exceptions\PostNotFoundException;
use FAWpmcp\Exceptions\PostTypeMismatchException;
use Brain\Monkey;
use Brain\Monkey\Functions;
use Mockery;
use PHPUnit\Framework\TestCase;

/**
 * Test GetMedia ability functionality.
 *
 * @package FAWpmcp\Tests\Abilities\Media
 */
class GetMediaTest extends TestCase {
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
		$ability = new GetMedia();
		$this->assertEquals( 'fa-wpmcp/get-media', $ability->getName() );
	}

	/**
	 * Test ability returns correct category.
	 *
	 * @return void
	 */
	public function testGetCategory(): void {
		$ability = new GetMedia();
		$this->assertEquals( 'media', $ability->getCategory() );
	}

	/**
	 * Test ability returns correct label.
	 *
	 * @return void
	 */
	public function testGetLabel(): void {
		$ability = new GetMedia();
		$this->assertEquals( 'Get Media', $ability->getLabel() );
	}

	/**
	 * Test ability returns correct operation type.
	 *
	 * @return void
	 */
	public function testGetOperationType(): void {
		$ability = new GetMedia();
		$this->assertEquals( 'read', $ability->getOperationType() );
	}

	/**
	 * Test ability returns correct required capability.
	 *
	 * @return void
	 */
	public function testGetRequiredCapability(): void {
		$ability = new GetMedia();
		$this->assertEquals( 'upload_files', $ability->getRequiredCapability() );
	}

	/**
	 * Test execute throws exception for non-existent media.
	 *
	 * @return void
	 */
	public function testExecuteThrowsExceptionForNonExistentMedia(): void {
		$ability = new GetMedia();

		Functions\when( 'get_post' )->justReturn( null );

		$this->expectException( PostNotFoundException::class );
		$ability->doExecute( array( 'media_id' => 999 ) );
	}

	/**
	 * Test execute throws exception for non-attachment post.
	 *
	 * @return void
	 */
	public function testExecuteThrowsExceptionForNonAttachment(): void {
		$ability = new GetMedia();

		$mock_post            = new \stdClass();
		$mock_post->ID        = 1;
		$mock_post->post_type = 'post';

		Functions\when( 'get_post' )->justReturn( $mock_post );

		$this->expectException( PostTypeMismatchException::class );
		$ability->doExecute( array( 'media_id' => 1 ) );
	}

	/**
	 * Test execute returns media data.
	 *
	 * @return void
	 */
	public function testExecuteReturnsMediaData(): void {
		$ability = new GetMedia();

		$mock_post                   = Mockery::mock( \WP_Post::class );
		$mock_post->ID               = 1;
		$mock_post->post_type        = 'attachment';
		$mock_post->post_title       = 'Test Image';
		$mock_post->post_mime_type   = 'image/jpeg';
		$mock_post->post_date        = '2024-01-01 00:00:00';
		$mock_post->post_modified    = '2024-01-01 00:00:00';
		$mock_post->post_author      = 1;
		$mock_post->post_excerpt     = 'Test caption';
		$mock_post->post_content     = 'Test description';

		Functions\when( 'get_post' )->justReturn( $mock_post );
		Functions\when( 'wp_get_attachment_metadata' )->justReturn( array( 'width' => 800, 'height' => 600 ) );
		Functions\when( 'get_attached_file' )->justReturn( 'file.jpg' );
		Functions\when( 'wp_get_attachment_url' )->justReturn( 'https://example.com/file.jpg' );
		Functions\when( 'get_post_meta' )->justReturn( array() );
		Functions\when( 'get_the_author_meta' )->justReturn( 'Test Author' );
		Functions\when( 'wp_get_attachment_image_src' )->justReturn( false );

		$result = $ability->doExecute( array( 'media_id' => 1 ) );

		$this->assertIsArray( $result );
		$this->assertArrayHasKey( 'media', $result );
		$this->assertEquals( 1, $result['media']['id'] );
		$this->assertEquals( 'Test Image', $result['media']['title'] );
	}
}
