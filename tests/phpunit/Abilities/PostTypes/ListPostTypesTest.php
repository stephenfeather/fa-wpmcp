<?php

/**
 * Tests for ListPostTypes ability.
 *
 * @package FAWpmcp\Tests\Abilities\PostTypes
 */

declare(strict_types=1);

namespace FAWpmcp\Tests\Abilities\PostTypes;

use FAWpmcp\Abilities\AbstractAbility;
use FAWpmcp\Abilities\PostTypes\ListPostTypes;
use FAWpmcp\Tests\TestCase\AbilityTestTrait;
use FAWpmcp\Tests\TestCase\BrainMonkeyTestCase;
use Brain\Monkey\Functions;
use Mockery;

/**
 * Test ListPostTypes ability functionality.
 *
 * @package FAWpmcp\Tests\Abilities\PostTypes
 */
class ListPostTypesTest extends BrainMonkeyTestCase
{
    use AbilityTestTrait;

    /**
     * Get an instance of the ability being tested.
     *
     * @return AbstractAbility
     */
    protected function getAbilityInstance(): AbstractAbility
    {
        return new ListPostTypes();
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
            'name'                 => 'fa-wpmcp/list-post-types',
            'category'             => 'post-types',
            'label'                => 'List Post Types',
            'description_contains' => 'registered wordpress post type definitions',
            'operation_type'       => 'read',
            'required_capability'  => 'read',
        );
    }

    /**
     * Test execute returns post types list.
     *
     * @return void
     */
    public function testExecuteReturnsPostTypesList(): void
    {
        $ability = $this->getAbilityInstance();

        $mock_post_type               = Mockery::mock(\WP_Post_Type::class);
        $mock_post_type->name         = 'post';
        $mock_post_type->label        = 'Posts';
        $mock_post_type->description  = 'Default post type';
        $mock_post_type->public       = true;
        $mock_post_type->hierarchical = false;
        $mock_post_type->show_ui      = true;
        $mock_post_type->show_in_rest = true;
        $mock_post_type->rest_base    = 'posts';

        Functions\when('get_post_types')->justReturn(array( 'post' => $mock_post_type ));

        $result = $ability->doExecute(array());

        $this->assertIsArray($result);
        $this->assertArrayHasKey('post_types', $result);
        $this->assertArrayHasKey('total', $result);
        $this->assertCount(1, $result['post_types']);
        $this->assertEquals(1, $result['total']);
        $this->assertEquals('post', $result['post_types'][0]['name']);
    }

    /**
     * Test execute returns empty list when no post types match.
     *
     * @return void
     */
    public function testExecuteReturnsEmptyListWhenNoMatch(): void
    {
        $ability = $this->getAbilityInstance();

        Functions\when('get_post_types')->justReturn(array());

        $result = $ability->doExecute(array( 'public' => false ));

        $this->assertIsArray($result);
        $this->assertEmpty($result['post_types']);
        $this->assertEquals(0, $result['total']);
    }

    /**
     * Test execute filters by public visibility.
     *
     * @return void
     */
    public function testExecuteFiltersByPublic(): void
    {
        $ability = $this->getAbilityInstance();

        $mock_post_type               = Mockery::mock(\WP_Post_Type::class);
        $mock_post_type->name         = 'post';
        $mock_post_type->label        = 'Posts';
        $mock_post_type->description  = '';
        $mock_post_type->public       = true;
        $mock_post_type->hierarchical = false;
        $mock_post_type->show_ui      = true;
        $mock_post_type->show_in_rest = true;
        $mock_post_type->rest_base    = 'posts';

        Functions\expect('get_post_types')
            ->once()
            ->with(
                Mockery::on(
                    function ($args) {
                        return isset($args['public']) && $args['public'] === true;
                    }
                ),
                'objects'
            )
            ->andReturn(array( 'post' => $mock_post_type ));

        $result = $ability->doExecute(array( 'public' => true ));

        $this->assertCount(1, $result['post_types']);
    }

    /**
     * Test execute filters by hierarchical.
     *
     * @return void
     */
    public function testExecuteFiltersByHierarchical(): void
    {
        $ability = $this->getAbilityInstance();

        $mock_post_type               = Mockery::mock(\WP_Post_Type::class);
        $mock_post_type->name         = 'page';
        $mock_post_type->label        = 'Pages';
        $mock_post_type->description  = '';
        $mock_post_type->public       = true;
        $mock_post_type->hierarchical = true;
        $mock_post_type->show_ui      = true;
        $mock_post_type->show_in_rest = true;
        $mock_post_type->rest_base    = 'pages';

        Functions\expect('get_post_types')
            ->once()
            ->with(
                Mockery::on(
                    function ($args) {
                        return isset($args['hierarchical']) && $args['hierarchical'] === true;
                    }
                ),
                'objects'
            )
            ->andReturn(array( 'page' => $mock_post_type ));

        $result = $ability->doExecute(array( 'hierarchical' => true ));

        $this->assertCount(1, $result['post_types']);
    }

    /**
     * Test execute returns multiple post types.
     *
     * @return void
     */
    public function testExecuteReturnsMultiplePostTypes(): void
    {
        $ability = $this->getAbilityInstance();

        $mock_post               = Mockery::mock(\WP_Post_Type::class);
        $mock_post->name         = 'post';
        $mock_post->label        = 'Posts';
        $mock_post->description  = '';
        $mock_post->public       = true;
        $mock_post->hierarchical = false;
        $mock_post->show_ui      = true;
        $mock_post->show_in_rest = true;
        $mock_post->rest_base    = 'posts';

        $mock_page               = Mockery::mock(\WP_Post_Type::class);
        $mock_page->name         = 'page';
        $mock_page->label        = 'Pages';
        $mock_page->description  = '';
        $mock_page->public       = true;
        $mock_page->hierarchical = true;
        $mock_page->show_ui      = true;
        $mock_page->show_in_rest = true;
        $mock_page->rest_base    = 'pages';

        Functions\when('get_post_types')->justReturn(
            array(
                'post' => $mock_post,
                'page' => $mock_page,
            )
        );

        $result = $ability->doExecute(array());

        $this->assertCount(2, $result['post_types']);
        $this->assertEquals(2, $result['total']);
    }

    /**
     * Test annotations are correct for read-only ability.
     *
     * @return void
     */
    public function testGetAnnotations(): void
    {
        $ability     = $this->getAbilityInstance();
        $annotations = $ability->getAnnotations();

        $this->assertTrue($annotations['readonly']);
        $this->assertFalse($annotations['destructive']);
        $this->assertTrue($annotations['idempotent']);
    }
}
