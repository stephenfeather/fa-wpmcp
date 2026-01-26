<?php

/**
 * Tests for CreateComment.
 *
 * @package FAWpmcp\Tests\Abilities\Comments
 */

declare(strict_types=1);

namespace FAWpmcp\Tests\Abilities\Comments;

use FAWpmcp\Abilities\AbstractAbility;
use FAWpmcp\Abilities\Comments\CreateComment;
use FAWpmcp\Exceptions\CommentCreationException;
use FAWpmcp\Tests\TestCase\AbilityTestTrait;
use FAWpmcp\Tests\TestCase\BrainMonkeyTestCase;
use Brain\Monkey\Functions;
use Mockery;

/**
 * Test CreateComment ability.
 *
 * @package FAWpmcp\Tests\Abilities\Comments
 */
final class CreateCommentTest extends BrainMonkeyTestCase
{
    use AbilityTestTrait;

    /**
     * Get an instance of the ability being tested.
     *
     * @return AbstractAbility
     */
    protected function getAbilityInstance(): AbstractAbility
    {
        return new CreateComment();
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
            'name'                 => 'fa-wpmcp/create-comment',
            'category'             => 'comments',
            'label'                => 'Create Comment',
            'description_contains' => 'comment',
            'operation_type'       => 'write',
            'required_capability'  => 'edit_posts',
        ];
    }

    /**
     * Test input schema requires all mandatory fields.
     *
     * @return void
     */
    public function testInputSchemaRequiresFields(): void
    {
        $schema = $this->getAbilityInstance()->getInputSchema();

        $this->assertArrayHasKey('post_id', $schema['properties']);
        $this->assertArrayHasKey('author', $schema['properties']);
        $this->assertArrayHasKey('email', $schema['properties']);
        $this->assertArrayHasKey('content', $schema['properties']);
        $this->assertContains('post_id', $schema['required']);
        $this->assertContains('author', $schema['required']);
        $this->assertContains('email', $schema['required']);
        $this->assertContains('content', $schema['required']);
    }

    /**
     * Test output schema includes comment_id and link.
     *
     * @return void
     */
    public function testOutputSchemaStructure(): void
    {
        $schema = $this->getAbilityInstance()->getOutputSchema();

        $this->assertArrayHasKey('comment_id', $schema['properties']);
        $this->assertArrayHasKey('link', $schema['properties']);
    }

    /**
     * Test creates comment successfully.
     *
     * @return void
     */
    public function testCreatesComment(): void
    {
        Functions\expect('sanitize_text_field')->once()->with('John')->andReturn('John');
        Functions\expect('sanitize_email')->once()->with('john@example.com')->andReturn('john@example.com');
        Functions\expect('wp_kses_post')->once()->with('Great post!')->andReturn('Great post!');

        Functions\expect('wp_insert_comment')
            ->once()
            ->with(
                Mockery::on(
                    function ($args) {
                        return 10 === $args['comment_post_ID']
                            && 'John' === $args['comment_author']
                            && 'john@example.com' === $args['comment_author_email']
                            && 'Great post!' === $args['comment_content'];
                    }
                )
            )
            ->andReturn(42);
        Functions\expect('get_comment_link')->once()->andReturn('https://example.com/post#comment-42');

        $result = $this->getAbilityInstance()->doExecute(
            [
                'post_id' => 10,
                'author'  => 'John',
                'email'   => 'john@example.com',
                'content' => 'Great post!',
            ]
        );

        $this->assertEquals(42, $result['comment_id']);
    }

    /**
     * Test creates comment with optional URL.
     *
     * @return void
     */
    public function testCreatesCommentWithOptionalUrl(): void
    {
        Functions\expect('sanitize_text_field')->once()->with('John')->andReturn('John');
        Functions\expect('sanitize_email')->once()->with('john@example.com')->andReturn('john@example.com');
        Functions\expect('wp_kses_post')->once()->with('Great post!')->andReturn('Great post!');
        Functions\expect('esc_url_raw')->once()->with('https://example.com')->andReturn('https://example.com');

        Functions\expect('wp_insert_comment')
            ->once()
            ->with(
                Mockery::on(
                    function ($args) {
                        return 'https://example.com' === $args['comment_author_url'];
                    }
                )
            )
            ->andReturn(77);

        Functions\expect('get_comment_link')->once()->andReturn('https://example.com/post#comment-77');

        $result = $this->getAbilityInstance()->doExecute(
            [
                'post_id' => 10,
                'author'  => 'John',
                'email'   => 'john@example.com',
                'content' => 'Great post!',
                'url'     => 'https://example.com',
            ]
        );

        $this->assertEquals(77, $result['comment_id']);
    }

    /**
     * Test throws exception when comment creation fails.
     *
     * @return void
     */
    public function testThrowsExceptionWhenCreationFails(): void
    {
        $this->expectException(CommentCreationException::class);
        $this->expectExceptionMessage('Failed to create comment');

        Functions\expect('sanitize_text_field')->once()->with('John')->andReturn('John');
        Functions\expect('sanitize_email')->once()->with('john@example.com')->andReturn('john@example.com');
        Functions\expect('wp_kses_post')->once()->with('Great post!')->andReturn('Great post!');

        Functions\expect('wp_insert_comment')->once()->andReturn(0);

        $this->getAbilityInstance()->doExecute(
            [
                'post_id' => 10,
                'author'  => 'John',
                'email'   => 'john@example.com',
                'content' => 'Great post!',
            ]
        );
    }
}
