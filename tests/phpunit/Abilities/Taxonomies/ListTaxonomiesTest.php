<?php

/**
 * Tests for ListTaxonomies ability.
 *
 * @package FAWpmcp\Tests\Abilities\Taxonomies
 */

declare(strict_types=1);

namespace FAWpmcp\Tests\Abilities\Taxonomies;

use FAWpmcp\Abilities\Taxonomies\ListTaxonomies;
use Brain\Monkey;
use Brain\Monkey\Functions;
use Mockery;
use PHPUnit\Framework\TestCase;

/**
 * Test ListTaxonomies ability functionality.
 *
 * @package FAWpmcp\Tests\Abilities\Taxonomies
 */
class ListTaxonomiesTest extends TestCase
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
        $ability = new ListTaxonomies();
        $this->assertEquals('fa-wpmcp/list-taxonomies', $ability->getName());
    }

    /**
     * Test ability returns correct category.
     *
     * @return void
     */
    public function testGetCategory(): void
    {
        $ability = new ListTaxonomies();
        $this->assertEquals('taxonomies', $ability->getCategory());
    }

    /**
     * Test ability returns correct label.
     *
     * @return void
     */
    public function testGetLabel(): void
    {
        $ability = new ListTaxonomies();
        $this->assertEquals('List Taxonomies', $ability->getLabel());
    }

    /**
     * Test ability returns correct operation type.
     *
     * @return void
     */
    public function testGetOperationType(): void
    {
        $ability = new ListTaxonomies();
        $this->assertEquals('read', $ability->getOperationType());
    }

    /**
     * Test ability returns correct required capability.
     *
     * @return void
     */
    public function testGetRequiredCapability(): void
    {
        $ability = new ListTaxonomies();
        $this->assertEquals('read', $ability->getRequiredCapability());
    }

    /**
     * Test ability returns input schema.
     *
     * @return void
     */
    public function testGetInputSchema(): void
    {
        $ability = new ListTaxonomies();
        $schema  = $ability->getInputSchema();

        $this->assertIsArray($schema);
        $this->assertArrayHasKey('type', $schema);
        $this->assertArrayHasKey('properties', $schema);
        $this->assertArrayHasKey('object_type', $schema['properties']);
        $this->assertArrayHasKey('public', $schema['properties']);
        $this->assertArrayHasKey('show_ui', $schema['properties']);
        $this->assertArrayHasKey('hierarchical', $schema['properties']);
    }

    /**
     * Test ability returns output schema.
     *
     * @return void
     */
    public function testGetOutputSchema(): void
    {
        $ability = new ListTaxonomies();
        $schema  = $ability->getOutputSchema();

        $this->assertIsArray($schema);
        $this->assertArrayHasKey('type', $schema);
        $this->assertArrayHasKey('properties', $schema);
        $this->assertArrayHasKey('taxonomies', $schema['properties']);
        $this->assertArrayHasKey('total', $schema['properties']);
    }

    /**
     * Test execute returns taxonomies list.
     *
     * @return void
     */
    public function testExecuteReturnsTaxonomiesList(): void
    {
        $ability = new ListTaxonomies();

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

        $result = $ability->doExecute(array());

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
        $ability = new ListTaxonomies();

        Functions\when('get_taxonomies')->justReturn(array());

        $result = $ability->doExecute(array( 'public' => false ));

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
        $ability = new ListTaxonomies();

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

        $result = $ability->doExecute(array( 'object_type' => 'post' ));

        $this->assertCount(1, $result['taxonomies']);
    }

    /**
     * Test execute filters by hierarchical.
     *
     * @return void
     */
    public function testExecuteFiltersByHierarchical(): void
    {
        $ability = new ListTaxonomies();

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

        $result = $ability->doExecute(array( 'hierarchical' => true ));

        $this->assertCount(1, $result['taxonomies']);
    }

    /**
     * Test execute returns multiple taxonomies.
     *
     * @return void
     */
    public function testExecuteReturnsMultipleTaxonomies(): void
    {
        $ability = new ListTaxonomies();

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

        $result = $ability->doExecute(array());

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
        $ability     = new ListTaxonomies();
        $annotations = $ability->getAnnotations();

        $this->assertTrue($annotations['readonly']);
        $this->assertFalse($annotations['destructive']);
        $this->assertTrue($annotations['idempotent']);
    }
}
