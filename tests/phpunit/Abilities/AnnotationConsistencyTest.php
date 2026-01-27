<?php

/**
 * Tests for annotation consistency across abilities.
 *
 * Ensures that:
 * - All Create abilities have idempotent: false (repeated calls create new resources)
 * - Update abilities that can trash content have destructive: true
 *
 * @package FAWpmcp\Tests\Abilities
 */

declare(strict_types=1);

namespace FAWpmcp\Tests\Abilities;

use FAWpmcp\Abilities\Comments\CreateComment;
use FAWpmcp\Abilities\Comments\UpdateComment;
use FAWpmcp\Abilities\Posts\CreatePost;
use FAWpmcp\Abilities\Posts\UpdatePost;
use FAWpmcp\Abilities\Privacy\CreateErasureRequest;
use FAWpmcp\Abilities\Privacy\CreateExportRequest;
use FAWpmcp\Abilities\Taxonomies\CreateTerm;
use FAWpmcp\Abilities\Users\CreateUser;
use FAWpmcp\Abilities\Users\RolePolicy;
use FAWpmcp\Tests\TestCase\BrainMonkeyTestCase;
use Brain\Monkey\Functions;

/**
 * Test annotation consistency for abilities.
 *
 * This test guards against regressions where ability annotations
 * may be incorrectly set, particularly for:
 * - Create operations (must be non-idempotent)
 * - Update operations with trash capability (must be destructive)
 *
 * @package FAWpmcp\Tests\Abilities
 */
final class AnnotationConsistencyTest extends BrainMonkeyTestCase
{
    /**
     * Set up before each test.
     *
     * @return void
     */
    protected function setUp(): void
    {
        parent::setUp();

        // Mock get_option for RolePolicy used by CreateUser.
        Functions\when('get_option')->justReturn(RolePolicy::DEFAULT_MAX_ROLE);
    }

    /**
     * Test that Create abilities are non-idempotent.
     *
     * Create operations should have idempotent=false because repeated
     * calls with the same parameters create new resources each time.
     *
     * @dataProvider createAbilityProvider
     *
     * @param string $className The ability class to test.
     * @return void
     */
    public function test_create_abilities_are_non_idempotent(string $className): void
    {
        $ability     = new $className();
        $annotations = $ability->getAnnotations();

        $this->assertFalse(
            $annotations['idempotent'],
            sprintf('%s should have idempotent=false', $className)
        );
    }

    /**
     * Test that Create abilities are not readonly.
     *
     * Create operations modify state, so readonly must be false.
     *
     * @dataProvider createAbilityProvider
     *
     * @param string $className The ability class to test.
     * @return void
     */
    public function test_create_abilities_are_not_readonly(string $className): void
    {
        $ability     = new $className();
        $annotations = $ability->getAnnotations();

        $this->assertFalse(
            $annotations['readonly'],
            sprintf('%s should have readonly=false', $className)
        );
    }

    /**
     * Data provider for Create abilities.
     *
     * @return array<array<string>> Array of ability class names.
     */
    public static function createAbilityProvider(): array
    {
        return array(
            'CreateComment'        => array( CreateComment::class ),
            'CreatePost'           => array( CreatePost::class ),
            'CreateTerm'           => array( CreateTerm::class ),
            'CreateUser'           => array( CreateUser::class ),
            'CreateErasureRequest' => array( CreateErasureRequest::class ),
            'CreateExportRequest'  => array( CreateExportRequest::class ),
        );
    }

    /**
     * Test that Update abilities with trash capability are destructive.
     *
     * Update operations that can move content to trash should have
     * destructive=true to warn AI agents about potential data loss.
     *
     * @dataProvider destructiveUpdateAbilityProvider
     *
     * @param string $className The ability class to test.
     * @return void
     */
    public function test_update_abilities_with_trash_are_destructive(string $className): void
    {
        $ability     = new $className();
        $annotations = $ability->getAnnotations();

        $this->assertTrue(
            $annotations['destructive'],
            sprintf('%s should have destructive=true', $className)
        );
    }

    /**
     * Data provider for destructive Update abilities.
     *
     * These are Update abilities that can set status to 'trash',
     * effectively deleting content.
     *
     * @return array<array<string>> Array of ability class names.
     */
    public static function destructiveUpdateAbilityProvider(): array
    {
        return array(
            'UpdateComment' => array( UpdateComment::class ),
            'UpdatePost'    => array( UpdatePost::class ),
        );
    }
}
