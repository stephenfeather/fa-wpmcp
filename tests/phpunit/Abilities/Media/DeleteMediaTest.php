<?php
/**
 * Tests for DeleteMedia.
 *
 * @package FAWpmcp\Tests\Abilities\Media
 */

declare(strict_types=1);

namespace FAWpmcp\Tests\Abilities\Media;

use FAWpmcp\Abilities\AbstractAbility;
use FAWpmcp\Abilities\Media\DeleteMedia;
use FAWpmcp\Exceptions\MediaDeletionException;
use FAWpmcp\Exceptions\MediaNotFoundException;
use FAWpmcp\Tests\TestCase\AbilityTestTrait;
use FAWpmcp\Tests\TestCase\BrainMonkeyTestCase;
use Brain\Monkey\Functions;

final class DeleteMediaTest extends BrainMonkeyTestCase {

	use AbilityTestTrait;

	protected function getAbilityInstance(): AbstractAbility {
		return new DeleteMedia();
	}

	protected function getExpectedMetadata(): array {
		return [
			'name'                 => 'fa-wpmcp/delete-media',
			'category'             => 'media',
			'label'                => 'Delete Media',
			'description_contains' => 'delete',
			'required_capability'  => 'delete_posts',
			'operation_type'       => 'write',
		];
	}

	public function test_annotations_mark_destructive(): void {
		$ability     = $this->getAbilityInstance();
		$annotations = $ability->getAnnotations();

		$this->assertTrue( $annotations['destructive'] );
		$this->assertFalse( $annotations['idempotent'] );
	}

	public function test_input_schema_requires_media_id(): void {
		$ability = $this->getAbilityInstance();
		$schema  = $ability->getInputSchema();

		$this->assertEquals( 'object', $schema['type'] );
		$this->assertArrayHasKey( 'media_id', $schema['properties'] );
		$this->assertArrayHasKey( 'force', $schema['properties'] );
		$this->assertContains( 'media_id', $schema['required'] );
		$this->assertNotContains( 'force', $schema['required'] );
	}

	public function test_output_schema_structure(): void {
		$ability = $this->getAbilityInstance();
		$schema  = $ability->getOutputSchema();

		$this->assertEquals( 'object', $schema['type'] );
		$this->assertArrayHasKey( 'media_id', $schema['properties'] );
		$this->assertArrayHasKey( 'action', $schema['properties'] );
		$this->assertArrayHasKey( 'success', $schema['properties'] );
	}

	public function test_trashes_media_by_default(): void {
		$post             = new \stdClass();
		$post->ID         = 42;
		$post->post_type  = 'attachment';

		Functions\expect( 'get_post' )->once()->with( 42 )->andReturn( $post );
		Functions\expect( 'wp_delete_attachment' )->once()->with( 42, false )->andReturn( $post );

		$ability = $this->getAbilityInstance();
		$result  = $ability->doExecute( array( 'media_id' => 42 ) );

		$this->assertEquals( 42, $result['media_id'] );
		$this->assertEquals( 'trashed', $result['action'] );
		$this->assertTrue( $result['success'] );
	}

	public function test_permanently_deletes_when_force_true(): void {
		$post             = new \stdClass();
		$post->ID         = 42;
		$post->post_type  = 'attachment';

		Functions\expect( 'get_post' )->once()->with( 42 )->andReturn( $post );
		Functions\expect( 'wp_delete_attachment' )->once()->with( 42, true )->andReturn( $post );

		$ability = $this->getAbilityInstance();
		$result  = $ability->doExecute(
			array(
				'media_id' => 42,
				'force'    => true,
			)
		);

		$this->assertEquals( 42, $result['media_id'] );
		$this->assertEquals( 'deleted', $result['action'] );
		$this->assertTrue( $result['success'] );
	}

	public function test_throws_exception_when_media_not_found(): void {
		$this->expectException( MediaNotFoundException::class );
		$this->expectExceptionMessage( 'Media 999 not found' );

		Functions\expect( 'get_post' )->once()->with( 999 )->andReturn( null );

		$ability = $this->getAbilityInstance();
		$ability->doExecute( array( 'media_id' => 999 ) );
	}

	public function test_throws_exception_when_post_is_not_attachment(): void {
		$this->expectException( MediaNotFoundException::class );
		$this->expectExceptionMessage( 'Post 42 is not a media attachment' );

		$post             = new \stdClass();
		$post->ID         = 42;
		$post->post_type  = 'post';

		Functions\expect( 'get_post' )->once()->with( 42 )->andReturn( $post );

		$ability = $this->getAbilityInstance();
		$ability->doExecute( array( 'media_id' => 42 ) );
	}

	public function test_throws_exception_when_trash_fails(): void {
		$this->expectException( MediaDeletionException::class );
		$this->expectExceptionMessage( 'Failed to trashed media 42' );

		$post             = new \stdClass();
		$post->ID         = 42;
		$post->post_type  = 'attachment';

		Functions\expect( 'get_post' )->once()->with( 42 )->andReturn( $post );
		Functions\expect( 'wp_delete_attachment' )->once()->with( 42, false )->andReturn( false );

		$ability = $this->getAbilityInstance();
		$ability->doExecute( array( 'media_id' => 42 ) );
	}

	public function test_throws_exception_when_delete_fails(): void {
		$this->expectException( MediaDeletionException::class );
		$this->expectExceptionMessage( 'Failed to deleted media 42' );

		$post             = new \stdClass();
		$post->ID         = 42;
		$post->post_type  = 'attachment';

		Functions\expect( 'get_post' )->once()->with( 42 )->andReturn( $post );
		Functions\expect( 'wp_delete_attachment' )->once()->with( 42, true )->andReturn( false );

		$ability = $this->getAbilityInstance();
		$ability->doExecute(
			array(
				'media_id' => 42,
				'force'    => true,
			)
		);
	}

	public function test_throws_exception_when_delete_returns_null(): void {
		$this->expectException( MediaDeletionException::class );

		$post             = new \stdClass();
		$post->ID         = 42;
		$post->post_type  = 'attachment';

		Functions\expect( 'get_post' )->once()->with( 42 )->andReturn( $post );
		Functions\expect( 'wp_delete_attachment' )->once()->with( 42, true )->andReturn( null );

		$ability = $this->getAbilityInstance();
		$ability->doExecute(
			array(
				'media_id' => 42,
				'force'    => true,
			)
		);
	}
}
