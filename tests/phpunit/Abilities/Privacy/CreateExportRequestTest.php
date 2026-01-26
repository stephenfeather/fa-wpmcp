<?php
/**
 * Tests for CreateExportRequest ability.
 *
 * @package FAWpmcp\Tests\Abilities\Privacy
 */

declare(strict_types=1);

namespace FAWpmcp\Tests\Abilities\Privacy;

use FAWpmcp\Abilities\AbstractAbility;
use FAWpmcp\Abilities\Privacy\CreateExportRequest;
use FAWpmcp\Tests\TestCase\AbilityTestTrait;
use FAWpmcp\Tests\TestCase\BrainMonkeyTestCase;
use Brain\Monkey\Functions;
use Mockery;

/**
 * Test CreateExportRequest ability functionality.
 *
 * Tests cover:
 * - Creating export requests with valid email
 * - Metadata handling
 * - Input validation
 * - Error handling
 *
 * @package FAWpmcp\Tests\Abilities\Privacy
 */
class CreateExportRequestTest extends BrainMonkeyTestCase {
	use AbilityTestTrait;

	/**
	 * Get an instance of the ability being tested.
	 *
	 * @return AbstractAbility
	 */
	protected function getAbilityInstance(): AbstractAbility {
		return new CreateExportRequest();
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
			'name'                 => 'fa-wpmcp/create-export-request',
			'category'             => 'privacy',
			'label'                => 'Create Export Request',
			'description_contains' => 'export request',
			'operation_type'       => 'write',
			'required_capability'  => 'manage_options',
		);
	}

	/**
	 * Test creating export request successfully.
	 *
	 * @return void
	 */
	public function testExecuteCreatesExportRequest(): void {
		$ability = $this->getAbilityInstance();
		$input   = array(
			'email' => 'user@example.com',
		);

		// Mock wp_create_user_request to return success.
		Functions\expect( 'wp_create_user_request' )
			->once()
			->with( 'user@example.com', 'export_personal_data', Mockery::type( 'array' ) )
			->andReturn( 123 );

		// Mock get_post to return request post.
		$mock_post              = Mockery::mock( '\WP_Post' );
		$mock_post->ID          = 123;
		$mock_post->post_status = 'request-pending';

		Functions\expect( 'get_post' )
			->once()
			->with( 123 )
			->andReturn( $mock_post );

		Functions\expect( 'get_post_meta' )
			->once()
			->with( 123, '_wp_user_request_confirmed_timestamp', true )
			->andReturn( '' );

		$result = $ability->doExecute( $input );

		$this->assertTrue( $result['success'] );
		$this->assertEquals( 123, $result['request_id'] );
		$this->assertEquals( 'request-pending', $result['status'] );
		$this->assertEquals( 'user@example.com', $result['email'] );
	}

	/**
	 * Test creating export request with description.
	 *
	 * @return void
	 */
	public function testExecuteCreatesExportRequestWithDescription(): void {
		$ability = $this->getAbilityInstance();
		$input   = array(
			'email'       => 'user@example.com',
			'description' => 'User requested data export',
		);

		// Mock wp_create_user_request to return success.
		Functions\expect( 'wp_create_user_request' )
			->once()
			->with(
				'user@example.com',
				'export_personal_data',
				Mockery::on(
					function ( $data ) {
						return isset( $data['description'] ) && 'User requested data export' === $data['description'];
					}
				)
			)
			->andReturn( 124 );

		// Mock get_post to return request post.
		$mock_post              = Mockery::mock( '\WP_Post' );
		$mock_post->ID          = 124;
		$mock_post->post_status = 'request-pending';

		Functions\expect( 'get_post' )
			->once()
			->with( 124 )
			->andReturn( $mock_post );

		Functions\expect( 'get_post_meta' )
			->once()
			->with( 124, '_wp_user_request_confirmed_timestamp', true )
			->andReturn( '' );

		$result = $ability->doExecute( $input );

		$this->assertTrue( $result['success'] );
		$this->assertEquals( 124, $result['request_id'] );
	}

	/**
	 * Test creating export request handles WP_Error.
	 *
	 * @return void
	 */
	public function testExecuteHandlesWpError(): void {
		$ability = $this->getAbilityInstance();
		$input   = array(
			'email' => 'invalid@example.com',
		);

		// Mock wp_create_user_request to return WP_Error.
		$error = Mockery::mock( '\WP_Error' );
		$error->shouldReceive( 'get_error_message' )
			->andReturn( 'Invalid email address' );

		Functions\expect( 'wp_create_user_request' )
			->once()
			->andReturn( $error );

		Functions\expect( 'is_wp_error' )
			->once()
			->with( $error )
			->andReturn( true );

		$this->expectException( \RuntimeException::class );
		$this->expectExceptionMessage( 'Failed to create export request: Invalid email address' );

		$ability->doExecute( $input );
	}
}
