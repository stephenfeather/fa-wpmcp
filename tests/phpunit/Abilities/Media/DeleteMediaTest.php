<?php

/**
 * Tests for DeleteMedia.
 *
 * @package FAWpmcp\Tests\Abilities\Media
 */

declare(strict_types=1);

namespace FAWpmcp\Tests\Abilities\Media;

use FAWpmcp\Abilities\Media\DeleteMedia;
use FAWpmcp\Exceptions\MediaDeletionException;
use FAWpmcp\Exceptions\MediaNotFoundException;
use Brain\Monkey\Functions;
use PHPUnit\Framework\TestCase;

final class DeleteMediaTest extends TestCase
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
        $ability = new DeleteMedia();
        $this->assertEquals('fa-wpmcp/delete-media', $ability->getName());
        $this->assertEquals('media', $ability->getCategory());
        $this->assertEquals('Delete Media', $ability->getLabel());
        $this->assertStringContainsString('delete', strtolower($ability->getDescription()));
        $this->assertEquals('delete_posts', $ability->getRequiredCapability());
    }

    public function test_operation_type_is_write(): void
    {
        $ability = new DeleteMedia();
        $this->assertEquals('write', $ability->getOperationType());
    }

    public function test_annotations_mark_destructive(): void
    {
        $ability     = new DeleteMedia();
        $annotations = $ability->getAnnotations();

        $this->assertTrue($annotations['destructive']);
        $this->assertFalse($annotations['idempotent']);
    }

    public function test_input_schema_requires_media_id(): void
    {
        $ability = new DeleteMedia();
        $schema  = $ability->getInputSchema();

        $this->assertEquals('object', $schema['type']);
        $this->assertArrayHasKey('media_id', $schema['properties']);
        $this->assertArrayHasKey('force', $schema['properties']);
        $this->assertContains('media_id', $schema['required']);
        $this->assertNotContains('force', $schema['required']);
    }

    public function test_output_schema_structure(): void
    {
        $ability = new DeleteMedia();
        $schema  = $ability->getOutputSchema();

        $this->assertEquals('object', $schema['type']);
        $this->assertArrayHasKey('media_id', $schema['properties']);
        $this->assertArrayHasKey('action', $schema['properties']);
        $this->assertArrayHasKey('success', $schema['properties']);
    }

    public function test_trashes_media_by_default(): void
    {
        $post             = new \stdClass();
        $post->ID         = 42;
        $post->post_type  = 'attachment';

        Functions\expect('get_post')->once()->with(42)->andReturn($post);
        Functions\expect('wp_delete_attachment')->once()->with(42, false)->andReturn($post);

        $ability = new DeleteMedia();
        $result  = $ability->doExecute(array( 'media_id' => 42 ));

        $this->assertEquals(42, $result['media_id']);
        $this->assertEquals('trashed', $result['action']);
        $this->assertTrue($result['success']);
    }

    public function test_permanently_deletes_when_force_true(): void
    {
        $post             = new \stdClass();
        $post->ID         = 42;
        $post->post_type  = 'attachment';

        Functions\expect('get_post')->once()->with(42)->andReturn($post);
        Functions\expect('wp_delete_attachment')->once()->with(42, true)->andReturn($post);

        $ability = new DeleteMedia();
        $result  = $ability->doExecute(
            array(
                'media_id' => 42,
                'force'    => true,
            )
        );

        $this->assertEquals(42, $result['media_id']);
        $this->assertEquals('deleted', $result['action']);
        $this->assertTrue($result['success']);
    }

    public function test_throws_exception_when_media_not_found(): void
    {
        $this->expectException(MediaNotFoundException::class);
        $this->expectExceptionMessage('Media 999 not found');

        Functions\expect('get_post')->once()->with(999)->andReturn(null);

        $ability = new DeleteMedia();
        $ability->doExecute(array( 'media_id' => 999 ));
    }

    public function test_throws_exception_when_post_is_not_attachment(): void
    {
        $this->expectException(MediaNotFoundException::class);
        $this->expectExceptionMessage('Post 42 is not a media attachment');

        $post             = new \stdClass();
        $post->ID         = 42;
        $post->post_type  = 'post';

        Functions\expect('get_post')->once()->with(42)->andReturn($post);

        $ability = new DeleteMedia();
        $ability->doExecute(array( 'media_id' => 42 ));
    }

    public function test_throws_exception_when_trash_fails(): void
    {
        $this->expectException(MediaDeletionException::class);
        $this->expectExceptionMessage('Failed to trashed media 42');

        $post             = new \stdClass();
        $post->ID         = 42;
        $post->post_type  = 'attachment';

        Functions\expect('get_post')->once()->with(42)->andReturn($post);
        Functions\expect('wp_delete_attachment')->once()->with(42, false)->andReturn(false);

        $ability = new DeleteMedia();
        $ability->doExecute(array( 'media_id' => 42 ));
    }

    public function test_throws_exception_when_delete_fails(): void
    {
        $this->expectException(MediaDeletionException::class);
        $this->expectExceptionMessage('Failed to deleted media 42');

        $post             = new \stdClass();
        $post->ID         = 42;
        $post->post_type  = 'attachment';

        Functions\expect('get_post')->once()->with(42)->andReturn($post);
        Functions\expect('wp_delete_attachment')->once()->with(42, true)->andReturn(false);

        $ability = new DeleteMedia();
        $ability->doExecute(
            array(
                'media_id' => 42,
                'force'    => true,
            )
        );
    }

    public function test_throws_exception_when_delete_returns_null(): void
    {
        $this->expectException(MediaDeletionException::class);

        $post             = new \stdClass();
        $post->ID         = 42;
        $post->post_type  = 'attachment';

        Functions\expect('get_post')->once()->with(42)->andReturn($post);
        Functions\expect('wp_delete_attachment')->once()->with(42, true)->andReturn(null);

        $ability = new DeleteMedia();
        $ability->doExecute(
            array(
                'media_id' => 42,
                'force'    => true,
            )
        );
    }
}
