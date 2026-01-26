<?php

/**
 * Tests for ActivateTheme.
 *
 * @package FAWpmcp\Tests\Abilities\Themes
 */

declare(strict_types=1);

namespace FAWpmcp\Tests\Abilities\Themes;

use FAWpmcp\Abilities\Themes\ActivateTheme;
use Brain\Monkey;
use Brain\Monkey\Functions;
use Mockery;
use PHPUnit\Framework\TestCase;

class ActivateThemeTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        Monkey\setUp();
    }

    protected function tearDown(): void
    {
        Monkey\tearDown();
        Mockery::close();
        parent::tearDown();
    }

    public function testGetName(): void
    {
        $this->assertEquals('fa-wpmcp/activate-theme', ( new ActivateTheme() )->getName());
    }

    public function testGetCategory(): void
    {
        $this->assertEquals('themes', ( new ActivateTheme() )->getCategory());
    }

    public function testGetOperationType(): void
    {
        $this->assertEquals('write', ( new ActivateTheme() )->getOperationType());
    }

    public function testExecuteActivatesTheme(): void
    {
        Functions\expect('switch_theme')->once()->with('twentytwentyfour');
        $result = ( new ActivateTheme() )->doExecute(array( 'stylesheet' => 'twentytwentyfour' ));
        $this->assertTrue($result['success']);
    }
}
