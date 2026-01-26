<?php

/**
 * Base test case with Brain\Monkey setup.
 *
 * @package FAWpmcp\Tests\TestCase
 */

declare(strict_types=1);

namespace FAWpmcp\Tests\TestCase;

use Brain\Monkey;
use Mockery;
use PHPUnit\Framework\TestCase;

/**
 * Base test case that handles Brain\Monkey setup and teardown.
 *
 * Extend this class instead of PHPUnit\Framework\TestCase for any test
 * that needs to mock WordPress functions.
 *
 * @package FAWpmcp\Tests\TestCase
 */
abstract class BrainMonkeyTestCase extends TestCase {

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
}
