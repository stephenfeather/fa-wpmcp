<?php

/**
 * Tests for UpdateTheme.
 *
 * @package FAWpmcp\Tests\Abilities\Themes
 */

declare(strict_types=1);

namespace FAWpmcp\Tests\Abilities\Themes;

use FAWpmcp\Abilities\Themes\UpdateTheme;
use Brain\Monkey;
use PHPUnit\Framework\TestCase;

class UpdateThemeTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        Monkey\setUp();
    }

    protected function tearDown(): void
    {
        Monkey\tearDown();
        parent::tearDown();
    }

    public function testGetName(): void
    {
        $this->assertEquals('fa-wpmcp/update-theme', ( new UpdateTheme() )->getName());
    }

    public function testGetOperationType(): void
    {
        $this->assertEquals('write', ( new UpdateTheme() )->getOperationType());
    }

    public function testExecuteReturnsSuccess(): void
    {
        $result = ( new UpdateTheme() )->doExecute(array( 'stylesheet' => 'twentytwentyfour' ));
        $this->assertTrue($result['success']);
    }
}
