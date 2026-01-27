<?php

/**
 * Main plugin orchestration class.
 *
 * @package FAWpmcp
 */

declare(strict_types=1);

namespace FAWpmcp;

use WP\MCP\Core\McpAdapter;

/**
 * Main plugin orchestration class.
 *
 * This class handles plugin initialization, activation, deactivation,
 * and uninstallation. It follows the singleton pattern for WordPress
 * plugin architecture.
 *
 * @package FAWpmcp
 */
final class Plugin
{
    /**
     * Singleton instance.
     *
     * @var self|null
     */
    private static ?self $instance = null;

    /**
     * Service container for dependency injection.
     *
     * @var array<string, object>
     */
    private array $services = array();

    /**
     * Private constructor to prevent direct instantiation.
     */
    private function __construct()
    {
        // Singleton - use getInstance().
    }

    /**
     * Get singleton instance.
     *
     * @return self
     */
    public static function getInstance(): self
    {
        if (null === self::$instance) {
            self::$instance = new self();
        }

        return self::$instance;
    }

    /**
     * Initialize plugin.
     *
     * Registers hooks and initializes components.
     *
     * @return void
     */
    public function init(): void
    {
        $this->registerHooks();

        // Initialize webhook system.
        $webhook_service = new \FAWpmcp\Webhooks\WebhookService();
        $webhook_service->init();
        $this->registerService('webhook', $webhook_service);

        // Initialize Ability Framework.
        // 1. Create AbilityRegistry and register abilities.
        $ability_registry = new \FAWpmcp\Abilities\AbilityRegistry();
        \FAWpmcp\Abilities\AbilityRegistrar::registerAll($ability_registry);
        $this->registerService('ability_registry', $ability_registry);

        // Initialize Admin Settings Page.
        $settings_page = new \FAWpmcp\Admin\SettingsPage($ability_registry);
        $settings_page->init();
        $this->registerService('settings_page', $settings_page);

        // 2. Create dependencies for AbilityExecutor.
        // Permission settings from WordPress options.
        $permission_settings = \FAWpmcp\Permissions\OptionsPermissionSettings::load();

        // Rate limiter with transient storage.
        $rate_limit_store  = new \FAWpmcp\RateLimiting\TransientRateLimitStore();
        $rate_limit_config = new \FAWpmcp\RateLimiting\OptionsRateLimitConfig();
        $rate_limiter      = new \FAWpmcp\RateLimiting\RateLimiter($rate_limit_store, $rate_limit_config);
        $this->registerService('rate_limiter', $rate_limiter);

        // Activity logger with database repository.
        global $wpdb;
        $log_repository  = new \FAWpmcp\Logging\LogRepository($wpdb);
        $activity_logger = new \FAWpmcp\Logging\ActivityLogger(
            $log_repository,
            fn() => wp_generate_uuid4()
        );
        $this->registerService('activity_logger', $activity_logger);

        // Webhook manager from webhook service.
        $webhook_manager = $webhook_service->getManager();

        // 3. Create AbilityExecutor with all dependencies.
        $ability_executor = new \FAWpmcp\Abilities\AbilityExecutor(
            $permission_settings,
            $rate_limiter,
            $activity_logger,
            $webhook_manager
        );
        $this->registerService('ability_executor', $ability_executor);

        // 4. Register ability categories BEFORE registering abilities.
        add_action(
            'wp_abilities_api_categories_init',
            function () {
                $this->registerAbilityCategories();

                // Verify categories were registered.
                $categories_registry = \WP_Ability_Categories_Registry::get_instance();
                if ($categories_registry && method_exists($categories_registry, 'is_registered')) {
                    // Categories verified during registration.
                }
            },
            5  // Priority 5 to run before McpAdapter's default priority 10.
        );

        // 5. Register all abilities with WordPress Abilities API.
        add_action(
            'wp_abilities_api_init',
            function () use ($ability_registry, $ability_executor) {
                // Verify categories are still registered before registering abilities.
                $categories_registry = \WP_Ability_Categories_Registry::get_instance();
                if ($categories_registry && method_exists($categories_registry, 'is_registered')) {
                    // Categories verified before ability registration.
                }

                $this->registerAbilitiesWithWordPress($ability_registry, $ability_executor);
            },
            15  // Priority 15 to run after other plugins (McpAdapter uses default 10).
        );

        // 6. Initialize MCP Adapter AFTER registering the hooks.
        // This ensures our categories and abilities are registered before the adapter fires the hooks.
        McpAdapter::instance();

        // 7. Hook into MCP adapter server config to add FA-WPMCP abilities as tools.
        // The MCP adapter only auto-discovers resources/prompts, not tools, so we must explicitly add them.
        add_filter(
            'mcp_adapter_default_server_config',
            function ($config) {
                $fa_wpmcp_categories = array(
                    'posts-pages',
                    'comments',
                    'media',
                    'taxonomies',
                    'post-types',
                    'users',
                    'settings',
                    'plugins',
                    'themes',
                    'privacy',
                    'cache',
                    'maintenance',
                    'transients',
                    'cron',
                    'role',
                    'site',
                    'menu',
                    'widgets',
                    'dotenv',
                    'core',
                    'rewrite',
                );

                $fa_abilities = array();
                foreach (wp_get_abilities() as $ability) {
                    $category = $ability->get_category();
                    if (in_array($category, $fa_wpmcp_categories, true)) {
                        $fa_abilities[] = $ability->get_name();
                    }
                }

                // Merge FA-WPMCP abilities with existing tools.
                if (! empty($fa_abilities)) {
                    $config['tools'] = array_merge($config['tools'] ?? array(), $fa_abilities);
                }

                // Enable observability logging to PHP error log.
                $config['observability_handler'] = \WP\MCP\Infrastructure\Observability\ErrorLogMcpObservabilityHandler::class;

                // Enable file-based error logging (controlled by settings).
                $config['error_handler'] = \FAWpmcp\Logging\FileErrorHandler::class;

                return $config;
            },
            10
        );
    }

    /**
     * Register WordPress hooks.
     *
     * @return void
     */
    private function registerHooks(): void
    {
        add_action('admin_init', array( $this, 'checkAbilitiesApiAndShowNotice' ));
    }

    /**
     * Register ability categories with WordPress Abilities API.
     *
     * Categories must be registered before abilities can use them.
     * This method is called on the 'wp_abilities_api_categories_init' hook.
     *
     * @return void
     */
    private function registerAbilityCategories(): void
    {
        if (! function_exists('wp_register_ability_category')) {
            return;
        }

        $categories = array(
            'posts-pages' => array(
                'label'       => __('Posts & Pages', 'fa-wpmcp'),
                'description' => __('Abilities for managing WordPress posts, pages, and custom post types', 'fa-wpmcp'),
            ),
            'comments'    => array(
                'label'       => __('Comments', 'fa-wpmcp'),
                'description' => __('Abilities for managing WordPress comments and comment moderation', 'fa-wpmcp'),
            ),
            'media'       => array(
                'label'       => __('Media Library', 'fa-wpmcp'),
                'description' => __('Abilities for managing WordPress media files and attachments', 'fa-wpmcp'),
            ),
            'taxonomies'  => array(
                'label'       => __('Taxonomies', 'fa-wpmcp'),
                'description' => __('Abilities for managing WordPress terms, categories, and tags', 'fa-wpmcp'),
            ),
            'post-types'  => array(
                'label'       => __('Post Types', 'fa-wpmcp'),
                'description' => __('Abilities for inspecting registered WordPress post type definitions', 'fa-wpmcp'),
            ),
            'users'       => array(
                'label'       => __('Users', 'fa-wpmcp'),
                'description' => __('Abilities for managing WordPress user accounts and profiles', 'fa-wpmcp'),
            ),
            'settings'    => array(
                'label'       => __('Settings', 'fa-wpmcp'),
                'description' => __('Abilities for managing WordPress options and site configuration', 'fa-wpmcp'),
            ),
            'plugins'     => array(
                'label'       => __('Plugins', 'fa-wpmcp'),
                'description' => __('Abilities for managing WordPress plugin installation, activation, and updates', 'fa-wpmcp'),
            ),
            'themes'      => array(
                'label'       => __('Themes', 'fa-wpmcp'),
                'description' => __('Abilities for managing WordPress theme installation, activation, and updates', 'fa-wpmcp'),
            ),
            'privacy'     => array(
                'label'       => __('Privacy', 'fa-wpmcp'),
                'description' => __('Abilities for managing WordPress privacy requests (GDPR data export and erasure)', 'fa-wpmcp'),
            ),
            'cache'       => array(
                'label'       => __('Cache', 'fa-wpmcp'),
                'description' => __('Abilities for managing WordPress object cache operations', 'fa-wpmcp'),
            ),
            'maintenance' => array(
                'label'       => __('Maintenance', 'fa-wpmcp'),
                'description' => __('Abilities for managing WordPress maintenance mode', 'fa-wpmcp'),
            ),
            'transients'  => array(
                'label'       => __('Transients', 'fa-wpmcp'),
                'description' => __('Abilities for managing WordPress transient cache entries', 'fa-wpmcp'),
            ),
            'cron'        => array(
                'label'       => __('Cron', 'fa-wpmcp'),
                'description' => __('Abilities for managing WordPress cron scheduled events and schedules', 'fa-wpmcp'),
            ),
            'role'        => array(
                'label'       => __('Roles', 'fa-wpmcp'),
                'description' => __('Abilities for managing WordPress user roles and capabilities', 'fa-wpmcp'),
            ),
            'menu'        => array(
                'label'       => __('Menus', 'fa-wpmcp'),
                'description' => __('Abilities for managing WordPress navigation menus', 'fa-wpmcp'),
            ),
            'widgets'     => array(
                'label'       => __('Widgets', 'fa-wpmcp'),
                'description' => __('Abilities for managing WordPress sidebar widgets', 'fa-wpmcp'),
            ),
            'dotenv'      => array(
                'label'       => __('Environment', 'fa-wpmcp'),
                'description' => __('Abilities for managing Bedrock .env environment variables', 'fa-wpmcp'),
            ),
            'core'        => array(
                'label'       => __('Core', 'fa-wpmcp'),
                'description' => __('Abilities for WordPress core version, updates, and database management', 'fa-wpmcp'),
            ),
            'rewrite'     => array(
                'label'       => __('Rewrite Rules', 'fa-wpmcp'),
                'description' => __('Abilities for managing WordPress rewrite rules and permalink structure', 'fa-wpmcp'),
            ),
        );

        foreach ($categories as $slug => $args) {
            wp_register_ability_category($slug, $args);
        }
    }

    /**
     * Register all abilities with WordPress Abilities API.
     *
     * @param \FAWpmcp\Abilities\AbilityRegistry $registry Registry containing all abilities.
     * @param \FAWpmcp\Abilities\AbilityExecutor $executor Executor for running abilities.
     * @return void
     */
    private function registerAbilitiesWithWordPress(
        \FAWpmcp\Abilities\AbilityRegistry $registry,
        \FAWpmcp\Abilities\AbilityExecutor $executor
    ): void {
        if (! function_exists('wp_register_ability')) {
            return;
        }

        foreach ($registry->all() as $ability) {
            $registration = $ability->toRegistrationArray();

            wp_register_ability(
                $registration['name'],
                array(
                    'label'               => $registration['label'],
                    'description'         => $registration['description'],
                    'category'            => $registration['category'],
                    'input_schema'        => $registration['inputSchema'],
                    'output_schema'       => $registration['outputSchema'],
                    'meta'                => array(
                        'annotations'  => $registration['annotations'],
                        'show_in_rest' => true,
                        'mcp'          => array(
                            'public' => true,
                            'type'   => 'tool',
                        ),
                    ),
                    'permission_callback' => function () use ($registration) {
                        return current_user_can($registration['requiredCapability']);
                    },
                    'execute_callback'    => function (array $input) use ($ability, $executor) {
                        $current_user = wp_get_current_user();
                        $result       = $executor->execute(
                            $ability,
                            $input,
                            $current_user->ID,
                            $current_user->user_login ?? '',
                            $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1'
                        );

                        if (! $result->is_success) {
                            return new \WP_Error(
                                'ability_execution_failed',
                                $result->error_message ?? 'Ability execution failed',
                                array( 'status' => 500 )
                            );
                        }

                        return $result->value;
                    },
                )
            );
        }
    }

    /**
     * Register a service in the container.
     *
     * @param string $name    Service name.
     * @param object $service Service instance.
     * @return void
     */
    public function registerService(string $name, object $service): void
    {
        $this->services[ $name ] = $service;
    }

    /**
     * Get a service from the container.
     *
     * @param string $name Service name.
     * @return object|null Service instance or null if not found.
     */
    public function getService(string $name): ?object
    {
        return $this->services[ $name ] ?? null;
    }

    /**
     * Check if WordPress Abilities API is available.
     *
     * @return bool True if Abilities API is available.
     */
    public function hasAbilitiesApi(): bool
    {
        return function_exists('wp_register_ability');
    }

    /**
     * Check Abilities API availability and show admin notice if missing.
     *
     * @return void
     */
    public function checkAbilitiesApiAndShowNotice(): void
    {
        if (! $this->hasAbilitiesApi()) {
            add_action(
                'admin_notices',
                function () {
                    echo '<div class="error"><p>';
                    echo esc_html__(
                        'FA WPMCP requires WordPress Abilities API (WordPress 6.9+). Please update WordPress.',
                        'fa-wpmcp'
                    );
                    echo '</p></div>';
                }
            );
        }
    }

    /**
     * Plugin activation handler.
     *
     * @return void
     */
    public function activate(): void
    {
        // Create database tables.
        $this->createDatabaseTables();

        // Migrate webhook secrets from dual storage to canonical location.
        \FAWpmcp\Database\SecretStorageMigration::migrate();

        // Initialize default permission settings if not already set.
        $this->initializePermissionSettings();
    }

    /**
     * Initialize default permission settings.
     *
     * Creates the fa_wpmcp_permissions option with safe defaults if it doesn't exist.
     * Defaults: global_read_enabled=true, global_write_enabled=false (safe by default).
     *
     * @return void
     */
    private function initializePermissionSettings(): void
    {
        $option_name = 'fa_wpmcp_permissions';

        // Only create if option doesn't exist.
        if (false === get_option($option_name)) {
            $defaults = array(
                'global_read_enabled'  => true,
                'global_write_enabled' => false,
                'category_settings'    => array(),
                'ability_settings'     => array(),
            );
            add_option($option_name, $defaults);
        }
    }

    /**
     * Create database tables using dbDelta.
     *
     * @return void
     */
    private function createDatabaseTables(): void
    {
        global $wpdb;

        // phpcs:ignore -- WordPress procedural include for dbDelta(), not autoloadable
        require_once ABSPATH . 'wp-admin/includes/upgrade.php'; // NOSONAR S4833

        $schemas = \FAWpmcp\Database\Schema::getAllSchemas($wpdb->prefix);

        foreach ($schemas as $sql) {
            dbDelta($sql);
        }
    }

    /**
     * Plugin deactivation handler.
     *
     * @return void
     */
    public function deactivate(): void
    {
        // Clean up webhook system.
        $webhook_service = $this->getService('webhook');
        if ($webhook_service instanceof \FAWpmcp\Webhooks\WebhookService) {
            $webhook_service->deactivate();
        }
    }

    /**
     * Plugin uninstall handler.
     *
     * Removes all plugin data if configured to do so.
     *
     * @return void
     */
    public function uninstall(): void
    {
        // Uninstall logic will be added in Phase 14.
    }

    /**
     * Prevent cloning.
     */
    private function __clone()
    {
        // Singleton - prevent cloning.
    }

    /**
     * Prevent unserialization.
     */
    public function __wakeup(): void
    {
        // Singleton - prevent unserialization.
    }
}
