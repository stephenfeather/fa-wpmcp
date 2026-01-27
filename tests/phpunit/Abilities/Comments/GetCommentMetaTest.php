<?php

/**
 * Tests for GetCommentMeta ability.
 *
 * @package FAWpmcp\Tests\Abilities\Comments
 */

declare(strict_types=1);

namespace FAWpmcp\Tests\Abilities\Comments;

use FAWpmcp\Abilities\AbstractAbility;
use FAWpmcp\Abilities\Comments\GetCommentMeta;
use FAWpmcp\Exceptions\CommentNotFoundException;
use FAWpmcp\Tests\TestCase\AbilityTestTrait;
use FAWpmcp\Tests\TestCase\BrainMonkeyTestCase;
use Brain\Monkey\Functions;

/**
 * Test GetCommentMeta ability.
 *
 * @package FAWpmcp\Tests\Abilities\Comments
 */
final class GetCommentMetaTest extends BrainMonkeyTestCase
{
    use AbilityTestTrait;

    /**
     * Get an instance of the ability being tested.
     *
     * @return AbstractAbility
     */
    protected function getAbilityInstance(): AbstractAbility
    {
        return new GetCommentMeta();
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
            'name'                 => 'fa-wpmcp/get-comment-meta',
            'category'             => 'comments',
            'label'                => 'Get Comment Meta',
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
     * Test gets specific meta by key.
     *
     * @return void
     */
    public function testGetsSpecificMetaByKey(): void
    {
        $mock_comment = (object) ['comment_ID' => 42];

        Functions\expect('get_comment')
            ->once()
            ->with(42)
            ->andReturn($mock_comment);

        Functions\expect('get_comment_meta')
            ->once()
            ->with(42, 'rating', true)
            ->andReturn('5');

        $result = $this->getAbilityInstance()->doExecute([
            'comment_id' => 42,
            'meta_key'   => 'rating',
            'single'     => true,
        ]);

        $this->assertEquals(42, $result['comment_id']);
        $this->assertEquals('rating', $result['meta_key']);
        $this->assertEquals('5', $result['meta_value']);
    }

    /**
     * Test gets all meta when no key specified.
     *
     * @return void
     */
    public function testGetsAllMetaWhenNoKeySpecified(): void
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
                'rating' => ['5'],
                'verified' => ['true'],
            ]);

        $result = $this->getAbilityInstance()->doExecute([
            'comment_id' => 42,
        ]);

        $this->assertEquals(42, $result['comment_id']);
        $this->assertArrayHasKey('meta', $result);
        $this->assertEquals('5', $result['meta']['rating']);
        $this->assertEquals('true', $result['meta']['verified']);
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
