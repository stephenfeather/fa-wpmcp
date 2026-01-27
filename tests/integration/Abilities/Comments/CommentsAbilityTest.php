<?php

/**
 * Integration tests for Comments abilities.
 *
 * Tests CRUD operations for comments via MCP protocol.
 *
 * @package FAWpmcp\Tests\Integration\Abilities\Comments
 */

declare(strict_types=1);

namespace FAWpmcp\Tests\Integration\Abilities\Comments;

use FAWpmcp\Tests\Integration\Support\McpIntegrationTestCase;

/**
 * Test Comments ability operations via MCP.
 *
 * @group comments
 * @group abilities
 */
class CommentsAbilityTest extends McpIntegrationTestCase
{
    /**
     * Parent post ID for comment tests.
     */
    private int $parentPostId;

    /**
     * Set up parent post before each test.
     */
    protected function setUp(): void
    {
        parent::setUp();

        // Create a parent post for all comment tests.
        $post = $this->createTestPost([
            'title'   => 'Comment Test Parent Post',
            'content' => 'Post for testing comments.',
            'status'  => 'publish',
        ]);

        $this->parentPostId = $post['id'];
    }

    /**
     * Test creating a comment with required fields.
     *
     * Response: {comment_id, link}
     */
    public function testCreateComment(): void
    {
        $comment = $this->createTestComment($this->parentPostId, [
            'author'  => 'Test Author',
            'email'   => 'author@example.com',
            'content' => 'This is a test comment.',
        ]);

        $this->assertArrayHasKey('id', $comment, 'Created comment should have an ID (normalized from comment_id)');
        $this->assertIsInt($comment['id'], 'Comment ID should be an integer');
        $this->assertGreaterThan(0, $comment['id'], 'Comment ID should be positive');
        $this->assertArrayHasKey('link', $comment, 'Response should include comment link');
    }

    /**
     * Test creating a comment with optional URL field.
     */
    public function testCreateCommentWithUrl(): void
    {
        $comment = $this->createTestComment($this->parentPostId, [
            'author'  => 'Author With Site',
            'email'   => 'author-url@example.com',
            'content' => 'Comment with author URL.',
            'url'     => 'https://example.com',
        ]);

        $this->assertArrayHasKey('id', $comment);

        // Just verify the comment was created - GetComment doesn't return author_url.
        $fetched = $this->getComment($comment['id']);
        $this->assertEquals('Author With Site', $fetched['author']);
    }

    /**
     * Test listing comments returns array.
     */
    public function testListComments(): void
    {
        // Create a test comment first.
        $this->createTestComment($this->parentPostId);

        $result = $this->callTool('fa-wpmcp-list-comments');

        $this->assertArrayHasKey('comments', $result, 'Result should have comments key');
        $this->assertIsArray($result['comments'], 'Comments should be an array');
    }

    /**
     * Test listing comments filtered by post.
     */
    public function testListCommentsFilteredByPost(): void
    {
        // Create comments on the parent post.
        $this->createTestComment($this->parentPostId, ['content' => 'Comment 1']);
        $this->createTestComment($this->parentPostId, ['content' => 'Comment 2']);

        // Create another post with its own comment.
        $otherPost = $this->createTestPost(['title' => 'Other Post']);
        $this->createTestComment($otherPost['id'], ['content' => 'Other post comment']);

        $filtered = $this->callTool('fa-wpmcp-list-comments', ['post_id' => $this->parentPostId]);

        $this->assertArrayHasKey('comments', $filtered);

        // All returned comments should belong to the parent post.
        foreach ($filtered['comments'] as $comment) {
            $this->assertEquals(
                $this->parentPostId,
                $comment['post_id'] ?? $comment['post'],
                'Filtered comments should belong to specified post'
            );
        }
    }

    /**
     * Test getting a specific comment by ID.
     *
     * Response: {comment: {id, content, author, ...}}
     */
    public function testGetComment(): void
    {
        $created = $this->createTestComment($this->parentPostId, [
            'author'  => 'Get Test Author',
            'content' => 'Unique content for get test.',
        ]);

        $fetched = $this->getComment($created['id']);

        $this->assertArrayHasKey('id', $fetched, 'Fetched comment should have ID');
        $this->assertEquals($created['id'], $fetched['id'], 'IDs should match');
        $this->assertEquals('Get Test Author', $fetched['author'], 'Author should match');
    }

    /**
     * Test getting a non-existent comment returns error.
     */
    public function testGetNonExistentComment(): void
    {
        $this->assertToolFails(
            'fa-wpmcp-get-comment',
            ['comment_id' => 999999999],
            'not found'
        );
    }

    /**
     * Test updating comment status to approved.
     *
     * Comments are created as approved by default, so we first hold then approve.
     * UpdateComment is a moderation tool - only changes status.
     */
    public function testUpdateCommentStatusApprove(): void
    {
        $created = $this->createTestComment($this->parentPostId, [
            'content' => 'Comment to approve.',
        ]);

        // First hold the comment (it starts as approved).
        $this->callTool('fa-wpmcp-update-comment', [
            'comment_id' => $created['id'],
            'status'     => 'hold',
        ]);

        // Now approve it.
        $result = $this->callTool('fa-wpmcp-update-comment', [
            'comment_id' => $created['id'],
            'status'     => 'approve',
        ]);

        // Update returns {comment_id, status, link} - id is normalized from comment_id.
        $this->assertTrue(
            isset($result['id']) || isset($result['comment_id']),
            'Update should return comment ID'
        );

        // Verify status persisted (WordPress may return '1' or 'approved').
        $fetched = $this->getComment($created['id']);
        $this->assertContains($fetched['status'], ['approved', '1', 1], 'Comment should be approved');
    }

    /**
     * Test updating comment status to hold (pending moderation).
     */
    public function testUpdateCommentStatusHold(): void
    {
        $created = $this->createTestComment($this->parentPostId, [
            'content' => 'Comment to hold.',
        ]);

        $result = $this->callTool('fa-wpmcp-update-comment', [
            'comment_id' => $created['id'],
            'status'     => 'hold',
        ]);

        $this->assertTrue(
            isset($result['id']) || isset($result['comment_id']),
            'Update should return comment ID'
        );

        // Verify status persisted (WordPress uses '0' or 'hold' for held comments).
        $fetched = $this->getComment($created['id']);
        $this->assertContains($fetched['status'], ['hold', 'unapproved', '0', 0], 'Comment should be held');
    }

    /**
     * Test deleting a comment moves it to trash.
     *
     * Response: {comment_id, action, success}
     */
    public function testDeleteCommentToTrash(): void
    {
        $created = $this->createTestComment($this->parentPostId, [
            'content' => 'Comment to delete.',
        ]);

        $result = $this->callTool('fa-wpmcp-delete-comment', ['comment_id' => $created['id']]);

        $this->assertArrayHasKey('success', $result, 'Delete result should have success flag');
        $this->assertTrue($result['success'], 'Delete should succeed');

        // Remove from cleanup list since we manually deleted.
        $this->createdResources['comments'] = array_filter(
            $this->createdResources['comments'],
            fn($id) => $id !== $created['id']
        );
    }

    /**
     * Test force deleting a comment permanently removes it.
     */
    public function testForceDeleteComment(): void
    {
        $created = $this->createTestComment($this->parentPostId, [
            'content' => 'Comment to force delete.',
        ]);

        $result = $this->callTool('fa-wpmcp-delete-comment', [
            'comment_id' => $created['id'],
            'force'      => true,
        ]);

        $this->assertTrue($result['success']);

        // Remove from cleanup list.
        $this->createdResources['comments'] = array_filter(
            $this->createdResources['comments'],
            fn($id) => $id !== $created['id']
        );

        // Verify comment no longer exists.
        $this->assertToolFails(
            'fa-wpmcp-get-comment',
            ['comment_id' => $created['id']],
            'not found'
        );
    }

    /**
     * Test full CRUD lifecycle.
     *
     * Note: Update only supports status changes (moderation).
     */
    public function testCommentCrudLifecycle(): void
    {
        // Create.
        $comment = $this->createTestComment($this->parentPostId, [
            'author'  => 'CRUD Lifecycle Author',
            'email'   => 'crud-lifecycle@example.com',
            'content' => 'Initial comment content.',
        ]);
        $this->assertArrayHasKey('id', $comment);
        $commentId = $comment['id'];

        // Read.
        $read = $this->getComment($commentId);
        $this->assertEquals($commentId, $read['id']);
        $this->assertEquals('CRUD Lifecycle Author', $read['author']);

        // Update (status only - moderation).
        $this->callTool('fa-wpmcp-update-comment', [
            'comment_id' => $commentId,
            'status'     => 'approve',
        ]);

        $updated = $this->getComment($commentId);
        $this->assertContains($updated['status'], ['approved', '1', 1], 'Comment should be approved');

        // Delete (force).
        $deleted = $this->callTool('fa-wpmcp-delete-comment', [
            'comment_id' => $commentId,
            'force'      => true,
        ]);
        $this->assertTrue($deleted['success']);

        // Remove from cleanup.
        $this->createdResources['comments'] = array_filter(
            $this->createdResources['comments'],
            fn($id) => $id !== $commentId
        );

        // Verify deleted.
        $this->assertToolFails(
            'fa-wpmcp-get-comment',
            ['comment_id' => $commentId],
            'not found'
        );
    }

    /**
     * Helper to get a comment and extract from nested response.
     *
     * @param int $commentId Comment ID.
     * @return array Comment data.
     */
    private function getComment(int $commentId): array
    {
        $response = $this->callTool('fa-wpmcp-get-comment', ['comment_id' => $commentId]);

        // get-comment returns {comment: {...}}
        if (isset($response['comment'])) {
            return $response['comment'];
        }

        return $response;
    }
}
