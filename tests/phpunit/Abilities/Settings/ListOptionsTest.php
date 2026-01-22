<?php
/**
 * Tests for ListOptions ability.
 *
 * @package FAWpmcp\Tests\Abilities\Settings
 */

declare(strict_types=1);

namespace FAWpmcp\Tests\Abilities\Settings;

use FAWpmcp\Abilities\Settings\ListOptions;
use Brain\Monkey;
use Brain\Monkey\Functions;
use Mockery;
use PHPUnit\Framework\TestCase;

/**
 * Test ListOptions ability functionality.
 *
 * Tests cover:
 * - List all options
 * - List with search filter
 * - List with pagination
 *
 * @package FAWpmcp\Tests\Abilities\Settings
 */
class ListOptionsTest extends TestCase {
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
		$ability = new ListOptions();
		$this->assertEquals( 'fa-wpmcp/list-options', $ability->getName() );
	}

	/**
	 * Test ability returns correct category.
	 *
	 * @return void
	 */
	public function testGetCategory(): void {
		$ability = new ListOptions();
		$this->assertEquals( 'settings', $ability->getCategory() );
	}

	/**
	 * Test ability returns correct label.
	 *
	 * @return void
	 */
	public function testGetLabel(): void {
		$ability = new ListOptions();
		$this->assertEquals( 'List Options', $ability->getLabel() );
	}

	/**
	 * Test ability returns correct operation type.
	 *
	 * @return void
	 */
	public function testGetOperationType(): void {
		$ability = new ListOptions();
		$this->assertEquals( 'read', $ability->getOperationType() );
	}

	/**
	 * Test ability returns correct required capability.
	 *
	 * @return void
	 */
	public function testGetRequiredCapability(): void {
		$ability = new ListOptions();
		$this->assertEquals( 'manage_options', $ability->getRequiredCapability() );
	}

	/**
	 * Test execute lists options successfully.
	 *
	 * @return void
	 */
	public function testExecuteListsOptions(): void {
		Functions\when( 'sanitize_key' )->returnArg();

		$ability   = new ListOptions();
		$mock_wpdb = Mockery::mock( 'wpdb' );

		$mock_wpdb->options = 'wp_options';
		$mock_wpdb->shouldReceive( 'prepare' )
			->twice()
			->andReturnUsing( fn( string $query, ...$args ) => $query );
		$mock_wpdb->shouldReceive( 'get_results' )
			->once()
			->with( Mockery::pattern( '/SELECT option_name, option_value/' ) )
			->andReturn(
				array(
					(object) array(
						'option_name'  => 'option1',
						'option_value' => 'value1',
					),
					(object) array(
						'option_name'  => 'option2',
						'option_value' => 'value2',
					),
				)
			);
		$mock_wpdb->shouldReceive( 'get_var' )
			->once()
			->with( Mockery::pattern( '/SELECT COUNT/' ) )
			->andReturn( '2' );

		Functions\expect( 'maybe_unserialize' )
			->twice()
			->andReturnUsing( fn( $v ) => $v );

		$GLOBALS['wpdb'] = $mock_wpdb;

		$result = $ability->doExecute( array() );

		$this->assertIsArray( $result['options'] );
		$this->assertCount( 2, $result['options'] );
		$this->assertEquals( 2, $result['total'] );
		$this->assertEquals( 'option1', $result['options'][0]['option_name'] );
		$this->assertEquals( 'value1', $result['options'][0]['option_value'] );
	}

	/**
	 * Test execute filters by search term.
	 *
	 * @return void
	 */
	public function testExecuteFiltersBySearch(): void {
		Functions\when( 'sanitize_key' )->returnArg();

		$ability   = new ListOptions();
		$mock_wpdb = Mockery::mock( 'wpdb' );

		$mock_wpdb->options = 'wp_options';
		$mock_wpdb->shouldReceive( 'esc_like' )
			->once()
			->with( 'test' )
			->andReturn( 'test' );
		$mock_wpdb->shouldReceive( 'prepare' )
			->twice()
			->andReturn( 'PREPARED_QUERY' );
		$mock_wpdb->shouldReceive( 'get_results' )
			->once()
			->andReturn(
				array(
					(object) array(
						'option_name'  => 'test_option',
						'option_value' => 'value',
					),
				)
			);
		$mock_wpdb->shouldReceive( 'get_var' )
			->once()
			->andReturn( '1' );

		Functions\expect( 'maybe_unserialize' )
			->once()
			->andReturnUsing( fn( $v ) => $v );

		$GLOBALS['wpdb'] = $mock_wpdb;

		$result = $ability->doExecute( array( 'search' => 'test' ) );

		$this->assertCount( 1, $result['options'] );
		$this->assertEquals( 'test_option', $result['options'][0]['option_name'] );
	}

	/**
	 * Test execute handles pagination.
	 *
	 * @return void
	 */
	public function testExecuteHandlesPagination(): void {
		Functions\when( 'sanitize_key' )->returnArg();

		$ability   = new ListOptions();
		$mock_wpdb = Mockery::mock( 'wpdb' );

		$mock_wpdb->options = 'wp_options';
		$mock_wpdb->shouldReceive( 'prepare' )
			->twice()
			->andReturnUsing( fn( string $query, ...$args ) => $query );
		$mock_wpdb->shouldReceive( 'get_results' )
			->once()
			->with( Mockery::pattern( '/SELECT option_name, option_value/' ) )
			->andReturn( array() );
		$mock_wpdb->shouldReceive( 'get_var' )
			->once()
			->with( Mockery::pattern( '/SELECT COUNT/' ) )
			->andReturn( '100' );

		$GLOBALS['wpdb'] = $mock_wpdb;

		$result = $ability->doExecute(
			array(
				'limit'  => 10,
				'offset' => 20,
			)
		);

		$this->assertEquals( 100, $result['total'] );
	}
}
