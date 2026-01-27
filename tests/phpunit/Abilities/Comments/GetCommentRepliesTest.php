<?php

/**
 * Tests for GetCommentReplies ability.
 *
 * @package FAWpmcp\Tests\Abilities\Comments
 */

declare(strict_types=1);

namespace FAWpmcp\Tests\Abilities\Comments;

use FAWpmcp\Abilities\AbstractAbility;
use FAWpmcp\Abilities\Comments\GetCommentReplies;
use FAWpmcp\Exceptions\CommentNotFoundException;
use FAWpmcp\Tests\TestCase\AbilityTestTrait;
use FAWpmcp\Tests\TestCase\BrainMonkeyTestCase;
use Brain\Monkey\Functions;

/**
 * Test GetCommentReplies ability.
 *
 * @package FAWpmcp\Tests\Abilities\Comments
 */
final class GetCommentRepliesTest extends BrainMonkeyTestCase
{
    use AbilityTestTrait;

    /**
     * Get an instance of the ability being tested.
     *
     * @return AbstractAbility
     */
    protected function getAbilityInstance(): AbstractAbility
    {
        return new GetCommentReplies();
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
            'name'                 => 'fa-wpmcp/get-comment-replies',
            'category'             => 'comments',
            'label'                => 'Get Comment Replies',
            'description_contains' => 'replies',
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
     * Test gets direct replies.
     *
     * @return void
     */
    public function testGetsDirectReplies(): void
    {
        $parent_comment = (object) ['comment_ID' => 1];
        $reply1 = (object) [
            'comment_ID'           => 2,
            'comment_parent'       => 1,
            'comment_post_ID'      => 10,
            'comment_author'       => 'Jane',
            'comment_author_email' => 'jane@example.com',
            'comment_content'      => 'Reply 1',
            'comment_date'         => '2026-01-27 10:00:00',
            'comment_approved'     => '1',
        ];
        $reply2 = (object) [
            'comment_ID'           => 3,
            'comment_parent'       => 1,
            'comment_post_ID'      => 10,
            'comment_author'       => 'Bob',
            'comment_author_email' => 'bob@example.com',
            'comment_content'      => 'Reply 2',
            'comment_date'         => '2026-01-27 11:00:00',
            'comment_approved'     => '1',
        ];

        Functions\expect('get_comment')
            ->once()
            ->with(1)
            ->andReturn($parent_comment);

        Functions\expect('get_comments')
            ->once()
            ->with(\Mockery::on(function ($args) {
                return $args['parent'] === 1 && $args['status'] === 'approve';
            }))
            ->andReturn([$reply1, $reply2]);

        $result = $this->getAbilityInstance()->doExecute(['comment_id' => 1]);

        $this->assertEquals(1, $result['parent_id']);
        $this->assertCount(2, $result['replies']);
        $this->assertEquals(2, $result['count']);
        $this->assertEquals(2, $result['replies'][0]['id']);
        $this->assertEquals('Jane', $result['replies'][0]['author']);
    }

    /**
     * Test returns empty array when no replies.
     *
     * @return void
     */
    public function testReturnsEmptyArrayWhenNoReplies(): void
    {
        $parent_comment = (object) ['comment_ID' => 1];

        Functions\expect('get_comment')
            ->once()
            ->andReturn($parent_comment);

        Functions\expect('get_comments')
            ->once()
            ->andReturn([]);

        $result = $this->getAbilityInstance()->doExecute(['comment_id' => 1]);

        $this->assertEquals([], $result['replies']);
        $this->assertEquals(0, $result['count']);
    }

    /**
     * Test filters by status.
     *
     * @return void
     */
    public function testFiltersByStatus(): void
    {
        $parent_comment = (object) ['comment_ID' => 1];

        Functions\expect('get_comment')
            ->once()
            ->andReturn($parent_comment);

        Functions\expect('get_comments')
            ->once()
            ->with(\Mockery::on(function ($args) {
                return $args['status'] === 'all';
            }))
            ->andReturn([]);

        $result = $this->getAbilityInstance()->doExecute([
            'comment_id' => 1,
            'status'     => 'all',
        ]);

        $this->assertEquals([], $result['replies']);
    }

    /**
     * Test throws exception for non-existent parent.
     *
     * @return void
     */
    public function testThrowsExceptionForNonExistentParent(): void
    {
        $this->expectException(CommentNotFoundException::class);
        $this->expectExceptionMessage('Parent comment not found');

        Functions\expect('get_comment')
            ->once()
            ->andReturn(null);

        $this->getAbilityInstance()->doExecute(['comment_id' => 999]);
    }
}
