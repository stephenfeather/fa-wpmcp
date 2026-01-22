<?php
/**
 * Main plugin orchestration class.
 *
 * @package FAWpmcp
 */

declare(strict_types=1);

namespace FAWpmcp;

/**
 * Main plugin orchestration class.
 *
 * This class handles plugin initialization, activation, deactivation,
 * and uninstallation. It follows the singleton pattern for WordPress
 * plugin architecture.
 *
 * @package FAWpmcp
 */
final class Plugin {
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
    private function __construct() {
        // Singleton - use getInstance().
    }

    /**
     * Get singleton instance.
     *
     * @return self
     */
    public static function getInstance(): self {
        if ( null === self::$instance ) {
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
    public function init(): void {
        $this->registerHooks();

        // Initialize webhook system.
        $webhook_service = new \FAWpmcp\Webhooks\WebhookService();
        $webhook_service->init();
        $this->registerService( 'webhook', $webhook_service );

        // Initialize Ability Framework.
        // 1. Create AbilityRegistry and register abilities.
        $ability_registry = new \FAWpmcp\Abilities\AbilityRegistry();
        $this->registerPostAbilities( $ability_registry );
        $this->registerCommentAbilities( $ability_registry );
        $this->registerMediaAbilities( $ability_registry );
        $this->registerTaxonomyAbilities( $ability_registry );
        $this->registerUserAbilities( $ability_registry );
        $this->registerService( 'ability_registry', $ability_registry );

        // Initialize Admin Settings Page.
        $settings_page = new \FAWpmcp\Admin\SettingsPage( $ability_registry );
        $settings_page->init();
        $this->registerService( 'settings_page', $settings_page );

        // 2. Create dependencies for AbilityExecutor.
        // Permission settings from WordPress options.
        $permission_settings = \FAWpmcp\Permissions\OptionsPermissionSettings::load();

        // Rate limiter with transient storage.
        $rate_limit_store  = new \FAWpmcp\RateLimiting\TransientRateLimitStore();
        $rate_limit_config = new \FAWpmcp\RateLimiting\OptionsRateLimitConfig();
        $rate_limiter      = new \FAWpmcp\RateLimiting\RateLimiter( $rate_limit_store, $rate_limit_config );
        $this->registerService( 'rate_limiter', $rate_limiter );

        // Activity logger with database repository.
        global $wpdb;
        $log_repository = new \FAWpmcp\Logging\LogRepository( $wpdb );
        $activity_logger = new \FAWpmcp\Logging\ActivityLogger(
            $log_repository,
            fn() => wp_generate_uuid4()
        );
        $this->registerService( 'activity_logger', $activity_logger );

        // Webhook manager from webhook service.
        $webhook_manager = $webhook_service->getManager();

        // 3. Create AbilityExecutor with all dependencies.
        $ability_executor = new \FAWpmcp\Abilities\AbilityExecutor(
            $permission_settings,
            $rate_limiter,
            $activity_logger,
            $webhook_manager
        );
        $this->registerService( 'ability_executor', $ability_executor );
    }

    /**
     * Register WordPress hooks.
     *
     * @return void
     */
    private function registerHooks(): void {
        add_action( 'admin_init', array( $this, 'checkAbilitiesApiAndShowNotice' ) );
    }

    /**
     * Register Post abilities with the registry.
     *
     * @param \FAWpmcp\Abilities\AbilityRegistry $registry Ability registry.
     * @return void
     */
    private function registerPostAbilities( \FAWpmcp\Abilities\AbilityRegistry $registry ): void {
        $registry->register( new \FAWpmcp\Abilities\Posts\GetPost() );
        $registry->register( new \FAWpmcp\Abilities\Posts\ListPosts() );
        $registry->register( new \FAWpmcp\Abilities\Posts\CreatePost() );
        $registry->register( new \FAWpmcp\Abilities\Posts\UpdatePost() );
    }

    /**
     * Register Comment abilities with the registry.
     *
     * @param \FAWpmcp\Abilities\AbilityRegistry $registry Ability registry.
     * @return void
     */
    private function registerCommentAbilities( \FAWpmcp\Abilities\AbilityRegistry $registry ): void {
        $registry->register( new \FAWpmcp\Abilities\Comments\GetComment() );
        $registry->register( new \FAWpmcp\Abilities\Comments\ListComments() );
        $registry->register( new \FAWpmcp\Abilities\Comments\CreateComment() );
        $registry->register( new \FAWpmcp\Abilities\Comments\UpdateComment() );
    }

    /**
     * Register Media abilities with the registry.
     *
     * @param \FAWpmcp\Abilities\AbilityRegistry $registry Ability registry.
     * @return void
     */
    private function registerMediaAbilities( \FAWpmcp\Abilities\AbilityRegistry $registry ): void {
        $registry->register( new \FAWpmcp\Abilities\Media\GetMedia() );
        $registry->register( new \FAWpmcp\Abilities\Media\ListMedia() );
        $registry->register( new \FAWpmcp\Abilities\Media\UploadMedia() );
        $registry->register( new \FAWpmcp\Abilities\Media\UpdateMedia() );
    }

    /**
     * Register Taxonomy abilities with the registry.
     *
     * @param \FAWpmcp\Abilities\AbilityRegistry $registry Ability registry.
     * @return void
     */
    private function registerTaxonomyAbilities( \FAWpmcp\Abilities\AbilityRegistry $registry ): void {
        $registry->register( new \FAWpmcp\Abilities\Taxonomies\GetTerm() );
        $registry->register( new \FAWpmcp\Abilities\Taxonomies\ListTerms() );
        $registry->register( new \FAWpmcp\Abilities\Taxonomies\CreateTerm() );
        $registry->register( new \FAWpmcp\Abilities\Taxonomies\UpdateTerm() );
    }

    /**
     * Register User abilities with the registry.
     *
     * @param \FAWpmcp\Abilities\AbilityRegistry $registry Ability registry.
     * @return void
     */
    private function registerUserAbilities( \FAWpmcp\Abilities\AbilityRegistry $registry ): void {
        $registry->register( new \FAWpmcp\Abilities\Users\GetUser() );
        $registry->register( new \FAWpmcp\Abilities\Users\ListUsers() );
        $registry->register( new \FAWpmcp\Abilities\Users\CreateUser() );
        $registry->register( new \FAWpmcp\Abilities\Users\UpdateUser() );
    }

    /**
     * Register a service in the container.
     *
     * @param string $name    Service name.
     * @param object $service Service instance.
     * @return void
     */
    public function registerService( string $name, object $service ): void {
        $this->services[ $name ] = $service;
    }

    /**
     * Get a service from the container.
     *
     * @param string $name Service name.
     * @return object|null Service instance or null if not found.
     */
    public function getService( string $name ): ?object {
        return $this->services[ $name ] ?? null;
    }

    /**
     * Check if WordPress Abilities API is available.
     *
     * @return bool True if Abilities API is available.
     */
    public function hasAbilitiesApi(): bool {
        return function_exists( 'wp_register_ability' );
    }

    /**
     * Check Abilities API availability and show admin notice if missing.
     *
     * @return void
     */
    public function checkAbilitiesApiAndShowNotice(): void {
        if ( ! $this->hasAbilitiesApi() ) {
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
    public function activate(): void {
        // Activation logic will be added in later phases.
    }

    /**
     * Plugin deactivation handler.
     *
     * @return void
     */
    public function deactivate(): void {
        // Clean up webhook system.
        $webhook_service = $this->getService( 'webhook' );
        if ( $webhook_service instanceof \FAWpmcp\Webhooks\WebhookService ) {
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
    public function uninstall(): void {
        // Uninstall logic will be added in Phase 14.
    }

    /**
     * Prevent cloning.
     */
    private function __clone() {
        // Singleton - prevent cloning.
    }

    /**
     * Prevent unserialization.
     */
    public function __wakeup(): void {
        // Singleton - prevent unserialization.
    }
}
