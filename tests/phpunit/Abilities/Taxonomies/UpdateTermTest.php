<?php
/**
 * Tests for UpdateTerm ability.
 *
 * @package FAWpmcp\Tests\Abilities\Taxonomies
 */

declare(strict_types=1);

namespace FAWpmcp\Tests\Abilities\Taxonomies;

use FAWpmcp\Abilities\Taxonomies\UpdateTerm;
use FAWpmcp\Exceptions\PostNotFoundException;
use FAWpmcp\Exceptions\PostUpdateException;
use Brain\Monkey;
use Brain\Monkey\Functions;
use Mockery;
use PHPUnit\Framework\TestCase;

/**
 * Test UpdateTerm ability functionality.
 *
 * @package FAWpmcp\Tests\Abilities\Taxonomies
 */
class UpdateTermTest extends TestCase {
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
		$ability = new UpdateTerm();
		$this->assertEquals( 'fa-wpmcp/update-term', $ability->getName() );
	}

	/**
	 * Test ability returns correct category.
	 *
	 * @return void
	 */
	public function testGetCategory(): void {
		$ability = new UpdateTerm();
		$this->assertEquals( 'taxonomies', $ability->getCategory() );
	}

	/**
	 * Test ability returns correct operation type.
	 *
	 * @return void
	 */
	public function testGetOperationType(): void {
		$ability = new UpdateTerm();
		$this->assertEquals( 'write', $ability->getOperationType() );
	}

	/**
	 * Test execute throws exception for non-existent term.
	 *
	 * @return void
	 */
	public function testExecuteThrowsExceptionForNonExistentTerm(): void {
		$ability = new UpdateTerm();

		Functions\when( 'get_term' )->justReturn( null );
		Functions\when( 'is_wp_error' )->justReturn( false );

		$this->expectException( PostNotFoundException::class );
		$ability->doExecute(
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
		$ability = new UpdateTerm();

		$mock_term = Mockery::mock( \WP_Term::class );
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
		$ability->doExecute(
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
		$ability = new UpdateTerm();

		$mock_term = Mockery::mock( \WP_Term::class );
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

		$result = $ability->doExecute(
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
}
