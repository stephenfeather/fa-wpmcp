<?php

/**
 * Tests for DeleteTransient ability.
 *
 * @package FAWpmcp\Tests\Abilities\Transients
 */

declare(strict_types=1);

namespace FAWpmcp\Tests\Abilities\Transients;

use FAWpmcp\Abilities\AbstractAbility;
use FAWpmcp\Abilities\Transients\DeleteTransient;
use FAWpmcp\Tests\TestCase\AbilityTestTrait;
use FAWpmcp\Tests\TestCase\BrainMonkeyTestCase;
use Brain\Monkey\Functions;
use Mockery;

/**
 * Test DeleteTransient ability functionality.
 *
 * @package FAWpmcp\Tests\Abilities\Transients
 */
class DeleteTransientTest extends BrainMonkeyTestCase {
	use AbilityTestTrait;

	/**
	 * Get an instance of the ability being tested.
	 *
	 * @return AbstractAbility
	 */
	protected function getAbilityInstance(): AbstractAbility {
		return new DeleteTransient();
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
			'name'                 => 'fa-wpmcp/delete-transient',
			'category'             => 'transients',
			'label'                => 'Delete Transient',
			'description_contains' => 'delete',
			'operation_type'       => 'delete',
			'required_capability'  => 'manage_options',
		);
	}

	/**
	 * Test input schema has optional parameters.
	 *
	 * @return void
	 */
	public function testInputSchemaHasOptionalParameters(): void {
		$ability = $this->getAbilityInstance();
		$schema  = $ability->getInputSchema();

		$this->assertArrayHasKey( 'key', $schema['properties'] );
		$this->assertArrayHasKey( 'all', $schema['properties'] );
		$this->assertArrayHasKey( 'expired', $schema['properties'] );
		$this->assertArrayHasKey( 'network', $schema['properties'] );
	}

	/**
	 * Test output schema has deleted_count field.
	 *
	 * @return void
	 */
	public function testOutputSchemaHasDeletedCountField(): void {
		$ability = $this->getAbilityInstance();
		$schema  = $ability->getOutputSchema();

		$this->assertArrayHasKey( 'deleted_count', $schema['properties'] );
	}

	/**
	 * Test execute deletes single transient by key.
	 *
	 * @return void
	 */
	public function testExecuteDeletesSingleTransientByKey(): void {
		$ability = $this->getAbilityInstance();

		Functions\expect( 'delete_transient' )
			->once()
			->with( 'my_cache' )
			->andReturn( true );

		$result = $ability->doExecute( array( 'key' => 'my_cache' ) );

		$this->assertIsArray( $result );
		$this->assertEquals( 1, $result['deleted_count'] );
	}

	/**
	 * Test execute returns 0 when transient does not exist.
	 *
	 * @return void
	 */
	public function testExecuteReturnsZeroWhenTransientNotFound(): void {
		$ability = $this->getAbilityInstance();

		Functions\when( 'delete_transient' )->justReturn( false );

		$result = $ability->doExecute( array( 'key' => 'nonexistent' ) );

		$this->assertEquals( 0, $result['deleted_count'] );
	}

	/**
	 * Test execute uses delete_site_transient for network transients.
	 *
	 * @return void
	 */
	public function testExecuteUsesDeleteSiteTransientForNetwork(): void {
		$ability = $this->getAbilityInstance();

		Functions\expect( 'delete_site_transient' )
			->once()
			->with( 'network_cache' )
			->andReturn( true );

		$result = $ability->doExecute(
			array(
				'key'     => 'network_cache',
				'network' => true,
			)
		);

		$this->assertEquals( 1, $result['deleted_count'] );
	}

	/**
	 * Test execute deletes all transients when all flag is true.
	 *
	 * @return void
	 */
	public function testExecuteDeletesAllTransients(): void {
		$ability = $this->getAbilityInstance();

		// Mock global wpdb.
		global $wpdb;
		$wpdb          = Mockery::mock( 'wpdb' );
		$wpdb->prefix  = 'wp_';
		$wpdb->options = 'wp_options';

		$wpdb->shouldReceive( 'prepare' )
			->andReturnUsing(
				function ( $query ) {
					return $query;
				}
			);

		$wpdb->shouldReceive( 'query' )
			->once()
			->andReturn( 5 );

		$result = $ability->doExecute( array( 'all' => true ) );

		$this->assertEquals( 5, $result['deleted_count'] );
	}

	/**
	 * Test execute deletes only expired transients.
	 *
	 * @return void
	 */
	public function testExecuteDeletesExpiredTransients(): void {
		$ability = $this->getAbilityInstance();

		// Mock global wpdb.
		global $wpdb;
		$wpdb          = Mockery::mock( 'wpdb' );
		$wpdb->prefix  = 'wp_';
		$wpdb->options = 'wp_options';

		$wpdb->shouldReceive( 'prepare' )
			->andReturnUsing(
				function ( $query ) {
					return $query;
				}
			);

		// Return expired timeout keys.
		$wpdb->shouldReceive( 'get_col' )
			->once()
			->andReturn(
				array(
					'_transient_timeout_cache1',
					'_transient_timeout_cache2',
					'_transient_timeout_cache3',
				)
			);

		// Delete queries for each expired transient.
		$wpdb->shouldReceive( 'query' )
			->andReturn( 1 );

		$result = $ability->doExecute( array( 'expired' => true ) );

		$this->assertEquals( 3, $result['deleted_count'] );
	}

	/**
	 * Test execute deletes all network transients.
	 *
	 * @return void
	 */
	public function testExecuteDeletesAllNetworkTransients(): void {
		$ability = $this->getAbilityInstance();

		// Mock global wpdb.
		global $wpdb;
		$wpdb              = Mockery::mock( 'wpdb' );
		$wpdb->prefix      = 'wp_';
		$wpdb->base_prefix = 'wp_';
		$wpdb->sitemeta    = 'wp_sitemeta';

		$wpdb->shouldReceive( 'prepare' )
			->andReturnUsing(
				function ( $query ) {
					return $query;
				}
			);

		$wpdb->shouldReceive( 'query' )
			->once()
			->andReturn( 2 );

		$result = $ability->doExecute(
			array(
				'all'     => true,
				'network' => true,
			)
		);

		$this->assertEquals( 2, $result['deleted_count'] );
	}

	/**
	 * Test execute throws exception when no action specified.
	 *
	 * @return void
	 */
	public function testExecuteThrowsExceptionWhenNoActionSpecified(): void {
		$ability = $this->getAbilityInstance();

		$this->expectException( \InvalidArgumentException::class );
		$this->expectExceptionMessage( 'Must specify key, all, or expired parameter' );

		$ability->doExecute( array() );
	}

	/**
	 * Test annotations are correct for delete operation.
	 *
	 * @return void
	 */
	public function testGetAnnotations(): void {
		$ability     = $this->getAbilityInstance();
		$annotations = $ability->getAnnotations();

		$this->assertFalse( $annotations['readonly'] );
		$this->assertTrue( $annotations['destructive'] );
		$this->assertTrue( $annotations['idempotent'] );
	}
}
