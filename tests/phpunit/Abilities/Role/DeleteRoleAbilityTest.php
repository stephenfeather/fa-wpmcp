<?php

/**
 * Tests for DeleteRoleAbility.
 *
 * @package FAWpmcp\Tests\Abilities\Role
 */

declare(strict_types=1);

namespace FAWpmcp\Tests\Abilities\Role;

use FAWpmcp\Abilities\Role\DeleteRoleAbility;
use FAWpmcp\Exceptions\RoleNotFoundException;
use FAWpmcp\Exceptions\RoleDeletionException;
use Brain\Monkey;
use Brain\Monkey\Functions;
use Mockery;
use PHPUnit\Framework\TestCase;

/**
 * Test DeleteRoleAbility functionality.
 *
 * @package FAWpmcp\Tests\Abilities\Role
 */
class DeleteRoleAbilityTest extends TestCase
{
    /**
     * Set up Brain\Monkey before each test.
     *
     * @return void
     */
    protected function setUp(): void
    {
        parent::setUp();
        Monkey\setUp();
    }

    /**
     * Tear down Brain\Monkey after each test.
     *
     * @return void
     */
    protected function tearDown(): void
    {
        Monkey\tearDown();
        Mockery::close();
        parent::tearDown();
    }

    /**
     * Test ability returns correct name.
     *
     * @return void
     */
    public function testGetName(): void
    {
        $ability = new DeleteRoleAbility();
        $this->assertEquals('fa-wpmcp/delete-role', $ability->getName());
    }

    /**
     * Test ability returns correct category.
     *
     * @return void
     */
    public function testGetCategory(): void
    {
        $ability = new DeleteRoleAbility();
        $this->assertEquals('role', $ability->getCategory());
    }

    /**
     * Test ability returns correct label.
     *
     * @return void
     */
    public function testGetLabel(): void
    {
        $ability = new DeleteRoleAbility();
        $this->assertEquals('Delete Role', $ability->getLabel());
    }

    /**
     * Test ability returns correct operation type.
     *
     * @return void
     */
    public function testGetOperationType(): void
    {
        $ability = new DeleteRoleAbility();
        $this->assertEquals('write', $ability->getOperationType());
    }

    /**
     * Test ability returns correct required capability.
     *
     * @return void
     */
    public function testGetRequiredCapability(): void
    {
        $ability = new DeleteRoleAbility();
        $this->assertEquals('delete_users', $ability->getRequiredCapability());
    }

    /**
     * Test ability returns input schema with required role field.
     *
     * @return void
     */
    public function testGetInputSchema(): void
    {
        $ability = new DeleteRoleAbility();
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
        $ability = new DeleteRoleAbility();
        $schema  = $ability->getOutputSchema();

        $this->assertIsArray($schema);
        $this->assertArrayHasKey('type', $schema);
        $this->assertArrayHasKey('properties', $schema);
        $this->assertArrayHasKey('deleted', $schema['properties']);
        $this->assertArrayHasKey('role', $schema['properties']);
    }

    /**
     * Test execute deletes custom role successfully.
     *
     * @return void
     */
    public function testExecuteDeletesCustomRoleSuccessfully(): void
    {
        $ability = new DeleteRoleAbility();

        $mock_role = Mockery::mock('WP_Role');
        $mock_role->name = 'custom_role';

        Functions\when('get_role')->justReturn($mock_role);

        $mock_roles = Mockery::mock('WP_Roles');
        $mock_roles->shouldReceive('remove_role')
            ->once()
            ->with('custom_role');

        Functions\when('wp_roles')->justReturn($mock_roles);

        $result = $ability->doExecute(array( 'role' => 'custom_role' ));

        $this->assertIsArray($result);
        $this->assertTrue($result['deleted']);
        $this->assertEquals('custom_role', $result['role']);
    }

    /**
     * Test execute throws exception when role not found.
     *
     * @return void
     */
    public function testExecuteThrowsExceptionWhenRoleNotFound(): void
    {
        $ability = new DeleteRoleAbility();

        Functions\when('get_role')->justReturn(null);

        $this->expectException(RoleNotFoundException::class);
        $this->expectExceptionMessage('Role "nonexistent" not found.');

        $ability->doExecute(array( 'role' => 'nonexistent' ));
    }

    /**
     * Test execute throws exception when trying to delete administrator role.
     *
     * @return void
     */
    public function testExecuteThrowsExceptionWhenDeletingAdministratorRole(): void
    {
        $ability = new DeleteRoleAbility();

        $mock_role = Mockery::mock('WP_Role');
        $mock_role->name = 'administrator';

        Functions\when('get_role')->justReturn($mock_role);

        $this->expectException(RoleDeletionException::class);
        $this->expectExceptionMessage('Cannot delete default WordPress role "administrator".');

        $ability->doExecute(array( 'role' => 'administrator' ));
    }

    /**
     * Test execute throws exception when trying to delete editor role.
     *
     * @return void
     */
    public function testExecuteThrowsExceptionWhenDeletingEditorRole(): void
    {
        $ability = new DeleteRoleAbility();

        $mock_role = Mockery::mock('WP_Role');
        $mock_role->name = 'editor';

        Functions\when('get_role')->justReturn($mock_role);

        $this->expectException(RoleDeletionException::class);
        $this->expectExceptionMessage('Cannot delete default WordPress role "editor".');

        $ability->doExecute(array( 'role' => 'editor' ));
    }

    /**
     * Test execute throws exception when trying to delete author role.
     *
     * @return void
     */
    public function testExecuteThrowsExceptionWhenDeletingAuthorRole(): void
    {
        $ability = new DeleteRoleAbility();

        $mock_role = Mockery::mock('WP_Role');
        $mock_role->name = 'author';

        Functions\when('get_role')->justReturn($mock_role);

        $this->expectException(RoleDeletionException::class);
        $this->expectExceptionMessage('Cannot delete default WordPress role "author".');

        $ability->doExecute(array( 'role' => 'author' ));
    }

    /**
     * Test execute throws exception when trying to delete contributor role.
     *
     * @return void
     */
    public function testExecuteThrowsExceptionWhenDeletingContributorRole(): void
    {
        $ability = new DeleteRoleAbility();

        $mock_role = Mockery::mock('WP_Role');
        $mock_role->name = 'contributor';

        Functions\when('get_role')->justReturn($mock_role);

        $this->expectException(RoleDeletionException::class);
        $this->expectExceptionMessage('Cannot delete default WordPress role "contributor".');

        $ability->doExecute(array( 'role' => 'contributor' ));
    }

    /**
     * Test execute throws exception when trying to delete subscriber role.
     *
     * @return void
     */
    public function testExecuteThrowsExceptionWhenDeletingSubscriberRole(): void
    {
        $ability = new DeleteRoleAbility();

        $mock_role = Mockery::mock('WP_Role');
        $mock_role->name = 'subscriber';

        Functions\when('get_role')->justReturn($mock_role);

        $this->expectException(RoleDeletionException::class);
        $this->expectExceptionMessage('Cannot delete default WordPress role "subscriber".');

        $ability->doExecute(array( 'role' => 'subscriber' ));
    }

    /**
     * Test annotations indicate destructive write operation.
     *
     * @return void
     */
    public function testGetAnnotations(): void
    {
        $ability     = new DeleteRoleAbility();
        $annotations = $ability->getAnnotations();

        $this->assertFalse($annotations['readonly']);
        $this->assertTrue($annotations['destructive']);
        $this->assertFalse($annotations['idempotent']);
    }
}
