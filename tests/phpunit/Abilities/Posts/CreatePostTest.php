<?php

/**
 * Tests for CreatePost ability.
 *
 * @package FAWpmcp\Tests\Abilities\Posts
 */

declare(strict_types=1);

namespace FAWpmcp\Tests\Abilities\Posts;

use FAWpmcp\Abilities\Posts\CreatePost;
use FAWpmcp\Exceptions\PostCreationException;
use Brain\Monkey;
use Brain\Monkey\Functions;
use Mockery;
use PHPUnit\Framework\TestCase;

/**
 * Test CreatePost ability functionality.
 *
 * Tests cover:
 * - Creating posts with sanitization
 * - Default status handling
 * - Category and tag assignment
 * - Error handling for creation failures
 * - Input validation and sanitization
 *
 * @package FAWpmcp\Tests\Abilities\Posts
 */
class CreatePostTest extends TestCase
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
    public function test_get_name(): void
    {
        $ability = new CreatePost();
        $this->assertEquals('fa-wpmcp/create-post', $ability->getName());
    }

    /**
     * Test ability returns correct category.
     *
     * @return void
     */
    public function test_get_category(): void
    {
        $ability = new CreatePost();
        $this->assertEquals('posts-pages', $ability->getCategory());
    }

    /**
     * Test ability returns correct label.
     *
     * @return void
     */
    public function test_get_label(): void
    {
        $ability = new CreatePost();
        $this->assertEquals('Create Post', $ability->getLabel());
    }

    /**
     * Test ability returns correct description.
     *
     * @return void
     */
    public function test_get_description(): void
    {
        $ability = new CreatePost();
        $this->assertStringContainsString('create', strtolower($ability->getDescription()));
    }

    /**
     * Test ability returns write operation type.
     *
     * @return void
     */
    public function test_get_operation_type(): void
    {
        $ability = new CreatePost();
        $this->assertEquals('write', $ability->getOperationType());
    }

    /**
     * Test ability returns correct required capability.
     *
     * @return void
     */
    public function test_get_required_capability(): void
    {
        $ability = new CreatePost();
        $this->assertEquals('publish_posts', $ability->getRequiredCapability());
    }

    /**
     * Test input schema requires title.
     *
     * @return void
     */
    public function test_input_schema_requires_title(): void
    {
        $ability = new CreatePost();
        $schema  = $ability->getInputSchema();

        $this->assertEquals('object', $schema['type']);
        $this->assertArrayHasKey('title', $schema['properties']);
        $this->assertContains('title', $schema['required']);
    }

    /**
     * Test input schema supports content field.
     *
     * @return void
     */
    public function test_input_schema_supports_content(): void
    {
        $ability = new CreatePost();
        $schema  = $ability->getInputSchema();

        $this->assertArrayHasKey('content', $schema['properties']);
        $this->assertEquals('string', $schema['properties']['content']['type']);
    }

    /**
     * Test input schema supports optional fields.
     *
     * @return void
     */
    public function test_input_schema_supports_optional_fields(): void
    {
        $ability = new CreatePost();
        $schema  = $ability->getInputSchema();

        $this->assertArrayHasKey('status', $schema['properties']);
        $this->assertArrayHasKey('author', $schema['properties']);
        $this->assertArrayHasKey('excerpt', $schema['properties']);
        $this->assertArrayHasKey('categories', $schema['properties']);
        $this->assertArrayHasKey('tags', $schema['properties']);
    }

    /**
     * Test input schema supports post_type parameter.
     *
     * @return void
     */
    public function test_input_schema_supports_post_type(): void
    {
        $ability = new CreatePost();
        $schema  = $ability->getInputSchema();

        $this->assertArrayHasKey('post_type', $schema['properties']);
        $this->assertEquals('string', $schema['properties']['post_type']['type']);
        $this->assertEquals('post', $schema['properties']['post_type']['default']);
    }

    /**
     * Test output schema has expected structure.
     *
     * @return void
     */
    public function test_output_schema_structure(): void
    {
        $ability = new CreatePost();
        $schema  = $ability->getOutputSchema();

        $this->assertEquals('object', $schema['type']);
        $this->assertArrayHasKey('post_id', $schema['properties']);
        $this->assertArrayHasKey('permalink', $schema['properties']);
        $this->assertArrayHasKey('status', $schema['properties']);
        $this->assertArrayHasKey('edit_url', $schema['properties']);
    }

    /**
     * Test execute creates post successfully.
     *
     * @return void
     */
    public function test_execute_creates_post(): void
    {
        Functions\expect('sanitize_text_field')
            ->once()
            ->with('Test Post Title')
            ->andReturnFirstArg();

        Functions\expect('wp_kses_post')
            ->once()
            ->with('<p>Test content</p>')
            ->andReturnFirstArg();

        Functions\expect('sanitize_textarea_field')
            ->once()
            ->with('Test excerpt')
            ->andReturnFirstArg();

        Functions\expect('wp_insert_post')
            ->once()
            ->with(
                Mockery::on(
                    function ($args) {
                        return 'Test Post Title' === $args['post_title']
                            && '<p>Test content</p>' === $args['post_content']
                            && 'draft' === $args['post_status'];
                    }
                ),
                true
            )
            ->andReturn(42);

        Functions\expect('is_wp_error')
            ->once()
            ->with(42)
            ->andReturn(false);

        Functions\expect('get_permalink')
            ->once()
            ->with(42)
            ->andReturn('https://example.com/test-post-title/');

        Functions\expect('get_post_status')
            ->once()
            ->with(42)
            ->andReturn('draft');

        Functions\expect('get_edit_post_link')
            ->once()
            ->with(42, 'raw')
            ->andReturn('https://example.com/wp-admin/post.php?post=42&action=edit');

        $ability = new CreatePost();
        $result  = $ability->doExecute(
            array(
                'title'   => 'Test Post Title',
                'content' => '<p>Test content</p>',
                'excerpt' => 'Test excerpt',
            )
        );

        $this->assertArrayHasKey('post_id', $result);
        $this->assertEquals(42, $result['post_id']);
        $this->assertEquals('https://example.com/test-post-title/', $result['permalink']);
        $this->assertEquals('draft', $result['status']);
    }

    /**
     * Test execute defaults to draft status.
     *
     * @return void
     */
    public function test_execute_defaults_to_draft(): void
    {
        Functions\expect('sanitize_text_field')->andReturnFirstArg();

        Functions\expect('wp_insert_post')
            ->once()
            ->with(
                Mockery::on(
                    function ($args) {
                        return 'draft' === $args['post_status'];
                    }
                ),
                true
            )
            ->andReturn(1);

        Functions\expect('is_wp_error')->andReturn(false);
        Functions\expect('get_permalink')->andReturn('https://example.com/post/');
        Functions\expect('get_post_status')->andReturn('draft');
        Functions\expect('get_edit_post_link')->andReturn('https://example.com/wp-admin/post.php?post=1&action=edit');

        $ability = new CreatePost();
        $result  = $ability->doExecute(
            array(
                'title' => 'Test',
            )
        );

        $this->assertEquals('draft', $result['status']);
    }

    /**
     * Test execute respects provided status.
     *
     * @return void
     */
    public function test_execute_respects_provided_status(): void
    {
        Functions\expect('sanitize_text_field')->andReturnFirstArg();

        Functions\expect('wp_insert_post')
            ->once()
            ->with(
                Mockery::on(
                    function ($args) {
                        return 'publish' === $args['post_status'];
                    }
                ),
                true
            )
            ->andReturn(1);

        Functions\expect('is_wp_error')->andReturn(false);
        Functions\expect('get_permalink')->andReturn('https://example.com/post/');
        Functions\expect('get_post_status')->andReturn('publish');
        Functions\expect('get_edit_post_link')->andReturn('https://example.com/wp-admin/post.php?post=1&action=edit');

        $ability = new CreatePost();
        $result  = $ability->doExecute(
            array(
                'title'  => 'Test',
                'status' => 'publish',
            )
        );

        $this->assertEquals('publish', $result['status']);
    }

    /**
     * Test execute sanitizes title.
     *
     * @return void
     */
    public function test_execute_sanitizes_title(): void
    {
        $dangerous_title = '<script>alert("xss")</script>Test';

        Functions\expect('sanitize_text_field')
            ->once()
            ->with($dangerous_title)
            ->andReturn('Test');

        Functions\expect('wp_insert_post')
            ->once()
            ->with(
                Mockery::on(
                    function ($args) {
                        return 'Test' === $args['post_title'];
                    }
                ),
                true
            )
            ->andReturn(1);

        Functions\expect('is_wp_error')->andReturn(false);
        Functions\expect('get_permalink')->andReturn('https://example.com/test/');
        Functions\expect('get_post_status')->andReturn('draft');
        Functions\expect('get_edit_post_link')->andReturn('https://example.com/wp-admin/post.php?post=1&action=edit');

        $ability = new CreatePost();
        $result  = $ability->doExecute(
            array(
                'title' => $dangerous_title,
            )
        );

        $this->assertArrayHasKey('post_id', $result);
    }

    /**
     * Test execute sanitizes content with wp_kses_post.
     *
     * @return void
     */
    public function test_execute_sanitizes_content(): void
    {
        $dangerous_content = '<script>alert("xss")</script><p>Safe content</p>';

        Functions\expect('sanitize_text_field')->andReturnFirstArg();

        Functions\expect('wp_kses_post')
            ->once()
            ->with($dangerous_content)
            ->andReturn('<p>Safe content</p>');

        Functions\expect('wp_insert_post')
            ->once()
            ->with(
                Mockery::on(
                    function ($args) {
                        return '<p>Safe content</p>' === $args['post_content'];
                    }
                ),
                true
            )
            ->andReturn(1);

        Functions\expect('is_wp_error')->andReturn(false);
        Functions\expect('get_permalink')->andReturn('https://example.com/test/');
        Functions\expect('get_post_status')->andReturn('draft');
        Functions\expect('get_edit_post_link')->andReturn('https://example.com/wp-admin/post.php?post=1&action=edit');

        $ability = new CreatePost();
        $result  = $ability->doExecute(
            array(
                'title'   => 'Test',
                'content' => $dangerous_content,
            )
        );

        $this->assertArrayHasKey('post_id', $result);
    }

    /**
     * Test execute throws on wp_insert_post error.
     *
     * @return void
     */
    public function test_execute_throws_on_insert_error(): void
    {
        Functions\expect('sanitize_text_field')->andReturnFirstArg();

        $wp_error = Mockery::mock('WP_Error');
        $wp_error->shouldReceive('get_error_message')
            ->once()
            ->andReturn('Database error occurred');

        Functions\expect('wp_insert_post')
            ->once()
            ->andReturn($wp_error);

        Functions\expect('is_wp_error')
            ->once()
            ->with($wp_error)
            ->andReturn(true);

        $this->expectException(PostCreationException::class);
        $this->expectExceptionMessage('Failed to create post');

        $ability = new CreatePost();
        $ability->doExecute(
            array(
                'title' => 'Test',
            )
        );
    }

    /**
     * Test execute sets author when provided.
     *
     * @return void
     */
    public function test_execute_sets_author(): void
    {
        Functions\expect('sanitize_text_field')->andReturnFirstArg();

        Functions\expect('wp_insert_post')
            ->once()
            ->with(
                Mockery::on(
                    function ($args) {
                        return 5 === $args['post_author'];
                    }
                ),
                true
            )
            ->andReturn(1);

        Functions\expect('is_wp_error')->andReturn(false);
        Functions\expect('get_permalink')->andReturn('https://example.com/test/');
        Functions\expect('get_post_status')->andReturn('draft');
        Functions\expect('get_edit_post_link')->andReturn('https://example.com/wp-admin/post.php?post=1&action=edit');

        $ability = new CreatePost();
        $result  = $ability->doExecute(
            array(
                'title'  => 'Test',
                'author' => 5,
            )
        );

        $this->assertEquals(1, $result['post_id']);
    }

    /**
     * Test execute assigns categories.
     *
     * @return void
     */
    public function test_execute_assigns_categories(): void
    {
        Functions\expect('sanitize_text_field')->andReturnFirstArg();

        Functions\expect('wp_insert_post')
            ->once()
            ->with(
                Mockery::on(
                    function ($args) {
                        return array( 1, 2, 3 ) === $args['post_category'];
                    }
                ),
                true
            )
            ->andReturn(1);

        Functions\expect('is_wp_error')->andReturn(false);
        Functions\expect('get_permalink')->andReturn('https://example.com/test/');
        Functions\expect('get_post_status')->andReturn('draft');
        Functions\expect('get_edit_post_link')->andReturn('https://example.com/wp-admin/post.php?post=1&action=edit');

        $ability = new CreatePost();
        $result  = $ability->doExecute(
            array(
                'title'      => 'Test',
                'categories' => array( 1, 2, 3 ),
            )
        );

        $this->assertEquals(1, $result['post_id']);
    }

    /**
     * Test execute assigns tags.
     *
     * @return void
     */
    public function test_execute_assigns_tags(): void
    {
        Functions\expect('sanitize_text_field')->andReturnFirstArg();

        Functions\expect('wp_insert_post')
            ->once()
            ->with(
                Mockery::on(
                    function ($args) {
                        return array( 'wordpress', 'php', 'testing' ) === $args['tags_input'];
                    }
                ),
                true
            )
            ->andReturn(1);

        Functions\expect('is_wp_error')->andReturn(false);
        Functions\expect('get_permalink')->andReturn('https://example.com/test/');
        Functions\expect('get_post_status')->andReturn('draft');
        Functions\expect('get_edit_post_link')->andReturn('https://example.com/wp-admin/post.php?post=1&action=edit');

        $ability = new CreatePost();
        $result  = $ability->doExecute(
            array(
                'title' => 'Test',
                'tags'  => array( 'wordpress', 'php', 'testing' ),
            )
        );

        $this->assertEquals(1, $result['post_id']);
    }

    /**
     * Test build_post_data is a pure function.
     *
     * @return void
     */
    public function test_build_post_data_is_pure(): void
    {
        Functions\stubs(
            array(
                'sanitize_text_field'     => fn($v) => $v,
                'wp_kses_post'            => fn($v) => $v,
                'sanitize_textarea_field' => fn($v) => $v,
            )
        );

        $ability = new CreatePost();

        $reflection = new \ReflectionClass($ability);
        $method     = $reflection->getMethod('buildPostData');

        $input = array(
            'title'   => 'Test',
            'content' => 'Content',
            'status'  => 'publish',
        );

        $result1 = $method->invoke($ability, $input);
        $result2 = $method->invoke($ability, $input);

        // Pure function should return identical structure.
        $this->assertEquals($result1['post_title'], $result2['post_title']);
        $this->assertEquals($result1['post_status'], $result2['post_status']);
    }

    /**
     * Test execute returns edit URL.
     *
     * @return void
     */
    public function test_execute_returns_edit_url(): void
    {
        Functions\expect('sanitize_text_field')->andReturnFirstArg();
        Functions\expect('wp_insert_post')->andReturn(123);
        Functions\expect('is_wp_error')->andReturn(false);
        Functions\expect('get_permalink')->andReturn('https://example.com/post/');
        Functions\expect('get_post_status')->andReturn('draft');

        Functions\expect('get_edit_post_link')
            ->once()
            ->with(123, 'raw')
            ->andReturn('https://example.com/wp-admin/post.php?post=123&action=edit');

        $ability = new CreatePost();
        $result  = $ability->doExecute(array( 'title' => 'Test' ));

        $this->assertEquals('https://example.com/wp-admin/post.php?post=123&action=edit', $result['edit_url']);
    }

    /**
     * Test execute validates status values.
     *
     * @return void
     */
    public function test_execute_validates_status(): void
    {
        Functions\expect('sanitize_text_field')->andReturnFirstArg();

        // Invalid status should default to draft.
        Functions\expect('wp_insert_post')
            ->once()
            ->with(
                Mockery::on(
                    function ($args) {
                        return 'draft' === $args['post_status'];
                    }
                ),
                true
            )
            ->andReturn(1);

        Functions\expect('is_wp_error')->andReturn(false);
        Functions\expect('get_permalink')->andReturn('https://example.com/test/');
        Functions\expect('get_post_status')->andReturn('draft');
        Functions\expect('get_edit_post_link')->andReturn('https://example.com/wp-admin/post.php?post=1&action=edit');

        $ability = new CreatePost();
        $result  = $ability->doExecute(
            array(
                'title'  => 'Test',
                'status' => 'invalid_status',
            )
        );

        $this->assertEquals('draft', $result['status']);
    }

    /**
     * Test execute defaults to post type.
     *
     * @return void
     */
    public function test_execute_defaults_to_post_type(): void
    {
        Functions\expect('sanitize_text_field')->andReturnFirstArg();

        Functions\expect('wp_insert_post')
            ->once()
            ->with(
                Mockery::on(
                    function ($args) {
                        return 'post' === $args['post_type'];
                    }
                ),
                true
            )
            ->andReturn(1);

        Functions\expect('is_wp_error')->andReturn(false);
        Functions\expect('get_permalink')->andReturn('https://example.com/test/');
        Functions\expect('get_post_status')->andReturn('draft');
        Functions\expect('get_edit_post_link')->andReturn('https://example.com/wp-admin/post.php?post=1&action=edit');

        $ability = new CreatePost();
        $ability->doExecute(array( 'title' => 'Test' ));
    }

    /**
     * Test execute creates page when post_type is page.
     *
     * @return void
     */
    public function test_execute_creates_page_type(): void
    {
        Functions\expect('sanitize_text_field')->andReturnFirstArg();

        Functions\expect('wp_insert_post')
            ->once()
            ->with(
                Mockery::on(
                    function ($args) {
                        return 'page' === $args['post_type'];
                    }
                ),
                true
            )
            ->andReturn(1);

        Functions\expect('is_wp_error')->andReturn(false);
        Functions\expect('get_permalink')->andReturn('https://example.com/test-page/');
        Functions\expect('get_post_status')->andReturn('draft');
        Functions\expect('get_edit_post_link')->andReturn('https://example.com/wp-admin/post.php?post=1&action=edit');

        $ability = new CreatePost();
        $ability->doExecute(
            array(
                'title'     => 'Test',
                'post_type' => 'page',
            )
        );
    }

    /**
     * Test execute supports custom post types.
     *
     * @return void
     */
    public function test_execute_supports_custom_post_types(): void
    {
        Functions\expect('sanitize_text_field')->andReturnFirstArg();

        Functions\expect('wp_insert_post')
            ->once()
            ->with(
                Mockery::on(
                    function ($args) {
                        return 'custom_type' === $args['post_type'];
                    }
                ),
                true
            )
            ->andReturn(1);

        Functions\expect('is_wp_error')->andReturn(false);
        Functions\expect('get_permalink')->andReturn('https://example.com/custom/');
        Functions\expect('get_post_status')->andReturn('draft');
        Functions\expect('get_edit_post_link')->andReturn('https://example.com/wp-admin/post.php?post=1&action=edit');

        $ability = new CreatePost();
        $ability->doExecute(
            array(
                'title'     => 'Test',
                'post_type' => 'custom_type',
            )
        );
    }
}
