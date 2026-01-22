<?php
/**
 * Tests for DeleteOption ability.
 *
 * @package FAWpmcp\Tests\Abilities\Settings
 */

declare(strict_types=1);

namespace FAWpmcp\Tests\Abilities\Settings;

use FAWpmcp\Abilities\Settings\DeleteOption;
use Brain\Monkey;
use Brain\Monkey\Functions;
use Mockery;
use PHPUnit\Framework\TestCase;

/**
 * Test DeleteOption ability functionality.
 *
 * Tests cover:
 * - Delete existing option
 * - Delete non-existent option
 *
 * @package FAWpmcp\Tests\Abilities\Settings
 */
class DeleteOptionTest extends TestCase {
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
		$ability = new DeleteOption();
		$this->assertEquals( 'fa-wpmcp/delete-option', $ability->getName() );
	}

	/**
	 * Test ability returns correct category.
	 *
	 * @return void
	 */
	public function testGetCategory(): void {
		$ability = new DeleteOption();
		$this->assertEquals( 'settings', $ability->getCategory() );
	}

	/**
	 * Test ability returns correct label.
	 *
	 * @return void
	 */
	public function testGetLabel(): void {
		$ability = new DeleteOption();
		$this->assertEquals( 'Delete Option', $ability->getLabel() );
	}

	/**
	 * Test ability returns correct operation type.
	 *
	 * @return void
	 */
	public function testGetOperationType(): void {
		$ability = new DeleteOption();
		$this->assertEquals( 'write', $ability->getOperationType() );
	}

	/**
	 * Test ability returns correct required capability.
	 *
	 * @return void
	 */
	public function testGetRequiredCapability(): void {
		$ability = new DeleteOption();
		$this->assertEquals( 'manage_options', $ability->getRequiredCapability() );
	}

	/**
	 * Test execute deletes existing option successfully.
	 *
	 * @return void
	 */
	public function testExecuteDeletesOption(): void {
		$ability = new DeleteOption();

		Functions\expect( 'delete_option' )
			->once()
			->with( 'test_option' )
			->andReturn( true );

		$result = $ability->doExecute( array( 'option_name' => 'test_option' ) );

		$this->assertEquals( 'test_option', $result['option_name'] );
		$this->assertTrue( $result['deleted'] );
	}

	/**
	 * Test execute handles delete failure for non-existent option.
	 *
	 * @return void
	 */
	public function testExecuteHandlesDeleteFailure(): void {
		$ability = new DeleteOption();

		Functions\expect( 'delete_option' )
			->once()
			->with( 'missing_option' )
			->andReturn( false );

		$result = $ability->doExecute( array( 'option_name' => 'missing_option' ) );

		$this->assertEquals( 'missing_option', $result['option_name'] );
		$this->assertFalse( $result['deleted'] );
	}
}
