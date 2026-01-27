<?php

/**
 * Integration tests for Posts abilities.
 *
 * Tests CRUD operations for posts via MCP protocol.
 *
 * @package FAWpmcp\Tests\Integration\Abilities\Posts
 */

declare(strict_types=1);

namespace FAWpmcp\Tests\Integration\Abilities\Posts;

use FAWpmcp\Tests\Integration\Support\McpIntegrationTestCase;

/**
 * Test Posts ability operations via MCP.
 *
 * @group posts
 * @group abilities
 */
class PostsAbilityTest extends McpIntegrationTestCase
{
    /**
     * Test creating a post with required fields.
     *
     * Response: {post_id, permalink, status, edit_url}
     */
    public function testCreatePost(): void
    {
        $post = $this->createTestPost([
            'title'   => 'Test Create Post',
            'content' => 'Content for testing create operation.',
            'status'  => 'draft',
        ]);

        $this->assertArrayHasKey('id', $post, 'Created post should have an ID (normalized from post_id)');
        $this->assertIsInt($post['id'], 'Post ID should be an integer');
        $this->assertGreaterThan(0, $post['id'], 'Post ID should be positive');
        $this->assertEquals('draft', $post['status'], 'Post status should match requested status');
    }

    /**
     * Test creating a published post.
     */
    public function testCreatePublishedPost(): void
    {
        $post = $this->createTestPost([
            'title'   => 'Published Test Post',
            'content' => 'This post should be published.',
            'status'  => 'publish',
        ]);

        $this->assertArrayHasKey('id', $post);
        $this->assertEquals('publish', $post['status']);

        // Verify via get-post that it's actually published.
        $fetched = $this->getPost($post['id']);
        $this->assertEquals('publish', $fetched['status']);
    }

    /**
     * Test listing posts returns array.
     */
    public function testListPosts(): void
    {
        // Create a test post first.
        $this->createTestPost(['title' => 'List Test Post']);

        $result = $this->callTool('fa-wpmcp-list-posts');

        $this->assertArrayHasKey('posts', $result, 'Result should have posts key');
        $this->assertIsArray($result['posts'], 'Posts should be an array');
    }

    /**
     * Test listing posts with status filter.
     */
    public function testListPostsWithStatusFilter(): void
    {
        // Create draft and published posts.
        $this->createTestPost(['title' => 'Draft Post', 'status' => 'draft']);
        $this->createTestPost(['title' => 'Published Post', 'status' => 'publish']);

        $drafts = $this->callTool('fa-wpmcp-list-posts', ['status' => 'draft']);

        $this->assertArrayHasKey('posts', $drafts);

        // All returned posts should be drafts.
        foreach ($drafts['posts'] as $post) {
            $this->assertEquals('draft', $post['status'], 'Filtered posts should all be drafts');
        }
    }

    /**
     * Test getting a specific post by ID.
     *
     * Response: {post: {id, title, content, status, ...}}
     */
    public function testGetPost(): void
    {
        $created = $this->createTestPost([
            'title'   => 'Get Post Test',
            'content' => 'Unique content for get test.',
        ]);

        $fetched = $this->getPost($created['id']);

        $this->assertArrayHasKey('id', $fetched, 'Fetched post should have ID');
        $this->assertEquals($created['id'], $fetched['id'], 'IDs should match');
        $this->assertEquals('Get Post Test', $fetched['title'], 'Title should match');
    }

    /**
     * Test getting a non-existent post returns error.
     */
    public function testGetNonExistentPost(): void
    {
        $this->assertToolFails(
            'fa-wpmcp-get-post',
            ['post_id' => 999999999],
            'not found'
        );
    }

    /**
     * Test updating a post.
     */
    public function testUpdatePost(): void
    {
        $created = $this->createTestPost(['title' => 'Original Title']);

        $this->callTool('fa-wpmcp-update-post', [
            'post_id' => $created['id'],
            'title'   => 'Updated Title',
            'content' => 'Updated content.',
        ]);

        // Verify the update persisted.
        $fetched = $this->getPost($created['id']);
        $this->assertEquals('Updated Title', $fetched['title']);
    }

    /**
     * Test updating post status.
     */
    public function testUpdatePostStatus(): void
    {
        $created = $this->createTestPost([
            'title'  => 'Status Test Post',
            'status' => 'draft',
        ]);

        $this->callTool('fa-wpmcp-update-post', [
            'post_id' => $created['id'],
            'status'  => 'publish',
        ]);

        $fetched = $this->getPost($created['id']);
        $this->assertEquals('publish', $fetched['status']);
    }

    /**
     * Test deleting a post moves it to trash.
     *
     * Response: {post_id, action, success}
     */
    public function testDeletePostToTrash(): void
    {
        $created = $this->createTestPost(['title' => 'Delete Test Post']);

        $result = $this->callTool('fa-wpmcp-delete-post', ['post_id' => $created['id']]);

        $this->assertArrayHasKey('success', $result, 'Delete result should have success flag');
        $this->assertTrue($result['success'], 'Delete should succeed');
        $this->assertEquals('trashed', $result['action'], 'Action should be trashed');

        // Remove from cleanup list since we manually deleted.
        $this->createdResources['posts'] = array_filter(
            $this->createdResources['posts'],
            fn($id) => $id !== $created['id']
        );

        // Verify post is in trash.
        $fetched = $this->getPost($created['id']);
        $this->assertEquals('trash', $fetched['status'], 'Deleted post should be in trash');
    }

    /**
     * Test full CRUD lifecycle.
     */
    public function testPostCrudLifecycle(): void
    {
        // Create.
        $post = $this->createTestPost([
            'title'   => 'CRUD Lifecycle Test',
            'content' => 'Initial content.',
            'status'  => 'draft',
        ]);
        $this->assertArrayHasKey('id', $post);
        $postId = $post['id'];

        // Read.
        $read = $this->getPost($postId);
        $this->assertEquals($postId, $read['id']);
        $this->assertEquals('CRUD Lifecycle Test', $read['title']);

        // Update.
        $this->callTool('fa-wpmcp-update-post', [
            'post_id' => $postId,
            'title'   => 'CRUD Lifecycle Updated',
            'status'  => 'publish',
        ]);

        $updated = $this->getPost($postId);
        $this->assertEquals('CRUD Lifecycle Updated', $updated['title']);
        $this->assertEquals('publish', $updated['status']);

        // Delete.
        $deleted = $this->callTool('fa-wpmcp-delete-post', ['post_id' => $postId]);
        $this->assertTrue($deleted['success']);

        // Remove from cleanup.
        $this->createdResources['posts'] = array_filter(
            $this->createdResources['posts'],
            fn($id) => $id !== $postId
        );

        // Verify trashed.
        $trashed = $this->getPost($postId);
        $this->assertEquals('trash', $trashed['status']);
    }

    /**
     * Helper to get a post and extract from nested response.
     *
     * @param int $postId Post ID.
     * @return array Post data.
     */
    private function getPost(int $postId): array
    {
        $response = $this->callTool('fa-wpmcp-get-post', ['post_id' => $postId]);

        // get-post returns {post: {...}}
        if (isset($response['post'])) {
            return $response['post'];
        }

        return $response;
    }
}
