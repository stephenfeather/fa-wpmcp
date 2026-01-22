<?php
/**
 * Tests for UpdateMedia ability.
 *
 * @package FAWpmcp\Tests\Abilities\Media
 */

declare(strict_types=1);

namespace FAWpmcp\Tests\Abilities\Media;

use FAWpmcp\Abilities\Media\UpdateMedia;
use FAWpmcp\Exceptions\PostNotFoundException;
use FAWpmcp\Exceptions\PostTypeMismatchException;
use FAWpmcp\Exceptions\PostUpdateException;
use Brain\Monkey;
use Brain\Monkey\Functions;
use Mockery;
use PHPUnit\Framework\TestCase;

/**
 * Test UpdateMedia ability functionality.
 *
 * @package FAWpmcp\Tests\Abilities\Media
 */
class UpdateMediaTest extends TestCase {
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
		$ability = new UpdateMedia();
		$this->assertEquals( 'fa-wpmcp/update-media', $ability->getName() );
	}

	/**
	 * Test ability returns correct category.
	 *
	 * @return void
	 */
	public function testGetCategory(): void {
		$ability = new UpdateMedia();
		$this->assertEquals( 'media', $ability->getCategory() );
	}

	/**
	 * Test ability returns correct operation type.
	 *
	 * @return void
	 */
	public function testGetOperationType(): void {
		$ability = new UpdateMedia();
		$this->assertEquals( 'write', $ability->getOperationType() );
	}

	/**
	 * Test execute throws exception for non-existent media.
	 *
	 * @return void
	 */
	public function testExecuteThrowsExceptionForNonExistentMedia(): void {
		$ability = new UpdateMedia();

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
		$ability = new UpdateMedia();

		$mock_post            = new \stdClass();
		$mock_post->ID        = 1;
		$mock_post->post_type = 'post';

		Functions\when( 'get_post' )->justReturn( $mock_post );

		$this->expectException( PostTypeMismatchException::class );
		$ability->doExecute( array( 'media_id' => 1 ) );
	}

	/**
	 * Test execute updates media metadata.
	 *
	 * @return void
	 */
	public function testExecuteUpdatesMediaMetadata(): void {
		$ability = new UpdateMedia();

		$mock_post            = new \stdClass();
		$mock_post->ID        = 1;
		$mock_post->post_type = 'attachment';

		Functions\when( 'get_post' )->justReturn( $mock_post );
		Functions\when( 'sanitize_text_field' )->returnArg();
		Functions\when( 'sanitize_textarea_field' )->returnArg();
		Functions\when( 'wp_update_post' )->justReturn( 1 );
		Functions\when( 'update_post_meta' )->justReturn( true );
		Functions\when( 'wp_get_attachment_url' )->justReturn( 'https://example.com/file.jpg' );

		$result = $ability->doExecute(
			array(
				'media_id'    => 1,
				'title'       => 'Updated Title',
				'caption'     => 'Updated Caption',
				'description' => 'Updated Description',
				'alt_text'    => 'Updated Alt',
			)
		);

		$this->assertIsArray( $result );
		$this->assertArrayHasKey( 'media_id', $result );
		$this->assertArrayHasKey( 'updated', $result );
		$this->assertTrue( $result['updated'] );
	}

	/**
	 * Test execute skips wp_update_post when no fields provided.
	 *
	 * @return void
	 */
	public function testExecuteSkipsUpdateWhenNoFieldsProvided(): void {
		$ability = new UpdateMedia();

		$mock_post            = new \stdClass();
		$mock_post->ID        = 5;
		$mock_post->post_type = 'attachment';

		Functions\when( 'get_post' )->justReturn( $mock_post );
		Functions\expect( 'wp_update_post' )->never();
		Functions\expect( 'update_post_meta' )->never();
		Functions\when( 'wp_get_attachment_url' )->justReturn( 'https://example.com/file.jpg' );

		$result = $ability->doExecute( array( 'media_id' => 5 ) );

		$this->assertTrue( $result['updated'] );
		$this->assertSame( 5, $result['media_id'] );
	}

	/**
	 * Test execute throws when update fails.
	 *
	 * @return void
	 */
	public function testExecuteThrowsWhenUpdateFails(): void {
		$ability = new UpdateMedia();

		$mock_post            = new \stdClass();
		$mock_post->ID        = 9;
		$mock_post->post_type = 'attachment';

		$error = Mockery::mock();
		$error->shouldReceive( 'get_error_message' )->andReturn( 'Update failed' );

		Functions\when( 'get_post' )->justReturn( $mock_post );
		Functions\when( 'sanitize_text_field' )->returnArg();
		Functions\when( 'sanitize_textarea_field' )->returnArg();
		Functions\when( 'wp_update_post' )->justReturn( $error );
		Functions\when( 'is_wp_error' )->justReturn( true );

		$this->expectException( PostUpdateException::class );
		$ability->doExecute(
			array(
				'media_id' => 9,
				'title'    => 'Bad Update',
			)
		);
	}
}
