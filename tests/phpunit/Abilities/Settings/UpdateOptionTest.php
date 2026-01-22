<?php
/**
 * Tests for UpdateOption ability.
 *
 * @package FAWpmcp\Tests\Abilities\Settings
 */

declare(strict_types=1);

namespace FAWpmcp\Tests\Abilities\Settings;

use FAWpmcp\Abilities\Settings\UpdateOption;
use Brain\Monkey;
use Brain\Monkey\Functions;
use Mockery;
use PHPUnit\Framework\TestCase;

/**
 * Test UpdateOption ability functionality.
 *
 * Tests cover:
 * - Update existing option
 * - Create new option
 * - Update with autoload setting
 *
 * @package FAWpmcp\Tests\Abilities\Settings
 */
class UpdateOptionTest extends TestCase {
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
		$ability = new UpdateOption();
		$this->assertEquals( 'fa-wpmcp/update-option', $ability->getName() );
	}

	/**
	 * Test ability returns correct category.
	 *
	 * @return void
	 */
	public function testGetCategory(): void {
		$ability = new UpdateOption();
		$this->assertEquals( 'settings', $ability->getCategory() );
	}

	/**
	 * Test ability returns correct label.
	 *
	 * @return void
	 */
	public function testGetLabel(): void {
		$ability = new UpdateOption();
		$this->assertEquals( 'Update Option', $ability->getLabel() );
	}

	/**
	 * Test ability returns correct operation type.
	 *
	 * @return void
	 */
	public function testGetOperationType(): void {
		$ability = new UpdateOption();
		$this->assertEquals( 'write', $ability->getOperationType() );
	}

	/**
	 * Test ability returns correct required capability.
	 *
	 * @return void
	 */
	public function testGetRequiredCapability(): void {
		$ability = new UpdateOption();
		$this->assertEquals( 'manage_options', $ability->getRequiredCapability() );
	}

	/**
	 * Test execute updates existing option successfully.
	 *
	 * @return void
	 */
	public function testExecuteUpdatesOption(): void {
		$ability = new UpdateOption();

		Functions\expect( 'update_option' )
			->once()
			->with( 'test_option', 'new_value', null )
			->andReturn( true );

		$result = $ability->doExecute(
			array(
				'option_name' => 'test_option',
				'value'       => 'new_value',
			)
		);

		$this->assertEquals( 'test_option', $result['option_name'] );
		$this->assertTrue( $result['updated'] );
	}

	/**
	 * Test execute updates option with autoload setting.
	 *
	 * @return void
	 */
	public function testExecuteUpdatesOptionWithAutoload(): void {
		$ability = new UpdateOption();

		Functions\expect( 'update_option' )
			->once()
			->with( 'test_option', 'value', 'yes' )
			->andReturn( true );

		$result = $ability->doExecute(
			array(
				'option_name' => 'test_option',
				'value'       => 'value',
				'autoload'    => 'yes',
			)
		);

		$this->assertEquals( 'test_option', $result['option_name'] );
		$this->assertTrue( $result['updated'] );
	}

	/**
	 * Test execute handles update failure.
	 *
	 * @return void
	 */
	public function testExecuteHandlesUpdateFailure(): void {
		$ability = new UpdateOption();

		Functions\expect( 'update_option' )
			->once()
			->with( 'test_option', 'value', null )
			->andReturn( false );

		$result = $ability->doExecute(
			array(
				'option_name' => 'test_option',
				'value'       => 'value',
			)
		);

		$this->assertEquals( 'test_option', $result['option_name'] );
		$this->assertFalse( $result['updated'] );
	}

	/**
	 * Test execute handles array values.
	 *
	 * @return void
	 */
	public function testExecuteHandlesArrayValues(): void {
		$ability = new UpdateOption();
		$value   = array(
			'key1' => 'value1',
			'key2' => 'value2',
		);

		Functions\expect( 'update_option' )
			->once()
			->with( 'test_option', $value, null )
			->andReturn( true );

		$result = $ability->doExecute(
			array(
				'option_name' => 'test_option',
				'value'       => $value,
			)
		);

		$this->assertTrue( $result['updated'] );
	}

	/**
	 * Test execute blocks protected options.
	 *
	 * @return void
	 */
	public function testExecuteBlocksProtectedOption(): void {
		$ability = new UpdateOption();

		Functions\expect( 'update_option' )
			->never();

		$this->expectException( \RuntimeException::class );
		$this->expectExceptionMessage( 'protected' );

		$ability->doExecute(
			array(
				'option_name' => 'admin_email',
				'value'       => 'hacker@example.com',
			)
		);
	}
}
