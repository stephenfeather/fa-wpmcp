<?php

/**
 * Tests for ListThemes ability.
 *
 * @package FAWpmcp\Tests\Abilities\Themes
 */

declare(strict_types=1);

namespace FAWpmcp\Tests\Abilities\Themes;

use FAWpmcp\Abilities\Themes\ListThemes;
use Brain\Monkey;
use Brain\Monkey\Functions;
use Mockery;
use PHPUnit\Framework\TestCase;

/**
 * Test ListThemes ability functionality.
 *
 * @package FAWpmcp\Tests\Abilities\Themes
 */
class ListThemesTest extends TestCase
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
        $ability = new ListThemes();
        $this->assertEquals('fa-wpmcp/list-themes', $ability->getName());
    }

    public function testGetCategory(): void
    {
        $ability = new ListThemes();
        $this->assertEquals('themes', $ability->getCategory());
    }

    public function testGetOperationType(): void
    {
        $ability = new ListThemes();
        $this->assertEquals('read', $ability->getOperationType());
    }

    public function testGetRequiredCapability(): void
    {
        $ability = new ListThemes();
        $this->assertEquals('switch_themes', $ability->getRequiredCapability());
    }

    public function testExecuteListsAllThemes(): void
    {
        $ability = new ListThemes();

        $theme1 = Mockery::mock('WP_Theme');
        $theme1->shouldReceive('get_stylesheet')->andReturn('twentytwentyfour');
        $theme1->shouldReceive('get')->with('Name')->andReturn('Twenty Twenty-Four');
        $theme1->shouldReceive('get')->with('Version')->andReturn('1.0');

        $theme2 = Mockery::mock('WP_Theme');
        $theme2->shouldReceive('get_stylesheet')->andReturn('twentytwentythree');
        $theme2->shouldReceive('get')->with('Name')->andReturn('Twenty Twenty-Three');
        $theme2->shouldReceive('get')->with('Version')->andReturn('1.1');

        Functions\expect('wp_get_themes')
            ->once()
            ->andReturn(
                array(
                    'twentytwentyfour'  => $theme1,
                    'twentytwentythree' => $theme2,
                )
            );

        Functions\expect('get_option')
            ->with('stylesheet')
            ->once()
            ->andReturn('twentytwentyfour');

        $result = $ability->doExecute(array());

        $this->assertCount(2, $result['themes']);
        $this->assertEquals('twentytwentyfour', $result['themes'][0]['stylesheet']);
        $this->assertTrue($result['themes'][0]['active']);
        $this->assertEquals('twentytwentythree', $result['themes'][1]['stylesheet']);
        $this->assertFalse($result['themes'][1]['active']);
        $this->assertEquals(2, $result['total']);
    }
}
