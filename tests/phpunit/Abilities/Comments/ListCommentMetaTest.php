<?php

/**
 * Tests for ListCommentMeta ability.
 *
 * @package FAWpmcp\Tests\Abilities\Comments
 */

declare(strict_types=1);

namespace FAWpmcp\Tests\Abilities\Comments;

use FAWpmcp\Abilities\AbstractAbility;
use FAWpmcp\Abilities\Comments\ListCommentMeta;
use FAWpmcp\Exceptions\CommentNotFoundException;
use FAWpmcp\Tests\TestCase\AbilityTestTrait;
use FAWpmcp\Tests\TestCase\BrainMonkeyTestCase;
use Brain\Monkey\Functions;

/**
 * Test ListCommentMeta ability.
 *
 * @package FAWpmcp\Tests\Abilities\Comments
 */
final class ListCommentMetaTest extends BrainMonkeyTestCase
{
    use AbilityTestTrait;

    /**
     * Get an instance of the ability being tested.
     *
     * @return AbstractAbility
     */
    protected function getAbilityInstance(): AbstractAbility
    {
        return new ListCommentMeta();
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
            'name'                 => 'fa-wpmcp/list-comment-meta',
            'category'             => 'comments',
            'label'                => 'List Comment Meta',
            'description_contains' => 'metadata',
            'operation_type'       => 'read',
            'required_capability'  => 'read',
        ];
    }

    /**
     * Test input schema requires comment_id.
     *
     * @return void
     */
    public function testInputSchemaRequiresCommentId(): void
    {
        $schema = $this->getAbilityInstance()->getInputSchema();
        $this->assertArrayHasKey('comment_id', $schema['properties']);
        $this->assertContains('comment_id', $schema['required']);
    }

    /**
     * Test lists all comment meta.
     *
     * @return void
     */
    public function testListsAllCommentMeta(): void
    {
        $mock_comment = (object) ['comment_ID' => 42];

        Functions\expect('get_comment')
            ->once()
            ->with(42)
            ->andReturn($mock_comment);

        Functions\expect('get_comment_meta')
            ->once()
            ->with(42)
            ->andReturn([
                'rating'   => ['5'],
                'verified' => ['true'],
                'tags'     => ['tag1', 'tag2'],
            ]);

        $result = $this->getAbilityInstance()->doExecute(['comment_id' => 42]);

        $this->assertEquals(42, $result['comment_id']);
        $this->assertArrayHasKey('meta', $result);
        $this->assertCount(3, $result['meta']);
        $this->assertEquals(3, $result['count']);

        // Check structure.
        $this->assertEquals('rating', $result['meta'][0]['key']);
        $this->assertEquals('5', $result['meta'][0]['value']);
    }

    /**
     * Test returns empty array when no meta.
     *
     * @return void
     */
    public function testReturnsEmptyArrayWhenNoMeta(): void
    {
        $mock_comment = (object) ['comment_ID' => 42];

        Functions\expect('get_comment')
            ->once()
            ->andReturn($mock_comment);

        Functions\expect('get_comment_meta')
            ->once()
            ->andReturn([]);

        $result = $this->getAbilityInstance()->doExecute(['comment_id' => 42]);

        $this->assertEquals([], $result['meta']);
        $this->assertEquals(0, $result['count']);
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

        $this->getAbilityInstance()->doExecute(['comment_id' => 999]);
    }
}
