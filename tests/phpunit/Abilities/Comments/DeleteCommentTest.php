<?php

/**
 * Tests for DeleteComment.
 *
 * @package FAWpmcp\Tests\Abilities\Comments
 */

declare(strict_types=1);

namespace FAWpmcp\Tests\Abilities\Comments;

use FAWpmcp\Abilities\AbstractAbility;
use FAWpmcp\Abilities\Comments\DeleteComment;
use FAWpmcp\Exceptions\CommentDeletionException;
use FAWpmcp\Exceptions\CommentNotFoundException;
use FAWpmcp\Tests\TestCase\AbilityTestTrait;
use FAWpmcp\Tests\TestCase\BrainMonkeyTestCase;
use Brain\Monkey\Functions;

/**
 * Test DeleteComment ability.
 *
 * @package FAWpmcp\Tests\Abilities\Comments
 */
final class DeleteCommentTest extends BrainMonkeyTestCase {

	use AbilityTestTrait;

	/**
	 * Get an instance of the ability being tested.
	 *
	 * @return AbstractAbility
	 */
	protected function getAbilityInstance(): AbstractAbility {
		return new DeleteComment();
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
		return [
			'name'                 => 'fa-wpmcp/delete-comment',
			'category'             => 'comments',
			'label'                => 'Delete Comment',
			'description_contains' => 'delete',
			'operation_type'       => 'write',
			'required_capability'  => 'moderate_comments',
		];
	}

	/**
	 * Test annotations mark as destructive and non-idempotent.
	 *
	 * @return void
	 */
	public function testAnnotationsMarkDestructive(): void {
		$annotations = $this->getAbilityInstance()->getAnnotations();

		$this->assertTrue( $annotations['destructive'] );
		$this->assertFalse( $annotations['idempotent'] );
	}

	/**
	 * Test input schema requires comment_id with optional force.
	 *
	 * @return void
	 */
	public function testInputSchemaRequiresCommentId(): void {
		$schema = $this->getAbilityInstance()->getInputSchema();

		$this->assertArrayHasKey( 'comment_id', $schema['properties'] );
		$this->assertArrayHasKey( 'force', $schema['properties'] );
		$this->assertContains( 'comment_id', $schema['required'] );
		$this->assertNotContains( 'force', $schema['required'] );
	}

	/**
	 * Test output schema includes result fields.
	 *
	 * @return void
	 */
	public function testOutputSchemaStructure(): void {
		$schema = $this->getAbilityInstance()->getOutputSchema();

		$this->assertArrayHasKey( 'comment_id', $schema['properties'] );
		$this->assertArrayHasKey( 'action', $schema['properties'] );
		$this->assertArrayHasKey( 'success', $schema['properties'] );
	}

	/**
	 * Test trashes comment by default.
	 *
	 * @return void
	 */
	public function testTrashesCommentByDefault(): void {
		$comment             = new \stdClass();
		$comment->comment_ID = 42;

		Functions\expect( 'get_comment' )->once()->with( 42 )->andReturn( $comment );
		Functions\expect( 'wp_trash_comment' )->once()->with( 42 )->andReturn( true );

		$result = $this->getAbilityInstance()->doExecute( [ 'comment_id' => 42 ] );

		$this->assertEquals( 42, $result['comment_id'] );
		$this->assertEquals( 'trashed', $result['action'] );
		$this->assertTrue( $result['success'] );
	}

	/**
	 * Test permanently deletes when force is true.
	 *
	 * @return void
	 */
	public function testPermanentlyDeletesWhenForceTrue(): void {
		$comment             = new \stdClass();
		$comment->comment_ID = 42;

		Functions\expect( 'get_comment' )->once()->with( 42 )->andReturn( $comment );
		Functions\expect( 'wp_delete_comment' )->once()->with( 42, true )->andReturn( true );

		$result = $this->getAbilityInstance()->doExecute(
			[
				'comment_id' => 42,
				'force'      => true,
			]
		);

		$this->assertEquals( 42, $result['comment_id'] );
		$this->assertEquals( 'deleted', $result['action'] );
		$this->assertTrue( $result['success'] );
	}

	/**
	 * Test throws exception when comment not found.
	 *
	 * @return void
	 */
	public function testThrowsExceptionWhenCommentNotFound(): void {
		$this->expectException( CommentNotFoundException::class );
		$this->expectExceptionMessage( 'Comment 999 not found' );

		Functions\expect( 'get_comment' )->once()->with( 999 )->andReturn( null );

		$this->getAbilityInstance()->doExecute( [ 'comment_id' => 999 ] );
	}

	/**
	 * Test throws exception when trash fails.
	 *
	 * @return void
	 */
	public function testThrowsExceptionWhenTrashFails(): void {
		$this->expectException( CommentDeletionException::class );
		$this->expectExceptionMessage( 'Failed to trashed comment 42' );

		$comment             = new \stdClass();
		$comment->comment_ID = 42;

		Functions\expect( 'get_comment' )->once()->with( 42 )->andReturn( $comment );
		Functions\expect( 'wp_trash_comment' )->once()->with( 42 )->andReturn( false );

		$this->getAbilityInstance()->doExecute( [ 'comment_id' => 42 ] );
	}

	/**
	 * Test throws exception when permanent delete fails.
	 *
	 * @return void
	 */
	public function testThrowsExceptionWhenDeleteFails(): void {
		$this->expectException( CommentDeletionException::class );
		$this->expectExceptionMessage( 'Failed to deleted comment 42' );

		$comment             = new \stdClass();
		$comment->comment_ID = 42;

		Functions\expect( 'get_comment' )->once()->with( 42 )->andReturn( $comment );
		Functions\expect( 'wp_delete_comment' )->once()->with( 42, true )->andReturn( false );

		$this->getAbilityInstance()->doExecute(
			[
				'comment_id' => 42,
				'force'      => true,
			]
		);
	}
}
