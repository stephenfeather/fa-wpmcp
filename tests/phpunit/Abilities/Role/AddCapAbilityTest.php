<?php

/**
 * Tests for AddCapAbility.
 *
 * @package FAWpmcp\Tests\Abilities\Role
 */

declare(strict_types=1);

namespace FAWpmcp\Tests\Abilities\Role;

use FAWpmcp\Abilities\AbstractAbility;
use FAWpmcp\Abilities\Role\AddCapAbility;
use FAWpmcp\Exceptions\RoleNotFoundException;
use FAWpmcp\Tests\TestCase\AbilityTestTrait;
use FAWpmcp\Tests\TestCase\BrainMonkeyTestCase;
use Brain\Monkey\Functions;
use Mockery;

/**
 * Test AddCapAbility functionality.
 *
 * @package FAWpmcp\Tests\Abilities\Role
 */
class AddCapAbilityTest extends BrainMonkeyTestCase
{
    use AbilityTestTrait;

    /**
     * Get the ability instance to test.
     *
     * @return AbstractAbility
     */
    protected function getAbilityInstance(): AbstractAbility
    {
        return new AddCapAbility();
    }

    /**
     * Get the expected metadata for this ability.
     *
     * @return array<string, mixed>
     */
    protected function getExpectedMetadata(): array
    {
        return array(
            'name'                 => 'fa-wpmcp/add-cap',
            'category'             => 'role',
            'label'                => 'Add Capability',
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
        $this->assertArrayHasKey('added', $schema['properties']);
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
     * Test execute adds single capability.
     *
     * @return void
     */
    public function testExecuteAddsSingleCapability(): void
    {
        $ability = $this->getAbilityInstance();

        $mock_role = Mockery::mock('WP_Role');
        $mock_role->name = 'editor';
        $mock_role->capabilities = array(
            'edit_posts' => true,
            'manage_options' => true,
        );

        $mock_role->shouldReceive('add_cap')
            ->once()
            ->with('manage_options', true);

        Functions\when('get_role')->justReturn($mock_role);

        $result = $ability->doExecute(array(
            'role'         => 'editor',
            'capabilities' => array( 'manage_options' ),
        ));

        $this->assertIsArray($result);
        $this->assertArrayHasKey('role', $result);
        $this->assertArrayHasKey('added', $result);
        $this->assertArrayHasKey('total_capabilities', $result);
        $this->assertEquals('editor', $result['role']);
        $this->assertContains('manage_options', $result['added']);
        $this->assertEquals(2, $result['total_capabilities']);
    }

    /**
     * Test execute adds multiple capabilities.
     *
     * @return void
     */
    public function testExecuteAddsMultipleCapabilities(): void
    {
        $ability = $this->getAbilityInstance();

        $mock_role = Mockery::mock('WP_Role');
        $mock_role->name = 'contributor';
        $mock_role->capabilities = array(
            'read'       => true,
            'edit_posts' => true,
            'delete_posts' => true,
        );

        $mock_role->shouldReceive('add_cap')
            ->once()
            ->with('edit_posts', true);
        $mock_role->shouldReceive('add_cap')
            ->once()
            ->with('delete_posts', true);

        Functions\when('get_role')->justReturn($mock_role);

        $result = $ability->doExecute(array(
            'role'         => 'contributor',
            'capabilities' => array( 'edit_posts', 'delete_posts' ),
        ));

        $this->assertCount(2, $result['added']);
        $this->assertContains('edit_posts', $result['added']);
        $this->assertContains('delete_posts', $result['added']);
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
        $ability     = new AddCapAbility();
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

        $this->assertEmpty($result['added']);
        $this->assertEquals(2, $result['total_capabilities']);
    }
}
