<?php

/**
 * Integration tests for Media abilities.
 *
 * Tests CRUD operations for media attachments via MCP protocol.
 *
 * @package FAWpmcp\Tests\Integration\Abilities\Media
 */

declare(strict_types=1);

namespace FAWpmcp\Tests\Integration\Abilities\Media;

use FAWpmcp\Tests\Integration\Support\McpIntegrationTestCase;

/**
 * Test Media ability operations via MCP.
 *
 * @group media
 * @group abilities
 */
class MediaAbilityTest extends McpIntegrationTestCase
{
    /**
     * Minimal 1x1 red pixel PNG as base64.
     *
     * This is the smallest valid PNG file (68 bytes).
     */
    private const TEST_PNG_BASE64 = 'iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mP8z8DwHwAFBQIAX8jx0gAAAABJRU5ErkJggg==';

    /**
     * Minimal 1x1 blue pixel PNG as base64 (different from red for update tests).
     */
    private const TEST_PNG_BLUE_BASE64 = 'iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mNkYPj/HwADBwIAMCbHYQAAAABJRU5ErkJggg==';

    /**
     * Test uploading media with base64 file data.
     *
     * Response: {media_id, url, mime_type, type, filesize}
     */
    public function testUploadMedia(): void
    {
        $media = $this->createTestMedia([
            'filename' => 'test-upload.png',
        ]);

        $this->assertArrayHasKey('id', $media, 'Uploaded media should have an ID (normalized from media_id)');
        $this->assertIsInt($media['id'], 'Media ID should be an integer');
        $this->assertGreaterThan(0, $media['id'], 'Media ID should be positive');
        $this->assertArrayHasKey('url', $media, 'Response should include URL');
        $this->assertStringContainsString('http', $media['url'], 'URL should be valid');
        $this->assertEquals('image/png', $media['mime_type'], 'MIME type should be image/png');
        $this->assertEquals('image', $media['media_type'], 'Media type should be image');
    }

    /**
     * Test uploading media with metadata (title, alt_text, caption, description).
     */
    public function testUploadMediaWithMetadata(): void
    {
        $media = $this->createTestMedia([
            'filename'    => 'test-with-meta.png',
            'title'       => 'Test Image Title',
            'alt_text'    => 'Alt text for test image',
            'caption'     => 'This is a test caption',
            'description' => 'This is a test description',
        ]);

        $this->assertArrayHasKey('id', $media);

        // Verify metadata was set by fetching the media.
        $fetched = $this->getMedia($media['id']);
        $this->assertEquals('Test Image Title', $fetched['title'], 'Title should match');
        $this->assertEquals('Alt text for test image', $fetched['alt_text'], 'Alt text should match');
        $this->assertEquals('This is a test caption', $fetched['caption'], 'Caption should match');
        $this->assertEquals('This is a test description', $fetched['description'], 'Description should match');
    }

    /**
     * Test listing media items.
     */
    public function testListMedia(): void
    {
        // Create a test media item first.
        $this->createTestMedia(['filename' => 'list-test.png']);

        $result = $this->callTool('fa-wpmcp-list-media');

        $this->assertArrayHasKey('media', $result, 'Result should have media key');
        $this->assertIsArray($result['media'], 'Media should be an array');
        $this->assertArrayHasKey('total', $result, 'Result should have total count');
        $this->assertArrayHasKey('pages', $result, 'Result should have pages count');
        $this->assertArrayHasKey('current_page', $result, 'Result should have current_page');
        $this->assertArrayHasKey('per_page', $result, 'Result should have per_page');
    }

    /**
     * Test listing media filtered by MIME type.
     */
    public function testListMediaFilteredByMimeType(): void
    {
        // Create a PNG image.
        $this->createTestMedia(['filename' => 'filter-test.png']);

        $images = $this->callTool('fa-wpmcp-list-media', ['mime_type' => 'image/*']);

        $this->assertArrayHasKey('media', $images);

        // All returned items should be images.
        foreach ($images['media'] as $item) {
            $this->assertStringStartsWith('image/', $item['mime_type'], 'Filtered media should all be images');
        }
    }

    /**
     * Test listing media with search.
     */
    public function testListMediaWithSearch(): void
    {
        $uniqueTitle = 'SearchableMediaTitle' . uniqid();

        // Create media with unique title.
        $this->createTestMedia([
            'filename' => 'search-test.png',
            'title'    => $uniqueTitle,
        ]);

        $result = $this->callTool('fa-wpmcp-list-media', ['search' => $uniqueTitle]);

        $this->assertArrayHasKey('media', $result);
        $this->assertGreaterThanOrEqual(1, count($result['media']), 'Should find at least one media item');

        // Check the first result matches.
        $found = false;
        foreach ($result['media'] as $item) {
            if ($item['title'] === $uniqueTitle) {
                $found = true;
                break;
            }
        }
        $this->assertTrue($found, 'Search should find media with matching title');
    }

    /**
     * Test listing media with pagination.
     */
    public function testListMediaWithPagination(): void
    {
        // Create multiple media items.
        $this->createTestMedia(['filename' => 'page-test-1.png']);
        $this->createTestMedia(['filename' => 'page-test-2.png']);
        $this->createTestMedia(['filename' => 'page-test-3.png']);

        // Request with per_page = 2.
        $result = $this->callTool('fa-wpmcp-list-media', [
            'per_page' => 2,
            'page'     => 1,
        ]);

        $this->assertArrayHasKey('media', $result);
        $this->assertLessThanOrEqual(2, count($result['media']), 'Should return at most per_page items');
        $this->assertEquals(2, $result['per_page'], 'per_page should be 2');
        $this->assertEquals(1, $result['current_page'], 'current_page should be 1');
    }

    /**
     * Test getting a specific media item by ID.
     *
     * Response: {media: {id, title, filename, url, ...}}
     */
    public function testGetMedia(): void
    {
        $created = $this->createTestMedia([
            'filename' => 'get-test.png',
            'title'    => 'Get Test Media',
        ]);

        $fetched = $this->getMedia($created['id']);

        $this->assertArrayHasKey('id', $fetched, 'Fetched media should have ID');
        $this->assertEquals($created['id'], $fetched['id'], 'IDs should match');
        $this->assertEquals('Get Test Media', $fetched['title'], 'Title should match');
        $this->assertEquals('image/png', $fetched['mime_type'], 'MIME type should match');
        $this->assertEquals('image', $fetched['type'], 'Type should be image');
        $this->assertArrayHasKey('url', $fetched, 'Should have URL');
        $this->assertArrayHasKey('filename', $fetched, 'Should have filename');
        $this->assertArrayHasKey('filesize', $fetched, 'Should have filesize');
    }

    /**
     * Test getting media returns image dimensions for images.
     */
    public function testGetMediaReturnsDimensions(): void
    {
        $created = $this->createTestMedia(['filename' => 'dimensions-test.png']);

        $fetched = $this->getMedia($created['id']);

        $this->assertArrayHasKey('width', $fetched, 'Should have width');
        $this->assertArrayHasKey('height', $fetched, 'Should have height');
        // Our 1x1 test PNG should have dimensions of 1x1.
        $this->assertEquals(1, $fetched['width'], 'Width should be 1 for 1x1 PNG');
        $this->assertEquals(1, $fetched['height'], 'Height should be 1 for 1x1 PNG');
    }

    /**
     * Test getting media returns author information.
     */
    public function testGetMediaReturnsAuthor(): void
    {
        $created = $this->createTestMedia(['filename' => 'author-test.png']);

        $fetched = $this->getMedia($created['id']);

        $this->assertArrayHasKey('author', $fetched, 'Should have author');
        $this->assertIsArray($fetched['author'], 'Author should be an array');
        $this->assertArrayHasKey('id', $fetched['author'], 'Author should have id');
        $this->assertArrayHasKey('name', $fetched['author'], 'Author should have name');
    }

    /**
     * Test getting a non-existent media item returns error.
     */
    public function testGetNonExistentMedia(): void
    {
        $this->assertToolFails(
            'fa-wpmcp-get-media',
            ['media_id' => 999999999],
            'not found'
        );
    }

    /**
     * Test updating media title.
     */
    public function testUpdateMediaTitle(): void
    {
        $created = $this->createTestMedia([
            'filename' => 'update-title-test.png',
            'title'    => 'Original Title',
        ]);

        $result = $this->callTool('fa-wpmcp-update-media', [
            'media_id' => $created['id'],
            'title'    => 'Updated Title',
        ]);

        $this->assertArrayHasKey('updated', $result, 'Result should have updated flag');
        $this->assertTrue($result['updated'], 'Update should succeed');

        // Verify the update persisted.
        $fetched = $this->getMedia($created['id']);
        $this->assertEquals('Updated Title', $fetched['title'], 'Title should be updated');
    }

    /**
     * Test updating media alt text.
     */
    public function testUpdateMediaAltText(): void
    {
        $created = $this->createTestMedia([
            'filename' => 'update-alt-test.png',
            'alt_text' => 'Original alt text',
        ]);

        $this->callTool('fa-wpmcp-update-media', [
            'media_id' => $created['id'],
            'alt_text' => 'Updated alt text',
        ]);

        $fetched = $this->getMedia($created['id']);
        $this->assertEquals('Updated alt text', $fetched['alt_text'], 'Alt text should be updated');
    }

    /**
     * Test updating media caption and description.
     */
    public function testUpdateMediaCaptionAndDescription(): void
    {
        $created = $this->createTestMedia([
            'filename'    => 'update-caption-test.png',
            'caption'     => 'Original caption',
            'description' => 'Original description',
        ]);

        $this->callTool('fa-wpmcp-update-media', [
            'media_id'    => $created['id'],
            'caption'     => 'Updated caption',
            'description' => 'Updated description',
        ]);

        $fetched = $this->getMedia($created['id']);
        $this->assertEquals('Updated caption', $fetched['caption'], 'Caption should be updated');
        $this->assertEquals('Updated description', $fetched['description'], 'Description should be updated');
    }

    /**
     * Test deleting media moves it to trash.
     *
     * Response: {media_id, action, success}
     */
    public function testDeleteMediaToTrash(): void
    {
        $created = $this->createTestMedia(['filename' => 'delete-test.png']);

        $result = $this->callTool('fa-wpmcp-delete-media', ['media_id' => $created['id']]);

        $this->assertArrayHasKey('success', $result, 'Delete result should have success flag');
        $this->assertTrue($result['success'], 'Delete should succeed');
        $this->assertEquals('trashed', $result['action'], 'Action should be trashed');

        // Remove from cleanup list since we manually deleted.
        $this->createdResources['media'] = array_filter(
            $this->createdResources['media'],
            fn($id) => $id !== $created['id']
        );
    }

    /**
     * Test permanently deleting media.
     */
    public function testDeleteMediaPermanently(): void
    {
        $created = $this->createTestMedia(['filename' => 'permanent-delete-test.png']);

        $result = $this->callTool('fa-wpmcp-delete-media', [
            'media_id' => $created['id'],
            'force'    => true,
        ]);

        $this->assertTrue($result['success'], 'Delete should succeed');
        $this->assertEquals('deleted', $result['action'], 'Action should be deleted (permanent)');

        // Remove from cleanup list since we manually deleted.
        $this->createdResources['media'] = array_filter(
            $this->createdResources['media'],
            fn($id) => $id !== $created['id']
        );

        // Verify media no longer exists.
        $this->assertToolFails(
            'fa-wpmcp-get-media',
            ['media_id' => $created['id']],
            'not found'
        );
    }

    /**
     * Test full CRUD lifecycle for media.
     */
    public function testMediaCrudLifecycle(): void
    {
        // Create.
        $media = $this->createTestMedia([
            'filename'    => 'crud-lifecycle.png',
            'title'       => 'CRUD Lifecycle Test',
            'alt_text'    => 'Initial alt text',
            'caption'     => 'Initial caption',
            'description' => 'Initial description',
        ]);
        $this->assertArrayHasKey('id', $media);
        $mediaId = $media['id'];

        // Read.
        $read = $this->getMedia($mediaId);
        $this->assertEquals($mediaId, $read['id']);
        $this->assertEquals('CRUD Lifecycle Test', $read['title']);
        $this->assertEquals('Initial alt text', $read['alt_text']);

        // Update.
        $this->callTool('fa-wpmcp-update-media', [
            'media_id'    => $mediaId,
            'title'       => 'CRUD Lifecycle Updated',
            'alt_text'    => 'Updated alt text',
            'caption'     => 'Updated caption',
            'description' => 'Updated description',
        ]);

        $updated = $this->getMedia($mediaId);
        $this->assertEquals('CRUD Lifecycle Updated', $updated['title']);
        $this->assertEquals('Updated alt text', $updated['alt_text']);
        $this->assertEquals('Updated caption', $updated['caption']);
        $this->assertEquals('Updated description', $updated['description']);

        // Delete (permanent).
        $deleted = $this->callTool('fa-wpmcp-delete-media', [
            'media_id' => $mediaId,
            'force'    => true,
        ]);
        $this->assertTrue($deleted['success']);
        $this->assertEquals('deleted', $deleted['action']);

        // Remove from cleanup.
        $this->createdResources['media'] = array_filter(
            $this->createdResources['media'],
            fn($id) => $id !== $mediaId
        );

        // Verify deleted.
        $this->assertToolFails(
            'fa-wpmcp-get-media',
            ['media_id' => $mediaId],
            'not found'
        );
    }

    /**
     * Create a test media item and track it for cleanup.
     *
     * @param array $overrides Override default media values.
     * @return array Created media data.
     */
    private function createTestMedia(array $overrides = []): array
    {
        $defaults = [
            'filename'  => 'test-image-' . uniqid() . '.png',
            'file_data' => self::TEST_PNG_BASE64,
        ];

        $args  = array_merge($defaults, $overrides);
        $media = $this->callTool('fa-wpmcp-upload-media', $args);

        if (isset($media['id'])) {
            $this->createdResources['media'][] = $media['id'];
        }

        return $media;
    }

    /**
     * Helper to get media and extract from nested response.
     *
     * @param int $mediaId Media ID.
     * @return array Media data.
     */
    private function getMedia(int $mediaId): array
    {
        $response = $this->callTool('fa-wpmcp-get-media', ['media_id' => $mediaId]);

        // get-media returns {media: {...}}
        if (isset($response['media'])) {
            return $response['media'];
        }

        return $response;
    }
}
