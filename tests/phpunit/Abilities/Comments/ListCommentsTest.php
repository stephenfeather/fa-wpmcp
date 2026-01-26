<?php

/**
 * Tests for ListComments ability.
 *
 * @package FAWpmcp\Tests\Abilities\Comments
 */

declare(strict_types=1);

namespace FAWpmcp\Tests\Abilities\Comments;

use FAWpmcp\Abilities\Comments\ListComments;
use Brain\Monkey\Functions;
use PHPUnit\Framework\TestCase;
use Mockery;

/**
 * Test ListComments ability.
 *
 * @package FAWpmcp\Tests\Abilities\Comments
 */
final class ListCommentsTest extends TestCase
{
    use \Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;

    /**
     * Set up test environment.
     *
     * @return void
     */
    protected function setUp(): void
    {
        parent::setUp();
        \Brain\Monkey\setUp();
    }

    /**
     * Tear down test environment.
     *
     * @return void
     */
    protected function tearDown(): void
    {
        \Brain\Monkey\tearDown();
        parent::tearDown();
    }

    /**
     * Test ability metadata.
     *
     * @return void
     */
    public function test_ability_metadata(): void
    {
        $ability = new ListComments();

        $this->assertEquals('fa-wpmcp/list-comments', $ability->getName());
        $this->assertEquals('comments', $ability->getCategory());
        $this->assertEquals('List Comments', $ability->getLabel());
        $this->assertEquals('read', $ability->getRequiredCapability());
        $this->assertStringContainsString('comment', strtolower($ability->getDescription()));
    }

    /**
     * Test schema structures.
     *
     * @return void
     */
    public function test_schema_structures(): void
    {
        $ability      = new ListComments();
        $input_schema = $ability->getInputSchema();

        $this->assertEquals('object', $input_schema['type']);
        $this->assertArrayHasKey('page', $input_schema['properties']);
        $this->assertArrayHasKey('per_page', $input_schema['properties']);
        $this->assertArrayHasKey('post_id', $input_schema['properties']);
        $this->assertArrayHasKey('status', $input_schema['properties']);

        $output_schema = $ability->getOutputSchema();
        $this->assertEquals('object', $output_schema['type']);
        $this->assertArrayHasKey('comments', $output_schema['properties']);
        $this->assertArrayHasKey('total', $output_schema['properties']);
        $this->assertArrayHasKey('page', $output_schema['properties']);
        $this->assertArrayHasKey('per_page', $output_schema['properties']);
    }

    /**
     * Test lists comments with pagination.
     *
     * @return void
     */
    public function test_lists_comments_with_pagination(): void
    {
        $mock_comments = array(
            (object) array(
                'comment_ID'           => 1,
                'comment_post_ID'      => 10,
                'comment_author'       => 'John Doe',
                'comment_author_email' => 'john@example.com',
                'comment_content'      => 'Great post!',
                'comment_date'         => '2026-01-21 10:00:00',
                'comment_approved'     => '1',
            ),
        );

        Functions\expect('get_comments')
            ->once()
            ->andReturn($mock_comments);

        Functions\expect('wp_count_comments')
            ->once()
            ->andReturn((object) array( 'approved' => '10' ));

        Functions\expect('get_comment_link')
            ->once()
            ->andReturn('https://example.com/post#comment-1');

        $ability = new ListComments();
        $result  = $ability->doExecute(
            array(
                'page'     => 1,
                'per_page' => 10,
            )
        );

        $this->assertArrayHasKey('comments', $result);
        $this->assertArrayHasKey('total', $result);
        $this->assertEquals(10, $result['total']);
    }

    /**
     * Test filters by post ID.
     *
     * @return void
     */
    public function test_filters_by_post_id(): void
    {
        Functions\expect('get_comments')
            ->once()
            ->with(
                Mockery::on(
                    function ($args) {
                        return $args['post_id'] === 42;
                    }
                )
            )
            ->andReturn(array());

        Functions\expect('wp_count_comments')
            ->with(42)
            ->andReturn((object) array( 'approved' => '0' ));

        $ability = new ListComments();
        $ability->doExecute(array( 'post_id' => 42 ));

        $this->assertTrue(true);
    }

    /**
     * Test filters by status.
     *
     * @return void
     */
    public function test_filters_by_status(): void
    {
        Functions\expect('get_comments')
            ->once()
            ->with(
                Mockery::on(
                    function ($args) {
                        return $args['status'] === 'hold';
                    }
                )
            )
            ->andReturn(array());

        Functions\expect('wp_count_comments')
            ->andReturn((object) array( 'moderated' => '5' ));

        $ability = new ListComments();
        $result  = $ability->doExecute(array( 'status' => 'hold' ));

        $this->assertEquals(5, $result['total']);
    }

    /**
     * Test returns total for all statuses.
     *
     * @return void
     */
    public function test_counts_all_status(): void
    {
        Functions\expect('get_comments')
            ->once()
            ->andReturn(array());

        Functions\expect('wp_count_comments')
            ->andReturn((object) array( 'total_comments' => '12' ));

        $ability = new ListComments();
        $result  = $ability->doExecute(array( 'status' => 'all' ));

        $this->assertEquals(12, $result['total']);
    }

    /**
     * Test returns spam count when status is spam.
     *
     * @return void
     */
    public function test_counts_spam_status(): void
    {
        Functions\expect('get_comments')
            ->once()
            ->andReturn(array());

        Functions\expect('wp_count_comments')
            ->andReturn((object) array( 'spam' => '3' ));

        $ability = new ListComments();
        $result  = $ability->doExecute(array( 'status' => 'spam' ));

        $this->assertEquals(3, $result['total']);
    }

    /**
     * Test returns trash count when status is trash.
     *
     * @return void
     */
    public function test_counts_trash_status(): void
    {
        Functions\expect('get_comments')
            ->once()
            ->andReturn(array());

        Functions\expect('wp_count_comments')
            ->andReturn((object) array( 'trash' => '2' ));

        $ability = new ListComments();
        $result  = $ability->doExecute(array( 'status' => 'trash' ));

        $this->assertEquals(2, $result['total']);
    }
}
