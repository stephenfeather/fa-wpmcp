<?php
/**
 * Tests for DeleteTransient ability.
 *
 * @package FAWpmcp\Tests\Abilities\Transients
 */

declare(strict_types=1);

namespace FAWpmcp\Tests\Abilities\Transients;

use FAWpmcp\Abilities\Transients\DeleteTransient;
use Brain\Monkey;
use Brain\Monkey\Functions;
use Mockery;
use PHPUnit\Framework\TestCase;

/**
 * Test DeleteTransient ability functionality.
 *
 * @package FAWpmcp\Tests\Abilities\Transients
 */
class DeleteTransientTest extends TestCase {
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
		$ability = new DeleteTransient();
		$this->assertEquals( 'fa-wpmcp/delete-transient', $ability->getName() );
	}

	/**
	 * Test ability returns correct category.
	 *
	 * @return void
	 */
	public function testGetCategory(): void {
		$ability = new DeleteTransient();
		$this->assertEquals( 'transients', $ability->getCategory() );
	}

	/**
	 * Test ability returns correct label.
	 *
	 * @return void
	 */
	public function testGetLabel(): void {
		$ability = new DeleteTransient();
		$this->assertEquals( 'Delete Transient', $ability->getLabel() );
	}

	/**
	 * Test ability returns correct operation type.
	 *
	 * @return void
	 */
	public function testGetOperationType(): void {
		$ability = new DeleteTransient();
		$this->assertEquals( 'delete', $ability->getOperationType() );
	}

	/**
	 * Test ability returns correct required capability.
	 *
	 * @return void
	 */
	public function testGetRequiredCapability(): void {
		$ability = new DeleteTransient();
		$this->assertEquals( 'manage_options', $ability->getRequiredCapability() );
	}

	/**
	 * Test ability returns input schema with optional parameters.
	 *
	 * @return void
	 */
	public function testGetInputSchema(): void {
		$ability = new DeleteTransient();
		$schema  = $ability->getInputSchema();

		$this->assertIsArray( $schema );
		$this->assertArrayHasKey( 'type', $schema );
		$this->assertArrayHasKey( 'properties', $schema );
		$this->assertArrayHasKey( 'key', $schema['properties'] );
		$this->assertArrayHasKey( 'all', $schema['properties'] );
		$this->assertArrayHasKey( 'expired', $schema['properties'] );
		$this->assertArrayHasKey( 'network', $schema['properties'] );
	}

	/**
	 * Test ability returns output schema.
	 *
	 * @return void
	 */
	public function testGetOutputSchema(): void {
		$ability = new DeleteTransient();
		$schema  = $ability->getOutputSchema();

		$this->assertIsArray( $schema );
		$this->assertArrayHasKey( 'type', $schema );
		$this->assertArrayHasKey( 'properties', $schema );
		$this->assertArrayHasKey( 'deleted_count', $schema['properties'] );
	}

	/**
	 * Test execute deletes single transient by key.
	 *
	 * @return void
	 */
	public function testExecuteDeletesSingleTransientByKey(): void {
		$ability = new DeleteTransient();

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
		$ability = new DeleteTransient();

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
		$ability = new DeleteTransient();

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
		$ability = new DeleteTransient();

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
		$ability = new DeleteTransient();

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
		$ability = new DeleteTransient();

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
		$ability = new DeleteTransient();

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
		$ability     = new DeleteTransient();
		$annotations = $ability->getAnnotations();

		$this->assertFalse( $annotations['readonly'] );
		$this->assertTrue( $annotations['destructive'] );
		$this->assertTrue( $annotations['idempotent'] );
	}
}
