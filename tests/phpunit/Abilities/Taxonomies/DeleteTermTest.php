<?php
/**
 * Tests for DeleteTerm.
 *
 * @package FAWpmcp\Tests\Abilities\Taxonomies
 */

declare(strict_types=1);

namespace FAWpmcp\Tests\Abilities\Taxonomies;

use FAWpmcp\Abilities\Taxonomies\DeleteTerm;
use FAWpmcp\Exceptions\TermDeletionException;
use FAWpmcp\Exceptions\TermNotFoundException;
use Brain\Monkey\Functions;
use PHPUnit\Framework\TestCase;
use Mockery;

final class DeleteTermTest extends TestCase {
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
		$ability = new DeleteTerm();
		$this->assertEquals( 'fa-wpmcp/delete-term', $ability->getName() );
		$this->assertEquals( 'taxonomies', $ability->getCategory() );
		$this->assertEquals( 'Delete Term', $ability->getLabel() );
		$this->assertStringContainsString( 'delete', strtolower( $ability->getDescription() ) );
		$this->assertEquals( 'manage_categories', $ability->getRequiredCapability() );
	}

	public function test_operation_type_is_write(): void {
		$ability = new DeleteTerm();
		$this->assertEquals( 'write', $ability->getOperationType() );
	}

	public function test_annotations_mark_destructive(): void {
		$ability     = new DeleteTerm();
		$annotations = $ability->getAnnotations();

		$this->assertTrue( $annotations['destructive'] );
		$this->assertFalse( $annotations['idempotent'] );
	}

	public function test_input_schema_requires_term_id_and_taxonomy(): void {
		$ability = new DeleteTerm();
		$schema  = $ability->getInputSchema();

		$this->assertEquals( 'object', $schema['type'] );
		$this->assertArrayHasKey( 'term_id', $schema['properties'] );
		$this->assertArrayHasKey( 'taxonomy', $schema['properties'] );
		$this->assertContains( 'term_id', $schema['required'] );
		$this->assertContains( 'taxonomy', $schema['required'] );
	}

	public function test_output_schema_structure(): void {
		$ability = new DeleteTerm();
		$schema  = $ability->getOutputSchema();

		$this->assertEquals( 'object', $schema['type'] );
		$this->assertArrayHasKey( 'term_id', $schema['properties'] );
		$this->assertArrayHasKey( 'taxonomy', $schema['properties'] );
		$this->assertArrayHasKey( 'action', $schema['properties'] );
		$this->assertArrayHasKey( 'success', $schema['properties'] );
	}

	public function test_deletes_term_permanently(): void {
		$term           = new \stdClass();
		$term->term_id  = 42;
		$term->taxonomy = 'category';

		Functions\expect( 'get_term' )->once()->with( 42, 'category' )->andReturn( $term );
		Functions\expect( 'wp_delete_term' )->once()->with( 42, 'category' )->andReturn( true );

		$ability = new DeleteTerm();
		$result  = $ability->doExecute(
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

		$ability = new DeleteTerm();
		$result  = $ability->doExecute(
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

		$ability = new DeleteTerm();
		$ability->doExecute(
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

		$ability = new DeleteTerm();
		$ability->doExecute(
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

		$ability = new DeleteTerm();
		$ability->doExecute(
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

		$ability = new DeleteTerm();
		$ability->doExecute(
			array(
				'term_id'  => 1,
				'taxonomy' => 'category',
			)
		);
	}
}
