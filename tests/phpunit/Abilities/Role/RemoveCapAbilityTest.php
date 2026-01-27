<?php

/**
 * Tests for RemoveCapAbility.
 *
 * @package FAWpmcp\Tests\Abilities\Role
 */

declare(strict_types=1);

namespace FAWpmcp\Tests\Abilities\Role;

use FAWpmcp\Abilities\AbstractAbility;
use FAWpmcp\Abilities\Role\RemoveCapAbility;
use FAWpmcp\Exceptions\RoleNotFoundException;
use FAWpmcp\Tests\TestCase\AbilityTestTrait;
use FAWpmcp\Tests\TestCase\BrainMonkeyTestCase;
use Brain\Monkey\Functions;
use Mockery;

/**
 * Test RemoveCapAbility functionality.
 *
 * @package FAWpmcp\Tests\Abilities\Role
 */
class RemoveCapAbilityTest extends BrainMonkeyTestCase
{
    use AbilityTestTrait;

    /**
     * Get the ability instance to test.
     *
     * @return AbstractAbility
     */
    protected function getAbilityInstance(): AbstractAbility
    {
        return new RemoveCapAbility();
    }

    /**
     * Get the expected metadata for this ability.
     *
     * @return array<string, mixed>
     */
    protected function getExpectedMetadata(): array
    {
        return array(
            'name'                 => 'fa-wpmcp/remove-cap',
            'category'             => 'role',
            'label'                => 'Remove Capability',
            'description_contains' => 'capabilities',
            'operation_type'       => 'write',
            'required_capability'  => 'promote_users',
        );
    }

    /**
     * Test ability returns input schema with required fields.
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
        $this->assertArrayHasKey('capabilities', $schema['properties']);
        $this->assertContains('role', $schema['required']);
        $this->assertContains('capabilities', $schema['required']);
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
        $this->assertArrayHasKey('removed', $schema['properties']);
        $this->assertArrayHasKey('total_capabilities', $schema['properties']);
    }

    /**
     * Test operation type is write.
     *
     * @return void
     */
    public function testGetOperationType(): void
    {
        $ability = $this->getAbilityInstance();

        $this->assertEquals('write', $ability->getOperationType());
    }

    /**
     * Test execute removes single capability.
     *
     * @return void
     */
    public function testExecuteRemovesSingleCapability(): void
    {
        $ability = $this->getAbilityInstance();

        $mock_role = Mockery::mock('WP_Role');
        $mock_role->name = 'editor';
        $mock_role->capabilities = array(
            'edit_posts' => true,
        );

        $mock_role->shouldReceive('remove_cap')
            ->once()
            ->with('manage_options');

        Functions\when('get_role')->justReturn($mock_role);

        $result = $ability->doExecute(array(
            'role'         => 'editor',
            'capabilities' => array( 'manage_options' ),
        ));

        $this->assertIsArray($result);
        $this->assertArrayHasKey('role', $result);
        $this->assertArrayHasKey('removed', $result);
        $this->assertArrayHasKey('total_capabilities', $result);
        $this->assertEquals('editor', $result['role']);
        $this->assertContains('manage_options', $result['removed']);
        $this->assertEquals(1, $result['total_capabilities']);
    }

    /**
     * Test execute removes multiple capabilities.
     *
     * @return void
     */
    public function testExecuteRemovesMultipleCapabilities(): void
    {
        $ability = $this->getAbilityInstance();

        $mock_role = Mockery::mock('WP_Role');
        $mock_role->name = 'contributor';
        $mock_role->capabilities = array(
            'read' => true,
        );

        $mock_role->shouldReceive('remove_cap')
            ->once()
            ->with('edit_posts');
        $mock_role->shouldReceive('remove_cap')
            ->once()
            ->with('delete_posts');

        Functions\when('get_role')->justReturn($mock_role);

        $result = $ability->doExecute(array(
            'role'         => 'contributor',
            'capabilities' => array( 'edit_posts', 'delete_posts' ),
        ));

        $this->assertCount(2, $result['removed']);
        $this->assertContains('edit_posts', $result['removed']);
        $this->assertContains('delete_posts', $result['removed']);
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

        $ability->doExecute(array(
            'role'         => 'nonexistent',
            'capabilities' => array( 'some_cap' ),
        ));
    }

    /**
     * Test annotations are correct for write ability.
     *
     * @return void
     */
    public function testGetAnnotations(): void
    {
        $ability     = new RemoveCapAbility();
        $annotations = $ability->getAnnotations();

        $this->assertFalse($annotations['readonly']);
        $this->assertFalse($annotations['destructive']);
        $this->assertTrue($annotations['idempotent']);
    }

    /**
     * Test execute with empty capabilities array.
     *
     * @return void
     */
    public function testExecuteWithEmptyCapabilities(): void
    {
        $ability = $this->getAbilityInstance();

        $mock_role = Mockery::mock('WP_Role');
        $mock_role->name = 'author';
        $mock_role->capabilities = array(
            'read'       => true,
            'edit_posts' => true,
        );

        Functions\when('get_role')->justReturn($mock_role);

        $result = $ability->doExecute(array(
            'role'         => 'author',
            'capabilities' => array(),
        ));

        $this->assertEmpty($result['removed']);
        $this->assertEquals(2, $result['total_capabilities']);
    }
}
