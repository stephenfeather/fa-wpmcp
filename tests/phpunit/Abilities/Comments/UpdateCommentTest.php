<?php

/**
 * Tests for UpdateComment.
 *
 * @package FAWpmcp\Tests\Abilities\Comments
 */

declare(strict_types=1);

namespace FAWpmcp\Tests\Abilities\Comments;

use FAWpmcp\Abilities\AbstractAbility;
use FAWpmcp\Abilities\Comments\UpdateComment;
use FAWpmcp\Exceptions\CommentUpdateException;
use FAWpmcp\Tests\TestCase\AbilityTestTrait;
use FAWpmcp\Tests\TestCase\BrainMonkeyTestCase;
use Brain\Monkey\Functions;

/**
 * Test UpdateComment ability.
 *
 * @package FAWpmcp\Tests\Abilities\Comments
 */
final class UpdateCommentTest extends BrainMonkeyTestCase {

	use AbilityTestTrait;

	/**
	 * Get an instance of the ability being tested.
	 *
	 * @return AbstractAbility
	 */
	protected function getAbilityInstance(): AbstractAbility {
		return new UpdateComment();
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
			'name'                 => 'fa-wpmcp/update-comment',
			'category'             => 'comments',
			'label'                => 'Update Comment',
			'description_contains' => 'comment',
			'operation_type'       => 'write',
			'required_capability'  => 'moderate_comments',
		];
	}

	/**
	 * Test input schema requires comment_id and status.
	 *
	 * @return void
	 */
	public function testInputSchemaRequiresFields(): void {
		$schema = $this->getAbilityInstance()->getInputSchema();

		$this->assertArrayHasKey( 'comment_id', $schema['properties'] );
		$this->assertArrayHasKey( 'status', $schema['properties'] );
		$this->assertContains( 'comment_id', $schema['required'] );
		$this->assertContains( 'status', $schema['required'] );
		$this->assertContains( 'approve', $schema['properties']['status']['enum'] );
	}

	/**
	 * Test output schema includes comment_id, status, and link.
	 *
	 * @return void
	 */
	public function testOutputSchemaStructure(): void {
		$schema = $this->getAbilityInstance()->getOutputSchema();

		$this->assertArrayHasKey( 'comment_id', $schema['properties'] );
		$this->assertArrayHasKey( 'status', $schema['properties'] );
		$this->assertArrayHasKey( 'link', $schema['properties'] );
	}

	/**
	 * Test updates comment status successfully.
	 *
	 * @return void
	 */
	public function testUpdatesCommentStatus(): void {
		Functions\expect( 'wp_set_comment_status' )->once()->andReturn( true );
		Functions\expect( 'get_comment_link' )->once()->andReturn( 'https://example.com/post#comment-42' );

		$result = $this->getAbilityInstance()->doExecute(
			[
				'comment_id' => 42,
				'status'     => 'approve',
			]
		);

		$this->assertEquals( 42, $result['comment_id'] );
	}

	/**
	 * Test throws exception when update fails.
	 *
	 * @return void
	 */
	public function testThrowsExceptionWhenUpdateFails(): void {
		$this->expectException( CommentUpdateException::class );
		$this->expectExceptionMessage( 'Failed to update comment status' );

		Functions\expect( 'wp_set_comment_status' )->once()->andReturn( false );

		$this->getAbilityInstance()->doExecute(
			[
				'comment_id' => 42,
				'status'     => 'trash',
			]
		);
	}
}
