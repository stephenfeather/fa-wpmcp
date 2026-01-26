<?php

/**
 * Tests for ListPrivacyRequests ability.
 *
 * @package FAWpmcp\Tests\Abilities\Privacy
 */

declare(strict_types=1);

namespace FAWpmcp\Tests\Abilities\Privacy;

use FAWpmcp\Abilities\Privacy\ListPrivacyRequests;
use Brain\Monkey;
use Brain\Monkey\Functions;
use Mockery;
use PHPUnit\Framework\TestCase;

/**
 * Test ListPrivacyRequests ability functionality.
 *
 * Tests cover:
 * - Listing privacy requests with pagination
 * - Filtering by request type and status
 * - Return format
 *
 * @package FAWpmcp\Tests\Abilities\Privacy
 */
class ListPrivacyRequestsTest extends TestCase
{
    /**
     * Set up Brain\Monkey before each test.
     *
     * @return void
     */
    protected function setUp(): void
    {
        parent::setUp();
        Monkey\setUp();
    }

    /**
     * Tear down Brain\Monkey after each test.
     *
     * @return void
     */
    protected function tearDown(): void
    {
        Monkey\tearDown();
        Mockery::close();
        parent::tearDown();
    }

    /**
     * Test ability returns correct name.
     *
     * @return void
     */
    public function testGetName(): void
    {
        $ability = new ListPrivacyRequests();
        $this->assertEquals('fa-wpmcp/list-privacy-requests', $ability->getName());
    }

    /**
     * Test ability returns correct category.
     *
     * @return void
     */
    public function testGetCategory(): void
    {
        $ability = new ListPrivacyRequests();
        $this->assertEquals('privacy', $ability->getCategory());
    }

    /**
     * Test ability returns correct label.
     *
     * @return void
     */
    public function testGetLabel(): void
    {
        $ability = new ListPrivacyRequests();
        $this->assertEquals('List Privacy Requests', $ability->getLabel());
    }

    /**
     * Test ability requires manage_options capability.
     *
     * @return void
     */
    public function testGetRequiredCapability(): void
    {
        $ability = new ListPrivacyRequests();
        $this->assertEquals('manage_options', $ability->getRequiredCapability());
    }

    /**
     * Test ability returns read operation type.
     *
     * @return void
     */
    public function testGetOperationType(): void
    {
        $ability = new ListPrivacyRequests();
        $this->assertEquals('read', $ability->getOperationType());
    }

    /**
     * Test listing privacy requests successfully.
     *
     * @return void
     */
    public function testExecuteListsPrivacyRequests(): void
    {
        $ability = new ListPrivacyRequests();
        $input   = array(
            'page'     => 1,
            'per_page' => 10,
        );

        // Mock WP_Query.
        $mock_post1              = Mockery::mock('\WP_Post');
        $mock_post1->ID          = 123;
        $mock_post1->post_status = 'request-pending';
        $mock_post1->post_date   = '2026-01-20 10:00:00';

        $mock_post2              = Mockery::mock('\WP_Post');
        $mock_post2->ID          = 124;
        $mock_post2->post_status = 'request-confirmed';
        $mock_post2->post_date   = '2026-01-21 11:00:00';

        $mock_query                = Mockery::mock('\WP_Query');
        $mock_query->posts         = array( $mock_post1, $mock_post2 );
        $mock_query->found_posts   = 2;
        $mock_query->max_num_pages = 1;

        Functions\expect('get_posts')
            ->once()
            ->andReturn(array( $mock_post1, $mock_post2 ));

        Functions\expect('wp_count_posts')
            ->once()
            ->with('user_request')
            ->andReturn(
                (object) array(
                    'request-pending'   => 1,
                    'request-confirmed' => 1,
                )
            );

        Functions\expect('get_post_meta')
            ->times(6)
            ->andReturnUsing(
                function ($post_id, $key, $single) {
                    if ('_wp_user_request_user_email' === $key) {
                        return 'user@example.com';
                    }
                    if ('action_name' === $key) {
                        return 123 === $post_id ? 'export_personal_data' : 'remove_personal_data';
                    }
                    return '';
                }
            );

        $result = $ability->doExecute($input);

        $this->assertArrayHasKey('requests', $result);
        $this->assertArrayHasKey('total', $result);
        $this->assertCount(2, $result['requests']);
        $this->assertEquals(2, $result['total']);
    }

    /**
     * Test listing with type filter.
     *
     * @return void
     */
    public function testExecuteListsWithTypeFilter(): void
    {
        $ability = new ListPrivacyRequests();
        $input   = array(
            'page'     => 1,
            'per_page' => 10,
            'type'     => 'export_personal_data',
        );

        $mock_post              = Mockery::mock('\WP_Post');
        $mock_post->ID          = 123;
        $mock_post->post_status = 'request-pending';
        $mock_post->post_date   = '2026-01-20 10:00:00';

        Functions\expect('get_posts')
            ->once()
            ->andReturn(array( $mock_post ));

        Functions\expect('wp_count_posts')
            ->once()
            ->andReturn((object) array( 'request-pending' => 1 ));

        Functions\expect('get_post_meta')
            ->times(3)
            ->andReturnUsing(
                function ($post_id, $key, $single) {
                    if ('_wp_user_request_user_email' === $key) {
                        return 'user@example.com';
                    }
                    if ('action_name' === $key) {
                        return 'export_personal_data';
                    }
                    return '';
                }
            );

        $result = $ability->doExecute($input);

        $this->assertCount(1, $result['requests']);
    }
}
