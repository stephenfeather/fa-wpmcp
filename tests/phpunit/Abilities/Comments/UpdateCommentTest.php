<?php
declare(strict_types=1);

namespace FAWpmcp\Tests\Abilities\Comments;

use FAWpmcp\Abilities\Comments\UpdateComment;
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
}
