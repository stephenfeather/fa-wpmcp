<?php
/**
 * Tests for ListPostTypes ability.
 *
 * @package FAWpmcp\Tests\Abilities\PostTypes
 */

declare(strict_types=1);

namespace FAWpmcp\Tests\Abilities\PostTypes;

use FAWpmcp\Abilities\PostTypes\ListPostTypes;
use Brain\Monkey;
use Brain\Monkey\Functions;
use Mockery;
use PHPUnit\Framework\TestCase;

/**
 * Test ListPostTypes ability functionality.
 *
 * @package FAWpmcp\Tests\Abilities\PostTypes
 */
class ListPostTypesTest extends TestCase {
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
		$ability = new ListPostTypes();
		$this->assertEquals( 'fa-wpmcp/list-post-types', $ability->getName() );
	}

	/**
	 * Test ability returns correct category.
	 *
	 * @return void
	 */
	public function testGetCategory(): void {
		$ability = new ListPostTypes();
		$this->assertEquals( 'post-types', $ability->getCategory() );
	}

	/**
	 * Test ability returns correct label.
	 *
	 * @return void
	 */
	public function testGetLabel(): void {
		$ability = new ListPostTypes();
		$this->assertEquals( 'List Post Types', $ability->getLabel() );
	}

	/**
	 * Test ability returns correct operation type.
	 *
	 * @return void
	 */
	public function testGetOperationType(): void {
		$ability = new ListPostTypes();
		$this->assertEquals( 'read', $ability->getOperationType() );
	}

	/**
	 * Test ability returns correct required capability.
	 *
	 * @return void
	 */
	public function testGetRequiredCapability(): void {
		$ability = new ListPostTypes();
		$this->assertEquals( 'read', $ability->getRequiredCapability() );
	}

	/**
	 * Test ability returns input schema.
	 *
	 * @return void
	 */
	public function testGetInputSchema(): void {
		$ability = new ListPostTypes();
		$schema  = $ability->getInputSchema();

		$this->assertIsArray( $schema );
		$this->assertArrayHasKey( 'type', $schema );
		$this->assertArrayHasKey( 'properties', $schema );
		$this->assertArrayHasKey( 'public', $schema['properties'] );
		$this->assertArrayHasKey( 'show_ui', $schema['properties'] );
		$this->assertArrayHasKey( 'hierarchical', $schema['properties'] );
		$this->assertArrayHasKey( 'capability_type', $schema['properties'] );
	}

	/**
	 * Test ability returns output schema.
	 *
	 * @return void
	 */
	public function testGetOutputSchema(): void {
		$ability = new ListPostTypes();
		$schema  = $ability->getOutputSchema();

		$this->assertIsArray( $schema );
		$this->assertArrayHasKey( 'type', $schema );
		$this->assertArrayHasKey( 'properties', $schema );
		$this->assertArrayHasKey( 'post_types', $schema['properties'] );
		$this->assertArrayHasKey( 'total', $schema['properties'] );
	}

	/**
	 * Test execute returns post types list.
	 *
	 * @return void
	 */
	public function testExecuteReturnsPostTypesList(): void {
		$ability = new ListPostTypes();

		$mock_post_type              = Mockery::mock( \WP_Post_Type::class );
		$mock_post_type->name        = 'post';
		$mock_post_type->label       = 'Posts';
		$mock_post_type->description = 'Default post type';
		$mock_post_type->public      = true;
		$mock_post_type->hierarchical = false;
		$mock_post_type->show_ui     = true;
		$mock_post_type->show_in_rest = true;
		$mock_post_type->rest_base   = 'posts';

		Functions\when( 'get_post_types' )->justReturn( array( 'post' => $mock_post_type ) );

		$result = $ability->doExecute( array() );

		$this->assertIsArray( $result );
		$this->assertArrayHasKey( 'post_types', $result );
		$this->assertArrayHasKey( 'total', $result );
		$this->assertCount( 1, $result['post_types'] );
		$this->assertEquals( 1, $result['total'] );
		$this->assertEquals( 'post', $result['post_types'][0]['name'] );
	}

	/**
	 * Test execute returns empty list when no post types match.
	 *
	 * @return void
	 */
	public function testExecuteReturnsEmptyListWhenNoMatch(): void {
		$ability = new ListPostTypes();

		Functions\when( 'get_post_types' )->justReturn( array() );

		$result = $ability->doExecute( array( 'public' => false ) );

		$this->assertIsArray( $result );
		$this->assertEmpty( $result['post_types'] );
		$this->assertEquals( 0, $result['total'] );
	}

	/**
	 * Test execute filters by public visibility.
	 *
	 * @return void
	 */
	public function testExecuteFiltersByPublic(): void {
		$ability = new ListPostTypes();

		$mock_post_type              = Mockery::mock( \WP_Post_Type::class );
		$mock_post_type->name        = 'post';
		$mock_post_type->label       = 'Posts';
		$mock_post_type->description = '';
		$mock_post_type->public      = true;
		$mock_post_type->hierarchical = false;
		$mock_post_type->show_ui     = true;
		$mock_post_type->show_in_rest = true;
		$mock_post_type->rest_base   = 'posts';

		Functions\expect( 'get_post_types' )
			->once()
			->with( Mockery::on( function ( $args ) {
				return isset( $args['public'] ) && $args['public'] === true;
			} ), 'objects' )
			->andReturn( array( 'post' => $mock_post_type ) );

		$result = $ability->doExecute( array( 'public' => true ) );

		$this->assertCount( 1, $result['post_types'] );
	}

	/**
	 * Test execute filters by hierarchical.
	 *
	 * @return void
	 */
	public function testExecuteFiltersByHierarchical(): void {
		$ability = new ListPostTypes();

		$mock_post_type              = Mockery::mock( \WP_Post_Type::class );
		$mock_post_type->name        = 'page';
		$mock_post_type->label       = 'Pages';
		$mock_post_type->description = '';
		$mock_post_type->public      = true;
		$mock_post_type->hierarchical = true;
		$mock_post_type->show_ui     = true;
		$mock_post_type->show_in_rest = true;
		$mock_post_type->rest_base   = 'pages';

		Functions\expect( 'get_post_types' )
			->once()
			->with( Mockery::on( function ( $args ) {
				return isset( $args['hierarchical'] ) && $args['hierarchical'] === true;
			} ), 'objects' )
			->andReturn( array( 'page' => $mock_post_type ) );

		$result = $ability->doExecute( array( 'hierarchical' => true ) );

		$this->assertCount( 1, $result['post_types'] );
	}

	/**
	 * Test execute returns multiple post types.
	 *
	 * @return void
	 */
	public function testExecuteReturnsMultiplePostTypes(): void {
		$ability = new ListPostTypes();

		$mock_post              = Mockery::mock( \WP_Post_Type::class );
		$mock_post->name        = 'post';
		$mock_post->label       = 'Posts';
		$mock_post->description = '';
		$mock_post->public      = true;
		$mock_post->hierarchical = false;
		$mock_post->show_ui     = true;
		$mock_post->show_in_rest = true;
		$mock_post->rest_base   = 'posts';

		$mock_page              = Mockery::mock( \WP_Post_Type::class );
		$mock_page->name        = 'page';
		$mock_page->label       = 'Pages';
		$mock_page->description = '';
		$mock_page->public      = true;
		$mock_page->hierarchical = true;
		$mock_page->show_ui     = true;
		$mock_page->show_in_rest = true;
		$mock_page->rest_base   = 'pages';

		Functions\when( 'get_post_types' )->justReturn( array(
			'post' => $mock_post,
			'page' => $mock_page,
		) );

		$result = $ability->doExecute( array() );

		$this->assertCount( 2, $result['post_types'] );
		$this->assertEquals( 2, $result['total'] );
	}

	/**
	 * Test annotations are correct for read-only ability.
	 *
	 * @return void
	 */
	public function testGetAnnotations(): void {
		$ability     = new ListPostTypes();
		$annotations = $ability->getAnnotations();

		$this->assertTrue( $annotations['readonly'] );
		$this->assertFalse( $annotations['destructive'] );
		$this->assertTrue( $annotations['idempotent'] );
	}
}
