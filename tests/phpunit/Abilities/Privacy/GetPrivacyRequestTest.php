<?php
/**
 * Tests for GetPrivacyRequest ability.
 *
 * @package FAWpmcp\Tests\Abilities\Privacy
 */

declare(strict_types=1);

namespace FAWpmcp\Tests\Abilities\Privacy;

use FAWpmcp\Abilities\Privacy\GetPrivacyRequest;
use Brain\Monkey;
use Brain\Monkey\Functions;
use Mockery;
use PHPUnit\Framework\TestCase;

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
class GetPrivacyRequestTest extends TestCase {
	/**
	 * Set up Brain\Monkey before each test.
	 *
	 * @return void
	 */
	protected function setUp(): void {
		parent::setUp();
		Monkey\setUp();
	}

	/**
	 * Tear down Brain\Monkey after each test.
	 *
	 * @return void
	 */
	protected function tearDown(): void {
		Monkey\tearDown();
		Mockery::close();
		parent::tearDown();
	}

	/**
	 * Test ability returns correct name.
	 *
	 * @return void
	 */
	public function testGetName(): void {
		$ability = new GetPrivacyRequest();
		$this->assertEquals( 'fa-wpmcp/get-privacy-request', $ability->getName() );
	}

	/**
	 * Test ability returns correct category.
	 *
	 * @return void
	 */
	public function testGetCategory(): void {
		$ability = new GetPrivacyRequest();
		$this->assertEquals( 'privacy', $ability->getCategory() );
	}

	/**
	 * Test ability returns correct label.
	 *
	 * @return void
	 */
	public function testGetLabel(): void {
		$ability = new GetPrivacyRequest();
		$this->assertEquals( 'Get Privacy Request', $ability->getLabel() );
	}

	/**
	 * Test ability requires manage_options capability.
	 *
	 * @return void
	 */
	public function testGetRequiredCapability(): void {
		$ability = new GetPrivacyRequest();
		$this->assertEquals( 'manage_options', $ability->getRequiredCapability() );
	}

	/**
	 * Test ability returns read operation type.
	 *
	 * @return void
	 */
	public function testGetOperationType(): void {
		$ability = new GetPrivacyRequest();
		$this->assertEquals( 'read', $ability->getOperationType() );
	}

	/**
	 * Test getting privacy request successfully.
	 *
	 * @return void
	 */
	public function testExecuteGetsPrivacyRequest(): void {
		$ability = new GetPrivacyRequest();
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
		$ability = new GetPrivacyRequest();
		$input   = array(
			'request_id' => 999,
		);

		Functions\expect( 'get_post' )
			->once()
			->with( 999 )
			->andReturn( null );

		$this->expectException( \RuntimeException::class );
		$this->expectExceptionMessage( 'Privacy request not found' );

		$ability->doExecute( $input );
	}

	/**
	 * Test getting non-request post throws exception.
	 *
	 * @return void
	 */
	public function testExecuteThrowsExceptionForWrongPostType(): void {
		$ability = new GetPrivacyRequest();
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

		$this->expectException( \RuntimeException::class );
		$this->expectExceptionMessage( 'Privacy request not found' );

		$ability->doExecute( $input );
	}
}
