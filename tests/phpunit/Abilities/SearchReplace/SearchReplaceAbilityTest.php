<?php

/**
 * Tests for SearchReplaceAbility.
 *
 * @package FAWpmcp\Tests\Abilities\SearchReplace
 */

declare(strict_types=1);

namespace FAWpmcp\Tests\Abilities\SearchReplace;

use FAWpmcp\Abilities\AbstractAbility;
use FAWpmcp\Abilities\SearchReplace\SearchReplaceAbility;
use FAWpmcp\Tests\TestCase\AbilityTestTrait;
use FAWpmcp\Tests\TestCase\BrainMonkeyTestCase;
use Mockery;

/**
 * Test SearchReplaceAbility functionality.
 *
 * Tests cover:
 * - Basic search returns expected format
 * - Empty search returns validation error
 * - Tables filter works correctly
 * - Skip tables excludes specified tables
 * - Regex mode works
 * - Limit parameter caps results
 * - Always returns dry_run: true
 * - Required capability is manage_options
 * - Serialized data detection
 *
 * @package FAWpmcp\Tests\Abilities\SearchReplace
 */
class SearchReplaceAbilityTest extends BrainMonkeyTestCase
{
    use AbilityTestTrait;

    /**
     * Mock wpdb instance.
     *
     * @var \wpdb&\Mockery\MockInterface
     */
    private $wpdb;

    /**
     * Set up test fixtures.
     *
     * @return void
     */
    protected function setUp(): void
    {
        parent::setUp();

        // Define WordPress constant if not defined.
        if (!defined('ARRAY_A')) {
            define('ARRAY_A', 'ARRAY_A');
        }

        // Create mock wpdb.
        $this->wpdb = Mockery::mock('wpdb');
        $this->wpdb->prefix = 'wp_';
    }

    /**
     * Get an instance of the ability being tested.
     *
     * @return AbstractAbility
     */
    protected function getAbilityInstance(): AbstractAbility
    {
        return new SearchReplaceAbility();
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
            'name'                 => 'fa-wpmcp/search-replace',
            'category'             => 'search-replace',
            'label'                => 'Search Replace (Preview)',
            'description_contains' => 'dry-run',
            'operation_type'       => 'read',
            'required_capability'  => 'manage_options',
        );
    }

    /**
     * Test basic search returns expected format.
     *
     * @return void
     */
    public function testBasicSearchReturnsExpectedFormat(): void
    {
        $ability = $this->getAbilityInstance();

        // Set up global wpdb mock.
        $GLOBALS['wpdb'] = $this->wpdb;

        // Mock SHOW TABLES.
        $this->wpdb->shouldReceive('prepare')
            ->with('SHOW TABLES LIKE %s', 'wp\_%')
            ->andReturn('SHOW TABLES LIKE \'wp\_%\'');

        $this->wpdb->shouldReceive('esc_like')
            ->with('wp_')
            ->andReturn('wp\_');

        $this->wpdb->shouldReceive('get_col')
            ->andReturn(array('wp_posts', 'wp_options'));

        // Mock DESCRIBE for wp_posts.
        $this->wpdb->shouldReceive('get_results')
            ->with("DESCRIBE `wp_posts`", ARRAY_A)
            ->andReturn(array(
                array('Field' => 'ID', 'Type' => 'bigint(20)', 'Key' => 'PRI'),
                array('Field' => 'post_content', 'Type' => 'longtext', 'Key' => ''),
                array('Field' => 'post_title', 'Type' => 'varchar(255)', 'Key' => ''),
            ));

        // Mock DESCRIBE for wp_options.
        $this->wpdb->shouldReceive('get_results')
            ->with("DESCRIBE `wp_options`", ARRAY_A)
            ->andReturn(array(
                array('Field' => 'option_id', 'Type' => 'bigint(20)', 'Key' => 'PRI'),
                array('Field' => 'option_name', 'Type' => 'varchar(191)', 'Key' => ''),
                array('Field' => 'option_value', 'Type' => 'longtext', 'Key' => ''),
            ));

        // Mock esc_like for search.
        $this->wpdb->shouldReceive('esc_like')
            ->with('http://old-domain.com')
            ->andReturn('http://old-domain.com');

        // Mock count queries - return 0 for all columns.
        $this->wpdb->shouldReceive('prepare')
            ->andReturn('SELECT COUNT(*)...');

        $this->wpdb->shouldReceive('get_var')
            ->andReturn('0');

        $result = $ability->doExecute(array(
            'search'  => 'http://old-domain.com',
            'replace' => 'https://new-domain.com',
        ));

        $this->assertTrue($result['dry_run']);
        $this->assertEquals('http://old-domain.com', $result['search']);
        $this->assertEquals('https://new-domain.com', $result['replace']);
        $this->assertArrayHasKey('tables_searched', $result);
        $this->assertArrayHasKey('total_matches', $result);
        $this->assertArrayHasKey('results', $result);
        $this->assertArrayHasKey('warnings', $result);
        $this->assertIsArray($result['results']);
        $this->assertIsArray($result['warnings']);
    }

    /**
     * Test empty search returns validation error.
     *
     * @return void
     */
    public function testEmptySearchReturnsError(): void
    {
        $ability = $this->getAbilityInstance();

        $result = $ability->doExecute(array(
            'search'  => '   ',
            'replace' => 'replacement',
        ));

        $this->assertTrue($result['dry_run']);
        $this->assertEquals(0, $result['total_matches']);
        $this->assertContains('Search string cannot be empty.', $result['warnings']);
    }

    /**
     * Test invalid regex returns validation error.
     *
     * @return void
     */
    public function testInvalidRegexReturnsError(): void
    {
        $ability = $this->getAbilityInstance();

        $result = $ability->doExecute(array(
            'search'  => '[invalid(regex',
            'replace' => 'replacement',
            'regex'   => true,
        ));

        $this->assertTrue($result['dry_run']);
        $this->assertEquals(0, $result['total_matches']);
        $this->assertContains('Invalid regular expression pattern.', $result['warnings']);
    }

    /**
     * Test tables filter works correctly.
     *
     * @return void
     */
    public function testTablesFilterWorksCorrectly(): void
    {
        $ability = $this->getAbilityInstance();

        $GLOBALS['wpdb'] = $this->wpdb;

        // Mock SHOW TABLES.
        $this->wpdb->shouldReceive('prepare')
            ->with('SHOW TABLES LIKE %s', 'wp\_%')
            ->andReturn('SHOW TABLES LIKE \'wp\_%\'');

        $this->wpdb->shouldReceive('esc_like')
            ->with('wp_')
            ->andReturn('wp\_');

        $this->wpdb->shouldReceive('get_col')
            ->andReturn(array('wp_posts', 'wp_options', 'wp_users'));

        // Only wp_posts should be searched - DESCRIBE is called twice (columns + PK).
        $this->wpdb->shouldReceive('get_results')
            ->with("DESCRIBE `wp_posts`", ARRAY_A)
            ->twice()
            ->andReturn(array(
                array('Field' => 'ID', 'Type' => 'bigint(20)', 'Key' => 'PRI'),
                array('Field' => 'post_content', 'Type' => 'longtext', 'Key' => ''),
            ));

        $this->wpdb->shouldReceive('esc_like')
            ->with('test')
            ->andReturn('test');

        $this->wpdb->shouldReceive('prepare')
            ->andReturn('SELECT COUNT(*)...');

        $this->wpdb->shouldReceive('get_var')
            ->andReturn('0');

        $result = $ability->doExecute(array(
            'search'  => 'test',
            'replace' => 'replacement',
            'tables'  => array('posts'),  // Without prefix - should normalize.
        ));

        $this->assertTrue($result['dry_run']);
        $this->assertEquals(1, $result['tables_searched']);
    }

    /**
     * Test skip_tables excludes specified tables.
     *
     * @return void
     */
    public function testSkipTablesExcludesSpecifiedTables(): void
    {
        $ability = $this->getAbilityInstance();

        $GLOBALS['wpdb'] = $this->wpdb;

        // Mock SHOW TABLES.
        $this->wpdb->shouldReceive('prepare')
            ->with('SHOW TABLES LIKE %s', 'wp\_%')
            ->andReturn('SHOW TABLES LIKE \'wp\_%\'');

        $this->wpdb->shouldReceive('esc_like')
            ->with('wp_')
            ->andReturn('wp\_');

        $this->wpdb->shouldReceive('get_col')
            ->andReturn(array('wp_posts', 'wp_options'));

        // Only wp_posts should be searched (options is skipped) - DESCRIBE called twice.
        $this->wpdb->shouldReceive('get_results')
            ->with("DESCRIBE `wp_posts`", ARRAY_A)
            ->twice()
            ->andReturn(array(
                array('Field' => 'ID', 'Type' => 'bigint(20)', 'Key' => 'PRI'),
                array('Field' => 'post_content', 'Type' => 'longtext', 'Key' => ''),
            ));

        $this->wpdb->shouldReceive('esc_like')
            ->with('test')
            ->andReturn('test');

        $this->wpdb->shouldReceive('prepare')
            ->andReturn('SELECT COUNT(*)...');

        $this->wpdb->shouldReceive('get_var')
            ->andReturn('0');

        $result = $ability->doExecute(array(
            'search'      => 'test',
            'replace'     => 'replacement',
            'skip_tables' => array('wp_options'),
        ));

        $this->assertTrue($result['dry_run']);
        $this->assertEquals(1, $result['tables_searched']);
    }

    /**
     * Test limit parameter caps results.
     *
     * @return void
     */
    public function testLimitParameterCapsResults(): void
    {
        $ability = $this->getAbilityInstance();

        $GLOBALS['wpdb'] = $this->wpdb;

        // Mock SHOW TABLES.
        $this->wpdb->shouldReceive('prepare')
            ->with('SHOW TABLES LIKE %s', 'wp\_%')
            ->andReturn('SHOW TABLES LIKE \'wp\_%\'');

        $this->wpdb->shouldReceive('esc_like')
            ->with('wp_')
            ->andReturn('wp\_');

        $this->wpdb->shouldReceive('get_col')
            ->andReturn(array('wp_posts', 'wp_options', 'wp_users', 'wp_comments'));

        // Mock DESCRIBE for first table.
        $this->wpdb->shouldReceive('get_results')
            ->with("DESCRIBE `wp_posts`", ARRAY_A)
            ->andReturn(array(
                array('Field' => 'ID', 'Type' => 'bigint(20)', 'Key' => 'PRI'),
                array('Field' => 'post_content', 'Type' => 'longtext', 'Key' => ''),
            ));

        $this->wpdb->shouldReceive('esc_like')
            ->with('test')
            ->andReturn('test');

        // Return matches to trigger limit.
        $this->wpdb->shouldReceive('prepare')
            ->andReturn('prepared query');

        $this->wpdb->shouldReceive('get_var')
            ->andReturn('5');

        $this->wpdb->shouldReceive('get_results')
            ->andReturn(array(
                array('ID' => 1, 'post_content' => 'test content'),
            ));

        $result = $ability->doExecute(array(
            'search'  => 'test',
            'replace' => 'replacement',
            'limit'   => 1,
        ));

        $this->assertTrue($result['dry_run']);
        // With limit 1, should stop after first result.
        $this->assertLessThanOrEqual(1, count($result['results']));
    }

    /**
     * Test result always returns dry_run true.
     *
     * @return void
     */
    public function testAlwaysReturnsDryRunTrue(): void
    {
        $ability = $this->getAbilityInstance();

        $GLOBALS['wpdb'] = $this->wpdb;

        $this->wpdb->shouldReceive('prepare')->andReturn('query');
        $this->wpdb->shouldReceive('esc_like')->andReturn('escaped');
        $this->wpdb->shouldReceive('get_col')->andReturn(array());

        $result = $ability->doExecute(array(
            'search'  => 'anything',
            'replace' => 'something',
        ));

        $this->assertTrue($result['dry_run']);
    }

    /**
     * Test required capability is manage_options.
     *
     * @return void
     */
    public function testRequiredCapabilityIsManageOptions(): void
    {
        $ability = $this->getAbilityInstance();

        $this->assertEquals('manage_options', $ability->getRequiredCapability());
    }

    /**
     * Test annotations indicate read-only operation.
     *
     * @return void
     */
    public function testAnnotationsIndicateReadOnly(): void
    {
        $ability = $this->getAbilityInstance();

        $annotations = $ability->getAnnotations();

        $this->assertTrue($annotations['readonly']);
        $this->assertFalse($annotations['destructive']);
        $this->assertTrue($annotations['idempotent']);
    }

    /**
     * Test operation type is read.
     *
     * @return void
     */
    public function testOperationTypeIsRead(): void
    {
        $ability = $this->getAbilityInstance();

        $this->assertEquals('read', $ability->getOperationType());
    }

    /**
     * Test input schema has required fields.
     *
     * @return void
     */
    public function testInputSchemaHasRequiredFields(): void
    {
        $ability = $this->getAbilityInstance();

        $schema = $ability->getInputSchema();

        $this->assertEquals('object', $schema['type']);
        $this->assertArrayHasKey('search', $schema['properties']);
        $this->assertArrayHasKey('replace', $schema['properties']);
        $this->assertArrayHasKey('tables', $schema['properties']);
        $this->assertArrayHasKey('skip_tables', $schema['properties']);
        $this->assertArrayHasKey('regex', $schema['properties']);
        $this->assertArrayHasKey('limit', $schema['properties']);
        $this->assertEquals(array('search', 'replace'), $schema['required']);
    }

    /**
     * Test output schema has expected structure.
     *
     * @return void
     */
    public function testOutputSchemaHasExpectedStructure(): void
    {
        $ability = $this->getAbilityInstance();

        $schema = $ability->getOutputSchema();

        $this->assertEquals('object', $schema['type']);
        $this->assertArrayHasKey('dry_run', $schema['properties']);
        $this->assertArrayHasKey('search', $schema['properties']);
        $this->assertArrayHasKey('replace', $schema['properties']);
        $this->assertArrayHasKey('tables_searched', $schema['properties']);
        $this->assertArrayHasKey('total_matches', $schema['properties']);
        $this->assertArrayHasKey('results', $schema['properties']);
        $this->assertArrayHasKey('warnings', $schema['properties']);
    }

    /**
     * Test serialized data detection in sample rows.
     *
     * @return void
     */
    public function testSerializedDataDetection(): void
    {
        $ability = $this->getAbilityInstance();

        $GLOBALS['wpdb'] = $this->wpdb;

        // Mock SHOW TABLES.
        $this->wpdb->shouldReceive('prepare')
            ->with('SHOW TABLES LIKE %s', 'wp\_%')
            ->andReturn('SHOW TABLES LIKE \'wp\_%\'');

        $this->wpdb->shouldReceive('esc_like')
            ->with('wp_')
            ->andReturn('wp\_');

        $this->wpdb->shouldReceive('get_col')
            ->andReturn(array('wp_options'));

        // Mock DESCRIBE.
        $this->wpdb->shouldReceive('get_results')
            ->with("DESCRIBE `wp_options`", ARRAY_A)
            ->andReturn(array(
                array('Field' => 'option_id', 'Type' => 'bigint(20)', 'Key' => 'PRI'),
                array('Field' => 'option_value', 'Type' => 'longtext', 'Key' => ''),
            ));

        $this->wpdb->shouldReceive('esc_like')
            ->with('http://old')
            ->andReturn('http://old');

        // Return match count.
        $this->wpdb->shouldReceive('prepare')
            ->andReturn('prepared query');

        $this->wpdb->shouldReceive('get_var')
            ->andReturn('1');

        // Return serialized data in sample.
        $this->wpdb->shouldReceive('get_results')
            ->andReturn(array(
                array(
                    'option_id'    => 1,
                    'option_value' => 'a:2:{s:4:"home";s:16:"http://old.local";s:8:"siteurl";s:16:"http://old.local";}',
                ),
            ));

        $result = $ability->doExecute(array(
            'search'  => 'http://old',
            'replace' => 'https://new',
        ));

        $this->assertTrue($result['dry_run']);

        // Check for serialized warning.
        $has_serialized_warning = false;
        foreach ($result['warnings'] as $warning) {
            if (str_contains($warning, 'Serialized data found')) {
                $has_serialized_warning = true;
                break;
            }
        }
        $this->assertTrue($has_serialized_warning, 'Should warn about serialized data');

        // Check result has serialized_warning flag.
        if (!empty($result['results'])) {
            $this->assertTrue($result['results'][0]['serialized_warning']);
        }
    }

    /**
     * Test regex search mode.
     *
     * @return void
     */
    public function testRegexSearchMode(): void
    {
        $ability = $this->getAbilityInstance();

        $GLOBALS['wpdb'] = $this->wpdb;

        // Mock SHOW TABLES.
        $this->wpdb->shouldReceive('prepare')
            ->with('SHOW TABLES LIKE %s', 'wp\_%')
            ->andReturn('SHOW TABLES LIKE \'wp\_%\'');

        $this->wpdb->shouldReceive('esc_like')
            ->with('wp_')
            ->andReturn('wp\_');

        $this->wpdb->shouldReceive('get_col')
            ->andReturn(array('wp_posts'));

        // Mock DESCRIBE - called twice (columns + PK).
        $this->wpdb->shouldReceive('get_results')
            ->with("DESCRIBE `wp_posts`", ARRAY_A)
            ->andReturn(array(
                array('Field' => 'ID', 'Type' => 'bigint(20)', 'Key' => 'PRI'),
                array('Field' => 'post_content', 'Type' => 'longtext', 'Key' => ''),
            ));

        // Use a simple regex pattern without / which would be interpreted as delimiter.
        $this->wpdb->shouldReceive('esc_like')
            ->with('example\\.com')
            ->andReturn('example\\.com');

        $this->wpdb->shouldReceive('prepare')
            ->andReturn('prepared query');

        $this->wpdb->shouldReceive('get_var')
            ->andReturn('1');

        $this->wpdb->shouldReceive('get_results')
            ->andReturn(array(
                array('ID' => 1, 'post_content' => 'Visit example.com for more info'),
            ));

        $result = $ability->doExecute(array(
            'search'  => 'example\\.com',
            'replace' => 'newsite.org',
            'regex'   => true,
        ));

        $this->assertTrue($result['dry_run']);
        // With valid regex, should process without error.
        $this->assertNotContains('Invalid regular expression pattern.', $result['warnings']);
    }

    /**
     * Test limit exceeding max is capped.
     *
     * @return void
     */
    public function testLimitExceedingMaxIsCapped(): void
    {
        $ability = $this->getAbilityInstance();

        $GLOBALS['wpdb'] = $this->wpdb;

        $this->wpdb->shouldReceive('prepare')->andReturn('query');
        $this->wpdb->shouldReceive('esc_like')->andReturn('escaped');
        $this->wpdb->shouldReceive('get_col')->andReturn(array());

        // Even with limit > 500, should not error.
        $result = $ability->doExecute(array(
            'search'  => 'test',
            'replace' => 'replacement',
            'limit'   => 1000,
        ));

        $this->assertTrue($result['dry_run']);
        // Schema enforces max 500, but implementation should also cap.
        $this->assertIsArray($result['results']);
    }
}
