<?php
/**
 * Tests for ListTerms ability.
 *
 * @package FAWpmcp\Tests\Abilities\Taxonomies
 */

declare(strict_types=1);

namespace FAWpmcp\Tests\Abilities\Taxonomies;

use FAWpmcp\Abilities\Taxonomies\ListTerms;
use Brain\Monkey;
use Brain\Monkey\Functions;
use Mockery;
use PHPUnit\Framework\TestCase;

/**
 * Test ListTerms ability functionality.
 *
 * @package FAWpmcp\Tests\Abilities\Taxonomies
 */
class ListTermsTest extends TestCase {
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
		$ability = new ListTerms();
		$this->assertEquals( 'fa-wpmcp/list-terms', $ability->getName() );
	}

	/**
	 * Test ability returns correct category.
	 *
	 * @return void
	 */
	public function testGetCategory(): void {
		$ability = new ListTerms();
		$this->assertEquals( 'taxonomies', $ability->getCategory() );
	}

	/**
	 * Test ability returns correct label.
	 *
	 * @return void
	 */
	public function testGetLabel(): void {
		$ability = new ListTerms();
		$this->assertEquals( 'List Terms', $ability->getLabel() );
	}

	/**
	 * Test ability returns correct operation type.
	 *
	 * @return void
	 */
	public function testGetOperationType(): void {
		$ability = new ListTerms();
		$this->assertEquals( 'read', $ability->getOperationType() );
	}

	/**
	 * Test ability returns correct required capability.
	 *
	 * @return void
	 */
	public function testGetRequiredCapability(): void {
		$ability = new ListTerms();
		$this->assertEquals( 'read', $ability->getRequiredCapability() );
	}

	/**
	 * Test ability returns input schema.
	 *
	 * @return void
	 */
	public function testGetInputSchema(): void {
		$ability = new ListTerms();
		$schema  = $ability->getInputSchema();

		$this->assertIsArray( $schema );
		$this->assertArrayHasKey( 'type', $schema );
		$this->assertArrayHasKey( 'properties', $schema );
		$this->assertArrayHasKey( 'taxonomy', $schema['properties'] );
		$this->assertArrayHasKey( 'page', $schema['properties'] );
	}

	/**
	 * Test ability returns output schema.
	 *
	 * @return void
	 */
	public function testGetOutputSchema(): void {
		$ability = new ListTerms();
		$schema  = $ability->getOutputSchema();

		$this->assertIsArray( $schema );
		$this->assertArrayHasKey( 'type', $schema );
		$this->assertArrayHasKey( 'properties', $schema );
		$this->assertArrayHasKey( 'terms', $schema['properties'] );
	}

	/**
	 * Test execute returns terms list.
	 *
	 * @return void
	 */
	public function testExecuteReturnsTermsList(): void {
		$ability = new ListTerms();

		$mock_term = Mockery::mock( \WP_Term::class );
		$mock_term->term_id     = 1;
		$mock_term->name        = 'Test Category';
		$mock_term->slug        = 'test-category';
		$mock_term->description = 'Test description';
		$mock_term->parent      = 0;
		$mock_term->count       = 5;
		$mock_term->taxonomy    = 'category';

		Functions\when( 'get_terms' )->justReturn( array( $mock_term ), 1 );
		Functions\when( 'get_term_link' )->justReturn( 'https://example.com/category/test' );

		$result = $ability->doExecute( array( 'taxonomy' => 'category', 'page' => 1 ) );

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
		$ability = new ListTerms();

		$mock_error = Mockery::mock( 'WP_Error' );
		Functions\when( 'get_terms' )->justReturn( $mock_error );
		Functions\when( 'is_wp_error' )->justReturn( true );

		$result = $ability->doExecute( array( 'taxonomy' => 'category' ) );

		$this->assertIsArray( $result );
		$this->assertEquals( 0, $result['total'] );
		$this->assertEmpty( $result['terms'] );
	}
}
