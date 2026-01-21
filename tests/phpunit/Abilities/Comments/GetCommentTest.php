<?php
/**
 * Tests for GetComment ability.
 *
 * @package FAWpmcp\Tests\Abilities\Comments
 */

declare(strict_types=1);

namespace FAWpmcp\Tests\Abilities\Comments;

use FAWpmcp\Abilities\Comments\GetComment;
use FAWpmcp\Exceptions\CommentNotFoundException;
use Brain\Monkey\Functions;
use PHPUnit\Framework\TestCase;

/**
 * Test GetComment ability.
 *
 * @package FAWpmcp\Tests\Abilities\Comments
 */
final class GetCommentTest extends TestCase {
	use \Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;

	/**
	 * Set up test environment.
	 *
	 * @return void
	 */
	protected function setUp(): void {
		parent::setUp();
		\Brain\Monkey\setUp();
	}

	/**
	 * Tear down test environment.
	 *
	 * @return void
	 */
	protected function tearDown(): void {
		\Brain\Monkey\tearDown();
		parent::tearDown();
	}

	/**
	 * Test ability metadata.
	 *
	 * @return void
	 */
	public function test_ability_metadata(): void {
		$ability = new GetComment();

		$this->assertEquals( 'fa-wpmcp/get-comment', $ability->get_name() );
		$this->assertEquals( 'comments', $ability->get_category() );
		$this->assertEquals( 'Get Comment', $ability->get_label() );
		$this->assertEquals( 'read', $ability->get_required_capability() );
		$this->assertStringContainsString( 'comment', strtolower( $ability->get_description() ) );
	}

	/**
	 * Test schema structures.
	 *
	 * @return void
	 */
	public function test_schema_structures(): void {
		$ability      = new GetComment();
		$input_schema = $ability->get_input_schema();
		$this->assertEquals( 'object', $input_schema['type'] );
		$this->assertArrayHasKey( 'comment_id', $input_schema['properties'] );
		$this->assertContains( 'comment_id', $input_schema['required'] );

		$output_schema = $ability->get_output_schema();
		$this->assertEquals( 'object', $output_schema['type'] );
		$this->assertArrayHasKey( 'comment', $output_schema['properties'] );
	}

	/**
	 * Test gets comment by ID.
	 *
	 * @return void
	 */
	public function test_gets_comment_by_id(): void {
		$mock_comment = (object) array(
			'comment_ID'           => 42,
			'comment_post_ID'      => 10,
			'comment_author'       => 'John Doe',
			'comment_author_email' => 'john@example.com',
			'comment_content'      => 'Great post!',
			'comment_date'         => '2026-01-21 10:00:00',
			'comment_approved'     => '1',
		);

		Functions\expect( 'get_comment' )
			->once()
			->with( 42 )
			->andReturn( $mock_comment );

		Functions\expect( 'get_comment_link' )
			->once()
			->andReturn( 'https://example.com/post#comment-42' );

		$ability = new GetComment();
		$result  = $ability->do_execute( array( 'comment_id' => 42 ) );

		$this->assertArrayHasKey( 'comment', $result );
		$this->assertEquals( 42, $result['comment']['id'] );
	}

	/**
	 * Test throws exception for non-existent comment.
	 *
	 * @return void
	 */
	public function test_throws_exception_for_non_existent_comment(): void {
		$this->expectException( CommentNotFoundException::class );
		$this->expectExceptionMessage( 'Comment not found' );

		Functions\expect( 'get_comment' )
			->once()
			->andReturn( null );

		$ability = new GetComment();
		$ability->do_execute( array( 'comment_id' => 999 ) );
	}
}
