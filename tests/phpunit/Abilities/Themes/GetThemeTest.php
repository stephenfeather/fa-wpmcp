<?php

/**
 * Tests for GetTheme ability.
 *
 * @package FAWpmcp\Tests\Abilities\Themes
 */

declare(strict_types=1);

namespace FAWpmcp\Tests\Abilities\Themes;

use FAWpmcp\Abilities\AbstractAbility;
use FAWpmcp\Abilities\Themes\GetTheme;
use FAWpmcp\Exceptions\ThemeNotFoundException;
use FAWpmcp\Tests\TestCase\AbilityTestTrait;
use FAWpmcp\Tests\TestCase\BrainMonkeyTestCase;
use Brain\Monkey\Functions;
use Mockery;

/**
 * Test GetTheme ability functionality.
 *
 * @package FAWpmcp\Tests\Abilities\Themes
 */
class GetThemeTest extends BrainMonkeyTestCase
{
    use AbilityTestTrait;

    /**
     * Get an instance of the ability being tested.
     *
     * @return AbstractAbility
     */
    protected function getAbilityInstance(): AbstractAbility
    {
        return new GetTheme();
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
    protected function getExpectedMetadata(): array
    {
        return array(
            'name'                  => 'fa-wpmcp/get-theme',
            'category'              => 'themes',
            'label'                 => 'Get Theme',
            'description_contains'  => 'get details about a specific',
            'operation_type'        => 'read',
            'required_capability'   => 'switch_themes',
        );
    }

    public function testExecuteReturnsThemeDetails(): void
    {
        $ability = $this->getAbilityInstance();

        $theme = Mockery::mock('WP_Theme');
        $theme->shouldReceive('get_stylesheet')->andReturn('twentytwentyfour');
        $theme->shouldReceive('get')->with('Name')->andReturn('Twenty Twenty-Four');
        $theme->shouldReceive('get')->with('Version')->andReturn('1.0');
        $theme->shouldReceive('exists')->andReturn(true);

        Functions\expect('wp_get_theme')
            ->once()
            ->with('twentytwentyfour')
            ->andReturn($theme);

        Functions\expect('get_option')
            ->with('stylesheet')
            ->once()
            ->andReturn('twentytwentyfour');

        $result = $ability->doExecute(array( 'stylesheet' => 'twentytwentyfour' ));

        $this->assertEquals('twentytwentyfour', $result['stylesheet']);
        $this->assertEquals('Twenty Twenty-Four', $result['name']);
        $this->assertEquals('1.0', $result['version']);
        $this->assertTrue($result['active']);
    }

    public function testExecuteThrowsWhenThemeNotFound(): void
    {
        $this->expectException(ThemeNotFoundException::class);

        $theme = Mockery::mock('WP_Theme');
        $theme->shouldReceive('exists')->andReturn(false);

        Functions\expect('wp_get_theme')
            ->once()
            ->with('nonexistent')
            ->andReturn($theme);

        $this->getAbilityInstance()->doExecute(array( 'stylesheet' => 'nonexistent' ));
    }
}
