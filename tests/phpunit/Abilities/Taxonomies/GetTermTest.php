<?php

/**
 * Tests for GetTerm ability.
 *
 * @package FAWpmcp\Tests\Abilities\Taxonomies
 */

declare(strict_types=1);

namespace FAWpmcp\Tests\Abilities\Taxonomies;

use FAWpmcp\Abilities\AbstractAbility;
use FAWpmcp\Abilities\Taxonomies\GetTerm;
use FAWpmcp\Exceptions\PostNotFoundException;
use FAWpmcp\Tests\TestCase\AbilityTestTrait;
use FAWpmcp\Tests\TestCase\BrainMonkeyTestCase;
use Brain\Monkey\Functions;
use Mockery;

/**
 * Test GetTerm ability functionality.
 *
 * @package FAWpmcp\Tests\Abilities\Taxonomies
 */
class GetTermTest extends BrainMonkeyTestCase {

	use AbilityTestTrait;

	/**
	 * Get an instance of the ability being tested.
	 *
	 * @return AbstractAbility
	 */
	protected function getAbilityInstance(): AbstractAbility {
		return new GetTerm();
	}

	/**
	 * Get expected metadata for the ability.
	 *
	 * @return array<string, string>
	 */
	protected function getExpectedMetadata(): array {
		return array(
			'name'                  => 'fa-wpmcp/get-term',
			'category'              => 'taxonomies',
			'label'                 => 'Get Term',
			'description_contains'  => 'retrieve',
			'operation_type'        => 'read',
			'required_capability'   => 'read',
		);
	}

	/**
	 * Test execute throws exception for non-existent term.
	 *
	 * @return void
	 */
	public function testExecuteThrowsExceptionForNonExistentTerm(): void {
		Functions\when( 'get_term' )->justReturn( null );
		Functions\when( 'is_wp_error' )->justReturn( false );

		$this->expectException( PostNotFoundException::class );
		$this->getAbilityInstance()->doExecute( array( 'term_id' => 999 ) );
	}

	/**
	 * Test execute throws exception for WP_Error.
	 *
	 * @return void
	 */
	public function testExecuteThrowsExceptionForWpError(): void {
		$mock_error = Mockery::mock( 'WP_Error' );
		Functions\when( 'get_term' )->justReturn( $mock_error );
		Functions\when( 'is_wp_error' )->justReturn( true );

		$this->expectException( PostNotFoundException::class );
		$this->getAbilityInstance()->doExecute( array( 'term_id' => 1 ) );
	}

	/**
	 * Test execute returns term data.
	 *
	 * @return void
	 */
	public function testExecuteReturnsTermData(): void {
		$mock_term              = Mockery::mock( \WP_Term::class );
		$mock_term->term_id     = 1;
		$mock_term->name        = 'Test Category';
		$mock_term->slug        = 'test-category';
		$mock_term->description = 'Test description';
		$mock_term->parent      = 0;
		$mock_term->count       = 5;
		$mock_term->taxonomy    = 'category';

		Functions\when( 'get_term' )->justReturn( $mock_term );
		Functions\when( 'is_wp_error' )->justReturn( false );
		Functions\when( 'get_term_link' )->justReturn( 'https://example.com/category/test' );
		Functions\when( 'get_term_meta' )->justReturn( array() );

		$result = $this->getAbilityInstance()->doExecute( array( 'term_id' => 1 ) );

		$this->assertIsArray( $result );
		$this->assertArrayHasKey( 'term', $result );
		$this->assertEquals( 1, $result['term']['term_id'] );
		$this->assertEquals( 'Test Category', $result['term']['name'] );
	}

	/**
	 * Test execute returns filtered meta data.
	 *
	 * @return void
	 */
	public function testExecuteFiltersTermMeta(): void {
		$mock_term              = Mockery::mock( \WP_Term::class );
		$mock_term->term_id     = 22;
		$mock_term->name        = 'Meta Term';
		$mock_term->slug        = 'meta-term';
		$mock_term->description = '';
		$mock_term->parent      = 0;
		$mock_term->count       = 0;
		$mock_term->taxonomy    = 'category';

		Functions\when( 'get_term' )->justReturn( $mock_term );
		Functions\when( 'is_wp_error' )->justReturn( false );
		Functions\when( 'get_term_link' )->justReturn( 'https://example.com/category/meta' );
		Functions\expect( 'get_term_meta' )
			->once()
			->with( 22 )
			->andReturn(
				array(
					'_edit_lock' => array( '123' ),
					'color'      => array( 'blue' ),
					'sizes'      => array( 's', 'm' ),
				)
			);

		$result = $this->getAbilityInstance()->doExecute( array( 'term_id' => 22 ) );

		$this->assertEquals(
			array(
				'color' => 'blue',
				'sizes' => array( 's', 'm' ),
			),
			$result['term']['meta']
		);
	}

	/**
	 * Test execute handles non-array meta responses.
	 *
	 * @return void
	 */
	public function testExecuteHandlesNonArrayMeta(): void {
		$mock_term              = Mockery::mock( \WP_Term::class );
		$mock_term->term_id     = 33;
		$mock_term->name        = 'Empty Meta Term';
		$mock_term->slug        = 'empty-meta-term';
		$mock_term->description = '';
		$mock_term->parent      = 0;
		$mock_term->count       = 0;
		$mock_term->taxonomy    = 'category';

		Functions\when( 'get_term' )->justReturn( $mock_term );
		Functions\when( 'is_wp_error' )->justReturn( false );
		Functions\when( 'get_term_link' )->justReturn( 'https://example.com/category/empty' );
		Functions\when( 'get_term_meta' )->justReturn( 'not-an-array' );

		$result = $this->getAbilityInstance()->doExecute( array( 'term_id' => 33 ) );

		$this->assertSame( array(), $result['term']['meta'] );
	}
}
