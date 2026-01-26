<?php
/**
 * Tests for CreateTerm ability.
 *
 * @package FAWpmcp\Tests\Abilities\Taxonomies
 */

declare(strict_types=1);

namespace FAWpmcp\Tests\Abilities\Taxonomies;

use FAWpmcp\Abilities\Taxonomies\CreateTerm;
use FAWpmcp\Exceptions\PostCreationException;
use Brain\Monkey;
use Brain\Monkey\Functions;
use Mockery;
use PHPUnit\Framework\TestCase;

/**
 * Test CreateTerm ability functionality.
 *
 * @package FAWpmcp\Tests\Abilities\Taxonomies
 */
class CreateTermTest extends TestCase {
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
		$ability = new CreateTerm();
		$this->assertEquals( 'fa-wpmcp/create-term', $ability->getName() );
	}

	/**
	 * Test ability returns correct category.
	 *
	 * @return void
	 */
	public function testGetCategory(): void {
		$ability = new CreateTerm();
		$this->assertEquals( 'taxonomies', $ability->getCategory() );
	}

	/**
	 * Test ability returns correct operation type.
	 *
	 * @return void
	 */
	public function testGetOperationType(): void {
		$ability = new CreateTerm();
		$this->assertEquals( 'write', $ability->getOperationType() );
	}

	/**
	 * Test ability returns correct required capability.
	 *
	 * @return void
	 */
	public function testGetRequiredCapability(): void {
		$ability = new CreateTerm();
		$this->assertEquals( 'manage_categories', $ability->getRequiredCapability() );
	}

	/**
	 * Test execute throws exception on WP_Error.
	 *
	 * @return void
	 */
	public function testExecuteThrowsExceptionOnWpError(): void {
		$ability = new CreateTerm();

		$mock_error = Mockery::mock( 'WP_Error' );
		$mock_error->shouldReceive( 'get_error_message' )->andReturn( 'Term creation failed' );

		Functions\when( 'sanitize_title' )->returnArg();
		Functions\when( 'sanitize_textarea_field' )->returnArg();
		Functions\when( 'wp_insert_term' )->justReturn( $mock_error );
		Functions\when( 'is_wp_error' )->justReturn( true );

		$this->expectException( PostCreationException::class );
		$ability->doExecute(
			array(
				'taxonomy' => 'category',
				'name'     => 'Test Category',
			)
		);
	}

	/**
	 * Test execute creates term successfully.
	 *
	 * @return void
	 */
	public function testExecuteCreatesTermSuccessfully(): void {
		$ability = new CreateTerm();

		$mock_term = Mockery::mock( \WP_Term::class );
		$mock_term->term_id  = 1;
		$mock_term->name     = 'Test Category';
		$mock_term->slug     = 'test-category';
		$mock_term->taxonomy = 'category';

		Functions\when( 'sanitize_title' )->returnArg();
		Functions\when( 'sanitize_textarea_field' )->returnArg();
		Functions\when( 'wp_insert_term' )->justReturn(
			array(
				'term_id' => 1,
				'term_taxonomy_id' => 1,
			)
		);
		Functions\when( 'is_wp_error' )->justReturn( false );
		Functions\when( 'get_term' )->justReturn( $mock_term );
		Functions\when( 'get_term_link' )->justReturn( 'https://example.com/category/test' );

		$result = $ability->doExecute(
			array(
				'taxonomy'    => 'category',
				'name'        => 'Test Category',
				'slug'        => 'test-category',
				'description' => 'Test description',
			)
		);

		$this->assertIsArray( $result );
		$this->assertArrayHasKey( 'term_id', $result );
		$this->assertArrayHasKey( 'created', $result );
		$this->assertTrue( $result['created'] );
	}

	/**
	 * Test execute builds sanitized term data with optional fields.
	 *
	 * @return void
	 */
	public function testExecuteBuildsSanitizedTermData(): void {
		$ability = new CreateTerm();

		$mock_term = Mockery::mock( \WP_Term::class );
		$mock_term->term_id  = 10;
		$mock_term->name     = 'Clean Name';
		$mock_term->slug     = 'clean-slug';
		$mock_term->taxonomy = 'category';

		Functions\expect( 'sanitize_title' )
			->once()
			->with( 'Raw Slug' )
			->andReturn( 'clean-slug' );
		Functions\expect( 'sanitize_textarea_field' )
			->once()
			->with( ' Raw description ' )
			->andReturn( 'Raw description' );
		Functions\expect( 'wp_insert_term' )
			->once()
			->with(
				'Clean Name',
				'category',
				array(
					'slug'        => 'clean-slug',
					'description' => 'Raw description',
					'parent'      => 12,
				)
			)
			->andReturn(
				array(
					'term_id' => 10,
					'term_taxonomy_id' => 10,
				)
			);
		Functions\when( 'is_wp_error' )->justReturn( false );
		Functions\when( 'get_term' )->justReturn( $mock_term );
		Functions\when( 'get_term_link' )->justReturn( 'https://example.com/category/clean' );

		$result = $ability->doExecute(
			array(
				'taxonomy'    => 'category',
				'name'        => 'Clean Name',
				'slug'        => 'Raw Slug',
				'description' => ' Raw description ',
				'parent'      => 12,
			)
		);

		$this->assertEquals( 10, $result['term_id'] );
		$this->assertEquals( 'clean-slug', $result['slug'] );
	}

	/**
	 * Test execute returns fallback response when term lookup fails.
	 *
	 * @return void
	 */
	public function testExecuteReturnsFallbackWhenTermLookupFails(): void {
		$ability = new CreateTerm();

		Functions\when( 'sanitize_title' )->returnArg();
		Functions\when( 'sanitize_textarea_field' )->returnArg();
		Functions\when( 'wp_insert_term' )->justReturn(
			array(
				'term_id' => 5,
				'term_taxonomy_id' => 5,
			)
		);
		Functions\when( 'is_wp_error' )->justReturn( false );
		Functions\when( 'get_term' )->justReturn( null );

		$result = $ability->doExecute(
			array(
				'taxonomy' => 'category',
				'name'     => 'Fallback Term',
			)
		);

		$this->assertEquals( 5, $result['term_id'] );
		$this->assertSame( '', $result['name'] );
		$this->assertSame( '', $result['slug'] );
		$this->assertSame( '', $result['link'] );
		$this->assertTrue( $result['created'] );
	}
}
