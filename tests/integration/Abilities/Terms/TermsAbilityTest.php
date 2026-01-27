<?php

/**
 * Integration tests for Terms (Taxonomies) abilities.
 *
 * Tests CRUD operations for taxonomy terms via MCP protocol.
 *
 * @package FAWpmcp\Tests\Integration\Abilities\Terms
 */

declare(strict_types=1);

namespace FAWpmcp\Tests\Integration\Abilities\Terms;

use FAWpmcp\Tests\Integration\Support\McpIntegrationTestCase;

/**
 * Test Terms ability operations via MCP.
 *
 * @group terms
 * @group taxonomies
 * @group abilities
 */
class TermsAbilityTest extends McpIntegrationTestCase
{
    /**
     * Test creating a category term with required fields.
     *
     * Response: {term_id, name, slug, taxonomy, link, created}
     */
    public function testCreateCategoryTerm(): void
    {
        $term = $this->createTestTerm('category', [
            'name' => 'Test Category ' . uniqid(),
        ]);

        $this->assertArrayHasKey('id', $term, 'Created term should have an ID (normalized from term_id)');
        $this->assertIsInt($term['id'], 'Term ID should be an integer');
        $this->assertGreaterThan(0, $term['id'], 'Term ID should be positive');
        $this->assertEquals('category', $term['taxonomy'], 'Taxonomy should be category');
        $this->assertTrue($term['created'], 'Created flag should be true');
    }

    /**
     * Test creating a tag term.
     */
    public function testCreateTagTerm(): void
    {
        $term = $this->createTestTerm('post_tag', [
            'name' => 'Test Tag ' . uniqid(),
        ]);

        $this->assertArrayHasKey('id', $term);
        $this->assertEquals('post_tag', $term['taxonomy']);
    }

    /**
     * Test creating a term with optional fields.
     */
    public function testCreateTermWithOptionalFields(): void
    {
        $unique = uniqid();
        $term   = $this->createTestTerm('category', [
            'name'        => 'Full Category ' . $unique,
            'slug'        => 'full-category-' . $unique,
            'description' => 'A test category with all fields.',
        ]);

        $this->assertArrayHasKey('id', $term);
        $this->assertEquals('full-category-' . $unique, $term['slug']);

        // Verify via get-term.
        $fetched = $this->getTerm($term['id'], 'category');
        $this->assertEquals('A test category with all fields.', $fetched['description']);
    }

    /**
     * Test creating a hierarchical term with parent.
     */
    public function testCreateTermWithParent(): void
    {
        // Create parent category.
        $parent = $this->createTestTerm('category', [
            'name' => 'Parent Category ' . uniqid(),
        ]);

        // Create child category.
        $child = $this->createTestTerm('category', [
            'name'   => 'Child Category ' . uniqid(),
            'parent' => $parent['id'],
        ]);

        $this->assertArrayHasKey('id', $child);

        // Verify parent relationship via get-term.
        $fetched = $this->getTerm($child['id'], 'category');
        $this->assertEquals($parent['id'], $fetched['parent'], 'Child should have correct parent ID');
    }

    /**
     * Test listing terms returns array with pagination.
     */
    public function testListTerms(): void
    {
        // Create a test term first.
        $this->createTestTerm('category', ['name' => 'List Test Category ' . uniqid()]);

        $result = $this->callTool('fa-wpmcp-list-terms', ['taxonomy' => 'category']);

        $this->assertArrayHasKey('terms', $result, 'Result should have terms key');
        $this->assertIsArray($result['terms'], 'Terms should be an array');
        $this->assertArrayHasKey('total', $result, 'Result should have total count');
        $this->assertArrayHasKey('pages', $result, 'Result should have pages count');
        $this->assertArrayHasKey('current_page', $result, 'Result should have current_page');
    }

    /**
     * Test listing terms filtered by parent (root terms only).
     */
    public function testListTermsFilteredByParent(): void
    {
        // Create parent and child categories.
        $parent = $this->createTestTerm('category', ['name' => 'Root Category ' . uniqid()]);
        $this->createTestTerm('category', [
            'name'   => 'Nested Category ' . uniqid(),
            'parent' => $parent['id'],
        ]);

        // List only root-level terms (parent = 0).
        $filtered = $this->callTool('fa-wpmcp-list-terms', [
            'taxonomy' => 'category',
            'parent'   => 0,
        ]);

        $this->assertArrayHasKey('terms', $filtered);

        // All returned terms should have parent = 0.
        foreach ($filtered['terms'] as $term) {
            $this->assertEquals(0, $term['parent'], 'Filtered terms should all be root-level');
        }
    }

    /**
     * Test listing terms with search filter.
     */
    public function testListTermsWithSearch(): void
    {
        $uniqueSearchTerm = 'searchable_term_' . uniqid();
        $this->createTestTerm('category', ['name' => $uniqueSearchTerm]);

        $result = $this->callTool('fa-wpmcp-list-terms', [
            'taxonomy' => 'category',
            'search'   => $uniqueSearchTerm,
        ]);

        $this->assertArrayHasKey('terms', $result);
        $this->assertGreaterThanOrEqual(1, count($result['terms']), 'Should find at least one term');

        // The searched term should be in results.
        $found = false;
        foreach ($result['terms'] as $term) {
            if (strpos($term['name'], $uniqueSearchTerm) !== false) {
                $found = true;
                break;
            }
        }
        $this->assertTrue($found, 'Search should find the created term');
    }

    /**
     * Test listing tags taxonomy.
     */
    public function testListTagTerms(): void
    {
        $this->createTestTerm('post_tag', ['name' => 'List Test Tag ' . uniqid()]);

        $result = $this->callTool('fa-wpmcp-list-terms', ['taxonomy' => 'post_tag']);

        $this->assertArrayHasKey('terms', $result);

        // All returned terms should be post_tag.
        foreach ($result['terms'] as $term) {
            $this->assertEquals('post_tag', $term['taxonomy'], 'All terms should be from post_tag taxonomy');
        }
    }

    /**
     * Test getting a specific term by ID.
     *
     * Response: {term: {term_id, name, slug, description, parent, count, taxonomy, link, meta}}
     */
    public function testGetTerm(): void
    {
        $unique  = uniqid();
        $created = $this->createTestTerm('category', [
            'name'        => 'Get Test Category ' . $unique,
            'description' => 'Description for get test.',
        ]);

        $fetched = $this->getTerm($created['id'], 'category');

        $this->assertArrayHasKey('term_id', $fetched, 'Fetched term should have term_id');
        $this->assertEquals($created['id'], $fetched['term_id'], 'IDs should match');
        $this->assertStringContainsString('Get Test Category', $fetched['name'], 'Name should match');
        $this->assertEquals('Description for get test.', $fetched['description']);
        $this->assertArrayHasKey('count', $fetched, 'Term should have count field');
        $this->assertArrayHasKey('link', $fetched, 'Term should have link field');
    }

    /**
     * Test getting a non-existent term returns error.
     */
    public function testGetNonExistentTerm(): void
    {
        $this->assertToolFails(
            'fa-wpmcp-get-term',
            ['term_id' => 999999999],
            'not found'
        );
    }

    /**
     * Test updating a term name.
     */
    public function testUpdateTermName(): void
    {
        $unique  = uniqid();
        $created = $this->createTestTerm('category', [
            'name' => 'Original Category ' . $unique,
        ]);

        $result = $this->callTool('fa-wpmcp-update-term', [
            'term_id'  => $created['id'],
            'taxonomy' => 'category',
            'name'     => 'Updated Category ' . $unique,
        ]);

        $this->assertTrue(
            isset($result['id']) || isset($result['term_id']),
            'Update should return term ID'
        );
        $this->assertTrue($result['updated'], 'Updated flag should be true');

        // Verify update persisted.
        $fetched = $this->getTerm($created['id'], 'category');
        $this->assertStringContainsString('Updated Category', $fetched['name']);
    }

    /**
     * Test updating term slug and description.
     */
    public function testUpdateTermSlugAndDescription(): void
    {
        $unique  = uniqid();
        $created = $this->createTestTerm('category', [
            'name' => 'Slug Test Category ' . $unique,
        ]);

        $newSlug = 'updated-slug-' . $unique;
        $this->callTool('fa-wpmcp-update-term', [
            'term_id'     => $created['id'],
            'taxonomy'    => 'category',
            'slug'        => $newSlug,
            'description' => 'Updated description.',
        ]);

        $fetched = $this->getTerm($created['id'], 'category');
        $this->assertEquals($newSlug, $fetched['slug']);
        $this->assertEquals('Updated description.', $fetched['description']);
    }

    /**
     * Test updating term parent (re-parenting).
     */
    public function testUpdateTermParent(): void
    {
        // Create two parent categories.
        $parent1 = $this->createTestTerm('category', ['name' => 'Parent 1 ' . uniqid()]);
        $parent2 = $this->createTestTerm('category', ['name' => 'Parent 2 ' . uniqid()]);

        // Create child under parent1.
        $child = $this->createTestTerm('category', [
            'name'   => 'Movable Child ' . uniqid(),
            'parent' => $parent1['id'],
        ]);

        // Move child to parent2.
        $this->callTool('fa-wpmcp-update-term', [
            'term_id'  => $child['id'],
            'taxonomy' => 'category',
            'parent'   => $parent2['id'],
        ]);

        $fetched = $this->getTerm($child['id'], 'category');
        $this->assertEquals($parent2['id'], $fetched['parent'], 'Parent should be updated to parent2');
    }

    /**
     * Test deleting a term (always permanent, no trash).
     *
     * Response: {term_id, taxonomy, action, success}
     */
    public function testDeleteTerm(): void
    {
        $created = $this->createTestTerm('category', [
            'name' => 'Delete Test Category ' . uniqid(),
        ]);

        $result = $this->callTool('fa-wpmcp-delete-term', [
            'term_id'  => $created['id'],
            'taxonomy' => 'category',
        ]);

        $this->assertArrayHasKey('success', $result, 'Delete result should have success flag');
        $this->assertTrue($result['success'], 'Delete should succeed');
        $this->assertEquals('deleted', $result['action'], 'Action should be deleted');

        // Remove from cleanup list since we manually deleted.
        $this->createdResources['terms'] = array_filter(
            $this->createdResources['terms'],
            fn($termData) => $termData['id'] !== $created['id']
        );

        // Verify term no longer exists.
        $this->assertToolFails(
            'fa-wpmcp-get-term',
            ['term_id' => $created['id'], 'taxonomy' => 'category'],
            'not found'
        );
    }

    /**
     * Test deleting a tag term.
     */
    public function testDeleteTagTerm(): void
    {
        $created = $this->createTestTerm('post_tag', [
            'name' => 'Delete Test Tag ' . uniqid(),
        ]);

        $result = $this->callTool('fa-wpmcp-delete-term', [
            'term_id'  => $created['id'],
            'taxonomy' => 'post_tag',
        ]);

        $this->assertTrue($result['success']);
        $this->assertEquals('post_tag', $result['taxonomy']);

        // Remove from cleanup list.
        $this->createdResources['terms'] = array_filter(
            $this->createdResources['terms'],
            fn($termData) => $termData['id'] !== $created['id']
        );
    }

    /**
     * Test full CRUD lifecycle for category.
     */
    public function testTermCrudLifecycle(): void
    {
        $unique = uniqid();

        // Create.
        $term = $this->createTestTerm('category', [
            'name'        => 'Lifecycle Category ' . $unique,
            'description' => 'Initial description.',
        ]);
        $this->assertArrayHasKey('id', $term);
        $termId = $term['id'];

        // Read.
        $read = $this->getTerm($termId, 'category');
        $this->assertEquals($termId, $read['term_id']);
        $this->assertStringContainsString('Lifecycle Category', $read['name']);
        $this->assertEquals('Initial description.', $read['description']);

        // Update.
        $this->callTool('fa-wpmcp-update-term', [
            'term_id'     => $termId,
            'taxonomy'    => 'category',
            'name'        => 'Updated Lifecycle Category ' . $unique,
            'description' => 'Updated description.',
        ]);

        $updated = $this->getTerm($termId, 'category');
        $this->assertStringContainsString('Updated Lifecycle Category', $updated['name']);
        $this->assertEquals('Updated description.', $updated['description']);

        // Delete.
        $deleted = $this->callTool('fa-wpmcp-delete-term', [
            'term_id'  => $termId,
            'taxonomy' => 'category',
        ]);
        $this->assertTrue($deleted['success']);

        // Remove from cleanup.
        $this->createdResources['terms'] = array_filter(
            $this->createdResources['terms'],
            fn($termData) => $termData['id'] !== $termId
        );

        // Verify deleted.
        $this->assertToolFails(
            'fa-wpmcp-get-term',
            ['term_id' => $termId, 'taxonomy' => 'category'],
            'not found'
        );
    }

    /**
     * Create a test term and track it for cleanup.
     *
     * @param string $taxonomy Taxonomy name.
     * @param array  $overrides Override default term values.
     * @return array Created term data.
     */
    private function createTestTerm(string $taxonomy, array $overrides = []): array
    {
        $defaults = [
            'taxonomy' => $taxonomy,
            'name'     => 'Integration Test Term ' . uniqid(),
        ];

        $args = array_merge($defaults, $overrides);
        $term = $this->callTool('fa-wpmcp-create-term', $args);

        if (isset($term['id'])) {
            // Store both id and taxonomy for cleanup.
            $this->createdResources['terms'][] = [
                'id'       => $term['id'],
                'taxonomy' => $taxonomy,
            ];
        }

        return $term;
    }

    /**
     * Helper to get a term and extract from nested response.
     *
     * @param int    $termId   Term ID.
     * @param string $taxonomy Taxonomy name.
     * @return array Term data.
     */
    private function getTerm(int $termId, string $taxonomy): array
    {
        $response = $this->callTool('fa-wpmcp-get-term', [
            'term_id'  => $termId,
            'taxonomy' => $taxonomy,
        ]);

        // get-term returns {term: {...}}
        if (isset($response['term'])) {
            return $response['term'];
        }

        return $response;
    }
}
