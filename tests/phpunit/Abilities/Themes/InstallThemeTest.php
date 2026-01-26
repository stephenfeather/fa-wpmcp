<?php

/**
 * Tests for InstallTheme.
 *
 * @package FAWpmcp\Tests\Abilities\Themes
 */

declare(strict_types=1);

namespace FAWpmcp\Tests\Abilities\Themes;

use FAWpmcp\Abilities\AbstractAbility;
use FAWpmcp\Abilities\Themes\InstallTheme;
use FAWpmcp\Tests\TestCase\AbilityTestTrait;
use FAWpmcp\Tests\TestCase\BrainMonkeyTestCase;

/**
 * Test InstallTheme ability functionality.
 *
 * @package FAWpmcp\Tests\Abilities\Themes
 */
class InstallThemeTest extends BrainMonkeyTestCase
{
    use AbilityTestTrait;

    /**
     * Get an instance of the ability being tested.
     *
     * @return AbstractAbility
     */
    protected function getAbilityInstance(): AbstractAbility
    {
        return new InstallTheme();
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
            'name'                  => 'fa-wpmcp/install-theme',
            'category'              => 'themes',
            'label'                 => 'Install Theme',
            'description_contains'  => 'install a wordpress theme',
            'operation_type'        => 'write',
            'required_capability'   => 'install_themes',
        );
    }

    public function testExecuteThrowsNotImplementedException(): void
    {
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Theme installation is not yet implemented');

        $this->getAbilityInstance()->doExecute(array( 'slug' => 'twentytwentyfour' ));
    }
}
