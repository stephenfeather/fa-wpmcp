<?php

/**
 * Base test case for MCP integration tests.
 *
 * Provides MCP session management and helper assertions.
 *
 * @package FAWpmcp\Tests\Integration\Support
 */

declare(strict_types=1);

namespace FAWpmcp\Tests\Integration\Support;

use PHPUnit\Framework\TestCase;

/**
 * Base test case with MCP session lifecycle management.
 */
abstract class McpIntegrationTestCase extends TestCase
{
    protected McpClient $mcp;

    /**
     * IDs of resources created during the test for cleanup.
     *
     * @var array<string, int[]>
     */
    protected array $createdResources = [
        'posts'    => [],
        'comments' => [],
        'users'    => [],
        'terms'    => [],
        'media'    => [],
    ];

    /**
     * Set up MCP session before each test.
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->mcp = McpClient::fromEnvironment();

        try {
            $this->mcp->initialize();
        } catch (\RuntimeException $e) {
            $this->markTestSkipped(
                'Could not connect to MCP server: ' . $e->getMessage()
            );
        }
    }

    /**
     * Clean up resources and close session after each test.
     */
    protected function tearDown(): void
    {
        // Clean up created resources in reverse dependency order.
        $this->cleanupComments();
        $this->cleanupMedia();
        $this->cleanupPosts();
        $this->cleanupTerms();
        $this->cleanupUsers();

        $this->mcp->close();

        parent::tearDown();
    }

    /**
     * Call an MCP tool and return the result.
     *
     * Normalizes response to include 'id' field from type-specific ID fields
     * (post_id, comment_id, user_id, etc.) for consistent test assertions.
     *
     * @param string $toolName  Tool name.
     * @param array  $arguments Tool arguments.
     * @return array Tool response content.
     */
    protected function callTool(string $toolName, array $arguments = []): array
    {
        $response = $this->mcp->callTool($toolName, $arguments);

        // Prefer structuredContent if available.
        if (isset($response['structuredContent'])) {
            return $this->normalizeIdField($response['structuredContent']);
        }

        // Fall back to parsing text content.
        if (isset($response['content'][0]['text'])) {
            $text = $response['content'][0]['text'];

            $decoded = json_decode($text, true);
            if (json_last_error() === JSON_ERROR_NONE) {
                return $this->normalizeIdField($decoded);
            }

            return ['text' => $text];
        }

        return $response;
    }

    /**
     * Normalize type-specific ID fields to a generic 'id' field.
     *
     * Maps post_id, comment_id, user_id, term_id, media_id to 'id'.
     *
     * @param array $data Response data.
     * @return array Data with normalized 'id' field.
     */
    private function normalizeIdField(array $data): array
    {
        $idFields = ['post_id', 'comment_id', 'user_id', 'term_id', 'media_id', 'request_id'];

        foreach ($idFields as $field) {
            if (isset($data[$field]) && !isset($data['id'])) {
                $data['id'] = $data[$field];
                break;
            }
        }

        return $data;
    }

    /**
     * Assert tool call succeeds without error.
     *
     * @param string $toolName  Tool name.
     * @param array  $arguments Tool arguments.
     * @return array Tool response.
     */
    protected function assertToolSucceeds(string $toolName, array $arguments = []): array
    {
        $response = $this->mcp->callTool($toolName, $arguments);

        $this->assertArrayHasKey('content', $response, "Tool {$toolName} should return content");
        $this->assertNotEmpty($response['content'], "Tool {$toolName} content should not be empty");

        // Check for isError flag.
        if (isset($response['isError']) && $response['isError']) {
            $errorText = $response['content'][0]['text'] ?? 'Unknown error';
            $this->fail("Tool {$toolName} returned error: {$errorText}");
        }

        return $this->callTool($toolName, $arguments);
    }

    /**
     * Assert tool call fails with expected error.
     *
     * @param string $toolName       Tool name.
     * @param array  $arguments      Tool arguments.
     * @param string $expectedError  Expected error message substring.
     */
    protected function assertToolFails(
        string $toolName,
        array $arguments,
        string $expectedError = ''
    ): void {
        $response = $this->mcp->callTool($toolName, $arguments);

        $this->assertTrue(
            isset($response['isError']) && $response['isError'],
            "Tool {$toolName} should have failed"
        );

        if ($expectedError !== '') {
            $errorText = $response['content'][0]['text'] ?? '';
            $this->assertStringContainsString(
                $expectedError,
                $errorText,
                "Error should contain: {$expectedError}"
            );
        }
    }

    /**
     * Create a test post and track it for cleanup.
     *
     * @param array $overrides Override default post values.
     * @return array Created post data.
     */
    protected function createTestPost(array $overrides = []): array
    {
        $defaults = [
            'title'   => 'Integration Test Post ' . uniqid(),
            'content' => 'Test content created by integration tests.',
            'status'  => 'draft',
        ];

        $args = array_merge($defaults, $overrides);
        $post = $this->callTool('fa-wpmcp-create-post', $args);

        if (isset($post['id'])) {
            $this->createdResources['posts'][] = $post['id'];
        }

        return $post;
    }

    /**
     * Create a test comment and track it for cleanup.
     *
     * @param int   $postId    Post ID to attach comment to.
     * @param array $overrides Override default comment values.
     * @return array Created comment data.
     */
    protected function createTestComment(int $postId, array $overrides = []): array
    {
        $defaults = [
            'post_id' => $postId,
            'author'  => 'Test Author',
            'email'   => 'test-' . uniqid() . '@example.com',
            'content' => 'Test comment content.',
        ];

        $args    = array_merge($defaults, $overrides);
        $comment = $this->callTool('fa-wpmcp-create-comment', $args);

        if (isset($comment['id'])) {
            $this->createdResources['comments'][] = $comment['id'];
        }

        return $comment;
    }

    /**
     * Create a test user and track it for cleanup.
     *
     * @param array $overrides Override default user values.
     * @return array Created user data.
     */
    protected function createTestUser(array $overrides = []): array
    {
        $unique   = uniqid();
        $defaults = [
            'username' => 'testuser_' . $unique,
            'email'    => 'testuser_' . $unique . '@example.com',
            'password' => 'TestPassword123!',
        ];

        $args = array_merge($defaults, $overrides);
        $user = $this->callTool('fa-wpmcp-create-user', $args);

        if (isset($user['id'])) {
            $this->createdResources['users'][] = $user['id'];
        }

        return $user;
    }

    /**
     * Clean up created posts.
     */
    private function cleanupPosts(): void
    {
        foreach ($this->createdResources['posts'] as $postId) {
            try {
                // Force delete (bypass trash).
                $this->mcp->callTool('fa-wpmcp-delete-post', [
                    'post_id' => $postId,
                    'force'   => true,
                ]);
            } catch (\RuntimeException $e) {
                // Ignore cleanup errors.
            }
        }
    }

    /**
     * Clean up created comments.
     */
    private function cleanupComments(): void
    {
        foreach ($this->createdResources['comments'] as $commentId) {
            try {
                $this->mcp->callTool('fa-wpmcp-delete-comment', [
                    'comment_id' => $commentId,
                    'force'      => true,
                ]);
            } catch (\RuntimeException $e) {
                // Ignore cleanup errors.
            }
        }
    }

    /**
     * Clean up created users.
     */
    private function cleanupUsers(): void
    {
        foreach ($this->createdResources['users'] as $userId) {
            try {
                $this->mcp->callTool('fa-wpmcp-delete-user', [
                    'user_id' => $userId,
                ]);
            } catch (\RuntimeException $e) {
                // Ignore cleanup errors.
            }
        }
    }

    /**
     * Clean up created terms.
     */
    private function cleanupTerms(): void
    {
        foreach ($this->createdResources['terms'] as $termData) {
            try {
                $this->mcp->callTool('fa-wpmcp-delete-term', [
                    'term_id'  => $termData['id'],
                    'taxonomy' => $termData['taxonomy'],
                ]);
            } catch (\RuntimeException $e) {
                // Ignore cleanup errors.
            }
        }
    }

    /**
     * Clean up created media.
     */
    private function cleanupMedia(): void
    {
        foreach ($this->createdResources['media'] as $mediaId) {
            try {
                $this->mcp->callTool('fa-wpmcp-delete-media', [
                    'media_id' => $mediaId,
                    'force'    => true,
                ]);
            } catch (\RuntimeException $e) {
                // Ignore cleanup errors.
            }
        }
    }
}
