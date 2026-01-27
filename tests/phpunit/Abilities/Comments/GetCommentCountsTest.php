<?php

/**
 * Tests for GetCommentCounts ability.
 *
 * @package FAWpmcp\Tests\Abilities\Comments
 */

declare(strict_types=1);

namespace FAWpmcp\Tests\Abilities\Comments;

use FAWpmcp\Abilities\AbstractAbility;
use FAWpmcp\Abilities\Comments\GetCommentCounts;
use FAWpmcp\Tests\TestCase\AbilityTestTrait;
use FAWpmcp\Tests\TestCase\BrainMonkeyTestCase;
use Brain\Monkey\Functions;

/**
 * Test GetCommentCounts ability.
 *
 * @package FAWpmcp\Tests\Abilities\Comments
 */
final class GetCommentCountsTest extends BrainMonkeyTestCase
{
    use AbilityTestTrait;

    /**
     * Get an instance of the ability being tested.
     *
     * @return AbstractAbility
     */
    protected function getAbilityInstance(): AbstractAbility
    {
        return new GetCommentCounts();
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
            'name'                 => 'fa-wpmcp/get-comment-counts',
            'category'             => 'comments',
            'label'                => 'Get Comment Counts',
            'description_contains' => 'counts',
            'operation_type'       => 'read',
            'required_capability'  => 'read',
        ];
    }

    /**
     * Test post_id is optional.
     *
     * @return void
     */
    public function testPostIdIsOptional(): void
    {
        $schema = $this->getAbilityInstance()->getInputSchema();
        $this->assertArrayHasKey('post_id', $schema['properties']);
        $this->assertEquals([], $schema['required']);
    }

    /**
     * Test gets global comment counts.
     *
     * @return void
     */
    public function testGetsGlobalCommentCounts(): void
    {
        $mock_counts = (object) [
            'approved'       => 100,
            'moderated'      => 5,
            'spam'           => 10,
            'trash'          => 2,
            'post-trashed'   => 1,
            'total_comments' => 118,
            'all'            => 115,
        ];

        Functions\expect('wp_count_comments')
            ->once()
            ->with(0)
            ->andReturn($mock_counts);

        $result = $this->getAbilityInstance()->doExecute([]);

        $this->assertEquals(100, $result['approved']);
        $this->assertEquals(5, $result['awaiting_moderation']);
        $this->assertEquals(10, $result['spam']);
        $this->assertEquals(2, $result['trash']);
        $this->assertEquals(1, $result['post_trashed']);
        $this->assertEquals(118, $result['total_comments']);
        $this->assertEquals(115, $result['all']);
        $this->assertArrayNotHasKey('post_id', $result);
    }

    /**
     * Test gets post-specific comment counts.
     *
     * @return void
     */
    public function testGetsPostSpecificCommentCounts(): void
    {
        $mock_counts = (object) [
            'approved'       => 10,
            'moderated'      => 2,
            'spam'           => 1,
            'trash'          => 0,
            'post-trashed'   => 0,
            'total_comments' => 13,
            'all'            => 12,
        ];

        Functions\expect('wp_count_comments')
            ->once()
            ->with(42)
            ->andReturn($mock_counts);

        $result = $this->getAbilityInstance()->doExecute(['post_id' => 42]);

        $this->assertEquals(42, $result['post_id']);
        $this->assertEquals(10, $result['approved']);
        $this->assertEquals(2, $result['awaiting_moderation']);
    }

    /**
     * Test handles missing post-trashed property.
     *
     * @return void
     */
    public function testHandlesMissingPostTrashedProperty(): void
    {
        $mock_counts = (object) [
            'approved'       => 10,
            'moderated'      => 2,
            'spam'           => 1,
            'trash'          => 0,
            'total_comments' => 13,
            'all'            => 12,
        ];

        Functions\expect('wp_count_comments')
            ->once()
            ->andReturn($mock_counts);

        $result = $this->getAbilityInstance()->doExecute([]);

        $this->assertEquals(0, $result['post_trashed']);
    }
}
