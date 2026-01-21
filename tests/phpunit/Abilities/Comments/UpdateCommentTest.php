<?php
declare(strict_types=1);

namespace FAWpmcp\Tests\Abilities\Comments;

use FAWpmcp\Abilities\Comments\UpdateComment;
use FAWpmcp\Exceptions\CommentUpdateException;
use Brain\Monkey\Functions;
use PHPUnit\Framework\TestCase;

final class UpdateCommentTest extends TestCase {
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
		$ability = new UpdateComment();
		$this->assertEquals( 'fa-wpmcp/update-comment', $ability->get_name() );
		$this->assertEquals( 'comments', $ability->get_category() );
		$this->assertEquals( 'Update Comment', $ability->get_label() );
		$this->assertStringContainsString( 'comment', strtolower( $ability->get_description() ) );
		$this->assertEquals( 'moderate_comments', $ability->get_required_capability() );
	}

	public function test_input_schema_requires_fields(): void {
		$ability = new UpdateComment();
		$schema  = $ability->get_input_schema();

		$this->assertEquals( 'object', $schema['type'] );
		$this->assertArrayHasKey( 'comment_id', $schema['properties'] );
		$this->assertArrayHasKey( 'status', $schema['properties'] );
		$this->assertContains( 'comment_id', $schema['required'] );
		$this->assertContains( 'status', $schema['required'] );
		$this->assertContains( 'approve', $schema['properties']['status']['enum'] );
	}

	public function test_output_schema_structure(): void {
		$ability = new UpdateComment();
		$schema  = $ability->get_output_schema();

		$this->assertEquals( 'object', $schema['type'] );
		$this->assertArrayHasKey( 'comment_id', $schema['properties'] );
		$this->assertArrayHasKey( 'status', $schema['properties'] );
		$this->assertArrayHasKey( 'link', $schema['properties'] );
	}

	public function test_updates_comment_status(): void {
		Functions\expect( 'wp_set_comment_status' )->once()->andReturn( true );
		Functions\expect( 'get_comment_link' )->once()->andReturn( 'https://example.com/post#comment-42' );

		$ability = new UpdateComment();
		$result  = $ability->do_execute( array(
			'comment_id' => 42,
			'status'     => 'approve',
		) );

		$this->assertEquals( 42, $result['comment_id'] );
	}

	public function test_throws_exception_when_update_fails(): void {
		$this->expectException( CommentUpdateException::class );
		$this->expectExceptionMessage( 'Failed to update comment status' );

		Functions\expect( 'wp_set_comment_status' )->once()->andReturn( false );

		$ability = new UpdateComment();
		$ability->do_execute( array(
			'comment_id' => 42,
			'status'     => 'trash',
		) );
	}
}
