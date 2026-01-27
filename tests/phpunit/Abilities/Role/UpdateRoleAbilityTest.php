<?php

/**
 * Tests for UpdateRoleAbility.
 *
 * @package FAWpmcp\Tests\Abilities\Role
 */

declare(strict_types=1);

namespace FAWpmcp\Tests\Abilities\Role;

use FAWpmcp\Abilities\AbstractAbility;
use FAWpmcp\Abilities\Role\UpdateRoleAbility;
use FAWpmcp\Exceptions\RoleNotFoundException;
use FAWpmcp\Tests\TestCase\AbilityTestTrait;
use FAWpmcp\Tests\TestCase\BrainMonkeyTestCase;
use Brain\Monkey\Functions;
use Mockery;

/**
 * Test UpdateRoleAbility functionality.
 *
 * @package FAWpmcp\Tests\Abilities\Role
 */
class UpdateRoleAbilityTest extends BrainMonkeyTestCase
{
    use AbilityTestTrait;

    /**
     * Get the ability instance to test.
     *
     * @return AbstractAbility
     */
    protected function getAbilityInstance(): AbstractAbility
    {
        return new UpdateRoleAbility();
    }

    /**
     * Get the expected metadata for this ability.
     *
     * @return array<string, mixed>
     */
    protected function getExpectedMetadata(): array
    {
        return array(
            'name'                 => 'fa-wpmcp/update-role',
            'category'             => 'role',
            'label'                => 'Update Role',
            'description_contains' => 'update',
            'operation_type'       => 'write',
            'required_capability'  => 'promote_users',
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
        $this->assertArrayHasKey('add_caps', $schema['properties']);
        $this->assertArrayHasKey('remove_caps', $schema['properties']);
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
        $this->assertArrayHasKey('name', $schema['properties']);
        $this->assertArrayHasKey('capabilities', $schema['properties']);
    }

    /**
     * Test execute adds capabilities to role.
     *
     * @return void
     */
    public function testExecuteAddsCapabilitiesToRole(): void
    {
        $ability = $this->getAbilityInstance();

        $mock_role = Mockery::mock('WP_Role');
        $mock_role->name = 'editor';
        $mock_role->capabilities = array(
            'edit_posts'        => true,
            'manage_categories' => true,
        );

        $mock_role->shouldReceive('add_cap')
            ->once()
            ->with('manage_categories', true);

        Functions\when('get_role')->justReturn($mock_role);

        $result = $ability->doExecute(
            array(
                'role'     => 'editor',
                'add_caps' => array( 'manage_categories' ),
            )
        );

        $this->assertIsArray($result);
        $this->assertEquals('editor', $result['name']);
        $this->assertArrayHasKey('capabilities', $result);
    }

    /**
     * Test execute removes capabilities from role.
     *
     * @return void
     */
    public function testExecuteRemovesCapabilitiesFromRole(): void
    {
        $ability = $this->getAbilityInstance();

        $mock_role = Mockery::mock('WP_Role');
        $mock_role->name = 'editor';
        $mock_role->capabilities = array(
            'edit_posts' => true,
        );

        $mock_role->shouldReceive('remove_cap')
            ->once()
            ->with('edit_others_posts');

        Functions\when('get_role')->justReturn($mock_role);

        $result = $ability->doExecute(
            array(
                'role'        => 'editor',
                'remove_caps' => array( 'edit_others_posts' ),
            )
        );

        $this->assertIsArray($result);
        $this->assertEquals('editor', $result['name']);
    }

    /**
     * Test execute adds and removes capabilities simultaneously.
     *
     * @return void
     */
    public function testExecuteAddsAndRemovesCapabilitiesSimultaneously(): void
    {
        $ability = $this->getAbilityInstance();

        $mock_role = Mockery::mock('WP_Role');
        $mock_role->name = 'author';
        $mock_role->capabilities = array(
            'edit_posts'       => true,
            'upload_files'     => true,
            'manage_downloads' => true,
        );

        $mock_role->shouldReceive('add_cap')
            ->once()
            ->with('upload_files', true);
        $mock_role->shouldReceive('add_cap')
            ->once()
            ->with('manage_downloads', true);
        $mock_role->shouldReceive('remove_cap')
            ->once()
            ->with('delete_posts');

        Functions\when('get_role')->justReturn($mock_role);

        $result = $ability->doExecute(
            array(
                'role'        => 'author',
                'add_caps'    => array( 'upload_files', 'manage_downloads' ),
                'remove_caps' => array( 'delete_posts' ),
            )
        );

        $this->assertEquals('author', $result['name']);
        $this->assertIsArray($result['capabilities']);
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

        $ability->doExecute(
            array(
                'role'     => 'nonexistent',
                'add_caps' => array( 'some_cap' ),
            )
        );
    }

    /**
     * Test execute with no capability changes returns current state.
     *
     * @return void
     */
    public function testExecuteWithNoCapabilityChangesReturnsCurrentState(): void
    {
        $ability = $this->getAbilityInstance();

        $mock_role = Mockery::mock('WP_Role');
        $mock_role->name = 'subscriber';
        $mock_role->capabilities = array(
            'read' => true,
        );

        Functions\when('get_role')->justReturn($mock_role);

        $result = $ability->doExecute(
            array(
                'role' => 'subscriber',
            )
        );

        $this->assertEquals('subscriber', $result['name']);
        $this->assertEquals(array( 'read' => true ), $result['capabilities']);
    }

    /**
     * Test annotations indicate write operation.
     *
     * @return void
     */
    public function testGetAnnotations(): void
    {
        $ability     = new UpdateRoleAbility();
        $annotations = $ability->getAnnotations();

        $this->assertFalse($annotations['readonly']);
        $this->assertTrue($annotations['destructive']);
        $this->assertTrue($annotations['idempotent']);
    }

    /**
     * Test execute returns updated capabilities.
     *
     * @return void
     */
    public function testExecuteReturnsUpdatedCapabilities(): void
    {
        $ability = $this->getAbilityInstance();

        $mock_role = Mockery::mock('WP_Role');
        $mock_role->name = 'contributor';
        $mock_role->capabilities = array(
            'read'        => true,
            'edit_posts'  => true,
            'custom_cap'  => true,
        );

        $mock_role->shouldReceive('add_cap')
            ->once()
            ->with('custom_cap', true);

        Functions\when('get_role')->justReturn($mock_role);

        $result = $ability->doExecute(
            array(
                'role'     => 'contributor',
                'add_caps' => array( 'custom_cap' ),
            )
        );

        $this->assertIsArray($result['capabilities']);
        // Capabilities should include the existing ones.
        $this->assertArrayHasKey('read', $result['capabilities']);
    }
}
