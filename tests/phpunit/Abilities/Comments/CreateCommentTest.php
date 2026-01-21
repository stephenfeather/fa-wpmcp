<?php
declare(strict_types=1);

namespace FAWpmcp\Tests\Abilities\Comments;

use FAWpmcp\Abilities\Comments\CreateComment;
use Brain\Monkey\Functions;
use PHPUnit\Framework\TestCase;

final class CreateCommentTest extends TestCase {
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
		$ability = new CreateComment();
		$this->assertEquals( 'fa-wpmcp/create-comment', $ability->get_name() );
		$this->assertEquals( 'comments', $ability->get_category() );
		$this->assertEquals( 'Create Comment', $ability->get_label() );
		$this->assertStringContainsString( 'comment', strtolower( $ability->get_description() ) );
		$this->assertEquals( 'edit_posts', $ability->get_required_capability() );
	}

	public function test_input_schema_requires_fields(): void {
		$ability = new CreateComment();
		$schema  = $ability->get_input_schema();

		$this->assertEquals( 'object', $schema['type'] );
		$this->assertArrayHasKey( 'post_id', $schema['properties'] );
		$this->assertArrayHasKey( 'author', $schema['properties'] );
		$this->assertArrayHasKey( 'email', $schema['properties'] );
		$this->assertArrayHasKey( 'content', $schema['properties'] );
		$this->assertContains( 'post_id', $schema['required'] );
		$this->assertContains( 'author', $schema['required'] );
		$this->assertContains( 'email', $schema['required'] );
		$this->assertContains( 'content', $schema['required'] );
	}

	public function test_output_schema_structure(): void {
		$ability = new CreateComment();
		$schema  = $ability->get_output_schema();

		$this->assertEquals( 'object', $schema['type'] );
		$this->assertArrayHasKey( 'comment_id', $schema['properties'] );
		$this->assertArrayHasKey( 'link', $schema['properties'] );
	}

	public function test_creates_comment(): void {
		Functions\expect( 'wp_insert_comment' )
			->once()
			->with( \Mockery::on( function( $args ) {
				return 10 === $args['comment_post_ID']
					&& 'John' === $args['comment_author']
					&& 'john@example.com' === $args['comment_author_email']
					&& 'Great post!' === $args['comment_content'];
			} ) )
			->andReturn( 42 );
		Functions\expect( 'get_comment_link' )->once()->andReturn( 'https://example.com/post#comment-42' );

		$ability = new CreateComment();
		$result  = $ability->do_execute( array(
			'post_id' => 10,
			'author'  => 'John',
			'email'   => 'john@example.com',
			'content' => 'Great post!',
		) );

		$this->assertEquals( 42, $result['comment_id'] );
	}

	public function test_creates_comment_with_optional_url(): void {
		Functions\expect( 'wp_insert_comment' )
			->once()
			->with( \Mockery::on( function( $args ) {
				return 'https://example.com' === $args['comment_author_url'];
			} ) )
			->andReturn( 77 );

		Functions\expect( 'get_comment_link' )->once()->andReturn( 'https://example.com/post#comment-77' );

		$ability = new CreateComment();
		$result  = $ability->do_execute( array(
			'post_id' => 10,
			'author'  => 'John',
			'email'   => 'john@example.com',
			'content' => 'Great post!',
			'url'     => 'https://example.com',
		) );

		$this->assertEquals( 77, $result['comment_id'] );
	}

	public function test_throws_exception_when_comment_creation_fails(): void {
		$this->expectException( \RuntimeException::class );
		$this->expectExceptionMessage( 'Failed to create comment' );

		Functions\expect( 'wp_insert_comment' )->once()->andReturn( 0 );

		$ability = new CreateComment();
		$ability->do_execute( array(
			'post_id' => 10,
			'author'  => 'John',
			'email'   => 'john@example.com',
			'content' => 'Great post!',
		) );
	}
}
