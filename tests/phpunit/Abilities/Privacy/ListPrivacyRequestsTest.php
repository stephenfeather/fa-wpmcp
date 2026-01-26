<?php

/**
 * Tests for ListPrivacyRequests ability.
 *
 * @package FAWpmcp\Tests\Abilities\Privacy
 */

declare(strict_types=1);

namespace FAWpmcp\Tests\Abilities\Privacy;

use FAWpmcp\Abilities\AbstractAbility;
use FAWpmcp\Abilities\Privacy\ListPrivacyRequests;
use FAWpmcp\Tests\TestCase\AbilityTestTrait;
use FAWpmcp\Tests\TestCase\BrainMonkeyTestCase;
use Brain\Monkey\Functions;
use Mockery;

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
class ListPrivacyRequestsTest extends BrainMonkeyTestCase {
	use AbilityTestTrait;

	/**
	 * Get an instance of the ability being tested.
	 *
	 * @return AbstractAbility
	 */
	protected function getAbilityInstance(): AbstractAbility {
		return new ListPrivacyRequests();
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
	protected function getExpectedMetadata(): array {
		return array(
			'name'                 => 'fa-wpmcp/list-privacy-requests',
			'category'             => 'privacy',
			'label'                => 'List Privacy Requests',
			'description_contains' => 'privacy request',
			'operation_type'       => 'read',
			'required_capability'  => 'manage_options',
		);
	}

	/**
	 * Test listing privacy requests successfully.
	 *
	 * @return void
	 */
	public function testExecuteListsPrivacyRequests(): void {
		$ability = $this->getAbilityInstance();
		$input   = array(
			'page'     => 1,
			'per_page' => 10,
		);

		// Mock WP_Query.
		$mock_post1              = Mockery::mock( '\WP_Post' );
		$mock_post1->ID          = 123;
		$mock_post1->post_status = 'request-pending';
		$mock_post1->post_date   = '2026-01-20 10:00:00';

		$mock_post2              = Mockery::mock( '\WP_Post' );
		$mock_post2->ID          = 124;
		$mock_post2->post_status = 'request-confirmed';
		$mock_post2->post_date   = '2026-01-21 11:00:00';

		$mock_query                = Mockery::mock( '\WP_Query' );
		$mock_query->posts         = array( $mock_post1, $mock_post2 );
		$mock_query->found_posts   = 2;
		$mock_query->max_num_pages = 1;

		Functions\expect( 'get_posts' )
			->once()
			->andReturn( array( $mock_post1, $mock_post2 ) );

		Functions\expect( 'wp_count_posts' )
			->once()
			->with( 'user_request' )
			->andReturn(
				(object) array(
					'request-pending'   => 1,
					'request-confirmed' => 1,
				)
			);

		Functions\expect( 'get_post_meta' )
			->times( 6 )
			->andReturnUsing(
				function ( $post_id, $key, $single ) {
					if ( '_wp_user_request_user_email' === $key ) {
						return 'user@example.com';
					}
					if ( 'action_name' === $key ) {
						return 123 === $post_id ? 'export_personal_data' : 'remove_personal_data';
					}
					return '';
				}
			);

		$result = $ability->doExecute( $input );

		$this->assertArrayHasKey( 'requests', $result );
		$this->assertArrayHasKey( 'total', $result );
		$this->assertCount( 2, $result['requests'] );
		$this->assertEquals( 2, $result['total'] );
	}

	/**
	 * Test listing with type filter.
	 *
	 * @return void
	 */
	public function testExecuteListsWithTypeFilter(): void {
		$ability = $this->getAbilityInstance();
		$input   = array(
			'page'     => 1,
			'per_page' => 10,
			'type'     => 'export_personal_data',
		);

		$mock_post              = Mockery::mock( '\WP_Post' );
		$mock_post->ID          = 123;
		$mock_post->post_status = 'request-pending';
		$mock_post->post_date   = '2026-01-20 10:00:00';

		Functions\expect( 'get_posts' )
			->once()
			->andReturn( array( $mock_post ) );

		Functions\expect( 'wp_count_posts' )
			->once()
			->andReturn( (object) array( 'request-pending' => 1 ) );

		Functions\expect( 'get_post_meta' )
			->times( 3 )
			->andReturnUsing(
				function ( $post_id, $key, $single ) {
					if ( '_wp_user_request_user_email' === $key ) {
						return 'user@example.com';
					}
					if ( 'action_name' === $key ) {
						return 'export_personal_data';
					}
					return '';
				}
			);

		$result = $ability->doExecute( $input );

		$this->assertCount( 1, $result['requests'] );
	}
}
