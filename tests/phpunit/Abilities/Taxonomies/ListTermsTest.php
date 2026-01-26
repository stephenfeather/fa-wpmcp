<?php

/**
 * Tests for ListTerms ability.
 *
 * @package FAWpmcp\Tests\Abilities\Taxonomies
 */

declare(strict_types=1);

namespace FAWpmcp\Tests\Abilities\Taxonomies;

use FAWpmcp\Abilities\AbstractAbility;
use FAWpmcp\Abilities\Taxonomies\ListTerms;
use FAWpmcp\Tests\TestCase\AbilityTestTrait;
use FAWpmcp\Tests\TestCase\BrainMonkeyTestCase;
use Brain\Monkey\Functions;
use Mockery;

/**
 * Test ListTerms ability functionality.
 *
 * @package FAWpmcp\Tests\Abilities\Taxonomies
 */
class ListTermsTest extends BrainMonkeyTestCase {

	use AbilityTestTrait;

	/**
	 * Get an instance of the ability being tested.
	 *
	 * @return AbstractAbility
	 */
	protected function getAbilityInstance(): AbstractAbility {
		return new ListTerms();
	}

	/**
	 * Get expected metadata for the ability.
	 *
	 * @return array<string, string>
	 */
	protected function getExpectedMetadata(): array {
		return array(
			'name'                  => 'fa-wpmcp/list-terms',
			'category'              => 'taxonomies',
			'label'                 => 'List Terms',
			'description_contains'  => 'retrieve',
			'operation_type'        => 'read',
			'required_capability'   => 'read',
		);
	}

	/**
	 * Test execute returns terms list.
	 *
	 * @return void
	 */
	public function testExecuteReturnsTermsList(): void {
		$mock_term              = Mockery::mock( \WP_Term::class );
		$mock_term->term_id     = 1;
		$mock_term->name        = 'Test Category';
		$mock_term->slug        = 'test-category';
		$mock_term->description = 'Test description';
		$mock_term->parent      = 0;
		$mock_term->count       = 5;
		$mock_term->taxonomy    = 'category';

		Functions\when( 'get_terms' )->justReturn( array( $mock_term ), 1 );
		Functions\when( 'get_term_link' )->justReturn( 'https://example.com/category/test' );

		$result = $this->getAbilityInstance()->doExecute(
			array(
				'taxonomy' => 'category',
				'page'     => 1,
			)
		);

		$this->assertIsArray( $result );
		$this->assertArrayHasKey( 'terms', $result );
		$this->assertArrayHasKey( 'total', $result );
		$this->assertCount( 1, $result['terms'] );
	}

	/**
	 * Test execute handles WP_Error gracefully.
	 *
	 * @return void
	 */
	public function testExecuteHandlesWpError(): void {
		$mock_error = Mockery::mock( 'WP_Error' );
		Functions\when( 'get_terms' )->justReturn( $mock_error );
		Functions\when( 'is_wp_error' )->justReturn( true );

		$result = $this->getAbilityInstance()->doExecute( array( 'taxonomy' => 'category' ) );

		$this->assertIsArray( $result );
		$this->assertEquals( 0, $result['total'] );
		$this->assertEmpty( $result['terms'] );
	}
}
