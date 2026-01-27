<?php

/**
 * Tests for ListCapsAbility.
 *
 * @package FAWpmcp\Tests\Abilities\Role
 */

declare(strict_types=1);

namespace FAWpmcp\Tests\Abilities\Role;

use FAWpmcp\Abilities\AbstractAbility;
use FAWpmcp\Abilities\Role\ListCapsAbility;
use FAWpmcp\Exceptions\RoleNotFoundException;
use FAWpmcp\Tests\TestCase\AbilityTestTrait;
use FAWpmcp\Tests\TestCase\BrainMonkeyTestCase;
use Brain\Monkey\Functions;
use Mockery;

/**
 * Test ListCapsAbility functionality.
 *
 * @package FAWpmcp\Tests\Abilities\Role
 */
class ListCapsAbilityTest extends BrainMonkeyTestCase
{
    use AbilityTestTrait;

    /**
     * Get the ability instance to test.
     *
     * @return AbstractAbility
     */
    protected function getAbilityInstance(): AbstractAbility
    {
        return new ListCapsAbility();
    }

    /**
     * Get the expected metadata for this ability.
     *
     * @return array<string, mixed>
     */
    protected function getExpectedMetadata(): array
    {
        return array(
            'name'                 => 'fa-wpmcp/list-caps',
            'category'             => 'role',
            'label'                => 'List Capabilities',
            'description_contains' => 'capabilities',
            'operation_type'       => 'read',
            'required_capability'  => 'list_users',
        );
    }

    /**
     * Test ability returns input schema with required role field.
     *
     * @return void
     */
    public function testGetInputSchema(): void
    {
        $ability = $this->getAbilityInstance();
        $schema  = $ability->getInputSchema();

        $this->assertIsArray($schema);
        $this->assertArrayHasKey('type', $schema);
        $this->assertArrayHasKey('properties', $schema);
        $this->assertArrayHasKey('required', $schema);
        $this->assertArrayHasKey('role', $schema['properties']);
        $this->assertContains('role', $schema['required']);
    }

    /**
     * Test ability returns output schema.
     *
     * @return void
     */
    public function testGetOutputSchema(): void
    {
        $ability = $this->getAbilityInstance();
        $schema  = $ability->getOutputSchema();

        $this->assertIsArray($schema);
        $this->assertArrayHasKey('type', $schema);
        $this->assertArrayHasKey('properties', $schema);
        $this->assertArrayHasKey('role', $schema['properties']);
        $this->assertArrayHasKey('capabilities', $schema['properties']);
        $this->assertArrayHasKey('total', $schema['properties']);
    }

    /**
     * Test execute returns capabilities list.
     *
     * @return void
     */
    public function testExecuteReturnsCapabilities(): void
    {
        $ability = $this->getAbilityInstance();

        $mock_role = Mockery::mock('WP_Role');
        $mock_role->name = 'editor';
        $mock_role->capabilities = array(
            'edit_posts'        => true,
            'edit_others_posts' => true,
            'publish_posts'     => true,
        );

        Functions\when('get_role')->justReturn($mock_role);

        $result = $ability->doExecute(array( 'role' => 'editor' ));

        $this->assertIsArray($result);
        $this->assertArrayHasKey('role', $result);
        $this->assertArrayHasKey('capabilities', $result);
        $this->assertArrayHasKey('total', $result);
        $this->assertEquals('editor', $result['role']);
        $this->assertIsArray($result['capabilities']);
        $this->assertContains('edit_posts', $result['capabilities']);
        $this->assertContains('edit_others_posts', $result['capabilities']);
        $this->assertContains('publish_posts', $result['capabilities']);
        $this->assertEquals(3, $result['total']);
    }

    /**
     * Test execute throws exception when role not found.
     *
     * @return void
     */
    public function testExecuteThrowsExceptionWhenRoleNotFound(): void
    {
        $ability = $this->getAbilityInstance();

        Functions\when('get_role')->justReturn(null);

        $this->expectException(RoleNotFoundException::class);
        $this->expectExceptionMessage('Role "nonexistent" not found.');

        $ability->doExecute(array( 'role' => 'nonexistent' ));
    }

    /**
     * Test annotations are correct for read-only ability.
     *
     * @return void
     */
    public function testGetAnnotations(): void
    {
        $ability     = new ListCapsAbility();
        $annotations = $ability->getAnnotations();

        $this->assertTrue($annotations['readonly']);
        $this->assertFalse($annotations['destructive']);
        $this->assertTrue($annotations['idempotent']);
    }

    /**
     * Test execute returns empty array for role with no capabilities.
     *
     * @return void
     */
    public function testExecuteReturnsEmptyCapabilitiesForRoleWithNone(): void
    {
        $ability = $this->getAbilityInstance();

        $mock_role = Mockery::mock('WP_Role');
        $mock_role->name = 'subscriber';
        $mock_role->capabilities = array();

        Functions\when('get_role')->justReturn($mock_role);

        $result = $ability->doExecute(array( 'role' => 'subscriber' ));

        $this->assertIsArray($result['capabilities']);
        $this->assertEmpty($result['capabilities']);
        $this->assertEquals(0, $result['total']);
    }

    /**
     * Test capabilities are returned as array keys, not values.
     *
     * @return void
     */
    public function testCapabilitiesAreCapabilityNames(): void
    {
        $ability = $this->getAbilityInstance();

        $mock_role = Mockery::mock('WP_Role');
        $mock_role->name = 'author';
        $mock_role->capabilities = array(
            'read'          => true,
            'edit_posts'    => true,
            'delete_posts'  => false,
        );

        Functions\when('get_role')->justReturn($mock_role);

        $result = $ability->doExecute(array( 'role' => 'author' ));

        // Should return capability names (keys), not boolean values
        $this->assertContains('read', $result['capabilities']);
        $this->assertContains('edit_posts', $result['capabilities']);
        $this->assertContains('delete_posts', $result['capabilities']);
        $this->assertNotContains(true, $result['capabilities']);
        $this->assertNotContains(false, $result['capabilities']);
    }
}
