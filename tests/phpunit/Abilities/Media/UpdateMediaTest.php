<?php
/**
 * Tests for UpdateMedia ability.
 *
 * @package FAWpmcp\Tests\Abilities\Media
 */

declare(strict_types=1);

namespace FAWpmcp\Tests\Abilities\Media;

use FAWpmcp\Abilities\AbstractAbility;
use FAWpmcp\Abilities\Media\UpdateMedia;
use FAWpmcp\Exceptions\PostNotFoundException;
use FAWpmcp\Exceptions\PostTypeMismatchException;
use FAWpmcp\Exceptions\PostUpdateException;
use FAWpmcp\Tests\TestCase\AbilityTestTrait;
use FAWpmcp\Tests\TestCase\BrainMonkeyTestCase;
use Brain\Monkey\Functions;
use Mockery;

/**
 * Test UpdateMedia ability functionality.
 *
 * @package FAWpmcp\Tests\Abilities\Media
 */
class UpdateMediaTest extends BrainMonkeyTestCase {

	use AbilityTestTrait;

	protected function getAbilityInstance(): AbstractAbility {
		return new UpdateMedia();
	}

	protected function getExpectedMetadata(): array {
		return [
			'name'                 => 'fa-wpmcp/update-media',
			'category'             => 'media',
			'label'                => 'Update Media',
			'description_contains' => 'update',
			'required_capability'  => 'upload_files',
			'operation_type'       => 'write',
		];
	}

	/**
	 * Test execute throws exception for non-existent media.
	 *
	 * @return void
	 */
	public function testExecuteThrowsExceptionForNonExistentMedia(): void {
		$ability = $this->getAbilityInstance();

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
		$ability = $this->getAbilityInstance();

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
		$ability = $this->getAbilityInstance();

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
		$ability = $this->getAbilityInstance();

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
		$ability = $this->getAbilityInstance();

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
