<?php
/**
 * Tests for ListComments ability.
 *
 * @package FAWpmcp\Tests\Abilities\Comments
 */

declare(strict_types=1);

namespace FAWpmcp\Tests\Abilities\Comments;

use FAWpmcp\Abilities\AbstractAbility;
use FAWpmcp\Abilities\Comments\ListComments;
use FAWpmcp\Tests\TestCase\AbilityTestTrait;
use FAWpmcp\Tests\TestCase\BrainMonkeyTestCase;
use Brain\Monkey\Functions;
use Mockery;

/**
 * Test ListComments ability.
 *
 * @package FAWpmcp\Tests\Abilities\Comments
 */
final class ListCommentsTest extends BrainMonkeyTestCase {

	use AbilityTestTrait;

	/**
	 * Get an instance of the ability being tested.
	 *
	 * @return AbstractAbility
	 */
	protected function getAbilityInstance(): AbstractAbility {
		return new ListComments();
	}

	/**
	 * Get expected metadata for the ability.
	 *
	 * @return array{
	 *     name: string,
	 *     category: string,
	 *     label: string,
	 *     description_contains: string,
	 *     operation_type: string,
	 *     required_capability: string
	 * }
	 */
	protected function getExpectedMetadata(): array {
		return [
			'name'                 => 'fa-wpmcp/list-comments',
			'category'             => 'comments',
			'label'                => 'List Comments',
			'description_contains' => 'comment',
			'operation_type'       => 'read',
			'required_capability'  => 'read',
		];
	}

	/**
	 * Test input schema has pagination and filter properties.
	 *
	 * @return void
	 */
	public function testInputSchemaHasFilterProperties(): void {
		$schema = $this->getAbilityInstance()->getInputSchema();

		$this->assertArrayHasKey( 'page', $schema['properties'] );
		$this->assertArrayHasKey( 'per_page', $schema['properties'] );
		$this->assertArrayHasKey( 'post_id', $schema['properties'] );
		$this->assertArrayHasKey( 'status', $schema['properties'] );
	}

	/**
	 * Test output schema includes comments array and pagination info.
	 *
	 * @return void
	 */
	public function testOutputSchemaStructure(): void {
		$schema = $this->getAbilityInstance()->getOutputSchema();

		$this->assertArrayHasKey( 'comments', $schema['properties'] );
		$this->assertArrayHasKey( 'total', $schema['properties'] );
		$this->assertArrayHasKey( 'page', $schema['properties'] );
		$this->assertArrayHasKey( 'per_page', $schema['properties'] );
	}

	/**
	 * Test lists comments with pagination.
	 *
	 * @return void
	 */
	public function testListsCommentsWithPagination(): void {
		$mock_comments = [
			(object) [
				'comment_ID'           => 1,
				'comment_post_ID'      => 10,
				'comment_author'       => 'John Doe',
				'comment_author_email' => 'john@example.com',
				'comment_content'      => 'Great post!',
				'comment_date'         => '2026-01-21 10:00:00',
				'comment_approved'     => '1',
			],
		];

		Functions\expect( 'get_comments' )
			->once()
			->andReturn( $mock_comments );

		Functions\expect( 'wp_count_comments' )
			->once()
			->andReturn( (object) [ 'approved' => '10' ] );

		Functions\expect( 'get_comment_link' )
			->once()
			->andReturn( 'https://example.com/post#comment-1' );

		$result = $this->getAbilityInstance()->doExecute(
			[
				'page'     => 1,
				'per_page' => 10,
			]
		);

		$this->assertArrayHasKey( 'comments', $result );
		$this->assertArrayHasKey( 'total', $result );
		$this->assertEquals( 10, $result['total'] );
	}

	/**
	 * Test filters by post ID.
	 *
	 * @return void
	 */
	public function testFiltersByPostId(): void {
		Functions\expect( 'get_comments' )
			->once()
			->with(
				Mockery::on(
					function ( $args ) {
						return $args['post_id'] === 42;
					}
				)
			)
			->andReturn( [] );

		Functions\expect( 'wp_count_comments' )
			->with( 42 )
			->andReturn( (object) [ 'approved' => '0' ] );

		$this->getAbilityInstance()->doExecute( [ 'post_id' => 42 ] );

		$this->assertTrue( true );
	}

	/**
	 * Test filters by status.
	 *
	 * @return void
	 */
	public function testFiltersByStatus(): void {
		Functions\expect( 'get_comments' )
			->once()
			->with(
				Mockery::on(
					function ( $args ) {
						return $args['status'] === 'hold';
					}
				)
			)
			->andReturn( [] );

		Functions\expect( 'wp_count_comments' )
			->andReturn( (object) [ 'moderated' => '5' ] );

		$result = $this->getAbilityInstance()->doExecute( [ 'status' => 'hold' ] );

		$this->assertEquals( 5, $result['total'] );
	}

	/**
	 * Test returns total for all statuses.
	 *
	 * @return void
	 */
	public function testCountsAllStatus(): void {
		Functions\expect( 'get_comments' )
			->once()
			->andReturn( [] );

		Functions\expect( 'wp_count_comments' )
			->andReturn( (object) [ 'total_comments' => '12' ] );

		$result = $this->getAbilityInstance()->doExecute( [ 'status' => 'all' ] );

		$this->assertEquals( 12, $result['total'] );
	}

	/**
	 * Test returns spam count when status is spam.
	 *
	 * @return void
	 */
	public function testCountsSpamStatus(): void {
		Functions\expect( 'get_comments' )
			->once()
			->andReturn( [] );

		Functions\expect( 'wp_count_comments' )
			->andReturn( (object) [ 'spam' => '3' ] );

		$result = $this->getAbilityInstance()->doExecute( [ 'status' => 'spam' ] );

		$this->assertEquals( 3, $result['total'] );
	}

	/**
	 * Test returns trash count when status is trash.
	 *
	 * @return void
	 */
	public function testCountsTrashStatus(): void {
		Functions\expect( 'get_comments' )
			->once()
			->andReturn( [] );

		Functions\expect( 'wp_count_comments' )
			->andReturn( (object) [ 'trash' => '2' ] );

		$result = $this->getAbilityInstance()->doExecute( [ 'status' => 'trash' ] );

		$this->assertEquals( 2, $result['total'] );
	}
}
