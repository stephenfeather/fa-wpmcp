<?php

declare(strict_types=1);

namespace FAWpmcp\Tests\Abilities;

use FAWpmcp\Abilities\AbilityRegistrar;
use FAWpmcp\Abilities\AbilityRegistry;
use PHPUnit\Framework\TestCase;

/**
 * Tests for AbilityRegistrar.
 */
class AbilityRegistrarTest extends TestCase
{
    /**
     * Test that registerAll registers all expected abilities.
     */
    public function test_register_all_registers_all_abilities(): void
    {
        $registry = new AbilityRegistry();

        AbilityRegistrar::registerAll($registry);

        $abilities = $registry->all();

        // Verify we have a substantial number of abilities registered.
        $this->assertGreaterThanOrEqual(70, count($abilities));

        // Verify key abilities from different categories exist.
        $abilityNames = array_map(fn($a) => $a->getName(), $abilities);

        // Posts.
        $this->assertContains('fa-wpmcp/get-post', $abilityNames);
        $this->assertContains('fa-wpmcp/list-posts', $abilityNames);

        // Comments.
        $this->assertContains('fa-wpmcp/get-comment', $abilityNames);

        // Media.
        $this->assertContains('fa-wpmcp/get-media', $abilityNames);

        // Taxonomies.
        $this->assertContains('fa-wpmcp/get-term', $abilityNames);
        $this->assertContains('fa-wpmcp/list-taxonomies', $abilityNames);

        // Users.
        $this->assertContains('fa-wpmcp/get-user', $abilityNames);

        // Settings.
        $this->assertContains('fa-wpmcp/get-option', $abilityNames);

        // Plugins.
        $this->assertContains('fa-wpmcp/list-plugins', $abilityNames);

        // Themes.
        $this->assertContains('fa-wpmcp/list-themes', $abilityNames);

        // Privacy.
        $this->assertContains('fa-wpmcp/list-privacy-requests', $abilityNames);

        // Cache.
        $this->assertContains('fa-wpmcp/get-cache-type', $abilityNames);

        // Maintenance.
        $this->assertContains('fa-wpmcp/get-maintenance-mode-status', $abilityNames);

        // Transients.
        $this->assertContains('fa-wpmcp/get-transient', $abilityNames);

        // Cron.
        $this->assertContains('fa-wpmcp/list-cron-events', $abilityNames);

        // Roles.
        $this->assertContains('fa-wpmcp/list-roles', $abilityNames);

        // Menus.
        $this->assertContains('fa-wpmcp/list-menus', $abilityNames);
    }

    /**
     * Test that registering twice throws an exception (registry prevents duplicates).
     */
    public function test_register_all_twice_throws_exception(): void
    {
        $registry = new AbilityRegistry();

        AbilityRegistrar::registerAll($registry);

        // Second call should throw because registry prevents duplicates.
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('is already registered');

        AbilityRegistrar::registerAll($registry);
    }
}
