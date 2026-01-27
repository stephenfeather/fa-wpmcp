<?php

/**
 * Tests for DeleteCommentMeta ability.
 *
 * @package FAWpmcp\Tests\Abilities\Comments
 */

declare(strict_types=1);

namespace FAWpmcp\Tests\Abilities\Comments;

use FAWpmcp\Abilities\AbstractAbility;
use FAWpmcp\Abilities\Comments\DeleteCommentMeta;
use FAWpmcp\Exceptions\CommentNotFoundException;
use FAWpmcp\Tests\TestCase\AbilityTestTrait;
use FAWpmcp\Tests\TestCase\BrainMonkeyTestCase;
use Brain\Monkey\Functions;

/**
 * Test DeleteCommentMeta ability.
 *
 * @package FAWpmcp\Tests\Abilities\Comments
 */
final class DeleteCommentMetaTest extends BrainMonkeyTestCase
{
    use AbilityTestTrait;

    /**
     * Get an instance of the ability being tested.
     *
     * @return AbstractAbility
     */
    protected function getAbilityInstance(): AbstractAbility
    {
        return new DeleteCommentMeta();
    }

    /**
     * Get expected metadata for the ability.
     *
     * @return array{
     *     name: string,
     *     category: string,
     *     label: string,
     *     description_contains: string,
     *     operation_type: string,
     *     required_capability: string
     * }
     */
    protected function getExpectedMetadata(): array
    {
        return [
            'name'                 => 'fa-wpmcp/delete-comment-meta',
            'category'             => 'comments',
            'label'                => 'Delete Comment Meta',
            'description_contains' => 'metadata',
            'operation_type'       => 'write',
            'required_capability'  => 'moderate_comments',
        ];
    }

    /**
     * Test input schema requires comment_id and meta_key.
     *
     * @return void
     */
    public function testInputSchemaRequiresFields(): void
    {
        $schema = $this->getAbilityInstance()->getInputSchema();
        $this->assertArrayHasKey('comment_id', $schema['properties']);
        $this->assertArrayHasKey('meta_key', $schema['properties']);
        $this->assertContains('comment_id', $schema['required']);
        $this->assertContains('meta_key', $schema['required']);
    }

    /**
     * Test deletes comment meta successfully.
     *
     * @return void
     */
    public function testDeletesCommentMetaSuccessfully(): void
    {
        $mock_comment = (object) ['comment_ID' => 42];

        Functions\expect('get_comment')
            ->once()
            ->with(42)
            ->andReturn($mock_comment);

        Functions\expect('delete_comment_meta')
            ->once()
            ->with(42, 'rating', '')
            ->andReturn(true);

        $result = $this->getAbilityInstance()->doExecute([
            'comment_id' => 42,
            'meta_key'   => 'rating',
        ]);

        $this->assertTrue($result['success']);
        $this->assertEquals(42, $result['comment_id']);
        $this->assertEquals('rating', $result['meta_key']);
    }

    /**
     * Test deletes specific meta value.
     *
     * @return void
     */
    public function testDeletesSpecificMetaValue(): void
    {
        $mock_comment = (object) ['comment_ID' => 42];

        Functions\expect('get_comment')
            ->once()
            ->andReturn($mock_comment);

        Functions\expect('delete_comment_meta')
            ->once()
            ->with(42, 'tags', 'old-tag')
            ->andReturn(true);

        $result = $this->getAbilityInstance()->doExecute([
            'comment_id' => 42,
            'meta_key'   => 'tags',
            'meta_value' => 'old-tag',
        ]);

        $this->assertTrue($result['success']);
    }

    /**
     * Test throws exception for non-existent comment.
     *
     * @return void
     */
    public function testThrowsExceptionForNonExistentComment(): void
    {
        $this->expectException(CommentNotFoundException::class);

        Functions\expect('get_comment')
            ->once()
            ->andReturn(null);

        $this->getAbilityInstance()->doExecute([
            'comment_id' => 999,
            'meta_key'   => 'rating',
        ]);
    }
}
