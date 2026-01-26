<?php

/**
 * Tests for DeleteTerm.
 *
 * @package FAWpmcp\Tests\Abilities\Taxonomies
 */

declare(strict_types=1);

namespace FAWpmcp\Tests\Abilities\Taxonomies;

use FAWpmcp\Abilities\AbstractAbility;
use FAWpmcp\Abilities\Taxonomies\DeleteTerm;
use FAWpmcp\Exceptions\TermDeletionException;
use FAWpmcp\Exceptions\TermNotFoundException;
use FAWpmcp\Tests\TestCase\AbilityTestTrait;
use FAWpmcp\Tests\TestCase\BrainMonkeyTestCase;
use Brain\Monkey\Functions;
use Mockery;

final class DeleteTermTest extends BrainMonkeyTestCase {

	use AbilityTestTrait;

	/**
	 * Get an instance of the ability being tested.
	 *
	 * @return AbstractAbility
	 */
	protected function getAbilityInstance(): AbstractAbility {
		return new DeleteTerm();
	}

	/**
	 * Get expected metadata for the ability.
	 *
	 * @return array<string, string>
	 */
	protected function getExpectedMetadata(): array {
		return array(
			'name'                  => 'fa-wpmcp/delete-term',
			'category'              => 'taxonomies',
			'label'                 => 'Delete Term',
			'description_contains'  => 'delete',
			'operation_type'        => 'write',
			'required_capability'   => 'manage_categories',
		);
	}

	public function test_annotations_mark_destructive(): void {
		$ability     = $this->getAbilityInstance();
		$annotations = $ability->getAnnotations();

		$this->assertTrue( $annotations['destructive'] );
		$this->assertFalse( $annotations['idempotent'] );
	}

	public function test_deletes_term_permanently(): void {
		$term           = new \stdClass();
		$term->term_id  = 42;
		$term->taxonomy = 'category';

		Functions\expect( 'get_term' )->once()->with( 42, 'category' )->andReturn( $term );
		Functions\expect( 'wp_delete_term' )->once()->with( 42, 'category' )->andReturn( true );

		$result = $this->getAbilityInstance()->doExecute(
			array(
				'term_id'  => 42,
				'taxonomy' => 'category',
			)
		);

		$this->assertEquals( 42, $result['term_id'] );
		$this->assertEquals( 'category', $result['taxonomy'] );
		$this->assertEquals( 'deleted', $result['action'] );
		$this->assertTrue( $result['success'] );
	}

	public function test_deletes_custom_taxonomy_term(): void {
		$term           = new \stdClass();
		$term->term_id  = 55;
		$term->taxonomy = 'product_cat';

		Functions\expect( 'get_term' )->once()->with( 55, 'product_cat' )->andReturn( $term );
		Functions\expect( 'wp_delete_term' )->once()->with( 55, 'product_cat' )->andReturn( true );

		$result = $this->getAbilityInstance()->doExecute(
			array(
				'term_id'  => 55,
				'taxonomy' => 'product_cat',
			)
		);

		$this->assertEquals( 55, $result['term_id'] );
		$this->assertEquals( 'product_cat', $result['taxonomy'] );
		$this->assertEquals( 'deleted', $result['action'] );
		$this->assertTrue( $result['success'] );
	}

	public function test_throws_exception_when_term_not_found(): void {
		$this->expectException( TermNotFoundException::class );
		$this->expectExceptionMessage( "Term 999 not found in taxonomy 'category'" );

		Functions\expect( 'get_term' )->once()->with( 999, 'category' )->andReturn( null );

		$this->getAbilityInstance()->doExecute(
			array(
				'term_id'  => 999,
				'taxonomy' => 'category',
			)
		);
	}

	public function test_throws_exception_when_get_term_returns_wp_error(): void {
		$this->expectException( TermNotFoundException::class );

		$wp_error = Mockery::mock( 'WP_Error' );
		Functions\expect( 'is_wp_error' )->once()->with( $wp_error )->andReturn( true );
		Functions\expect( 'get_term' )->once()->with( 42, 'invalid_tax' )->andReturn( $wp_error );

		$this->getAbilityInstance()->doExecute(
			array(
				'term_id'  => 42,
				'taxonomy' => 'invalid_tax',
			)
		);
	}

	public function test_throws_exception_when_delete_returns_false(): void {
		$this->expectException( TermDeletionException::class );
		$this->expectExceptionMessage( 'Failed to delete term 42' );

		$term           = new \stdClass();
		$term->term_id  = 42;
		$term->taxonomy = 'category';

		Functions\expect( 'get_term' )->once()->with( 42, 'category' )->andReturn( $term );
		Functions\expect( 'wp_delete_term' )->once()->with( 42, 'category' )->andReturn( false );
		Functions\expect( 'is_wp_error' )->twice()->andReturn( false );

		$this->getAbilityInstance()->doExecute(
			array(
				'term_id'  => 42,
				'taxonomy' => 'category',
			)
		);
	}

	public function test_throws_exception_when_delete_returns_wp_error(): void {
		$this->expectException( TermDeletionException::class );
		$this->expectExceptionMessage( 'Cannot delete default category' );

		$term           = new \stdClass();
		$term->term_id  = 1;
		$term->taxonomy = 'category';

		$wp_error = Mockery::mock( 'WP_Error' );
		$wp_error->shouldReceive( 'get_error_message' )->andReturn( 'Cannot delete default category' );

		Functions\expect( 'get_term' )->once()->with( 1, 'category' )->andReturn( $term );
		Functions\expect( 'wp_delete_term' )->once()->with( 1, 'category' )->andReturn( $wp_error );
		Functions\expect( 'is_wp_error' )->once()->with( $wp_error )->andReturn( true );

		$this->getAbilityInstance()->doExecute(
			array(
				'term_id'  => 1,
				'taxonomy' => 'category',
			)
		);
	}
}
