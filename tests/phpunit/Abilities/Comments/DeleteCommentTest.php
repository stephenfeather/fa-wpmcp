<?php

/**
 * Tests for DeleteComment.
 *
 * @package FAWpmcp\Tests\Abilities\Comments
 */

declare(strict_types=1);

namespace FAWpmcp\Tests\Abilities\Comments;

use FAWpmcp\Abilities\Comments\DeleteComment;
use FAWpmcp\Exceptions\CommentDeletionException;
use FAWpmcp\Exceptions\CommentNotFoundException;
use Brain\Monkey\Functions;
use PHPUnit\Framework\TestCase;

final class DeleteCommentTest extends TestCase
{
    use \Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;

    protected function setUp(): void
    {
        parent::setUp();
        \Brain\Monkey\setUp();
    }

    protected function tearDown(): void
    {
        \Brain\Monkey\tearDown();
        parent::tearDown();
    }

    public function test_ability_metadata(): void
    {
        $ability = new DeleteComment();
        $this->assertEquals('fa-wpmcp/delete-comment', $ability->getName());
        $this->assertEquals('comments', $ability->getCategory());
        $this->assertEquals('Delete Comment', $ability->getLabel());
        $this->assertStringContainsString('delete', strtolower($ability->getDescription()));
        $this->assertEquals('moderate_comments', $ability->getRequiredCapability());
    }

    public function test_operation_type_is_write(): void
    {
        $ability = new DeleteComment();
        $this->assertEquals('write', $ability->getOperationType());
    }

    public function test_annotations_mark_destructive(): void
    {
        $ability     = new DeleteComment();
        $annotations = $ability->getAnnotations();

        $this->assertTrue($annotations['destructive']);
        $this->assertFalse($annotations['idempotent']);
    }

    public function test_input_schema_requires_comment_id(): void
    {
        $ability = new DeleteComment();
        $schema  = $ability->getInputSchema();

        $this->assertEquals('object', $schema['type']);
        $this->assertArrayHasKey('comment_id', $schema['properties']);
        $this->assertArrayHasKey('force', $schema['properties']);
        $this->assertContains('comment_id', $schema['required']);
        $this->assertNotContains('force', $schema['required']);
    }

    public function test_output_schema_structure(): void
    {
        $ability = new DeleteComment();
        $schema  = $ability->getOutputSchema();

        $this->assertEquals('object', $schema['type']);
        $this->assertArrayHasKey('comment_id', $schema['properties']);
        $this->assertArrayHasKey('action', $schema['properties']);
        $this->assertArrayHasKey('success', $schema['properties']);
    }

    public function test_trashes_comment_by_default(): void
    {
        $comment             = new \stdClass();
        $comment->comment_ID = 42;

        Functions\expect('get_comment')->once()->with(42)->andReturn($comment);
        Functions\expect('wp_trash_comment')->once()->with(42)->andReturn(true);

        $ability = new DeleteComment();
        $result  = $ability->doExecute(array( 'comment_id' => 42 ));

        $this->assertEquals(42, $result['comment_id']);
        $this->assertEquals('trashed', $result['action']);
        $this->assertTrue($result['success']);
    }

    public function test_permanently_deletes_when_force_true(): void
    {
        $comment             = new \stdClass();
        $comment->comment_ID = 42;

        Functions\expect('get_comment')->once()->with(42)->andReturn($comment);
        Functions\expect('wp_delete_comment')->once()->with(42, true)->andReturn(true);

        $ability = new DeleteComment();
        $result  = $ability->doExecute(
            array(
                'comment_id' => 42,
                'force'      => true,
            )
        );

        $this->assertEquals(42, $result['comment_id']);
        $this->assertEquals('deleted', $result['action']);
        $this->assertTrue($result['success']);
    }

    public function test_throws_exception_when_comment_not_found(): void
    {
        $this->expectException(CommentNotFoundException::class);
        $this->expectExceptionMessage('Comment 999 not found');

        Functions\expect('get_comment')->once()->with(999)->andReturn(null);

        $ability = new DeleteComment();
        $ability->doExecute(array( 'comment_id' => 999 ));
    }

    public function test_throws_exception_when_trash_fails(): void
    {
        $this->expectException(CommentDeletionException::class);
        $this->expectExceptionMessage('Failed to trashed comment 42');

        $comment             = new \stdClass();
        $comment->comment_ID = 42;

        Functions\expect('get_comment')->once()->with(42)->andReturn($comment);
        Functions\expect('wp_trash_comment')->once()->with(42)->andReturn(false);

        $ability = new DeleteComment();
        $ability->doExecute(array( 'comment_id' => 42 ));
    }

    public function test_throws_exception_when_delete_fails(): void
    {
        $this->expectException(CommentDeletionException::class);
        $this->expectExceptionMessage('Failed to deleted comment 42');

        $comment             = new \stdClass();
        $comment->comment_ID = 42;

        Functions\expect('get_comment')->once()->with(42)->andReturn($comment);
        Functions\expect('wp_delete_comment')->once()->with(42, true)->andReturn(false);

        $ability = new DeleteComment();
        $ability->doExecute(
            array(
                'comment_id' => 42,
                'force'      => true,
            )
        );
    }
}
