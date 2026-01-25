<?php
declare(strict_types=1);

namespace FAWpmcp\Tests\Abilities\Cache;

use FAWpmcp\Abilities\Cache\FlushCache;
use FAWpmcp\Exceptions\CacheOperationException;
use Brain\Monkey\Functions;
use PHPUnit\Framework\TestCase;

final class FlushCacheTest extends TestCase {
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
		$ability = new FlushCache();
		$this->assertEquals( 'fa-wpmcp/flush-cache', $ability->getName() );
		$this->assertEquals( 'cache', $ability->getCategory() );
		$this->assertEquals( 'Flush Cache', $ability->getLabel() );
		$this->assertStringContainsString( 'flush', strtolower( $ability->getDescription() ) );
		$this->assertEquals( 'manage_options', $ability->getRequiredCapability() );
	}

	public function test_operation_type_is_write(): void {
		$ability = new FlushCache();
		$this->assertEquals( 'write', $ability->getOperationType() );
	}

	public function test_annotations_mark_idempotent(): void {
		$ability     = new FlushCache();
		$annotations = $ability->getAnnotations();

		$this->assertTrue( $annotations['idempotent'] );
	}

	public function test_input_schema_has_optional_group(): void {
		$ability = new FlushCache();
		$schema  = $ability->getInputSchema();

		$this->assertEquals( 'object', $schema['type'] );
		$this->assertArrayHasKey( 'group', $schema['properties'] );
		$this->assertArrayNotHasKey( 'required', $schema );
	}

	public function test_output_schema_structure(): void {
		$ability = new FlushCache();
		$schema  = $ability->getOutputSchema();

		$this->assertEquals( 'object', $schema['type'] );
		$this->assertArrayHasKey( 'flushed', $schema['properties'] );
		$this->assertArrayHasKey( 'group', $schema['properties'] );
		$this->assertArrayHasKey( 'message', $schema['properties'] );
	}

	public function test_flushes_entire_cache_without_group(): void {
		Functions\expect( 'wp_cache_flush' )->once()->andReturn( true );

		$ability = new FlushCache();
		$result  = $ability->doExecute( array() );

		$this->assertTrue( $result['flushed'] );
		$this->assertEquals( 'all', $result['group'] );
		$this->assertStringContainsString( 'Entire cache flushed', $result['message'] );
	}

	public function test_flushes_specific_group_when_supported(): void {
		Functions\expect( 'wp_cache_supports' )->once()->with( 'flush_group' )->andReturn( true );
		Functions\expect( 'wp_cache_flush_group' )->once()->with( 'posts' )->andReturn( true );

		$ability = new FlushCache();
		$result  = $ability->doExecute( array( 'group' => 'posts' ) );

		$this->assertTrue( $result['flushed'] );
		$this->assertEquals( 'posts', $result['group'] );
		$this->assertStringContainsString( 'posts', $result['message'] );
	}

	public function test_throws_exception_when_group_flush_not_supported(): void {
		$this->expectException( CacheOperationException::class );
		$this->expectExceptionMessage( 'does not support group flushing' );

		Functions\expect( 'wp_cache_supports' )->once()->with( 'flush_group' )->andReturn( false );

		$ability = new FlushCache();
		$ability->doExecute( array( 'group' => 'posts' ) );
	}

	public function test_throws_exception_when_flush_fails(): void {
		$this->expectException( CacheOperationException::class );
		$this->expectExceptionMessage( 'Failed to flush cache' );

		Functions\expect( 'wp_cache_flush' )->once()->andReturn( false );

		$ability = new FlushCache();
		$ability->doExecute( array() );
	}

	public function test_throws_exception_when_group_flush_fails(): void {
		$this->expectException( CacheOperationException::class );
		$this->expectExceptionMessage( "Failed to flush cache group 'posts'" );

		Functions\expect( 'wp_cache_supports' )->once()->with( 'flush_group' )->andReturn( true );
		Functions\expect( 'wp_cache_flush_group' )->once()->with( 'posts' )->andReturn( false );

		$ability = new FlushCache();
		$ability->doExecute( array( 'group' => 'posts' ) );
	}

	public function test_handles_empty_group_string_as_full_flush(): void {
		Functions\expect( 'wp_cache_flush' )->once()->andReturn( true );

		$ability = new FlushCache();
		$result  = $ability->doExecute( array( 'group' => '' ) );

		$this->assertTrue( $result['flushed'] );
		$this->assertEquals( 'all', $result['group'] );
	}
}
