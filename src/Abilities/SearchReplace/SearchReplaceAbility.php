<?php

/**
 * SearchReplaceAbility - previews search-replace operations (dry-run only).
 *
 * @package FAWpmcp\Abilities\SearchReplace
 */

declare(strict_types=1);

namespace FAWpmcp\Abilities\SearchReplace;

use FAWpmcp\Abilities\AbstractAbility;

/**
 * Ability to preview search-replace operations.
 *
 * IMPORTANT: This ability is DRY-RUN ONLY. It never modifies data.
 * It scans database tables for occurrences of a search string and
 * shows what would be changed if a replacement were performed.
 *
 * Useful for:
 * - Auditing URL migrations
 * - Finding serialized data references
 * - Previewing bulk text changes
 *
 * @package FAWpmcp\Abilities\SearchReplace
 */
final class SearchReplaceAbility extends AbstractAbility
{
    /**
     * Default result limit.
     */
    private const DEFAULT_LIMIT = 100;

    /**
     * Maximum result limit.
     */
    private const MAX_LIMIT = 500;

    /**
     * Maximum sample rows per table/column.
     */
    private const MAX_SAMPLE_ROWS = 5;

    /**
     * Returns the ability identifier.
     *
     * @return string Ability name.
     */
    public function getName(): string
    {
        return 'fa-wpmcp/search-replace';
    }

    /**
     * Returns the ability category.
     *
     * @return string Category name.
     */
    public function getCategory(): string
    {
        return 'search-replace';
    }

    /**
     * Returns the display label.
     *
     * @return string Ability label.
     */
    public function getLabel(): string
    {
        return 'Search Replace (Preview)';
    }

    /**
     * Returns the ability description.
     *
     * @return string Description.
     */
    public function getDescription(): string
    {
        return 'Preview what a search-replace operation would change. ' .
               'Dry-run only - never modifies data. Useful for auditing URL migrations ' .
               'or finding serialized data references.';
    }

    /**
     * Returns the JSON Schema for input validation.
     *
     * @return array<string, mixed> JSON Schema array.
     */
    public function getInputSchema(): array
    {
        return array(
            'type'       => 'object',
            'properties' => array(
                'search'      => array(
                    'type'        => 'string',
                    'description' => 'String to search for in the database.',
                    'minLength'   => 1,
                ),
                'replace'     => array(
                    'type'        => 'string',
                    'description' => 'Replacement string (for preview only).',
                ),
                'tables'      => array(
                    'type'        => 'array',
                    'items'       => array( 'type' => 'string' ),
                    'description' => 'Specific tables to search (default: all WP tables).',
                ),
                'skip_tables' => array(
                    'type'        => 'array',
                    'items'       => array( 'type' => 'string' ),
                    'description' => 'Tables to exclude from search.',
                ),
                'regex'       => array(
                    'type'        => 'boolean',
                    'description' => 'Treat search as a regular expression (default: false).',
                    'default'     => false,
                ),
                'limit'       => array(
                    'type'        => 'integer',
                    'description' => 'Maximum total results to return (default: 100, max: 500).',
                    'minimum'     => 1,
                    'maximum'     => self::MAX_LIMIT,
                    'default'     => self::DEFAULT_LIMIT,
                ),
            ),
            'required'   => array( 'search', 'replace' ),
        );
    }

    /**
     * Returns the JSON Schema for output.
     *
     * @return array<string, mixed> JSON Schema array.
     */
    public function getOutputSchema(): array
    {
        return array(
            'type'       => 'object',
            'properties' => array(
                'dry_run'         => array(
                    'type'        => 'boolean',
                    'description' => 'Always true - this ability never modifies data.',
                ),
                'search'          => array(
                    'type'        => 'string',
                    'description' => 'The search string used.',
                ),
                'replace'         => array(
                    'type'        => 'string',
                    'description' => 'The replacement string (preview only).',
                ),
                'tables_searched' => array(
                    'type'        => 'integer',
                    'description' => 'Number of tables searched.',
                ),
                'total_matches'   => array(
                    'type'        => 'integer',
                    'description' => 'Total number of matches found.',
                ),
                'results'         => array(
                    'type'        => 'array',
                    'description' => 'Results grouped by table and column.',
                    'items'       => array(
                        'type'       => 'object',
                        'properties' => array(
                            'table'       => array(
                                'type'        => 'string',
                                'description' => 'Table name.',
                            ),
                            'column'      => array(
                                'type'        => 'string',
                                'description' => 'Column name.',
                            ),
                            'matches'     => array(
                                'type'        => 'integer',
                                'description' => 'Number of rows with matches.',
                            ),
                            'sample_rows' => array(
                                'type'        => 'array',
                                'description' => 'Sample rows showing before/after.',
                                'items'       => array(
                                    'type'       => 'object',
                                    'properties' => array(
                                        'id'     => array(
                                            'description' => 'Primary key value.',
                                        ),
                                        'before' => array(
                                            'type'        => 'string',
                                            'description' => 'Original value (truncated).',
                                        ),
                                        'after'  => array(
                                            'type'        => 'string',
                                            'description' => 'Value after replacement (preview).',
                                        ),
                                    ),
                                ),
                            ),
                            'serialized_warning' => array(
                                'type'        => 'boolean',
                                'description' => 'True if serialized data detected.',
                            ),
                        ),
                    ),
                ),
                'warnings'        => array(
                    'type'        => 'array',
                    'items'       => array( 'type' => 'string' ),
                    'description' => 'Any warnings about the search results.',
                ),
            ),
        );
    }

    /**
     * Returns the WordPress capability required.
     *
     * @return string WordPress capability name.
     */
    public function getRequiredCapability(): string
    {
        return 'manage_options';
    }

    /**
     * Returns ability annotations.
     *
     * @return array<string, mixed> Annotations array.
     */
    public function getAnnotations(): array
    {
        return array(
            'readonly'     => true,
            'destructive'  => false,
            'idempotent'   => true,
            'instructions' => $this->getDescription(),
        );
    }

    /**
     * Executes the ability.
     *
     * @param array<string, mixed> $input Validated input data.
     * @return array<string, mixed> Search results.
     */
    public function doExecute(array $input): array
    {
        global $wpdb;

        $search      = $input['search'];
        $replace     = $input['replace'];
        $tables      = $input['tables'] ?? array();
        $skip_tables = $input['skip_tables'] ?? array();
        $regex       = $input['regex'] ?? false;
        $limit       = min($input['limit'] ?? self::DEFAULT_LIMIT, self::MAX_LIMIT);

        // Validate search string.
        if ('' === trim($search)) {
            return $this->buildErrorResponse('Search string cannot be empty.');
        }

        // Validate regex if enabled.
        if ($regex && false === @preg_match('/' . $search . '/', '')) {
            return $this->buildErrorResponse('Invalid regular expression pattern.');
        }

        // Get tables to search.
        $all_tables      = $this->getWordPressTables($wpdb);
        $tables_to_search = $this->filterTables($all_tables, $tables, $skip_tables, $wpdb->prefix);

        $results        = array();
        $total_matches  = 0;
        $warnings       = array();
        $results_count  = 0;

        foreach ($tables_to_search as $table) {
            if ($results_count >= $limit) {
                $warnings[] = "Result limit ({$limit}) reached. Some tables may not have been fully searched.";
                break;
            }

            $table_results = $this->searchTable(
                $wpdb,
                $table,
                $search,
                $replace,
                $regex,
                $limit - $results_count
            );

            foreach ($table_results as $result) {
                $results[]      = $result;
                $total_matches += $result['matches'];
                $results_count++;

                if ($result['serialized_warning']) {
                    $warning_msg = "Serialized data found in {$table}.{$result['column']}. " .
                                   'Direct replacement may corrupt data.';
                    if (!in_array($warning_msg, $warnings, true)) {
                        $warnings[] = $warning_msg;
                    }
                }
            }
        }

        return array(
            'dry_run'         => true,
            'search'          => $search,
            'replace'         => $replace,
            'tables_searched' => count($tables_to_search),
            'total_matches'   => $total_matches,
            'results'         => $results,
            'warnings'        => $warnings,
        );
    }

    /**
     * Build an error response.
     *
     * @param string $message Error message.
     * @return array<string, mixed> Error response.
     */
    private function buildErrorResponse(string $message): array
    {
        return array(
            'dry_run'         => true,
            'search'          => '',
            'replace'         => '',
            'tables_searched' => 0,
            'total_matches'   => 0,
            'results'         => array(),
            'warnings'        => array( $message ),
        );
    }

    /**
     * Get all WordPress tables.
     *
     * @param \wpdb $wpdb WordPress database object.
     * @return array<string> List of table names.
     */
    private function getWordPressTables(\wpdb $wpdb): array
    {
        // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching
        $tables = $wpdb->get_col(
            $wpdb->prepare(
                'SHOW TABLES LIKE %s',
                $wpdb->esc_like($wpdb->prefix) . '%'
            )
        );

        return is_array($tables) ? $tables : array();
    }

    /**
     * Filter tables based on include/exclude lists.
     *
     * @param array<string> $all_tables All available tables.
     * @param array<string> $include    Tables to include (empty = all).
     * @param array<string> $exclude    Tables to exclude.
     * @param string        $prefix     WordPress table prefix.
     * @return array<string> Filtered table list.
     */
    private function filterTables(
        array $all_tables,
        array $include,
        array $exclude,
        string $prefix
    ): array {
        // Normalize include list with prefix.
        $include_normalized = array();
        foreach ($include as $table) {
            $include_normalized[] = str_starts_with($table, $prefix) ? $table : $prefix . $table;
        }

        // Normalize exclude list with prefix.
        $exclude_normalized = array();
        foreach ($exclude as $table) {
            $exclude_normalized[] = str_starts_with($table, $prefix) ? $table : $prefix . $table;
        }

        // Filter tables.
        $filtered = array();
        foreach ($all_tables as $table) {
            // If include list is specified, only include those tables.
            if (!empty($include_normalized) && !in_array($table, $include_normalized, true)) {
                continue;
            }

            // Exclude specified tables.
            if (in_array($table, $exclude_normalized, true)) {
                continue;
            }

            $filtered[] = $table;
        }

        return $filtered;
    }

    /**
     * Search a single table for matches.
     *
     * @param \wpdb  $wpdb    WordPress database object.
     * @param string $table   Table name.
     * @param string $search  Search string.
     * @param string $replace Replacement string (preview).
     * @param bool   $regex   Whether to use regex.
     * @param int    $limit   Maximum results.
     * @return array<array<string, mixed>> Results for this table.
     */
    private function searchTable(
        \wpdb $wpdb,
        string $table,
        string $search,
        string $replace,
        bool $regex,
        int $limit
    ): array {
        $results = array();
        $columns = $this->getTextColumns($wpdb, $table);
        $pk      = $this->getPrimaryKey($wpdb, $table);

        foreach ($columns as $column) {
            if (count($results) >= $limit) {
                break;
            }

            $column_result = $this->searchColumn(
                $wpdb,
                $table,
                $column,
                $pk,
                $search,
                $replace,
                $regex
            );

            if ($column_result['matches'] > 0) {
                $results[] = $column_result;
            }
        }

        return $results;
    }

    /**
     * Get text-type columns from a table.
     *
     * @param \wpdb  $wpdb  WordPress database object.
     * @param string $table Table name.
     * @return array<string> Column names.
     */
    private function getTextColumns(\wpdb $wpdb, string $table): array
    {
        // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching
        $columns_info = $wpdb->get_results(
            // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
            "DESCRIBE `{$table}`",
            ARRAY_A
        );

        if (!is_array($columns_info)) {
            return array();
        }

        $text_columns = array();
        foreach ($columns_info as $column_info) {
            $type = strtolower($column_info['Type'] ?? '');
            if (
                str_contains($type, 'char') ||
                str_contains($type, 'text') ||
                str_contains($type, 'blob')
            ) {
                $text_columns[] = $column_info['Field'];
            }
        }

        return $text_columns;
    }

    /**
     * Get primary key column for a table.
     *
     * @param \wpdb  $wpdb  WordPress database object.
     * @param string $table Table name.
     * @return string Primary key column name or 'id'.
     */
    private function getPrimaryKey(\wpdb $wpdb, string $table): string
    {
        // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching
        $columns_info = $wpdb->get_results(
            // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
            "DESCRIBE `{$table}`",
            ARRAY_A
        );

        if (!is_array($columns_info)) {
            return 'id';
        }

        foreach ($columns_info as $column_info) {
            if ('PRI' === ($column_info['Key'] ?? '')) {
                return $column_info['Field'];
            }
        }

        return 'id';
    }

    /**
     * Search a single column for matches.
     *
     * @param \wpdb  $wpdb    WordPress database object.
     * @param string $table   Table name.
     * @param string $column  Column name.
     * @param string $pk      Primary key column.
     * @param string $search  Search string.
     * @param string $replace Replacement string.
     * @param bool   $regex   Whether to use regex.
     * @return array<string, mixed> Column search result.
     */
    private function searchColumn(
        \wpdb $wpdb,
        string $table,
        string $column,
        string $pk,
        string $search,
        string $replace,
        bool $regex
    ): array {
        // Count matches.
        // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching
        $count = $wpdb->get_var(
            $wpdb->prepare(
                // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
                "SELECT COUNT(*) FROM `{$table}` WHERE `{$column}` LIKE %s",
                '%' . $wpdb->esc_like($search) . '%'
            )
        );

        $matches = (int) $count;

        if (0 === $matches) {
            return array(
                'table'              => $table,
                'column'             => $column,
                'matches'            => 0,
                'sample_rows'        => array(),
                'serialized_warning' => false,
            );
        }

        // Get sample rows.
        // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching
        $rows = $wpdb->get_results(
            $wpdb->prepare(
                // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
                "SELECT `{$pk}`, `{$column}` FROM `{$table}` WHERE `{$column}` LIKE %s LIMIT %d",
                '%' . $wpdb->esc_like($search) . '%',
                self::MAX_SAMPLE_ROWS
            ),
            ARRAY_A
        );

        $sample_rows         = array();
        $serialized_warning  = false;

        if (is_array($rows)) {
            foreach ($rows as $row) {
                $original = $row[$column] ?? '';
                $id_value = $row[$pk] ?? null;

                // Check for serialized data.
                if ($this->isSerialized($original)) {
                    $serialized_warning = true;
                }

                // Perform replacement for preview.
                if ($regex) {
                    $replaced = @preg_replace('/' . $search . '/', $replace, $original);
                    $replaced = (null === $replaced) ? $original : $replaced;
                } else {
                    $replaced = str_replace($search, $replace, $original);
                }

                $sample_rows[] = array(
                    'id'     => $id_value,
                    'before' => $this->truncateValue($original),
                    'after'  => $this->truncateValue($replaced),
                );
            }
        }

        return array(
            'table'              => $table,
            'column'             => $column,
            'matches'            => $matches,
            'sample_rows'        => $sample_rows,
            'serialized_warning' => $serialized_warning,
        );
    }

    /**
     * Check if a value is serialized.
     *
     * @param mixed $value Value to check.
     * @return bool True if serialized.
     */
    private function isSerialized($value): bool
    {
        if (!is_string($value) || '' === $value) {
            return false;
        }

        // Check for common serialized patterns.
        $first_char = $value[0] ?? '';
        $second_char = $value[1] ?? '';

        // Serialized arrays start with 'a:', objects with 'O:', strings with 's:'.
        if (
            ('a' === $first_char || 'O' === $first_char || 's' === $first_char || 'i' === $first_char) &&
            ':' === $second_char
        ) {
            return true;
        }

        return false;
    }

    /**
     * Truncate a value for display.
     *
     * @param string $value Value to truncate.
     * @param int    $max   Maximum length.
     * @return string Truncated value.
     */
    private function truncateValue(string $value, int $max = 200): string
    {
        if (strlen($value) <= $max) {
            return $value;
        }

        return substr($value, 0, $max) . '...';
    }
}
