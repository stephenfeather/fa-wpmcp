<?php

/**
 * Tests for SetCommentMeta ability.
 *
 * @package FAWpmcp\Tests\Abilities\Comments
 */

declare(strict_types=1);

namespace FAWpmcp\Tests\Abilities\Comments;

use FAWpmcp\Abilities\AbstractAbility;
use FAWpmcp\Abilities\Comments\SetCommentMeta;
use FAWpmcp\Exceptions\CommentNotFoundException;
use FAWpmcp\Tests\TestCase\AbilityTestTrait;
use FAWpmcp\Tests\TestCase\BrainMonkeyTestCase;
use Brain\Monkey\Functions;

/**
 * Test SetCommentMeta ability.
 *
 * @package FAWpmcp\Tests\Abilities\Comments
 */
final class SetCommentMetaTest extends BrainMonkeyTestCase
{
    use AbilityTestTrait;

    /**
     * Get an instance of the ability being tested.
     *
     * @return AbstractAbility
     */
    protected function getAbilityInstance(): AbstractAbility
    {
        return new SetCommentMeta();
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
            'name'                 => 'fa-wpmcp/set-comment-meta',
            'category'             => 'comments',
            'label'                => 'Set Comment Meta',
            'description_contains' => 'metadata',
            'operation_type'       => 'write',
            'required_capability'  => 'moderate_comments',
        ];
    }

    /**
     * Test input schema requires comment_id, meta_key, meta_value.
     *
     * @return void
     */
    public function testInputSchemaRequiresFields(): void
    {
        $schema = $this->getAbilityInstance()->getInputSchema();
        $this->assertArrayHasKey('comment_id', $schema['properties']);
        $this->assertArrayHasKey('meta_key', $schema['properties']);
        $this->assertArrayHasKey('meta_value', $schema['properties']);
        $this->assertContains('comment_id', $schema['required']);
        $this->assertContains('meta_key', $schema['required']);
        $this->assertContains('meta_value', $schema['required']);
    }

    /**
     * Test sets comment meta successfully.
     *
     * @return void
     */
    public function testSetsCommentMetaSuccessfully(): void
    {
        $mock_comment = (object) ['comment_ID' => 42];

        Functions\expect('get_comment')
            ->once()
            ->with(42)
            ->andReturn($mock_comment);

        Functions\expect('update_comment_meta')
            ->once()
            ->with(42, 'rating', '5')
            ->andReturn(123);

        $result = $this->getAbilityInstance()->doExecute([
            'comment_id' => 42,
            'meta_key'   => 'rating',
            'meta_value' => '5',
        ]);

        $this->assertTrue($result['success']);
        $this->assertEquals(42, $result['comment_id']);
        $this->assertEquals('rating', $result['meta_key']);
        $this->assertEquals(123, $result['meta_id']);
    }

    /**
     * Test returns success true on update.
     *
     * @return void
     */
    public function testReturnsSuccessTrueOnUpdate(): void
    {
        $mock_comment = (object) ['comment_ID' => 42];

        Functions\expect('get_comment')
            ->once()
            ->andReturn($mock_comment);

        Functions\expect('update_comment_meta')
            ->once()
            ->andReturn(true);

        $result = $this->getAbilityInstance()->doExecute([
            'comment_id' => 42,
            'meta_key'   => 'rating',
            'meta_value' => '4',
        ]);

        $this->assertTrue($result['success']);
        $this->assertEquals(0, $result['meta_id']);
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
            'meta_value' => '5',
        ]);
    }
}
