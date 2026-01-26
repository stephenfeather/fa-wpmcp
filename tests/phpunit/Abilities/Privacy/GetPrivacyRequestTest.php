<?php

/**
 * Tests for GetPrivacyRequest ability.
 *
 * @package FAWpmcp\Tests\Abilities\Privacy
 */

declare(strict_types=1);

namespace FAWpmcp\Tests\Abilities\Privacy;

use FAWpmcp\Abilities\AbstractAbility;
use FAWpmcp\Abilities\Privacy\GetPrivacyRequest;
use FAWpmcp\Exceptions\PrivacyRequestNotFoundException;
use FAWpmcp\Tests\TestCase\AbilityTestTrait;
use FAWpmcp\Tests\TestCase\BrainMonkeyTestCase;
use Brain\Monkey\Functions;
use Mockery;

/**
 * Test GetPrivacyRequest ability functionality.
 *
 * Tests cover:
 * - Getting privacy request by ID
 * - Error handling for non-existent requests
 * - Return format
 *
 * @package FAWpmcp\Tests\Abilities\Privacy
 */
class GetPrivacyRequestTest extends BrainMonkeyTestCase {
	use AbilityTestTrait;

	/**
	 * Get an instance of the ability being tested.
	 *
	 * @return AbstractAbility
	 */
	protected function getAbilityInstance(): AbstractAbility {
		return new GetPrivacyRequest();
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
			'name'                 => 'fa-wpmcp/get-privacy-request',
			'category'             => 'privacy',
			'label'                => 'Get Privacy Request',
			'description_contains' => 'privacy request',
			'operation_type'       => 'read',
			'required_capability'  => 'manage_options',
		);
	}

	/**
	 * Test getting privacy request successfully.
	 *
	 * @return void
	 */
	public function testExecuteGetsPrivacyRequest(): void {
		$ability = $this->getAbilityInstance();
		$input   = array(
			'request_id' => 123,
		);

		// Mock get_post to return request post.
		$mock_post              = Mockery::mock( '\WP_Post' );
		$mock_post->ID          = 123;
		$mock_post->post_type   = 'user_request';
		$mock_post->post_status = 'request-confirmed';
		$mock_post->post_date   = '2026-01-20 10:00:00';

		Functions\expect( 'get_post' )
			->once()
			->with( 123 )
			->andReturn( $mock_post );

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
					if ( '_wp_user_request_confirmed_timestamp' === $key ) {
						return '1737369600';
					}
					return '';
				}
			);

		$result = $ability->doExecute( $input );

		$this->assertEquals( 123, $result['id'] );
		$this->assertEquals( 'user@example.com', $result['email'] );
		$this->assertEquals( 'export_personal_data', $result['type'] );
		$this->assertEquals( 'request-confirmed', $result['status'] );
	}

	/**
	 * Test getting non-existent request throws exception.
	 *
	 * @return void
	 */
	public function testExecuteThrowsExceptionForNonExistentRequest(): void {
		$ability = $this->getAbilityInstance();
		$input   = array(
			'request_id' => 999,
		);

		Functions\expect( 'get_post' )
			->once()
			->with( 999 )
			->andReturn( null );

		$this->expectException( PrivacyRequestNotFoundException::class );
		$this->expectExceptionMessage( 'Privacy request not found' );

		$ability->doExecute( $input );
	}

	/**
	 * Test getting non-request post throws exception.
	 *
	 * @return void
	 */
	public function testExecuteThrowsExceptionForWrongPostType(): void {
		$ability = $this->getAbilityInstance();
		$input   = array(
			'request_id' => 123,
		);

		// Mock get_post to return a regular post.
		$mock_post            = Mockery::mock( '\WP_Post' );
		$mock_post->ID        = 123;
		$mock_post->post_type = 'post';

		Functions\expect( 'get_post' )
			->once()
			->with( 123 )
			->andReturn( $mock_post );

		$this->expectException( PrivacyRequestNotFoundException::class );
		$this->expectExceptionMessage( 'Privacy request not found' );

		$ability->doExecute( $input );
	}
}
