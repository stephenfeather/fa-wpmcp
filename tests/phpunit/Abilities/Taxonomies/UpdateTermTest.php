<?php

/**
 * Tests for UpdateTerm ability.
 *
 * @package FAWpmcp\Tests\Abilities\Taxonomies
 */

declare(strict_types=1);

namespace FAWpmcp\Tests\Abilities\Taxonomies;

use FAWpmcp\Abilities\AbstractAbility;
use FAWpmcp\Abilities\Taxonomies\UpdateTerm;
use FAWpmcp\Exceptions\PostNotFoundException;
use FAWpmcp\Exceptions\PostUpdateException;
use FAWpmcp\Tests\TestCase\AbilityTestTrait;
use FAWpmcp\Tests\TestCase\BrainMonkeyTestCase;
use Brain\Monkey\Functions;
use Mockery;

/**
 * Test UpdateTerm ability functionality.
 *
 * @package FAWpmcp\Tests\Abilities\Taxonomies
 */
class UpdateTermTest extends BrainMonkeyTestCase {

	use AbilityTestTrait;

	/**
	 * Get an instance of the ability being tested.
	 *
	 * @return AbstractAbility
	 */
	protected function getAbilityInstance(): AbstractAbility {
		return new UpdateTerm();
	}

	/**
	 * Get expected metadata for the ability.
	 *
	 * @return array<string, string>
	 */
	protected function getExpectedMetadata(): array {
		return array(
			'name'                  => 'fa-wpmcp/update-term',
			'category'              => 'taxonomies',
			'label'                 => 'Update Term',
			'description_contains'  => 'update',
			'operation_type'        => 'write',
			'required_capability'   => 'manage_categories',
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
		$this->getAbilityInstance()->doExecute(
			array(
				'term_id'  => 999,
				'taxonomy' => 'category',
			)
		);
	}

	/**
	 * Test execute throws exception on WP_Error during update.
	 *
	 * @return void
	 */
	public function testExecuteThrowsExceptionOnUpdateError(): void {
		$mock_term          = Mockery::mock( \WP_Term::class );
		$mock_term->term_id = 1;

		$mock_error = Mockery::mock( 'WP_Error' );
		$mock_error->shouldReceive( 'get_error_message' )->andReturn( 'Update failed' );

		Functions\when( 'get_term' )->alias(
			function ( $term_id, $taxonomy ) use ( $mock_term, $mock_error ) {
				static $call_count = 0;
				++$call_count;
				return 1 === $call_count ? $mock_term : $mock_error;
			}
		);
		Functions\when( 'is_wp_error' )->alias(
			function ( $thing ) use ( $mock_error ) {
				return $thing === $mock_error;
			}
		);
		Functions\when( 'sanitize_text_field' )->returnArg();
		Functions\when( 'sanitize_title' )->returnArg();
		Functions\when( 'sanitize_textarea_field' )->returnArg();
		Functions\when( 'wp_update_term' )->justReturn( $mock_error );

		$this->expectException( PostUpdateException::class );
		$this->getAbilityInstance()->doExecute(
			array(
				'term_id'  => 1,
				'taxonomy' => 'category',
				'name'     => 'Updated Name',
			)
		);
	}

	/**
	 * Test execute updates term successfully.
	 *
	 * @return void
	 */
	public function testExecuteUpdatesTermSuccessfully(): void {
		$mock_term           = Mockery::mock( \WP_Term::class );
		$mock_term->term_id  = 1;
		$mock_term->name     = 'Updated Category';
		$mock_term->slug     = 'updated-category';
		$mock_term->taxonomy = 'category';

		Functions\when( 'get_term' )->justReturn( $mock_term );
		Functions\when( 'is_wp_error' )->justReturn( false );
		Functions\when( 'sanitize_text_field' )->returnArg();
		Functions\when( 'sanitize_title' )->returnArg();
		Functions\when( 'sanitize_textarea_field' )->returnArg();
		Functions\when( 'wp_update_term' )->justReturn( array( 'term_id' => 1 ) );
		Functions\when( 'get_term_link' )->justReturn( 'https://example.com/category/updated' );

		$result = $this->getAbilityInstance()->doExecute(
			array(
				'term_id'     => 1,
				'taxonomy'    => 'category',
				'name'        => 'Updated Category',
				'description' => 'Updated description',
			)
		);

		$this->assertIsArray( $result );
		$this->assertArrayHasKey( 'term_id', $result );
		$this->assertArrayHasKey( 'updated', $result );
		$this->assertTrue( $result['updated'] );
	}

	/**
	 * Test execute updates term with sanitized fields.
	 *
	 * @return void
	 */
	public function testExecuteBuildsSanitizedUpdateData(): void {
		$mock_term           = Mockery::mock( \WP_Term::class );
		$mock_term->term_id  = 2;
		$mock_term->name     = 'After Update';
		$mock_term->slug     = 'after-update';
		$mock_term->taxonomy = 'category';

		Functions\expect( 'sanitize_text_field' )
			->once()
			->with( ' New Name ' )
			->andReturn( 'New Name' );
		Functions\expect( 'sanitize_title' )
			->once()
			->with( ' New Slug ' )
			->andReturn( 'new-slug' );
		Functions\expect( 'sanitize_textarea_field' )
			->once()
			->with( ' New description ' )
			->andReturn( 'New description' );
		Functions\expect( 'wp_update_term' )
			->once()
			->with(
				2,
				'category',
				array(
					'name'        => 'New Name',
					'slug'        => 'new-slug',
					'description' => 'New description',
					'parent'      => 3,
				)
			)
			->andReturn( array( 'term_id' => 2 ) );
		Functions\when( 'get_term' )->justReturn( $mock_term );
		Functions\when( 'is_wp_error' )->justReturn( false );
		Functions\when( 'get_term_link' )->justReturn( 'https://example.com/category/after-update' );

		$result = $this->getAbilityInstance()->doExecute(
			array(
				'term_id'     => 2,
				'taxonomy'    => 'category',
				'name'        => ' New Name ',
				'slug'        => ' New Slug ',
				'description' => ' New description ',
				'parent'      => 3,
			)
		);

		$this->assertEquals( 2, $result['term_id'] );
		$this->assertEquals( 'after-update', $result['slug'] );
	}

	/**
	 * Test execute returns fallback response when term lookup fails after update.
	 *
	 * @return void
	 */
	public function testExecuteReturnsFallbackWhenTermLookupFails(): void {
		$existing_term          = Mockery::mock( \WP_Term::class );
		$existing_term->term_id = 7;

		Functions\when( 'get_term' )->alias(
			function () use ( $existing_term ) {
				static $call_count = 0;
				++$call_count;
				return 1 === $call_count ? $existing_term : null;
			}
		);
		Functions\when( 'is_wp_error' )->justReturn( false );
		Functions\when( 'sanitize_text_field' )->returnArg();
		Functions\when( 'sanitize_title' )->returnArg();
		Functions\when( 'sanitize_textarea_field' )->returnArg();
		Functions\when( 'wp_update_term' )->justReturn( array( 'term_id' => 7 ) );

		$result = $this->getAbilityInstance()->doExecute(
			array(
				'term_id'  => 7,
				'taxonomy' => 'category',
				'name'     => 'Updated',
			)
		);

		$this->assertEquals( 7, $result['term_id'] );
		$this->assertSame( '', $result['name'] );
		$this->assertSame( '', $result['slug'] );
		$this->assertSame( '', $result['link'] );
		$this->assertTrue( $result['updated'] );
	}
}
