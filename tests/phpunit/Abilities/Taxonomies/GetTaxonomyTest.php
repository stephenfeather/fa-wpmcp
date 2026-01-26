<?php
/**
 * Tests for GetTaxonomy ability.
 *
 * @package FAWpmcp\Tests\Abilities\Taxonomies
 */

declare(strict_types=1);

namespace FAWpmcp\Tests\Abilities\Taxonomies;

use FAWpmcp\Abilities\AbstractAbility;
use FAWpmcp\Abilities\Taxonomies\GetTaxonomy;
use FAWpmcp\Exceptions\PostNotFoundException;
use FAWpmcp\Tests\TestCase\AbilityTestTrait;
use FAWpmcp\Tests\TestCase\BrainMonkeyTestCase;
use Brain\Monkey\Functions;
use Mockery;

/**
 * Test GetTaxonomy ability functionality.
 *
 * @package FAWpmcp\Tests\Abilities\Taxonomies
 */
class GetTaxonomyTest extends BrainMonkeyTestCase {

	use AbilityTestTrait;

	/**
	 * Get an instance of the ability being tested.
	 *
	 * @return AbstractAbility
	 */
	protected function getAbilityInstance(): AbstractAbility {
		return new GetTaxonomy();
	}

	/**
	 * Get expected metadata for the ability.
	 *
	 * @return array<string, string>
	 */
	protected function getExpectedMetadata(): array {
		return array(
			'name'                  => 'fa-wpmcp/get-taxonomy',
			'category'              => 'taxonomies',
			'label'                 => 'Get Taxonomy',
			'description_contains'  => 'retrieve',
			'operation_type'        => 'read',
			'required_capability'   => 'read',
		);
	}

	/**
	 * Test execute throws exception for non-existent taxonomy.
	 *
	 * @return void
	 */
	public function testExecuteThrowsExceptionForNonExistentTaxonomy(): void {
		Functions\when( 'get_taxonomy' )->justReturn( false );

		$this->expectException( PostNotFoundException::class );
		$this->expectExceptionMessage( 'Taxonomy not found' );

		$this->getAbilityInstance()->doExecute( array( 'taxonomy' => 'nonexistent_taxonomy' ) );
	}

	/**
	 * Test execute returns taxonomy data.
	 *
	 * @return void
	 */
	public function testExecuteReturnsTaxonomyData(): void {
		$mock_labels = (object) array(
			'name'          => 'Categories',
			'singular_name' => 'Category',
			'search_items'  => 'Search Categories',
		);

		$mock_cap = (object) array(
			'manage_terms' => 'manage_categories',
			'edit_terms'   => 'edit_categories',
			'delete_terms' => 'delete_categories',
			'assign_terms' => 'assign_categories',
		);

		$mock_taxonomy              = Mockery::mock( \WP_Taxonomy::class );
		$mock_taxonomy->name        = 'category';
		$mock_taxonomy->label       = 'Categories';
		$mock_taxonomy->labels      = $mock_labels;
		$mock_taxonomy->description = 'Post categories';
		$mock_taxonomy->public      = true;
		$mock_taxonomy->hierarchical = true;
		$mock_taxonomy->show_ui     = true;
		$mock_taxonomy->show_in_rest = true;
		$mock_taxonomy->rest_base   = 'categories';
		$mock_taxonomy->object_type = array( 'post' );
		$mock_taxonomy->cap         = $mock_cap;
		$mock_taxonomy->rewrite     = array(
			'slug' => 'category',
			'with_front' => true,
		);

		Functions\when( 'get_taxonomy' )->justReturn( $mock_taxonomy );

		$result = $this->getAbilityInstance()->doExecute( array( 'taxonomy' => 'category' ) );

		$this->assertIsArray( $result );
		$this->assertArrayHasKey( 'taxonomy', $result );
		$this->assertEquals( 'category', $result['taxonomy']['name'] );
		$this->assertEquals( 'Categories', $result['taxonomy']['label'] );
		$this->assertTrue( $result['taxonomy']['hierarchical'] );
		$this->assertEquals( array( 'post' ), $result['taxonomy']['object_type'] );
	}

	/**
	 * Test execute returns labels as array.
	 *
	 * @return void
	 */
	public function testExecuteReturnsLabelsAsArray(): void {
		$mock_labels = (object) array(
			'name'          => 'Tags',
			'singular_name' => 'Tag',
		);

		$mock_taxonomy              = Mockery::mock( \WP_Taxonomy::class );
		$mock_taxonomy->name        = 'post_tag';
		$mock_taxonomy->label       = 'Tags';
		$mock_taxonomy->labels      = $mock_labels;
		$mock_taxonomy->description = '';
		$mock_taxonomy->public      = true;
		$mock_taxonomy->hierarchical = false;
		$mock_taxonomy->show_ui     = true;
		$mock_taxonomy->show_in_rest = true;
		$mock_taxonomy->rest_base   = 'tags';
		$mock_taxonomy->object_type = array( 'post' );
		$mock_taxonomy->cap         = null;
		$mock_taxonomy->rewrite     = true;

		Functions\when( 'get_taxonomy' )->justReturn( $mock_taxonomy );

		$result = $this->getAbilityInstance()->doExecute( array( 'taxonomy' => 'post_tag' ) );

		$this->assertIsArray( $result['taxonomy']['labels'] );
		$this->assertEquals( 'Tags', $result['taxonomy']['labels']['name'] );
		$this->assertEquals( 'Tag', $result['taxonomy']['labels']['singular_name'] );
	}

	/**
	 * Test execute handles null labels.
	 *
	 * @return void
	 */
	public function testExecuteHandlesNullLabels(): void {
		$mock_taxonomy              = Mockery::mock( \WP_Taxonomy::class );
		$mock_taxonomy->name        = 'custom_tax';
		$mock_taxonomy->label       = 'Custom';
		$mock_taxonomy->labels      = null;
		$mock_taxonomy->description = '';
		$mock_taxonomy->public      = true;
		$mock_taxonomy->hierarchical = false;
		$mock_taxonomy->show_ui     = true;
		$mock_taxonomy->show_in_rest = false;
		$mock_taxonomy->rest_base   = null;
		$mock_taxonomy->object_type = array( 'post' );
		$mock_taxonomy->cap         = null;
		$mock_taxonomy->rewrite     = false;

		Functions\when( 'get_taxonomy' )->justReturn( $mock_taxonomy );

		$result = $this->getAbilityInstance()->doExecute( array( 'taxonomy' => 'custom_tax' ) );

		$this->assertIsArray( $result['taxonomy']['labels'] );
		$this->assertEmpty( $result['taxonomy']['labels'] );
	}

	/**
	 * Test execute handles boolean rewrite.
	 *
	 * @return void
	 */
	public function testExecuteHandlesBooleanRewrite(): void {
		$mock_taxonomy              = Mockery::mock( \WP_Taxonomy::class );
		$mock_taxonomy->name        = 'post_tag';
		$mock_taxonomy->label       = 'Tags';
		$mock_taxonomy->labels      = null;
		$mock_taxonomy->description = '';
		$mock_taxonomy->public      = true;
		$mock_taxonomy->hierarchical = false;
		$mock_taxonomy->show_ui     = true;
		$mock_taxonomy->show_in_rest = true;
		$mock_taxonomy->rest_base   = 'tags';
		$mock_taxonomy->object_type = array( 'post' );
		$mock_taxonomy->cap         = null;
		$mock_taxonomy->rewrite     = false;

		Functions\when( 'get_taxonomy' )->justReturn( $mock_taxonomy );

		$result = $this->getAbilityInstance()->doExecute( array( 'taxonomy' => 'post_tag' ) );

		$this->assertFalse( $result['taxonomy']['rewrite'] );
	}

	/**
	 * Test execute handles array rewrite.
	 *
	 * @return void
	 */
	public function testExecuteHandlesArrayRewrite(): void {
		$mock_taxonomy              = Mockery::mock( \WP_Taxonomy::class );
		$mock_taxonomy->name        = 'category';
		$mock_taxonomy->label       = 'Categories';
		$mock_taxonomy->labels      = null;
		$mock_taxonomy->description = '';
		$mock_taxonomy->public      = true;
		$mock_taxonomy->hierarchical = true;
		$mock_taxonomy->show_ui     = true;
		$mock_taxonomy->show_in_rest = true;
		$mock_taxonomy->rest_base   = 'categories';
		$mock_taxonomy->object_type = array( 'post' );
		$mock_taxonomy->cap         = null;
		$mock_taxonomy->rewrite     = array(
			'slug' => 'category',
			'with_front' => true,
		);

		Functions\when( 'get_taxonomy' )->justReturn( $mock_taxonomy );

		$result = $this->getAbilityInstance()->doExecute( array( 'taxonomy' => 'category' ) );

		$this->assertIsArray( $result['taxonomy']['rewrite'] );
		$this->assertEquals( 'category', $result['taxonomy']['rewrite']['slug'] );
	}

	/**
	 * Test annotations are correct for read-only ability.
	 *
	 * @return void
	 */
	public function testGetAnnotations(): void {
		$ability     = $this->getAbilityInstance();
		$annotations = $ability->getAnnotations();

		$this->assertTrue( $annotations['readonly'] );
		$this->assertFalse( $annotations['destructive'] );
		$this->assertTrue( $annotations['idempotent'] );
	}
}
