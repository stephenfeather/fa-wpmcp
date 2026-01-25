<?php
/**
 * Tests for GetTaxonomy ability.
 *
 * @package FAWpmcp\Tests\Abilities\Taxonomies
 */

declare(strict_types=1);

namespace FAWpmcp\Tests\Abilities\Taxonomies;

use FAWpmcp\Abilities\Taxonomies\GetTaxonomy;
use FAWpmcp\Exceptions\PostNotFoundException;
use Brain\Monkey;
use Brain\Monkey\Functions;
use Mockery;
use PHPUnit\Framework\TestCase;

/**
 * Test GetTaxonomy ability functionality.
 *
 * @package FAWpmcp\Tests\Abilities\Taxonomies
 */
class GetTaxonomyTest extends TestCase {
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
		$ability = new GetTaxonomy();
		$this->assertEquals( 'fa-wpmcp/get-taxonomy', $ability->getName() );
	}

	/**
	 * Test ability returns correct category.
	 *
	 * @return void
	 */
	public function testGetCategory(): void {
		$ability = new GetTaxonomy();
		$this->assertEquals( 'taxonomies', $ability->getCategory() );
	}

	/**
	 * Test ability returns correct label.
	 *
	 * @return void
	 */
	public function testGetLabel(): void {
		$ability = new GetTaxonomy();
		$this->assertEquals( 'Get Taxonomy', $ability->getLabel() );
	}

	/**
	 * Test ability returns correct operation type.
	 *
	 * @return void
	 */
	public function testGetOperationType(): void {
		$ability = new GetTaxonomy();
		$this->assertEquals( 'read', $ability->getOperationType() );
	}

	/**
	 * Test ability returns correct required capability.
	 *
	 * @return void
	 */
	public function testGetRequiredCapability(): void {
		$ability = new GetTaxonomy();
		$this->assertEquals( 'read', $ability->getRequiredCapability() );
	}

	/**
	 * Test ability returns input schema with required taxonomy field.
	 *
	 * @return void
	 */
	public function testGetInputSchema(): void {
		$ability = new GetTaxonomy();
		$schema  = $ability->getInputSchema();

		$this->assertIsArray( $schema );
		$this->assertArrayHasKey( 'type', $schema );
		$this->assertArrayHasKey( 'properties', $schema );
		$this->assertArrayHasKey( 'required', $schema );
		$this->assertArrayHasKey( 'taxonomy', $schema['properties'] );
		$this->assertContains( 'taxonomy', $schema['required'] );
	}

	/**
	 * Test ability returns output schema.
	 *
	 * @return void
	 */
	public function testGetOutputSchema(): void {
		$ability = new GetTaxonomy();
		$schema  = $ability->getOutputSchema();

		$this->assertIsArray( $schema );
		$this->assertArrayHasKey( 'type', $schema );
		$this->assertArrayHasKey( 'properties', $schema );
		$this->assertArrayHasKey( 'taxonomy', $schema['properties'] );
	}

	/**
	 * Test execute throws exception for non-existent taxonomy.
	 *
	 * @return void
	 */
	public function testExecuteThrowsExceptionForNonExistentTaxonomy(): void {
		$ability = new GetTaxonomy();

		Functions\when( 'get_taxonomy' )->justReturn( false );

		$this->expectException( PostNotFoundException::class );
		$this->expectExceptionMessage( 'Taxonomy not found' );

		$ability->doExecute( array( 'taxonomy' => 'nonexistent_taxonomy' ) );
	}

	/**
	 * Test execute returns taxonomy data.
	 *
	 * @return void
	 */
	public function testExecuteReturnsTaxonomyData(): void {
		$ability = new GetTaxonomy();

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
		$mock_taxonomy->rewrite     = array( 'slug' => 'category', 'with_front' => true );

		Functions\when( 'get_taxonomy' )->justReturn( $mock_taxonomy );

		$result = $ability->doExecute( array( 'taxonomy' => 'category' ) );

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
		$ability = new GetTaxonomy();

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

		$result = $ability->doExecute( array( 'taxonomy' => 'post_tag' ) );

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
		$ability = new GetTaxonomy();

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

		$result = $ability->doExecute( array( 'taxonomy' => 'custom_tax' ) );

		$this->assertIsArray( $result['taxonomy']['labels'] );
		$this->assertEmpty( $result['taxonomy']['labels'] );
	}

	/**
	 * Test execute handles boolean rewrite.
	 *
	 * @return void
	 */
	public function testExecuteHandlesBooleanRewrite(): void {
		$ability = new GetTaxonomy();

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

		$result = $ability->doExecute( array( 'taxonomy' => 'post_tag' ) );

		$this->assertFalse( $result['taxonomy']['rewrite'] );
	}

	/**
	 * Test execute handles array rewrite.
	 *
	 * @return void
	 */
	public function testExecuteHandlesArrayRewrite(): void {
		$ability = new GetTaxonomy();

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
		$mock_taxonomy->rewrite     = array( 'slug' => 'category', 'with_front' => true );

		Functions\when( 'get_taxonomy' )->justReturn( $mock_taxonomy );

		$result = $ability->doExecute( array( 'taxonomy' => 'category' ) );

		$this->assertIsArray( $result['taxonomy']['rewrite'] );
		$this->assertEquals( 'category', $result['taxonomy']['rewrite']['slug'] );
	}

	/**
	 * Test annotations are correct for read-only ability.
	 *
	 * @return void
	 */
	public function testGetAnnotations(): void {
		$ability     = new GetTaxonomy();
		$annotations = $ability->getAnnotations();

		$this->assertTrue( $annotations['readonly'] );
		$this->assertFalse( $annotations['destructive'] );
		$this->assertTrue( $annotations['idempotent'] );
	}
}
