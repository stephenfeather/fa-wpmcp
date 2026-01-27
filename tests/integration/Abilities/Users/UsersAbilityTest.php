<?php

/**
 * Integration tests for Users abilities.
 *
 * Tests CRUD operations for users via MCP protocol.
 *
 * @package FAWpmcp\Tests\Integration\Abilities\Users
 */

declare(strict_types=1);

namespace FAWpmcp\Tests\Integration\Abilities\Users;

use FAWpmcp\Tests\Integration\Support\McpIntegrationTestCase;

/**
 * Test Users ability operations via MCP.
 *
 * @group users
 * @group abilities
 */
class UsersAbilityTest extends McpIntegrationTestCase
{
    /**
     * Test creating a user with required fields.
     *
     * Response: {user_id, username, email, role, edit_url}
     */
    public function testCreateUser(): void
    {
        $user = $this->createTestUser([
            'username' => 'testuser_' . uniqid(),
            'email'    => 'testuser_' . uniqid() . '@example.com',
        ]);

        $this->assertArrayHasKey('id', $user, 'Created user should have an ID (normalized from user_id)');
        $this->assertIsInt($user['id'], 'User ID should be an integer');
        $this->assertGreaterThan(0, $user['id'], 'User ID should be positive');
        $this->assertArrayHasKey('role', $user, 'Response should include user role');
        $this->assertEquals('subscriber', $user['role'], 'Default role should be subscriber');
    }

    /**
     * Test creating a user with optional profile fields.
     */
    public function testCreateUserWithProfile(): void
    {
        $unique = uniqid();
        $user   = $this->createTestUser([
            'username'     => 'profileuser_' . $unique,
            'email'        => 'profileuser_' . $unique . '@example.com',
            'first_name'   => 'Test',
            'last_name'    => 'User',
            'display_name' => 'Test Display Name',
            'website'      => 'https://example.com',
            'description'  => 'A test user biography.',
        ]);

        $this->assertArrayHasKey('id', $user);

        // Verify profile data via get-user.
        $fetched = $this->getUser($user['id']);
        $this->assertEquals('Test', $fetched['first_name']);
        $this->assertEquals('User', $fetched['last_name']);
        $this->assertEquals('Test Display Name', $fetched['display_name']);
        $this->assertEquals('https://example.com', $fetched['website']);
        $this->assertEquals('A test user biography.', $fetched['description']);
    }

    /**
     * Test creating a user with a specific role.
     */
    public function testCreateUserWithRole(): void
    {
        $unique = uniqid();
        $user   = $this->createTestUser([
            'username' => 'editoruser_' . $unique,
            'email'    => 'editoruser_' . $unique . '@example.com',
            'role'     => 'editor',
        ]);

        $this->assertArrayHasKey('id', $user);
        $this->assertEquals('editor', $user['role']);

        // Verify role via get-user.
        $fetched = $this->getUser($user['id']);
        $this->assertContains('editor', $fetched['roles']);
    }

    /**
     * Test listing users returns array with pagination.
     */
    public function testListUsers(): void
    {
        // Create a test user first.
        $this->createTestUser();

        $result = $this->callTool('fa-wpmcp-list-users');

        $this->assertArrayHasKey('users', $result, 'Result should have users key');
        $this->assertIsArray($result['users'], 'Users should be an array');
        $this->assertArrayHasKey('total', $result, 'Result should have total count');
        $this->assertArrayHasKey('pages', $result, 'Result should have pages count');
        $this->assertArrayHasKey('current_page', $result, 'Result should have current_page');
    }

    /**
     * Test listing users filtered by role.
     */
    public function testListUsersFilteredByRole(): void
    {
        // Create users with different roles.
        $unique1 = uniqid();
        $unique2 = uniqid();

        $this->createTestUser([
            'username' => 'author_' . $unique1,
            'email'    => 'author_' . $unique1 . '@example.com',
            'role'     => 'author',
        ]);
        $this->createTestUser([
            'username' => 'contributor_' . $unique2,
            'email'    => 'contributor_' . $unique2 . '@example.com',
            'role'     => 'contributor',
        ]);

        $filtered = $this->callTool('fa-wpmcp-list-users', ['role' => 'author']);

        $this->assertArrayHasKey('users', $filtered);

        // All returned users should have the author role.
        foreach ($filtered['users'] as $user) {
            $this->assertContains(
                'author',
                $user['roles'],
                'Filtered users should all have author role'
            );
        }
    }

    /**
     * Test listing users with search filter.
     */
    public function testListUsersWithSearch(): void
    {
        $uniqueSearchTerm = 'searchable_' . uniqid();
        $this->createTestUser([
            'username' => $uniqueSearchTerm,
            'email'    => $uniqueSearchTerm . '@example.com',
        ]);

        $result = $this->callTool('fa-wpmcp-list-users', ['search' => $uniqueSearchTerm]);

        $this->assertArrayHasKey('users', $result);
        $this->assertGreaterThanOrEqual(1, count($result['users']), 'Should find at least one user');

        // The searched user should be in results.
        $found = false;
        foreach ($result['users'] as $user) {
            if (strpos($user['username'], $uniqueSearchTerm) !== false) {
                $found = true;
                break;
            }
        }
        $this->assertTrue($found, 'Search should find the created user');
    }

    /**
     * Test getting a specific user by ID.
     *
     * Response: {id, username, email, display_name, first_name, last_name, nickname, description, roles, registered, avatar_url, website}
     */
    public function testGetUserById(): void
    {
        $unique  = uniqid();
        $created = $this->createTestUser([
            'username' => 'gettest_' . $unique,
            'email'    => 'gettest_' . $unique . '@example.com',
        ]);

        $fetched = $this->getUser($created['id']);

        $this->assertArrayHasKey('id', $fetched, 'Fetched user should have ID');
        $this->assertEquals($created['id'], $fetched['id'], 'IDs should match');
        $this->assertStringContainsString('gettest_', $fetched['username'], 'Username should match');
        $this->assertArrayHasKey('roles', $fetched, 'User should have roles array');
        $this->assertArrayHasKey('avatar_url', $fetched, 'User should have avatar_url');
    }

    /**
     * Test getting a user by username.
     */
    public function testGetUserByUsername(): void
    {
        $unique   = uniqid();
        $username = 'byusername_' . $unique;
        $created  = $this->createTestUser([
            'username' => $username,
            'email'    => 'byusername_' . $unique . '@example.com',
        ]);

        $response = $this->callTool('fa-wpmcp-get-user', ['username' => $username]);

        $this->assertArrayHasKey('id', $response);
        $this->assertEquals($created['id'], $response['id']);
        $this->assertEquals($username, $response['username']);
    }

    /**
     * Test getting a user by email.
     */
    public function testGetUserByEmail(): void
    {
        $unique  = uniqid();
        $email   = 'byemail_' . $unique . '@example.com';
        $created = $this->createTestUser([
            'username' => 'byemail_' . $unique,
            'email'    => $email,
        ]);

        $response = $this->callTool('fa-wpmcp-get-user', ['email' => $email]);

        $this->assertArrayHasKey('id', $response);
        $this->assertEquals($created['id'], $response['id']);
        $this->assertEquals($email, $response['email']);
    }

    /**
     * Test getting a non-existent user returns error.
     */
    public function testGetNonExistentUser(): void
    {
        $this->assertToolFails(
            'fa-wpmcp-get-user',
            ['user_id' => 999999999],
            'not found'
        );
    }

    /**
     * Test updating user profile fields.
     */
    public function testUpdateUserProfile(): void
    {
        $unique  = uniqid();
        $created = $this->createTestUser([
            'username' => 'updateprofile_' . $unique,
            'email'    => 'updateprofile_' . $unique . '@example.com',
        ]);

        $result = $this->callTool('fa-wpmcp-update-user', [
            'user_id'      => $created['id'],
            'first_name'   => 'Updated',
            'last_name'    => 'Name',
            'display_name' => 'Updated Display',
        ]);

        $this->assertTrue(
            isset($result['id']) || isset($result['user_id']),
            'Update should return user ID'
        );
        $this->assertArrayHasKey('updated_fields', $result, 'Update should return list of updated fields');

        // Verify update persisted.
        $fetched = $this->getUser($created['id']);
        $this->assertEquals('Updated', $fetched['first_name']);
        $this->assertEquals('Name', $fetched['last_name']);
        $this->assertEquals('Updated Display', $fetched['display_name']);
    }

    /**
     * Test updating user email.
     */
    public function testUpdateUserEmail(): void
    {
        $unique   = uniqid();
        $created  = $this->createTestUser([
            'username' => 'updateemail_' . $unique,
            'email'    => 'updateemail_' . $unique . '@example.com',
        ]);
        $newEmail = 'newemail_' . $unique . '@example.com';

        $this->callTool('fa-wpmcp-update-user', [
            'user_id' => $created['id'],
            'email'   => $newEmail,
        ]);

        $fetched = $this->getUser($created['id']);
        $this->assertEquals($newEmail, $fetched['email']);
    }

    /**
     * Test updating user role.
     */
    public function testUpdateUserRole(): void
    {
        $unique  = uniqid();
        $created = $this->createTestUser([
            'username' => 'updaterole_' . $unique,
            'email'    => 'updaterole_' . $unique . '@example.com',
            'role'     => 'subscriber',
        ]);

        $this->callTool('fa-wpmcp-update-user', [
            'user_id' => $created['id'],
            'role'    => 'author',
        ]);

        $fetched = $this->getUser($created['id']);
        $this->assertContains('author', $fetched['roles'], 'User role should be updated to author');
    }

    /**
     * Test deleting a user (always permanent).
     *
     * Response: {user_id, reassigned, action, success}
     */
    public function testDeleteUser(): void
    {
        $unique  = uniqid();
        $created = $this->createTestUser([
            'username' => 'deletetest_' . $unique,
            'email'    => 'deletetest_' . $unique . '@example.com',
        ]);

        $result = $this->callTool('fa-wpmcp-delete-user', ['user_id' => $created['id']]);

        $this->assertArrayHasKey('success', $result, 'Delete result should have success flag');
        $this->assertTrue($result['success'], 'Delete should succeed');
        $this->assertEquals('deleted', $result['action'], 'Action should be deleted');

        // Remove from cleanup list since we manually deleted.
        $this->createdResources['users'] = array_filter(
            $this->createdResources['users'],
            fn($id) => $id !== $created['id']
        );

        // Verify user no longer exists.
        $this->assertToolFails(
            'fa-wpmcp-get-user',
            ['user_id' => $created['id']],
            'not found'
        );
    }

    /**
     * Test full CRUD lifecycle.
     */
    public function testUserCrudLifecycle(): void
    {
        $unique = uniqid();

        // Create.
        $user = $this->createTestUser([
            'username'   => 'lifecycle_' . $unique,
            'email'      => 'lifecycle_' . $unique . '@example.com',
            'first_name' => 'Lifecycle',
            'last_name'  => 'Test',
            'role'       => 'subscriber',
        ]);
        $this->assertArrayHasKey('id', $user);
        $userId = $user['id'];

        // Read.
        $read = $this->getUser($userId);
        $this->assertEquals($userId, $read['id']);
        $this->assertEquals('Lifecycle', $read['first_name']);
        $this->assertContains('subscriber', $read['roles']);

        // Update.
        $this->callTool('fa-wpmcp-update-user', [
            'user_id'    => $userId,
            'first_name' => 'Updated Lifecycle',
            'role'       => 'author',
        ]);

        $updated = $this->getUser($userId);
        $this->assertEquals('Updated Lifecycle', $updated['first_name']);
        $this->assertContains('author', $updated['roles']);

        // Delete.
        $deleted = $this->callTool('fa-wpmcp-delete-user', ['user_id' => $userId]);
        $this->assertTrue($deleted['success']);

        // Remove from cleanup.
        $this->createdResources['users'] = array_filter(
            $this->createdResources['users'],
            fn($id) => $id !== $userId
        );

        // Verify deleted.
        $this->assertToolFails(
            'fa-wpmcp-get-user',
            ['user_id' => $userId],
            'not found'
        );
    }

    /**
     * Helper to get a user by ID.
     *
     * @param int $userId User ID.
     * @return array User data.
     */
    private function getUser(int $userId): array
    {
        return $this->callTool('fa-wpmcp-get-user', ['user_id' => $userId]);
    }
}
