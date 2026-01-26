<?php

/**
 * Tests for GetComment ability.
 *
 * @package FAWpmcp\Tests\Abilities\Comments
 */

declare(strict_types=1);

namespace FAWpmcp\Tests\Abilities\Comments;

use FAWpmcp\Abilities\AbstractAbility;
use FAWpmcp\Abilities\Comments\GetComment;
use FAWpmcp\Exceptions\CommentNotFoundException;
use FAWpmcp\Tests\TestCase\AbilityTestTrait;
use FAWpmcp\Tests\TestCase\BrainMonkeyTestCase;
use Brain\Monkey\Functions;

/**
 * Test GetComment ability.
 *
 * @package FAWpmcp\Tests\Abilities\Comments
 */
final class GetCommentTest extends BrainMonkeyTestCase {

	use AbilityTestTrait;

	/**
	 * Get an instance of the ability being tested.
	 *
	 * @return AbstractAbility
	 */
	protected function getAbilityInstance(): AbstractAbility {
		return new GetComment();
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
			'name'                 => 'fa-wpmcp/get-comment',
			'category'             => 'comments',
			'label'                => 'Get Comment',
			'description_contains' => 'comment',
			'operation_type'       => 'read',
			'required_capability'  => 'read',
		];
	}

	/**
	 * Test input schema requires comment_id.
	 *
	 * @return void
	 */
	public function testInputSchemaRequiresCommentId(): void {
		$schema = $this->getAbilityInstance()->getInputSchema();
		$this->assertArrayHasKey( 'comment_id', $schema['properties'] );
		$this->assertContains( 'comment_id', $schema['required'] );
	}

	/**
	 * Test output schema includes comment object.
	 *
	 * @return void
	 */
	public function testOutputSchemaIncludesComment(): void {
		$schema = $this->getAbilityInstance()->getOutputSchema();
		$this->assertArrayHasKey( 'comment', $schema['properties'] );
	}

	/**
	 * Test gets comment by ID.
	 *
	 * @return void
	 */
	public function testGetsCommentById(): void {
		$mock_comment = (object) [
			'comment_ID'           => 42,
			'comment_post_ID'      => 10,
			'comment_author'       => 'John Doe',
			'comment_author_email' => 'john@example.com',
			'comment_content'      => 'Great post!',
			'comment_date'         => '2026-01-21 10:00:00',
			'comment_approved'     => '1',
		];

		Functions\expect( 'get_comment' )
			->once()
			->with( 42 )
			->andReturn( $mock_comment );

		Functions\expect( 'get_comment_link' )
			->once()
			->andReturn( 'https://example.com/post#comment-42' );

		$result = $this->getAbilityInstance()->doExecute( [ 'comment_id' => 42 ] );

		$this->assertArrayHasKey( 'comment', $result );
		$this->assertEquals( 42, $result['comment']['id'] );
	}

	/**
	 * Test throws exception for non-existent comment.
	 *
	 * @return void
	 */
	public function testThrowsExceptionForNonExistentComment(): void {
		$this->expectException( CommentNotFoundException::class );
		$this->expectExceptionMessage( 'Comment not found' );

		Functions\expect( 'get_comment' )
			->once()
			->andReturn( null );

		$this->getAbilityInstance()->doExecute( [ 'comment_id' => 999 ] );
	}
}
