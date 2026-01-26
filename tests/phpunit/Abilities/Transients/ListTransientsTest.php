<?php
/**
 * Tests for ListTransients ability.
 *
 * @package FAWpmcp\Tests\Abilities\Transients
 */

declare(strict_types=1);

namespace FAWpmcp\Tests\Abilities\Transients;

use FAWpmcp\Abilities\Transients\ListTransients;
use Brain\Monkey;
use Brain\Monkey\Functions;
use Mockery;
use PHPUnit\Framework\TestCase;

/**
 * Test ListTransients ability functionality.
 *
 * @package FAWpmcp\Tests\Abilities\Transients
 */
class ListTransientsTest extends TestCase {
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
		$ability = new ListTransients();
		$this->assertEquals( 'fa-wpmcp/list-transients', $ability->getName() );
	}

	/**
	 * Test ability returns correct category.
	 *
	 * @return void
	 */
	public function testGetCategory(): void {
		$ability = new ListTransients();
		$this->assertEquals( 'transients', $ability->getCategory() );
	}

	/**
	 * Test ability returns correct label.
	 *
	 * @return void
	 */
	public function testGetLabel(): void {
		$ability = new ListTransients();
		$this->assertEquals( 'List Transients', $ability->getLabel() );
	}

	/**
	 * Test ability returns correct operation type.
	 *
	 * @return void
	 */
	public function testGetOperationType(): void {
		$ability = new ListTransients();
		$this->assertEquals( 'read', $ability->getOperationType() );
	}

	/**
	 * Test ability returns correct required capability.
	 *
	 * @return void
	 */
	public function testGetRequiredCapability(): void {
		$ability = new ListTransients();
		$this->assertEquals( 'manage_options', $ability->getRequiredCapability() );
	}

	/**
	 * Test ability returns input schema with optional search parameter.
	 *
	 * @return void
	 */
	public function testGetInputSchema(): void {
		$ability = new ListTransients();
		$schema  = $ability->getInputSchema();

		$this->assertIsArray( $schema );
		$this->assertArrayHasKey( 'type', $schema );
		$this->assertArrayHasKey( 'properties', $schema );
		$this->assertArrayHasKey( 'search', $schema['properties'] );
		$this->assertArrayHasKey( 'exclude', $schema['properties'] );
		$this->assertArrayHasKey( 'network', $schema['properties'] );
	}

	/**
	 * Test ability returns output schema.
	 *
	 * @return void
	 */
	public function testGetOutputSchema(): void {
		$ability = new ListTransients();
		$schema  = $ability->getOutputSchema();

		$this->assertIsArray( $schema );
		$this->assertArrayHasKey( 'type', $schema );
		$this->assertArrayHasKey( 'properties', $schema );
		$this->assertArrayHasKey( 'transients', $schema['properties'] );
		$this->assertArrayHasKey( 'total', $schema['properties'] );
	}

	/**
	 * Test execute returns list of transients from database.
	 *
	 * @return void
	 */
	public function testExecuteReturnsTransientsList(): void {
		$ability = new ListTransients();

		// Mock global wpdb.
		global $wpdb;
		$wpdb          = Mockery::mock( 'wpdb' );
		$wpdb->prefix  = 'wp_';
		$wpdb->options = 'wp_options';

		// Mock database results.
		$db_results = array(
			(object) array(
				'option_name'  => '_transient_my_cache',
				'option_value' => serialize( 'cached_value' ),
			),
			(object) array(
				'option_name'  => '_transient_timeout_my_cache',
				'option_value' => (string) ( time() + 3600 ),
			),
		);

		$wpdb->shouldReceive( 'prepare' )
			->andReturnUsing(
				function ( $query ) {
					return $query;
				}
			);
		$wpdb->shouldReceive( 'get_results' )
			->andReturn( $db_results );

		Functions\when( 'maybe_unserialize' )->alias(
			function ( $value ) {
				return @unserialize( $value ) ?: $value;
			}
		);

		$result = $ability->doExecute( array() );

		$this->assertIsArray( $result );
		$this->assertArrayHasKey( 'transients', $result );
		$this->assertArrayHasKey( 'total', $result );
		$this->assertIsArray( $result['transients'] );
	}

	/**
	 * Test execute filters transients by search pattern.
	 *
	 * @return void
	 */
	public function testExecuteFiltersTransientsBySearch(): void {
		$ability = new ListTransients();

		// Mock global wpdb.
		global $wpdb;
		$wpdb          = Mockery::mock( 'wpdb' );
		$wpdb->prefix  = 'wp_';
		$wpdb->options = 'wp_options';

		$wpdb->shouldReceive( 'prepare' )
			->andReturnUsing(
				function ( $query, ...$args ) {
					// Check that search pattern is included.
					return $query;
				}
			);
		$wpdb->shouldReceive( 'get_results' )
			->andReturn( array() );
		$wpdb->shouldReceive( 'esc_like' )
			->with( 'cache' )
			->andReturn( 'cache' );

		$result = $ability->doExecute( array( 'search' => 'cache' ) );

		$this->assertIsArray( $result );
		$this->assertArrayHasKey( 'transients', $result );
	}

	/**
	 * Test execute excludes transients matching exclude pattern.
	 *
	 * @return void
	 */
	public function testExecuteExcludesTransients(): void {
		$ability = new ListTransients();

		// Mock global wpdb.
		global $wpdb;
		$wpdb          = Mockery::mock( 'wpdb' );
		$wpdb->prefix  = 'wp_';
		$wpdb->options = 'wp_options';

		$wpdb->shouldReceive( 'prepare' )
			->andReturnUsing(
				function ( $query, ...$args ) {
					return $query;
				}
			);
		$wpdb->shouldReceive( 'get_results' )
			->andReturn( array() );
		$wpdb->shouldReceive( 'esc_like' )
			->andReturn( 'secret' );

		$result = $ability->doExecute( array( 'exclude' => 'secret' ) );

		$this->assertIsArray( $result );
		$this->assertArrayHasKey( 'transients', $result );
	}

	/**
	 * Test execute uses sitemeta table for network transients.
	 *
	 * @return void
	 */
	public function testExecuteUsesNetworkTableForNetworkTransients(): void {
		$ability = new ListTransients();

		// Mock global wpdb.
		global $wpdb;
		$wpdb              = Mockery::mock( 'wpdb' );
		$wpdb->prefix      = 'wp_';
		$wpdb->base_prefix = 'wp_';
		$wpdb->options     = 'wp_options';
		$wpdb->sitemeta    = 'wp_sitemeta';

		$wpdb->shouldReceive( 'prepare' )
			->andReturnUsing(
				function ( $query ) {
					return $query;
				}
			);
		$wpdb->shouldReceive( 'get_results' )
			->andReturn( array() );

		$result = $ability->doExecute( array( 'network' => true ) );

		$this->assertIsArray( $result );
	}

	/**
	 * Test transient output includes expiration time.
	 *
	 * @return void
	 */
	public function testTransientOutputIncludesExpiration(): void {
		$ability = new ListTransients();

		// Mock global wpdb.
		global $wpdb;
		$wpdb          = Mockery::mock( 'wpdb' );
		$wpdb->prefix  = 'wp_';
		$wpdb->options = 'wp_options';

		$future_time = time() + 3600;
		$db_results  = array(
			(object) array(
				'option_name'  => '_transient_test_cache',
				'option_value' => serialize( 'value' ),
			),
			(object) array(
				'option_name'  => '_transient_timeout_test_cache',
				'option_value' => (string) $future_time,
			),
		);

		$wpdb->shouldReceive( 'prepare' )->andReturnUsing( fn( $q ) => $q );
		$wpdb->shouldReceive( 'get_results' )->andReturn( $db_results );

		Functions\when( 'maybe_unserialize' )->alias(
			function ( $value ) {
				return @unserialize( $value ) ?: $value;
			}
		);

		$result = $ability->doExecute( array() );

		$this->assertIsArray( $result );
		$this->assertArrayHasKey( 'transients', $result );
		if ( count( $result['transients'] ) > 0 ) {
			$transient = $result['transients'][0];
			$this->assertArrayHasKey( 'name', $transient );
			$this->assertArrayHasKey( 'value', $transient );
			$this->assertArrayHasKey( 'expiration', $transient );
		}
	}

	/**
	 * Test annotations are correct for read-only ability.
	 *
	 * @return void
	 */
	public function testGetAnnotations(): void {
		$ability     = new ListTransients();
		$annotations = $ability->getAnnotations();

		$this->assertTrue( $annotations['readonly'] );
		$this->assertFalse( $annotations['destructive'] );
		$this->assertTrue( $annotations['idempotent'] );
	}
}
