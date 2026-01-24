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
		error_log( 'FA WPMCP: Plugin::init() called' );
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
		$this->registerSettingsAbilities( $ability_registry );
		$this->registerPluginAbilities( $ability_registry );
		$this->registerThemeAbilities( $ability_registry );
		$this->registerPrivacyAbilities( $ability_registry );
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

		// 4. Register ability categories BEFORE registering abilities.
		add_action(
			'wp_abilities_api_categories_init',
			function () {
				error_log( 'FA WPMCP: wp_abilities_api_categories_init hook fired' );
				error_log( 'FA WPMCP: wp_register_ability_category function exists: ' . ( function_exists( 'wp_register_ability_category' ) ? 'yes' : 'no' ) );
				$this->registerAbilityCategories();
				error_log( 'FA WPMCP: Finished registering 9 categories' );

				// Verify categories were registered.
				$categories_registry = \WP_Ability_Categories_Registry::get_instance();
				if ( $categories_registry && method_exists( $categories_registry, 'is_registered' ) ) {
					error_log( 'FA WPMCP: Verifying category registration:' );
					error_log( 'FA WPMCP: - privacy: ' . ( $categories_registry->is_registered( 'privacy' ) ? 'YES' : 'NO' ) );
					error_log( 'FA WPMCP: - posts-pages: ' . ( $categories_registry->is_registered( 'posts-pages' ) ? 'YES' : 'NO' ) );
				}
			},
			5  // Priority 5 to run before McpAdapter's default priority 10.
		);

		// 5. Register all abilities with WordPress Abilities API.
		error_log( 'FA WPMCP: About to add wp_abilities_api_init hook. Registry has ' . count( $ability_registry->all() ) . ' abilities ready' );
		add_action(
			'wp_abilities_api_init',
			function () use ( $ability_registry, $ability_executor ) {
				error_log( 'FA WPMCP: wp_abilities_api_init hook fired. Registry has ' . count( $ability_registry->all() ) . ' abilities' );
				error_log( 'FA WPMCP: wp_register_ability function exists: ' . ( function_exists( 'wp_register_ability' ) ? 'yes' : 'no' ) );
				error_log( 'FA WPMCP: doing_action wp_abilities_api_init: ' . ( doing_action( 'wp_abilities_api_init' ) ? 'yes' : 'no' ) );

				// Verify categories are still registered before registering abilities.
				$categories_registry = \WP_Ability_Categories_Registry::get_instance();
				if ( $categories_registry && method_exists( $categories_registry, 'is_registered' ) ) {
					error_log( 'FA WPMCP: Before ability registration, checking categories:' );
					error_log( 'FA WPMCP: - privacy: ' . ( $categories_registry->is_registered( 'privacy' ) ? 'YES' : 'NO' ) );
				}

				$this->registerAbilitiesWithWordPress( $ability_registry, $ability_executor );
				error_log( 'FA WPMCP: Finished registering abilities with WordPress' );
			},
			15  // Priority 15 to run after other plugins (McpAdapter uses default 10).
		);

		// 6. Initialize MCP Adapter AFTER registering the hooks.
		// This ensures our categories and abilities are registered before the adapter fires the hooks.
		error_log( 'FA WPMCP: About to call McpAdapter::instance()' );
		McpAdapter::instance();
		error_log( 'FA WPMCP: McpAdapter::instance() completed' );
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
	 * Register ability categories with WordPress Abilities API.
	 *
	 * Categories must be registered before abilities can use them.
	 * This method is called on the 'wp_abilities_api_categories_init' hook.
	 *
	 * @return void
	 */
	private function registerAbilityCategories(): void {
		if ( ! function_exists( 'wp_register_ability_category' ) ) {
			return;
		}

		$categories = array(
			'posts-pages' => array(
				'label'       => __( 'Posts & Pages', 'fa-wpmcp' ),
				'description' => __( 'Abilities for managing WordPress posts, pages, and custom post types', 'fa-wpmcp' ),
			),
			'comments' => array(
				'label'       => __( 'Comments', 'fa-wpmcp' ),
				'description' => __( 'Abilities for managing WordPress comments and comment moderation', 'fa-wpmcp' ),
			),
			'media' => array(
				'label'       => __( 'Media Library', 'fa-wpmcp' ),
				'description' => __( 'Abilities for managing WordPress media files and attachments', 'fa-wpmcp' ),
			),
			'taxonomies' => array(
				'label'       => __( 'Taxonomies', 'fa-wpmcp' ),
				'description' => __( 'Abilities for managing WordPress terms, categories, and tags', 'fa-wpmcp' ),
			),
			'users' => array(
				'label'       => __( 'Users', 'fa-wpmcp' ),
				'description' => __( 'Abilities for managing WordPress user accounts and profiles', 'fa-wpmcp' ),
			),
			'settings' => array(
				'label'       => __( 'Settings', 'fa-wpmcp' ),
				'description' => __( 'Abilities for managing WordPress options and site configuration', 'fa-wpmcp' ),
			),
			'plugins' => array(
				'label'       => __( 'Plugins', 'fa-wpmcp' ),
				'description' => __( 'Abilities for managing WordPress plugin installation, activation, and updates', 'fa-wpmcp' ),
			),
			'themes' => array(
				'label'       => __( 'Themes', 'fa-wpmcp' ),
				'description' => __( 'Abilities for managing WordPress theme installation, activation, and updates', 'fa-wpmcp' ),
			),
			'privacy' => array(
				'label'       => __( 'Privacy', 'fa-wpmcp' ),
				'description' => __( 'Abilities for managing WordPress privacy requests (GDPR data export and erasure)', 'fa-wpmcp' ),
			),
		);

		foreach ( $categories as $slug => $args ) {
			wp_register_ability_category( $slug, $args );
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
		if ( ! function_exists( 'wp_register_ability' ) ) {
			return;
		}

		foreach ( $registry->all() as $ability ) {
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
						'annotations'   => $registration['annotations'],
						'show_in_rest'  => true,
						'mcp'           => array(
							'public' => true,
							'type'   => 'tool',
						),
					),
					'permission_callback' => function () use ( $registration ) {
						return current_user_can( $registration['requiredCapability'] );
					},
					'execute_callback'    => function ( array $input ) use ( $ability, $executor ) {
						$result = $executor->execute( $ability->getName(), $input );

						if ( ! $result->isSuccess() ) {
							return new \WP_Error(
								'ability_execution_failed',
								$result->getErrorMessage(),
								array( 'status' => $result->getHttpStatus() )
							);
						}

						return $result->getData();
					},
				)
			);
		}
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
	 * Register Settings abilities with the registry.
	 *
	 * @param \FAWpmcp\Abilities\AbilityRegistry $registry Ability registry.
	 * @return void
	 */
	private function registerSettingsAbilities( \FAWpmcp\Abilities\AbilityRegistry $registry ): void {
		$registry->register( new \FAWpmcp\Abilities\Settings\DeleteOption() );
		$registry->register( new \FAWpmcp\Abilities\Settings\GetOption() );
		$registry->register( new \FAWpmcp\Abilities\Settings\ListOptions() );
		$registry->register( new \FAWpmcp\Abilities\Settings\UpdateOption() );
	}

	/**
	 * Register Plugin abilities with the registry.
	 *
	 * @param \FAWpmcp\Abilities\AbilityRegistry $registry Ability registry.
	 * @return void
	 */
	private function registerPluginAbilities( \FAWpmcp\Abilities\AbilityRegistry $registry ): void {
		$registry->register( new \FAWpmcp\Abilities\Plugins\ActivatePlugin() );
		$registry->register( new \FAWpmcp\Abilities\Plugins\DeactivatePlugin() );
		$registry->register( new \FAWpmcp\Abilities\Plugins\DeletePlugin() );
		$registry->register( new \FAWpmcp\Abilities\Plugins\GetPlugin() );
		$registry->register( new \FAWpmcp\Abilities\Plugins\InstallPlugin() );
		$registry->register( new \FAWpmcp\Abilities\Plugins\ListPlugins() );
		$registry->register( new \FAWpmcp\Abilities\Plugins\UpdatePlugin() );
	}

	/**
	 * Register Theme abilities with the registry.
	 *
	 * @param \FAWpmcp\Abilities\AbilityRegistry $registry Ability registry.
	 * @return void
	 */
	private function registerThemeAbilities( \FAWpmcp\Abilities\AbilityRegistry $registry ): void {
		$registry->register( new \FAWpmcp\Abilities\Themes\ActivateTheme() );
		$registry->register( new \FAWpmcp\Abilities\Themes\DeleteTheme() );
		$registry->register( new \FAWpmcp\Abilities\Themes\GetTheme() );
		$registry->register( new \FAWpmcp\Abilities\Themes\InstallTheme() );
		$registry->register( new \FAWpmcp\Abilities\Themes\ListThemes() );
		$registry->register( new \FAWpmcp\Abilities\Themes\StatusTheme() );
		$registry->register( new \FAWpmcp\Abilities\Themes\UpdateTheme() );
	}

	/**
	 * Register Privacy abilities with the registry.
	 *
	 * @param \FAWpmcp\Abilities\AbilityRegistry $registry Ability registry.
	 * @return void
	 */
	private function registerPrivacyAbilities( \FAWpmcp\Abilities\AbilityRegistry $registry ): void {
		$registry->register( new \FAWpmcp\Abilities\Privacy\CreateErasureRequest() );
		$registry->register( new \FAWpmcp\Abilities\Privacy\CreateExportRequest() );
		$registry->register( new \FAWpmcp\Abilities\Privacy\GetPrivacyRequest() );
		$registry->register( new \FAWpmcp\Abilities\Privacy\ListPrivacyRequests() );
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
