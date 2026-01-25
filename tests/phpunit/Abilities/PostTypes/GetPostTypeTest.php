<?php
/**
 * Tests for GetPostType ability.
 *
 * @package FAWpmcp\Tests\Abilities\PostTypes
 */

declare(strict_types=1);

namespace FAWpmcp\Tests\Abilities\PostTypes;

use FAWpmcp\Abilities\PostTypes\GetPostType;
use FAWpmcp\Exceptions\PostNotFoundException;
use Brain\Monkey;
use Brain\Monkey\Functions;
use Mockery;
use PHPUnit\Framework\TestCase;

/**
 * Test GetPostType ability functionality.
 *
 * @package FAWpmcp\Tests\Abilities\PostTypes
 */
class GetPostTypeTest extends TestCase {
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
		$ability = new GetPostType();
		$this->assertEquals( 'fa-wpmcp/get-post-type', $ability->getName() );
	}

	/**
	 * Test ability returns correct category.
	 *
	 * @return void
	 */
	public function testGetCategory(): void {
		$ability = new GetPostType();
		$this->assertEquals( 'post-types', $ability->getCategory() );
	}

	/**
	 * Test ability returns correct label.
	 *
	 * @return void
	 */
	public function testGetLabel(): void {
		$ability = new GetPostType();
		$this->assertEquals( 'Get Post Type', $ability->getLabel() );
	}

	/**
	 * Test ability returns correct operation type.
	 *
	 * @return void
	 */
	public function testGetOperationType(): void {
		$ability = new GetPostType();
		$this->assertEquals( 'read', $ability->getOperationType() );
	}

	/**
	 * Test ability returns correct required capability.
	 *
	 * @return void
	 */
	public function testGetRequiredCapability(): void {
		$ability = new GetPostType();
		$this->assertEquals( 'read', $ability->getRequiredCapability() );
	}

	/**
	 * Test ability returns input schema with required post_type field.
	 *
	 * @return void
	 */
	public function testGetInputSchema(): void {
		$ability = new GetPostType();
		$schema  = $ability->getInputSchema();

		$this->assertIsArray( $schema );
		$this->assertArrayHasKey( 'type', $schema );
		$this->assertArrayHasKey( 'properties', $schema );
		$this->assertArrayHasKey( 'required', $schema );
		$this->assertArrayHasKey( 'post_type', $schema['properties'] );
		$this->assertContains( 'post_type', $schema['required'] );
	}

	/**
	 * Test ability returns output schema.
	 *
	 * @return void
	 */
	public function testGetOutputSchema(): void {
		$ability = new GetPostType();
		$schema  = $ability->getOutputSchema();

		$this->assertIsArray( $schema );
		$this->assertArrayHasKey( 'type', $schema );
		$this->assertArrayHasKey( 'properties', $schema );
		$this->assertArrayHasKey( 'post_type', $schema['properties'] );
	}

	/**
	 * Test execute throws exception for non-existent post type.
	 *
	 * @return void
	 */
	public function testExecuteThrowsExceptionForNonExistentPostType(): void {
		$ability = new GetPostType();

		Functions\when( 'get_post_type_object' )->justReturn( null );

		$this->expectException( PostNotFoundException::class );
		$this->expectExceptionMessage( 'Post type not found' );

		$ability->doExecute( array( 'post_type' => 'nonexistent_type' ) );
	}

	/**
	 * Test execute returns post type data.
	 *
	 * @return void
	 */
	public function testExecuteReturnsPostTypeData(): void {
		$ability = new GetPostType();

		$mock_labels = (object) array(
			'name'          => 'Posts',
			'singular_name' => 'Post',
			'add_new'       => 'Add New',
			'add_new_item'  => 'Add New Post',
		);

		$mock_cap = (object) array(
			'edit_post'          => 'edit_post',
			'read_post'          => 'read_post',
			'delete_post'        => 'delete_post',
			'edit_posts'         => 'edit_posts',
			'publish_posts'      => 'publish_posts',
		);

		$mock_post_type              = Mockery::mock( \WP_Post_Type::class );
		$mock_post_type->name        = 'post';
		$mock_post_type->label       = 'Posts';
		$mock_post_type->labels      = $mock_labels;
		$mock_post_type->description = 'Default post type';
		$mock_post_type->public      = true;
		$mock_post_type->hierarchical = false;
		$mock_post_type->show_ui     = true;
		$mock_post_type->show_in_rest = true;
		$mock_post_type->rest_base   = 'posts';
		$mock_post_type->cap         = $mock_cap;
		$mock_post_type->rewrite     = array( 'slug' => 'posts', 'with_front' => true );
		$mock_post_type->supports    = array( 'title', 'editor', 'thumbnail', 'excerpt' );
		$mock_post_type->taxonomies  = array( 'category', 'post_tag' );

		Functions\when( 'get_post_type_object' )->justReturn( $mock_post_type );

		$result = $ability->doExecute( array( 'post_type' => 'post' ) );

		$this->assertIsArray( $result );
		$this->assertArrayHasKey( 'post_type', $result );
		$this->assertEquals( 'post', $result['post_type']['name'] );
		$this->assertEquals( 'Posts', $result['post_type']['label'] );
		$this->assertFalse( $result['post_type']['hierarchical'] );
		$this->assertEquals( array( 'category', 'post_tag' ), $result['post_type']['taxonomies'] );
		$this->assertEquals( array( 'title', 'editor', 'thumbnail', 'excerpt' ), $result['post_type']['supports'] );
	}

	/**
	 * Test execute returns labels as array.
	 *
	 * @return void
	 */
	public function testExecuteReturnsLabelsAsArray(): void {
		$ability = new GetPostType();

		$mock_labels = (object) array(
			'name'          => 'Pages',
			'singular_name' => 'Page',
		);

		$mock_post_type              = Mockery::mock( \WP_Post_Type::class );
		$mock_post_type->name        = 'page';
		$mock_post_type->label       = 'Pages';
		$mock_post_type->labels      = $mock_labels;
		$mock_post_type->description = '';
		$mock_post_type->public      = true;
		$mock_post_type->hierarchical = true;
		$mock_post_type->show_ui     = true;
		$mock_post_type->show_in_rest = true;
		$mock_post_type->rest_base   = 'pages';
		$mock_post_type->cap         = null;
		$mock_post_type->rewrite     = true;
		$mock_post_type->supports    = array( 'title', 'editor' );
		$mock_post_type->taxonomies  = array();

		Functions\when( 'get_post_type_object' )->justReturn( $mock_post_type );

		$result = $ability->doExecute( array( 'post_type' => 'page' ) );

		$this->assertIsArray( $result['post_type']['labels'] );
		$this->assertEquals( 'Pages', $result['post_type']['labels']['name'] );
		$this->assertEquals( 'Page', $result['post_type']['labels']['singular_name'] );
	}

	/**
	 * Test execute handles null labels.
	 *
	 * @return void
	 */
	public function testExecuteHandlesNullLabels(): void {
		$ability = new GetPostType();

		$mock_post_type              = Mockery::mock( \WP_Post_Type::class );
		$mock_post_type->name        = 'custom_type';
		$mock_post_type->label       = 'Custom';
		$mock_post_type->labels      = null;
		$mock_post_type->description = '';
		$mock_post_type->public      = true;
		$mock_post_type->hierarchical = false;
		$mock_post_type->show_ui     = true;
		$mock_post_type->show_in_rest = false;
		$mock_post_type->rest_base   = null;
		$mock_post_type->cap         = null;
		$mock_post_type->rewrite     = false;
		$mock_post_type->supports    = array();
		$mock_post_type->taxonomies  = array();

		Functions\when( 'get_post_type_object' )->justReturn( $mock_post_type );

		$result = $ability->doExecute( array( 'post_type' => 'custom_type' ) );

		$this->assertIsArray( $result['post_type']['labels'] );
		$this->assertEmpty( $result['post_type']['labels'] );
	}

	/**
	 * Test execute handles boolean rewrite.
	 *
	 * @return void
	 */
	public function testExecuteHandlesBooleanRewrite(): void {
		$ability = new GetPostType();

		$mock_post_type              = Mockery::mock( \WP_Post_Type::class );
		$mock_post_type->name        = 'attachment';
		$mock_post_type->label       = 'Media';
		$mock_post_type->labels      = null;
		$mock_post_type->description = '';
		$mock_post_type->public      = true;
		$mock_post_type->hierarchical = false;
		$mock_post_type->show_ui     = true;
		$mock_post_type->show_in_rest = true;
		$mock_post_type->rest_base   = 'media';
		$mock_post_type->cap         = null;
		$mock_post_type->rewrite     = false;
		$mock_post_type->supports    = array( 'title' );
		$mock_post_type->taxonomies  = array();

		Functions\when( 'get_post_type_object' )->justReturn( $mock_post_type );

		$result = $ability->doExecute( array( 'post_type' => 'attachment' ) );

		$this->assertFalse( $result['post_type']['rewrite'] );
	}

	/**
	 * Test execute handles array rewrite.
	 *
	 * @return void
	 */
	public function testExecuteHandlesArrayRewrite(): void {
		$ability = new GetPostType();

		$mock_post_type              = Mockery::mock( \WP_Post_Type::class );
		$mock_post_type->name        = 'post';
		$mock_post_type->label       = 'Posts';
		$mock_post_type->labels      = null;
		$mock_post_type->description = '';
		$mock_post_type->public      = true;
		$mock_post_type->hierarchical = false;
		$mock_post_type->show_ui     = true;
		$mock_post_type->show_in_rest = true;
		$mock_post_type->rest_base   = 'posts';
		$mock_post_type->cap         = null;
		$mock_post_type->rewrite     = array( 'slug' => 'posts', 'with_front' => true, 'pages' => true, 'feeds' => true );
		$mock_post_type->supports    = array( 'title', 'editor' );
		$mock_post_type->taxonomies  = array();

		Functions\when( 'get_post_type_object' )->justReturn( $mock_post_type );

		$result = $ability->doExecute( array( 'post_type' => 'post' ) );

		$this->assertIsArray( $result['post_type']['rewrite'] );
		$this->assertEquals( 'posts', $result['post_type']['rewrite']['slug'] );
	}

	/**
	 * Test annotations are correct for read-only ability.
	 *
	 * @return void
	 */
	public function testGetAnnotations(): void {
		$ability     = new GetPostType();
		$annotations = $ability->getAnnotations();

		$this->assertTrue( $annotations['readonly'] );
		$this->assertFalse( $annotations['destructive'] );
		$this->assertTrue( $annotations['idempotent'] );
	}
}
