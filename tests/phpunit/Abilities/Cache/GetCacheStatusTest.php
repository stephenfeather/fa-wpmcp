<?php
/**
 * Tests for GetCacheStatus.
 *
 * @package FAWpmcp\Tests\Abilities\Cache
 */

declare(strict_types=1);

namespace FAWpmcp\Tests\Abilities\Cache;

use FAWpmcp\Abilities\Cache\GetCacheStatus;
use Brain\Monkey\Functions;
use PHPUnit\Framework\TestCase;

final class GetCacheStatusTest extends TestCase {
	use \Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;

	protected function setUp(): void {
		parent::setUp();
		\Brain\Monkey\setUp();
	}

	protected function tearDown(): void {
		\Brain\Monkey\tearDown();
		parent::tearDown();
	}

	public function test_ability_metadata(): void {
		$ability = new GetCacheStatus();
		$this->assertEquals( 'fa-wpmcp/get-cache-status', $ability->getName() );
		$this->assertEquals( 'cache', $ability->getCategory() );
		$this->assertEquals( 'Get Cache Status', $ability->getLabel() );
		$this->assertStringContainsString( 'cache', strtolower( $ability->getDescription() ) );
		$this->assertEquals( 'manage_options', $ability->getRequiredCapability() );
	}

	public function test_operation_type_is_read(): void {
		$ability = new GetCacheStatus();
		$this->assertEquals( 'read', $ability->getOperationType() );
	}

	public function test_input_schema_has_no_required_fields(): void {
		$ability = new GetCacheStatus();
		$schema  = $ability->getInputSchema();

		$this->assertEquals( 'object', $schema['type'] );
		$this->assertArrayHasKey( 'properties', $schema );
		$this->assertArrayNotHasKey( 'required', $schema );
	}

	public function test_output_schema_structure(): void {
		$ability = new GetCacheStatus();
		$schema  = $ability->getOutputSchema();

		$this->assertEquals( 'object', $schema['type'] );
		$this->assertArrayHasKey( 'persistent', $schema['properties'] );
		$this->assertArrayHasKey( 'supports', $schema['properties'] );
		$this->assertArrayHasKey( 'global_groups', $schema['properties'] );
		$this->assertArrayHasKey( 'non_persistent_groups', $schema['properties'] );
	}

	public function test_returns_status_for_default_cache(): void {
		Functions\expect( 'wp_using_ext_object_cache' )->once()->andReturn( false );
		Functions\expect( 'wp_cache_supports' )->times( 6 )->andReturn( false );

		$ability = new GetCacheStatus();
		$result  = $ability->doExecute( array() );

		$this->assertFalse( $result['persistent'] );
		$this->assertIsArray( $result['supports'] );
		$this->assertFalse( $result['supports']['add_multiple'] );
		$this->assertFalse( $result['supports']['flush_group'] );
	}

	public function test_returns_status_for_persistent_cache(): void {
		Functions\expect( 'wp_using_ext_object_cache' )->once()->andReturn( true );
		Functions\expect( 'wp_cache_supports' )
			->with( 'add_multiple' )->andReturn( true );
		Functions\expect( 'wp_cache_supports' )
			->with( 'set_multiple' )->andReturn( true );
		Functions\expect( 'wp_cache_supports' )
			->with( 'get_multiple' )->andReturn( true );
		Functions\expect( 'wp_cache_supports' )
			->with( 'delete_multiple' )->andReturn( true );
		Functions\expect( 'wp_cache_supports' )
			->with( 'flush_runtime' )->andReturn( true );
		Functions\expect( 'wp_cache_supports' )
			->with( 'flush_group' )->andReturn( true );

		$ability = new GetCacheStatus();
		$result  = $ability->doExecute( array() );

		$this->assertTrue( $result['persistent'] );
		$this->assertTrue( $result['supports']['add_multiple'] );
		$this->assertTrue( $result['supports']['flush_group'] );
	}

	public function test_returns_global_groups_when_available(): void {
		global $wp_object_cache;
		$wp_object_cache = new \stdClass();
		// WordPress stores global_groups as numerically-indexed array with group names as values.
		$wp_object_cache->global_groups = array( 'users', 'site-options' );

		Functions\expect( 'wp_using_ext_object_cache' )->once()->andReturn( true );
		Functions\expect( 'wp_cache_supports' )->times( 6 )->andReturn( false );

		$ability = new GetCacheStatus();
		$result  = $ability->doExecute( array() );

		$this->assertContains( 'users', $result['global_groups'] );
		$this->assertContains( 'site-options', $result['global_groups'] );

		// Clean up global.
		$wp_object_cache = null;
	}

	public function test_returns_non_persistent_groups_when_available(): void {
		global $wp_object_cache;
		$wp_object_cache               = new \stdClass();
		$wp_object_cache->no_mc_groups = array( 'counts', 'plugins' );

		Functions\expect( 'wp_using_ext_object_cache' )->once()->andReturn( true );
		Functions\expect( 'wp_cache_supports' )->times( 6 )->andReturn( false );

		$ability = new GetCacheStatus();
		$result  = $ability->doExecute( array() );

		$this->assertContains( 'counts', $result['non_persistent_groups'] );
		$this->assertContains( 'plugins', $result['non_persistent_groups'] );

		// Clean up global.
		$wp_object_cache = null;
	}
}
