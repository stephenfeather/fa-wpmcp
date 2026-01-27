<?php

/**
 * Tests for BulkModerateComments ability.
 *
 * @package FAWpmcp\Tests\Abilities\Comments
 */

declare(strict_types=1);

namespace FAWpmcp\Tests\Abilities\Comments;

use FAWpmcp\Abilities\AbstractAbility;
use FAWpmcp\Abilities\Comments\BulkModerateComments;
use FAWpmcp\Tests\TestCase\AbilityTestTrait;
use FAWpmcp\Tests\TestCase\BrainMonkeyTestCase;
use Brain\Monkey\Functions;

/**
 * Test BulkModerateComments ability.
 *
 * @package FAWpmcp\Tests\Abilities\Comments
 */
final class BulkModerateCommentsTest extends BrainMonkeyTestCase
{
    use AbilityTestTrait;

    /**
     * Get an instance of the ability being tested.
     *
     * @return AbstractAbility
     */
    protected function getAbilityInstance(): AbstractAbility
    {
        return new BulkModerateComments();
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
            'name'                 => 'fa-wpmcp/bulk-moderate-comments',
            'category'             => 'comments',
            'label'                => 'Bulk Moderate Comments',
            'description_contains' => 'moderate',
            'operation_type'       => 'write',
            'required_capability'  => 'moderate_comments',
        ];
    }

    /**
     * Test input schema requires comment_ids and action.
     *
     * @return void
     */
    public function testInputSchemaRequiresFields(): void
    {
        $schema = $this->getAbilityInstance()->getInputSchema();
        $this->assertArrayHasKey('comment_ids', $schema['properties']);
        $this->assertArrayHasKey('action', $schema['properties']);
        $this->assertContains('comment_ids', $schema['required']);
        $this->assertContains('action', $schema['required']);
    }

    /**
     * Test approves multiple comments.
     *
     * @return void
     */
    public function testApprovesMultipleComments(): void
    {
        $mock_comment = (object) ['comment_ID' => 1];

        Functions\expect('get_comment')
            ->times(3)
            ->andReturn($mock_comment);

        Functions\expect('wp_set_comment_status')
            ->times(3)
            ->with(\Mockery::type('int'), 'approve')
            ->andReturn(true);

        $result = $this->getAbilityInstance()->doExecute([
            'comment_ids' => [1, 2, 3],
            'action'      => 'approve',
        ]);

        $this->assertEquals('approve', $result['action']);
        $this->assertEquals(3, $result['processed']);
        $this->assertEquals(3, $result['succeeded']);
        $this->assertEquals(0, $result['failed']);
    }

    /**
     * Test spams comments.
     *
     * @return void
     */
    public function testSpamsComments(): void
    {
        $mock_comment = (object) ['comment_ID' => 1];

        Functions\expect('get_comment')
            ->times(2)
            ->andReturn($mock_comment);

        Functions\expect('wp_spam_comment')
            ->times(2)
            ->andReturn(true);

        $result = $this->getAbilityInstance()->doExecute([
            'comment_ids' => [1, 2],
            'action'      => 'spam',
        ]);

        $this->assertEquals('spam', $result['action']);
        $this->assertEquals(2, $result['succeeded']);
    }

    /**
     * Test trashes comments.
     *
     * @return void
     */
    public function testTrashesComments(): void
    {
        $mock_comment = (object) ['comment_ID' => 1];

        Functions\expect('get_comment')
            ->twice()
            ->andReturn($mock_comment);

        Functions\expect('wp_trash_comment')
            ->twice()
            ->andReturn(true);

        $result = $this->getAbilityInstance()->doExecute([
            'comment_ids' => [1, 2],
            'action'      => 'trash',
        ]);

        $this->assertEquals('trash', $result['action']);
        $this->assertEquals(2, $result['succeeded']);
    }

    /**
     * Test handles non-existent comments.
     *
     * @return void
     */
    public function testHandlesNonExistentComments(): void
    {
        $mock_comment = (object) ['comment_ID' => 1];

        Functions\expect('get_comment')
            ->twice()
            ->andReturnUsing(function ($id) use ($mock_comment) {
                return $id === 1 ? $mock_comment : null;
            });

        Functions\expect('wp_set_comment_status')
            ->once()
            ->andReturn(true);

        $result = $this->getAbilityInstance()->doExecute([
            'comment_ids' => [1, 999],
            'action'      => 'approve',
        ]);

        $this->assertEquals(2, $result['processed']);
        $this->assertEquals(1, $result['succeeded']);
        $this->assertEquals(1, $result['failed']);
        $this->assertEquals('Comment not found', $result['results'][1]['error']);
    }

    /**
     * Test unapproves comments.
     *
     * @return void
     */
    public function testUnapprovesComments(): void
    {
        $mock_comment = (object) ['comment_ID' => 1];

        Functions\expect('get_comment')
            ->once()
            ->andReturn($mock_comment);

        Functions\expect('wp_set_comment_status')
            ->once()
            ->with(1, 'hold')
            ->andReturn(true);

        $result = $this->getAbilityInstance()->doExecute([
            'comment_ids' => [1],
            'action'      => 'unapprove',
        ]);

        $this->assertEquals(1, $result['succeeded']);
    }

    /**
     * Test unspams comments.
     *
     * @return void
     */
    public function testUnspamsComments(): void
    {
        $mock_comment = (object) ['comment_ID' => 1];

        Functions\expect('get_comment')
            ->once()
            ->andReturn($mock_comment);

        Functions\expect('wp_unspam_comment')
            ->once()
            ->andReturn(true);

        $result = $this->getAbilityInstance()->doExecute([
            'comment_ids' => [1],
            'action'      => 'unspam',
        ]);

        $this->assertEquals(1, $result['succeeded']);
    }

    /**
     * Test untrashes comments.
     *
     * @return void
     */
    public function testUntrashesComments(): void
    {
        $mock_comment = (object) ['comment_ID' => 1];

        Functions\expect('get_comment')
            ->once()
            ->andReturn($mock_comment);

        Functions\expect('wp_untrash_comment')
            ->once()
            ->andReturn(true);

        $result = $this->getAbilityInstance()->doExecute([
            'comment_ids' => [1],
            'action'      => 'untrash',
        ]);

        $this->assertEquals(1, $result['succeeded']);
    }
}
