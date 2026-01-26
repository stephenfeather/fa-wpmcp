<?php

/**
 * Tests for ListTaxonomies ability.
 *
 * @package FAWpmcp\Tests\Abilities\Taxonomies
 */

declare(strict_types=1);

namespace FAWpmcp\Tests\Abilities\Taxonomies;

use FAWpmcp\Abilities\AbstractAbility;
use FAWpmcp\Abilities\Taxonomies\ListTaxonomies;
use FAWpmcp\Tests\TestCase\AbilityTestTrait;
use FAWpmcp\Tests\TestCase\BrainMonkeyTestCase;
use Brain\Monkey\Functions;
use Mockery;

/**
 * Test ListTaxonomies ability functionality.
 *
 * @package FAWpmcp\Tests\Abilities\Taxonomies
 */
class ListTaxonomiesTest extends BrainMonkeyTestCase
{
    use AbilityTestTrait;

    /**
     * Get an instance of the ability being tested.
     *
     * @return AbstractAbility
     */
    protected function getAbilityInstance(): AbstractAbility
    {
        return new ListTaxonomies();
    }

    /**
     * Get expected metadata for the ability.
     *
     * @return array<string, string>
     */
    protected function getExpectedMetadata(): array
    {
        return array(
            'name'                  => 'fa-wpmcp/list-taxonomies',
            'category'              => 'taxonomies',
            'label'                 => 'List Taxonomies',
            'description_contains'  => 'retrieve',
            'operation_type'        => 'read',
            'required_capability'   => 'read',
        );
    }

    /**
     * Test execute returns taxonomies list.
     *
     * @return void
     */
    public function testExecuteReturnsTaxonomiesList(): void
    {
        $mock_taxonomy              = Mockery::mock(\WP_Taxonomy::class);
        $mock_taxonomy->name        = 'category';
        $mock_taxonomy->label       = 'Categories';
        $mock_taxonomy->description = 'Post categories';
        $mock_taxonomy->public      = true;
        $mock_taxonomy->hierarchical = true;
        $mock_taxonomy->show_ui     = true;
        $mock_taxonomy->show_in_rest = true;
        $mock_taxonomy->rest_base   = 'categories';
        $mock_taxonomy->object_type = array( 'post' );

        Functions\when('get_taxonomies')->justReturn(array( 'category' => $mock_taxonomy ));

        $result = $this->getAbilityInstance()->doExecute(array());

        $this->assertIsArray($result);
        $this->assertArrayHasKey('taxonomies', $result);
        $this->assertArrayHasKey('total', $result);
        $this->assertCount(1, $result['taxonomies']);
        $this->assertEquals(1, $result['total']);
        $this->assertEquals('category', $result['taxonomies'][0]['name']);
    }

    /**
     * Test execute returns empty list when no taxonomies match.
     *
     * @return void
     */
    public function testExecuteReturnsEmptyListWhenNoMatch(): void
    {
        Functions\when('get_taxonomies')->justReturn(array());

        $result = $this->getAbilityInstance()->doExecute(array( 'public' => false ));

        $this->assertIsArray($result);
        $this->assertEmpty($result['taxonomies']);
        $this->assertEquals(0, $result['total']);
    }

    /**
     * Test execute filters by object_type.
     *
     * @return void
     */
    public function testExecuteFiltersByObjectType(): void
    {
        $mock_taxonomy              = Mockery::mock(\WP_Taxonomy::class);
        $mock_taxonomy->name        = 'category';
        $mock_taxonomy->label       = 'Categories';
        $mock_taxonomy->description = '';
        $mock_taxonomy->public      = true;
        $mock_taxonomy->hierarchical = true;
        $mock_taxonomy->show_ui     = true;
        $mock_taxonomy->show_in_rest = true;
        $mock_taxonomy->rest_base   = 'categories';
        $mock_taxonomy->object_type = array( 'post' );

        Functions\expect('get_taxonomies')
            ->once()
            ->with(
                Mockery::on(
                    function ($args) {
                        return isset($args['object_type']) && $args['object_type'] === array( 'post' );
                    }
                ),
                'objects'
            )
            ->andReturn(array( 'category' => $mock_taxonomy ));

        $result = $this->getAbilityInstance()->doExecute(array( 'object_type' => 'post' ));

        $this->assertCount(1, $result['taxonomies']);
    }

    /**
     * Test execute filters by hierarchical.
     *
     * @return void
     */
    public function testExecuteFiltersByHierarchical(): void
    {
        $mock_taxonomy              = Mockery::mock(\WP_Taxonomy::class);
        $mock_taxonomy->name        = 'category';
        $mock_taxonomy->label       = 'Categories';
        $mock_taxonomy->description = '';
        $mock_taxonomy->public      = true;
        $mock_taxonomy->hierarchical = true;
        $mock_taxonomy->show_ui     = true;
        $mock_taxonomy->show_in_rest = true;
        $mock_taxonomy->rest_base   = 'categories';
        $mock_taxonomy->object_type = array( 'post' );

        Functions\expect('get_taxonomies')
            ->once()
            ->with(
                Mockery::on(
                    function ($args) {
                        return isset($args['hierarchical']) && $args['hierarchical'] === true;
                    }
                ),
                'objects'
            )
            ->andReturn(array( 'category' => $mock_taxonomy ));

        $result = $this->getAbilityInstance()->doExecute(array( 'hierarchical' => true ));

        $this->assertCount(1, $result['taxonomies']);
    }

    /**
     * Test execute returns multiple taxonomies.
     *
     * @return void
     */
    public function testExecuteReturnsMultipleTaxonomies(): void
    {
        $mock_category              = Mockery::mock(\WP_Taxonomy::class);
        $mock_category->name        = 'category';
        $mock_category->label       = 'Categories';
        $mock_category->description = '';
        $mock_category->public      = true;
        $mock_category->hierarchical = true;
        $mock_category->show_ui     = true;
        $mock_category->show_in_rest = true;
        $mock_category->rest_base   = 'categories';
        $mock_category->object_type = array( 'post' );

        $mock_tag              = Mockery::mock(\WP_Taxonomy::class);
        $mock_tag->name        = 'post_tag';
        $mock_tag->label       = 'Tags';
        $mock_tag->description = '';
        $mock_tag->public      = true;
        $mock_tag->hierarchical = false;
        $mock_tag->show_ui     = true;
        $mock_tag->show_in_rest = true;
        $mock_tag->rest_base   = 'tags';
        $mock_tag->object_type = array( 'post' );

        Functions\when('get_taxonomies')->justReturn(
            array(
                'category' => $mock_category,
                'post_tag' => $mock_tag,
            )
        );

        $result = $this->getAbilityInstance()->doExecute(array());

        $this->assertCount(2, $result['taxonomies']);
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
