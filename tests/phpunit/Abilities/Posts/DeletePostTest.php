<?php
declare(strict_types=1);

namespace FAWpmcp\Tests\Abilities\Posts;

use FAWpmcp\Abilities\Posts\DeletePost;
use FAWpmcp\Exceptions\PostDeletionException;
use FAWpmcp\Exceptions\PostNotFoundException;
use Brain\Monkey\Functions;
use PHPUnit\Framework\TestCase;

final class DeletePostTest extends TestCase {
	use \Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;

	protected function setUp(): void {
		parent::setUp();
		\Brain\Monkey\setUp();
	}

	protected function tearDown(): void {
		\Brain\Monkey\tearDown();
		parent::tearDown();
	}

	public function test_ability_metadata(): void {
		$ability = new DeletePost();
		$this->assertEquals( 'fa-wpmcp/delete-post', $ability->getName() );
		$this->assertEquals( 'posts-pages', $ability->getCategory() );
		$this->assertEquals( 'Delete Post', $ability->getLabel() );
		$this->assertStringContainsString( 'delete', strtolower( $ability->getDescription() ) );
		$this->assertEquals( 'delete_posts', $ability->getRequiredCapability() );
	}

	public function test_operation_type_is_write(): void {
		$ability = new DeletePost();
		$this->assertEquals( 'write', $ability->getOperationType() );
	}

	public function test_annotations_mark_destructive(): void {
		$ability     = new DeletePost();
		$annotations = $ability->getAnnotations();

		$this->assertTrue( $annotations['destructive'] );
		$this->assertFalse( $annotations['idempotent'] );
	}

	public function test_input_schema_requires_post_id(): void {
		$ability = new DeletePost();
		$schema  = $ability->getInputSchema();

		$this->assertEquals( 'object', $schema['type'] );
		$this->assertArrayHasKey( 'post_id', $schema['properties'] );
		$this->assertArrayHasKey( 'force', $schema['properties'] );
		$this->assertContains( 'post_id', $schema['required'] );
		$this->assertNotContains( 'force', $schema['required'] );
	}

	public function test_output_schema_structure(): void {
		$ability = new DeletePost();
		$schema  = $ability->getOutputSchema();

		$this->assertEquals( 'object', $schema['type'] );
		$this->assertArrayHasKey( 'post_id', $schema['properties'] );
		$this->assertArrayHasKey( 'action', $schema['properties'] );
		$this->assertArrayHasKey( 'success', $schema['properties'] );
	}

	public function test_trashes_post_by_default(): void {
		$post     = new \stdClass();
		$post->ID = 42;

		Functions\expect( 'get_post' )->once()->with( 42 )->andReturn( $post );
		Functions\expect( 'wp_trash_post' )->once()->with( 42 )->andReturn( $post );

		$ability = new DeletePost();
		$result  = $ability->doExecute( array( 'post_id' => 42 ) );

		$this->assertEquals( 42, $result['post_id'] );
		$this->assertEquals( 'trashed', $result['action'] );
		$this->assertTrue( $result['success'] );
	}

	public function test_permanently_deletes_when_force_true(): void {
		$post     = new \stdClass();
		$post->ID = 42;

		Functions\expect( 'get_post' )->once()->with( 42 )->andReturn( $post );
		Functions\expect( 'wp_delete_post' )->once()->with( 42, true )->andReturn( $post );

		$ability = new DeletePost();
		$result  = $ability->doExecute( array(
			'post_id' => 42,
			'force'   => true,
		) );

		$this->assertEquals( 42, $result['post_id'] );
		$this->assertEquals( 'deleted', $result['action'] );
		$this->assertTrue( $result['success'] );
	}

	public function test_throws_exception_when_post_not_found(): void {
		$this->expectException( PostNotFoundException::class );
		$this->expectExceptionMessage( 'Post 999 not found' );

		Functions\expect( 'get_post' )->once()->with( 999 )->andReturn( null );

		$ability = new DeletePost();
		$ability->doExecute( array( 'post_id' => 999 ) );
	}

	public function test_throws_exception_when_trash_fails(): void {
		$this->expectException( PostDeletionException::class );
		$this->expectExceptionMessage( 'Failed to trashed post 42' );

		$post     = new \stdClass();
		$post->ID = 42;

		Functions\expect( 'get_post' )->once()->with( 42 )->andReturn( $post );
		Functions\expect( 'wp_trash_post' )->once()->with( 42 )->andReturn( false );

		$ability = new DeletePost();
		$ability->doExecute( array( 'post_id' => 42 ) );
	}

	public function test_throws_exception_when_delete_fails(): void {
		$this->expectException( PostDeletionException::class );
		$this->expectExceptionMessage( 'Failed to deleted post 42' );

		$post     = new \stdClass();
		$post->ID = 42;

		Functions\expect( 'get_post' )->once()->with( 42 )->andReturn( $post );
		Functions\expect( 'wp_delete_post' )->once()->with( 42, true )->andReturn( false );

		$ability = new DeletePost();
		$ability->doExecute( array(
			'post_id' => 42,
			'force'   => true,
		) );
	}

	public function test_throws_exception_when_delete_returns_null(): void {
		$this->expectException( PostDeletionException::class );

		$post     = new \stdClass();
		$post->ID = 42;

		Functions\expect( 'get_post' )->once()->with( 42 )->andReturn( $post );
		Functions\expect( 'wp_delete_post' )->once()->with( 42, true )->andReturn( null );

		$ability = new DeletePost();
		$ability->doExecute( array(
			'post_id' => 42,
			'force'   => true,
		) );
	}
}
