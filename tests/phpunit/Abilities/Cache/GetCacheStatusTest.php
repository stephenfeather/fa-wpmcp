<?php

/**
 * Tests for GetCacheStatus.
 *
 * @package FAWpmcp\Tests\Abilities\Cache
 */

declare(strict_types=1);

namespace FAWpmcp\Tests\Abilities\Cache;

use FAWpmcp\Abilities\AbstractAbility;
use FAWpmcp\Abilities\Cache\GetCacheStatus;
use FAWpmcp\Tests\TestCase\AbilityTestTrait;
use FAWpmcp\Tests\TestCase\BrainMonkeyTestCase;
use Brain\Monkey\Functions;

final class GetCacheStatusTest extends BrainMonkeyTestCase {

	use AbilityTestTrait;

	protected function getAbilityInstance(): AbstractAbility {
		return new GetCacheStatus();
	}

	protected function getExpectedMetadata(): array {
		return [
			'name'                 => 'fa-wpmcp/get-cache-status',
			'category'             => 'cache',
			'label'                => 'Get Cache Status',
			'description_contains' => 'cache',
			'operation_type'       => 'read',
			'required_capability'  => 'manage_options',
		];
	}

	public function test_output_schema_structure(): void {
		$ability = $this->getAbilityInstance();
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

		$ability = $this->getAbilityInstance();
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

		$ability = $this->getAbilityInstance();
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

		$ability = $this->getAbilityInstance();
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

		$ability = $this->getAbilityInstance();
		$result  = $ability->doExecute( array() );

		$this->assertContains( 'counts', $result['non_persistent_groups'] );
		$this->assertContains( 'plugins', $result['non_persistent_groups'] );

		// Clean up global.
		$wp_object_cache = null;
	}
}
