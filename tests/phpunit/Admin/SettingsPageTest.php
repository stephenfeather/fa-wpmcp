<?php
/**
 * Tests for Admin SettingsPage functionality.
 *
 * @package FAWpmcp\Tests\Admin
 */

declare(strict_types=1);

namespace FAWpmcp\Tests\Admin;

use FAWpmcp\Admin\SettingsPage;
use FAWpmcp\Abilities\AbilityRegistry;
use FAWpmcp\Abilities\AbstractAbility;
use Brain\Monkey;
use Brain\Monkey\Functions;
use Mockery;
use PHPUnit\Framework\TestCase;

// Load Hamcrest matchers.
require_once __DIR__ . '/../../../vendor/hamcrest/hamcrest-php/hamcrest/Hamcrest.php';

/**
 * Test Admin SettingsPage functionality.
 *
 * Tests cover:
 * - Admin menu registration with add_menu_page()
 * - Settings form rendering with proper structure
 * - Permission toggle functionality (global, category, ability levels)
 * - Rate limit configuration UI
 * - Webhook configuration UI
 * - Nonce verification for settings save
 * - Capability checks (manage_options)
 * - Asset enqueuing (CSS/JS on plugin pages only)
 *
 * @package FAWpmcp\Tests\Admin
 */
class SettingsPageTest extends TestCase {
	/**
	 * Set up Brain\Monkey before each test.
	 *
	 * @return void
	 */
	protected function setUp(): void {
		parent::setUp();
		Monkey\setUp();
	}

	/**
	 * Tear down Brain\Monkey after each test.
	 *
	 * @return void
	 */
	protected function tearDown(): void {
		Monkey\tearDown();
		Mockery::close();
		parent::tearDown();
	}

	/**
	 * Create a registry with test abilities.
	 *
	 * @param array<array<string, mixed>> $abilities_config Array of ability configs.
	 * @return AbilityRegistry Registry with mock abilities.
	 */
	private function create_registry_with_abilities( array $abilities_config = array() ): AbilityRegistry {
		$registry = new AbilityRegistry();

		foreach ( $abilities_config as $config ) {
			$ability = $this->create_stub_ability( $config );
			$registry->register( $ability );
		}

		return $registry;
	}

	/**
	 * Create a stub ability for testing.
	 *
	 * @param array<string, mixed> $config Ability configuration.
	 * @return AbstractAbility Stub ability.
	 */
	private function create_stub_ability( array $config ): AbstractAbility {
		return new class( $config ) extends AbstractAbility {
			/**
			 * Ability configuration.
			 *
			 * @var array<string, mixed>
			 */
			private array $config;

			/**
			 * Constructor.
			 *
			 * @param array<string, mixed> $config Ability configuration.
			 */
			public function __construct( array $config ) {
				$this->config = $config;
			}

			/**
			 * Get ability name.
			 *
			 * @return string Ability name.
			 */
			public function getName(): string {
				return $this->config['name'] ?? 'test-ability';
			}

			/**
			 * Get ability category.
			 *
			 * @return string Category.
			 */
			public function getCategory(): string {
				return $this->config['category'] ?? 'test-category';
			}

			/**
			 * Get ability label.
			 *
			 * @return string Label.
			 */
			public function getLabel(): string {
				return $this->config['label'] ?? 'Test Ability';
			}

			/**
			 * Get ability description.
			 *
			 * @return string Description.
			 */
			public function getDescription(): string {
				return $this->config['description'] ?? 'Test description';
			}

			/**
			 * Get input schema.
			 *
			 * @return array<string, mixed> Schema.
			 */
			public function getInputSchema(): array {
				return $this->config['input_schema'] ?? array( 'type' => 'object' );
			}

			/**
			 * Get output schema.
			 *
			 * @return array<string, mixed> Schema.
			 */
			public function getOutputSchema(): array {
				return $this->config['output_schema'] ?? array( 'type' => 'object' );
			}

			/**
			 * Get required capability.
			 *
			 * @return string Capability.
			 */
			public function getRequiredCapability(): string {
				return $this->config['capability'] ?? 'read';
			}

			/**
			 * Get operation type.
			 *
			 * @return string Operation type.
			 */
			public function getOperationType(): string {
				return $this->config['operation'] ?? 'read';
			}

			/**
			 * Execute ability (stub).
			 *
			 * @param array<string, mixed> $input Input data.
			 * @return array<string, mixed> Output data.
			 */
			public function doExecute( array $input ): array {
				return array();
			}
		};
	}

	// =========================================================================
	// Admin Menu Registration Tests
	// =========================================================================

	/**
	 * Test registers admin menu with add_menu_page().
	 *
	 * @return void
	 */
	public function test_registers_admin_menu(): void {
		Functions\expect( 'add_menu_page' )
			->once()
			->withArgs(
				function (
					string $page_title,
					string $menu_title,
					string $capability,
					string $menu_slug,
					callable $callback,
					string $icon_url,
					?int $position
				): bool {
					return 'FA WPMCP' === $page_title
						&& 'FA WPMCP' === $menu_title
						&& 'manage_options' === $capability
						&& 'fa-wpmcp' === $menu_slug
						&& is_callable( $callback );
				}
			);

		Functions\expect( 'add_submenu_page' )
			->times( 3 );

		$registry = $this->create_registry_with_abilities();
		$settings_page = new SettingsPage( $registry );
		$settings_page->registerMenu();
	}

	/**
	 * Test menu slug is 'fa-wpmcp'.
	 *
	 * @return void
	 */
	public function test_menu_slug_is_fa_wpmcp(): void {
		$registry = $this->create_registry_with_abilities();
		$settings_page = new SettingsPage( $registry );

		$this->assertEquals( 'fa-wpmcp', $settings_page->getMenuSlug() );
	}

	/**
	 * Test capability requirement is 'manage_options'.
	 *
	 * @return void
	 */
	public function test_capability_is_manage_options(): void {
		$registry = $this->create_registry_with_abilities();
		$settings_page = new SettingsPage( $registry );

		$this->assertEquals( 'manage_options', $settings_page->getCapability() );
	}

	/**
	 * Test registers submenu pages for permissions, rate limits, and webhooks.
	 *
	 * @return void
	 */
	public function test_registers_submenu_pages(): void {
		Functions\expect( 'add_menu_page' )->once();

		Functions\expect( 'add_submenu_page' )
			->times( 3 )
			->withArgs(
				function (
					string $parent_slug,
					string $page_title,
					string $menu_title,
					string $capability,
					string $menu_slug,
					callable $callback
				): bool {
					return 'fa-wpmcp' === $parent_slug
						&& 'manage_options' === $capability
						&& in_array(
							$menu_slug,
							array( 'fa-wpmcp-permissions', 'fa-wpmcp-rate-limits', 'fa-wpmcp-webhooks' ),
							true
						);
				}
			);

		$registry = $this->create_registry_with_abilities();
		$settings_page = new SettingsPage( $registry );
		$settings_page->registerMenu();
	}

	// =========================================================================
	// Settings Form Rendering Tests
	// =========================================================================

	/**
	 * Test renders settings form with proper structure.
	 *
	 * @return void
	 */
	public function test_renders_settings_form(): void {
		Functions\expect( 'current_user_can' )
			->once()
			->with( 'manage_options' )
			->andReturn( true );

		Functions\expect( 'get_option' )
			->andReturn( array() );

		Functions\expect( 'wp_nonce_field' )
			->once()
			->with( 'fa_wpmcp_settings', 'fa_wpmcp_nonce', true, false )
			->andReturn( '<input type="hidden" name="fa_wpmcp_nonce" value="test-nonce" />' );

		Functions\expect( 'esc_attr' )
			->andReturnFirstArg();

		Functions\expect( 'esc_html' )
			->andReturnFirstArg();

		Functions\expect( 'admin_url' )
			->andReturn( 'http://example.com/wp-admin/admin-post.php' );

		$registry = $this->create_registry_with_abilities();

		$settings_page = new SettingsPage( $registry );
		$output = $settings_page->renderSettingsPage();

		$this->assertStringContainsString( '<form', $output );
		$this->assertStringContainsString( 'method="post"', $output );
		$this->assertStringContainsString( 'fa_wpmcp_nonce', $output );
	}

	/**
	 * Test form contains nonce field.
	 *
	 * @return void
	 */
	public function test_form_contains_nonce_field(): void {
		Functions\expect( 'current_user_can' )
			->with( 'manage_options' )
			->andReturn( true );

		Functions\expect( 'get_option' )
			->andReturn( array() );

		Functions\expect( 'wp_nonce_field' )
			->once()
			->with( 'fa_wpmcp_settings', 'fa_wpmcp_nonce', true, false )
			->andReturn( '<input type="hidden" name="fa_wpmcp_nonce" value="abc123" />' );

		Functions\expect( 'esc_attr' )
			->andReturnFirstArg();

		Functions\expect( 'esc_html' )
			->andReturnFirstArg();

		Functions\expect( 'admin_url' )
			->andReturn( 'http://example.com/wp-admin/admin-post.php' );

		$registry = $this->create_registry_with_abilities();

		$settings_page = new SettingsPage( $registry );
		$output = $settings_page->renderSettingsPage();

		$this->assertStringContainsString( 'fa_wpmcp_nonce', $output );
	}

	/**
	 * Test form action points to correct endpoint.
	 *
	 * @return void
	 */
	public function test_form_action_points_to_correct_endpoint(): void {
		Functions\expect( 'current_user_can' )
			->with( 'manage_options' )
			->andReturn( true );

		Functions\expect( 'get_option' )
			->andReturn( array() );

		Functions\expect( 'wp_nonce_field' )
			->andReturn( '' );

		Functions\expect( 'esc_attr' )
			->andReturnFirstArg();

		Functions\expect( 'esc_html' )
			->andReturnFirstArg();

		Functions\expect( 'admin_url' )
			->once()
			->with( 'admin-post.php' )
			->andReturn( 'http://example.com/wp-admin/admin-post.php' );

		$registry = $this->create_registry_with_abilities();

		$settings_page = new SettingsPage( $registry );
		$output = $settings_page->renderSettingsPage();

		$this->assertStringContainsString( 'action="http://example.com/wp-admin/admin-post.php"', $output );
		$this->assertStringContainsString( 'name="action" value="fa_wpmcp_save_settings"', $output );
	}

	// =========================================================================
	// Permission Toggle Tests
	// =========================================================================

	/**
	 * Test renders global permission toggles (read/write).
	 *
	 * @return void
	 */
	public function test_renders_global_permission_toggles(): void {
		Functions\expect( 'current_user_can' )
			->with( 'manage_options' )
			->andReturn( true );

		Functions\expect( 'get_option' )
			->andReturn(
				array(
					'global_read_enabled'  => true,
					'global_write_enabled' => false,
				)
			);

		Functions\expect( 'wp_nonce_field' )
			->andReturn( '' );

		Functions\expect( 'esc_attr' )
			->andReturnFirstArg();

		Functions\expect( 'esc_html' )
			->andReturnFirstArg();

		Functions\expect( 'checked' )
			->andReturnUsing(
				function ( $checked, $current, $echo ) {
					return $checked === $current ? 'checked="checked"' : '';
				}
			);

		Functions\expect( 'admin_url' )
			->andReturn( 'http://example.com/wp-admin/admin-post.php' );

		$registry = $this->create_registry_with_abilities();

		$settings_page = new SettingsPage( $registry );
		$output = $settings_page->renderPermissionsPage();

		$this->assertStringContainsString( 'global_read_enabled', $output );
		$this->assertStringContainsString( 'global_write_enabled', $output );
		$this->assertStringContainsString( 'type="checkbox"', $output );
	}

	/**
	 * Test renders category-level permission toggles.
	 *
	 * @return void
	 */
	public function test_renders_category_permission_toggles(): void {
		Functions\expect( 'current_user_can' )
			->with( 'manage_options' )
			->andReturn( true );

		Functions\expect( 'get_option' )
			->andReturn(
				array(
					'global_read_enabled'  => true,
					'global_write_enabled' => true,
					'category_settings'    => array(
						'posts-pages' => array(
							'enable_read'  => true,
							'enable_write' => false,
						),
					),
				)
			);

		Functions\expect( 'wp_nonce_field' )
			->andReturn( '' );

		Functions\expect( 'esc_attr' )
			->andReturnFirstArg();

		Functions\expect( 'esc_html' )
			->andReturnFirstArg();

		Functions\expect( 'checked' )
			->andReturnUsing(
				function ( $checked, $current, $echo ) {
					return $checked === $current ? 'checked="checked"' : '';
				}
			);

		Functions\expect( 'admin_url' )
			->andReturn( 'http://example.com/wp-admin/admin-post.php' );

		// Create registry with abilities in different categories.
		$registry = $this->create_registry_with_abilities(
			array(
				array(
					'name'     => 'fa-wpmcp/list-posts',
					'category' => 'posts-pages',
				),
				array(
					'name'     => 'fa-wpmcp/upload-media',
					'category' => 'media',
				),
				array(
					'name'     => 'fa-wpmcp/list-users',
					'category' => 'users',
				),
			)
		);

		$settings_page = new SettingsPage( $registry );
		$output = $settings_page->renderPermissionsPage();

		$this->assertStringContainsString( 'category_settings[posts-pages]', $output );
		$this->assertStringContainsString( 'enable_read', $output );
		$this->assertStringContainsString( 'enable_write', $output );
	}

	/**
	 * Test renders ability-level permission toggles.
	 *
	 * @return void
	 */
	public function test_renders_ability_permission_toggles(): void {
		Functions\expect( 'current_user_can' )
			->with( 'manage_options' )
			->andReturn( true );

		Functions\expect( 'get_option' )
			->andReturn(
				array(
					'global_read_enabled'  => true,
					'global_write_enabled' => true,
					'ability_settings'     => array(
						'fa-wpmcp/create-post' => array( 'enabled' => false ),
					),
				)
			);

		Functions\expect( 'wp_nonce_field' )
			->andReturn( '' );

		Functions\expect( 'esc_attr' )
			->andReturnFirstArg();

		Functions\expect( 'esc_html' )
			->andReturnFirstArg();

		Functions\expect( 'checked' )
			->andReturnUsing(
				function ( $checked, $current, $echo ) {
					return $checked === $current ? 'checked="checked"' : '';
				}
			);

		Functions\expect( 'admin_url' )
			->andReturn( 'http://example.com/wp-admin/admin-post.php' );

		$registry = $this->create_registry_with_abilities(
			array(
				array(
					'name'     => 'fa-wpmcp/create-post',
					'category' => 'posts-pages',
					'label'    => 'Create Post',
				),
			)
		);

		$settings_page = new SettingsPage( $registry );
		$output = $settings_page->renderPermissionsPage();

		$this->assertStringContainsString( 'ability_settings[fa-wpmcp/create-post]', $output );
		$this->assertStringContainsString( 'Create Post', $output );
	}

	/**
	 * Test default permission values are loaded correctly.
	 *
	 * @return void
	 */
	public function test_default_permission_values_loaded(): void {
		Functions\expect( 'current_user_can' )
			->with( 'manage_options' )
			->andReturn( true );

		// Return empty array to trigger defaults.
		Functions\expect( 'get_option' )
			->andReturn(
				array(
					'global_read_enabled'  => true,
					'global_write_enabled' => false,
					'category_settings'    => array(),
					'ability_settings'     => array(),
				)
			);

		Functions\expect( 'wp_nonce_field' )
			->andReturn( '' );

		Functions\expect( 'esc_attr' )
			->andReturnFirstArg();

		Functions\expect( 'esc_html' )
			->andReturnFirstArg();

		Functions\expect( 'checked' )
			->andReturnUsing(
				function ( $checked, $current, $echo ) {
					return $checked === $current ? 'checked="checked"' : '';
				}
			);

		Functions\expect( 'admin_url' )
			->andReturn( 'http://example.com/wp-admin/admin-post.php' );

		$registry = $this->create_registry_with_abilities();

		$settings_page = new SettingsPage( $registry );
		$output = $settings_page->renderPermissionsPage();

		// Global read should be checked by default.
		$this->assertStringContainsString( 'global_read_enabled', $output );
	}

	// =========================================================================
	// Rate Limit Configuration UI Tests
	// =========================================================================

	/**
	 * Test renders rate limit input fields (requests per minute, per hour).
	 *
	 * @return void
	 */
	public function test_renders_rate_limit_input_fields(): void {
		Functions\expect( 'current_user_can' )
			->with( 'manage_options' )
			->andReturn( true );

		Functions\expect( 'get_option' )
			->andReturn(
				array(
					'default_requests_per_minute' => 60,
					'default_requests_per_hour'   => 500,
					'ability_rate_limits'         => array(),
				)
			);

		Functions\expect( 'wp_nonce_field' )
			->andReturn( '' );

		Functions\expect( 'esc_attr' )
			->andReturnFirstArg();

		Functions\expect( 'esc_html' )
			->andReturnFirstArg();

		Functions\expect( 'admin_url' )
			->andReturn( 'http://example.com/wp-admin/admin-post.php' );

		$registry = $this->create_registry_with_abilities();

		$settings_page = new SettingsPage( $registry );
		$output = $settings_page->renderRateLimitsPage();

		$this->assertStringContainsString( 'default_requests_per_minute', $output );
		$this->assertStringContainsString( 'default_requests_per_hour', $output );
		$this->assertStringContainsString( 'type="number"', $output );
	}

	/**
	 * Test renders per-ability rate limit overrides.
	 *
	 * @return void
	 */
	public function test_renders_per_ability_rate_limits(): void {
		Functions\expect( 'current_user_can' )
			->with( 'manage_options' )
			->andReturn( true );

		Functions\expect( 'get_option' )
			->andReturn(
				array(
					'default_requests_per_minute' => 60,
					'default_requests_per_hour'   => 500,
					'ability_rate_limits'         => array(
						'fa-wpmcp/create-post' => array(
							'requests_per_minute' => 10,
							'requests_per_hour'   => 100,
						),
					),
				)
			);

		Functions\expect( 'wp_nonce_field' )
			->andReturn( '' );

		Functions\expect( 'esc_attr' )
			->andReturnFirstArg();

		Functions\expect( 'esc_html' )
			->andReturnFirstArg();

		Functions\expect( 'admin_url' )
			->andReturn( 'http://example.com/wp-admin/admin-post.php' );

		$registry = $this->create_registry_with_abilities(
			array(
				array(
					'name'  => 'fa-wpmcp/create-post',
					'label' => 'Create Post',
				),
			)
		);

		$settings_page = new SettingsPage( $registry );
		$output = $settings_page->renderRateLimitsPage();

		$this->assertStringContainsString( 'ability_rate_limits[fa-wpmcp/create-post]', $output );
		$this->assertStringContainsString( 'requests_per_minute', $output );
		$this->assertStringContainsString( 'requests_per_hour', $output );
	}

	/**
	 * Test default rate limits are displayed.
	 *
	 * @return void
	 */
	public function test_default_rate_limits_displayed(): void {
		Functions\expect( 'current_user_can' )
			->with( 'manage_options' )
			->andReturn( true );

		Functions\expect( 'get_option' )
			->andReturn(
				array(
					'default_requests_per_minute' => 60,
					'default_requests_per_hour'   => 500,
					'ability_rate_limits'         => array(),
				)
			);

		Functions\expect( 'wp_nonce_field' )
			->andReturn( '' );

		Functions\expect( 'esc_attr' )
			->andReturnFirstArg();

		Functions\expect( 'esc_html' )
			->andReturnFirstArg();

		Functions\expect( 'admin_url' )
			->andReturn( 'http://example.com/wp-admin/admin-post.php' );

		$registry = $this->create_registry_with_abilities();

		$settings_page = new SettingsPage( $registry );
		$output = $settings_page->renderRateLimitsPage();

		$this->assertStringContainsString( 'value="60"', $output );
		$this->assertStringContainsString( 'value="500"', $output );
	}

	// =========================================================================
	// Webhook Configuration UI Tests
	// =========================================================================

	/**
	 * Test renders webhook endpoint URL input.
	 *
	 * @return void
	 */
	public function test_renders_webhook_endpoint_url_input(): void {
		Functions\expect( 'current_user_can' )
			->with( 'manage_options' )
			->andReturn( true );

		// Mock separate get_option calls for webhook secret and settings.
		Functions\expect( 'get_option' )
			->with( 'fa_wpmcp_webhook_secret', '' )
			->andReturn( 'test-secret-key' );

		Functions\expect( 'get_option' )
			->with( 'fa_wpmcp_webhooks', array() )
			->andReturn(
				array(
					'webhook_endpoints' => array(
						array(
							'url'    => 'https://example.com/webhook',
							'events' => array( 'ability.after_execute' ),
						),
					),
				)
			);

		Functions\expect( 'wp_nonce_field' )
			->andReturn( '' );

		Functions\expect( 'esc_attr' )
			->andReturnFirstArg();

		Functions\expect( 'esc_html' )
			->andReturnFirstArg();

		Functions\expect( 'esc_url' )
			->andReturnFirstArg();

		Functions\expect( 'checked' )
			->andReturnUsing(
				function ( $checked, $current, $echo ) {
					return $checked === $current ? 'checked="checked"' : '';
				}
			);

		Functions\expect( 'admin_url' )
			->andReturn( 'http://example.com/wp-admin/admin-post.php' );

		$registry = $this->create_registry_with_abilities();

		$settings_page = new SettingsPage( $registry );
		$output = $settings_page->renderWebhooksPage();

		$this->assertStringContainsString( 'webhook_endpoints', $output );
		$this->assertStringContainsString( 'type="url"', $output );
		$this->assertStringContainsString( 'https://example.com/webhook', $output );
	}

	/**
	 * Test renders webhook secret input.
	 *
	 * @return void
	 */
	public function test_renders_webhook_secret_input(): void {
		Functions\expect( 'current_user_can' )
			->with( 'manage_options' )
			->andReturn( true );

		// Mock separate get_option calls for webhook secret and settings.
		Functions\expect( 'get_option' )
			->with( 'fa_wpmcp_webhook_secret', '' )
			->andReturn( 'existing-secret-key' );

		Functions\expect( 'get_option' )
			->with( 'fa_wpmcp_webhooks', array() )
			->andReturn(
				array(
					'webhook_endpoints' => array(),
				)
			);

		Functions\expect( 'wp_nonce_field' )
			->andReturn( '' );

		Functions\expect( 'esc_attr' )
			->andReturnFirstArg();

		Functions\expect( 'esc_html' )
			->andReturnFirstArg();

		Functions\expect( 'esc_url' )
			->andReturnFirstArg();

		Functions\expect( 'checked' )
			->andReturnUsing(
				function ( $checked, $current, $echo ) {
					return $checked === $current ? 'checked="checked"' : '';
				}
			);

		Functions\expect( 'admin_url' )
			->andReturn( 'http://example.com/wp-admin/admin-post.php' );

		$registry = $this->create_registry_with_abilities();

		$settings_page = new SettingsPage( $registry );
		$output = $settings_page->renderWebhooksPage();

		$this->assertStringContainsString( 'webhook_secret', $output );
		$this->assertStringContainsString( 'type="password"', $output );
	}

	/**
	 * Test renders webhook event subscriptions.
	 *
	 * @return void
	 */
	public function test_renders_webhook_event_subscriptions(): void {
		Functions\expect( 'current_user_can' )
			->with( 'manage_options' )
			->andReturn( true );

		// Mock separate get_option calls for webhook secret and settings.
		Functions\expect( 'get_option' )
			->with( 'fa_wpmcp_webhook_secret', '' )
			->andReturn( 'test-secret' );

		Functions\expect( 'get_option' )
			->with( 'fa_wpmcp_webhooks', array() )
			->andReturn(
				array(
					'webhook_endpoints' => array(
						array(
							'url'    => 'https://example.com/webhook',
							'events' => array( 'ability.before_execute', 'ability.after_execute' ),
						),
					),
				)
			);

		Functions\expect( 'wp_nonce_field' )
			->andReturn( '' );

		Functions\expect( 'esc_attr' )
			->andReturnFirstArg();

		Functions\expect( 'esc_html' )
			->andReturnFirstArg();

		Functions\expect( 'esc_url' )
			->andReturnFirstArg();

		Functions\expect( 'checked' )
			->andReturnUsing(
				function ( $checked, $current, $echo ) {
					return $checked === $current ? 'checked="checked"' : '';
				}
			);

		Functions\expect( 'admin_url' )
			->andReturn( 'http://example.com/wp-admin/admin-post.php' );

		$registry = $this->create_registry_with_abilities();

		$settings_page = new SettingsPage( $registry );
		$output = $settings_page->renderWebhooksPage();

		$this->assertStringContainsString( 'ability.before_execute', $output );
		$this->assertStringContainsString( 'ability.after_execute', $output );
		$this->assertStringContainsString( '[events][]', $output );
	}

	// =========================================================================
	// Nonce Verification Tests
	// =========================================================================

	/**
	 * Test verifies nonce on settings save.
	 *
	 * @return void
	 */
	public function test_verifies_nonce_on_settings_save(): void {
		$_POST['fa_wpmcp_nonce'] = 'valid-nonce';
		$_POST['action']         = 'fa_wpmcp_save_settings';

		Functions\expect( 'wp_unslash' )
			->andReturnFirstArg();

		Functions\expect( 'wp_verify_nonce' )
			->once()
			->with( 'valid-nonce', 'fa_wpmcp_settings' )
			->andReturn( 1 );

		Functions\expect( 'current_user_can' )
			->with( 'manage_options' )
			->andReturn( true );

		Functions\expect( 'sanitize_text_field' )
			->andReturnFirstArg();

		Functions\expect( 'update_option' )
			->once();

		Functions\expect( 'admin_url' )
			->andReturn( 'http://example.com/wp-admin/admin.php' );

		Functions\expect( 'add_query_arg' )
			->andReturn( 'http://example.com/wp-admin/admin.php?page=fa-wpmcp&settings-updated=true' );

		Functions\expect( 'wp_safe_redirect' )
			->once();

		$registry = $this->create_registry_with_abilities();
		$settings_page = new SettingsPage( $registry );

		$settings_page->handleSettingsSave();

		// Clean up.
		unset( $_POST['fa_wpmcp_nonce'], $_POST['action'] );
	}

	/**
	 * Test rejects save without valid nonce.
	 *
	 * @return void
	 */
	public function test_rejects_save_without_valid_nonce(): void {
		$_POST['fa_wpmcp_nonce'] = 'invalid-nonce';
		$_POST['action']         = 'fa_wpmcp_save_settings';

		Functions\expect( 'wp_unslash' )
			->andReturnFirstArg();

		Functions\expect( 'sanitize_text_field' )
			->andReturnFirstArg();

		Functions\expect( 'wp_verify_nonce' )
			->once()
			->with( 'invalid-nonce', 'fa_wpmcp_settings' )
			->andReturn( false );

		Functions\expect( 'wp_die' )
			->once()
			->with( containsString( 'Security check failed' ), Mockery::any(), Mockery::any() );

		$registry = $this->create_registry_with_abilities();
		$settings_page = new SettingsPage( $registry );

		$settings_page->handleSettingsSave();

		// Clean up.
		unset( $_POST['fa_wpmcp_nonce'], $_POST['action'] );
	}

	/**
	 * Test uses wp_verify_nonce() correctly.
	 *
	 * @return void
	 */
	public function test_uses_wp_verify_nonce_correctly(): void {
		$_POST['fa_wpmcp_nonce'] = 'test-nonce-value';
		$_POST['action']         = 'fa_wpmcp_save_settings';

		Functions\expect( 'wp_unslash' )
			->andReturnFirstArg();

		Functions\expect( 'wp_verify_nonce' )
			->once()
			->with( 'test-nonce-value', 'fa_wpmcp_settings' )
			->andReturn( 1 );

		Functions\expect( 'current_user_can' )
			->with( 'manage_options' )
			->andReturn( true );

		Functions\expect( 'sanitize_text_field' )
			->andReturnFirstArg();

		Functions\expect( 'update_option' )
			->once();

		Functions\expect( 'admin_url' )
			->andReturn( 'http://example.com/wp-admin/admin.php' );

		Functions\expect( 'add_query_arg' )
			->andReturn( 'http://example.com/wp-admin/admin.php?page=fa-wpmcp&settings-updated=true' );

		Functions\expect( 'wp_safe_redirect' )
			->once();

		$registry = $this->create_registry_with_abilities();
		$settings_page = new SettingsPage( $registry );

		$settings_page->handleSettingsSave();

		// Clean up.
		unset( $_POST['fa_wpmcp_nonce'], $_POST['action'] );
	}

	// =========================================================================
	// Capability Check Tests
	// =========================================================================

	/**
	 * Test requires 'manage_options' capability.
	 *
	 * @return void
	 */
	public function test_requires_manage_options_capability(): void {
		Functions\expect( 'current_user_can' )
			->once()
			->with( 'manage_options' )
			->andReturn( true );

		Functions\expect( 'get_option' )
			->andReturn( array() );

		Functions\expect( 'wp_nonce_field' )
			->andReturn( '' );

		Functions\expect( 'esc_attr' )
			->andReturnFirstArg();

		Functions\expect( 'esc_html' )
			->andReturnFirstArg();

		Functions\expect( 'admin_url' )
			->andReturn( 'http://example.com/wp-admin/admin-post.php' );

		$registry = $this->create_registry_with_abilities();

		$settings_page = new SettingsPage( $registry );
		$output = $settings_page->renderSettingsPage();

		$this->assertNotEmpty( $output );
	}

	/**
	 * Test blocks access without capability.
	 *
	 * @return void
	 */
	public function test_blocks_access_without_capability(): void {
		Functions\expect( 'current_user_can' )
			->once()
			->with( 'manage_options' )
			->andReturn( false );

		Functions\expect( 'wp_die' )
			->once()
			->with(
				containsString( 'permission' ),
				Mockery::any(),
				Mockery::on(
					function ( $args ) {
						return 403 === $args['response'];
					}
				)
			);

		$registry = $this->create_registry_with_abilities();
		$settings_page = new SettingsPage( $registry );

		$settings_page->renderSettingsPage();
	}

	/**
	 * Test uses current_user_can() correctly.
	 *
	 * @return void
	 */
	public function test_uses_current_user_can_correctly(): void {
		Functions\expect( 'current_user_can' )
			->once()
			->with( 'manage_options' )
			->andReturn( true );

		Functions\expect( 'get_option' )
			->andReturn( array() );

		Functions\expect( 'wp_nonce_field' )
			->andReturn( '' );

		Functions\expect( 'esc_attr' )
			->andReturnFirstArg();

		Functions\expect( 'esc_html' )
			->andReturnFirstArg();

		Functions\expect( 'admin_url' )
			->andReturn( 'http://example.com/wp-admin/admin-post.php' );

		$registry = $this->create_registry_with_abilities();

		$settings_page = new SettingsPage( $registry );
		$settings_page->renderSettingsPage();

		// Verify current_user_can was called with correct argument.
		$this->assertTrue( true ); // Brain\Monkey expectations verify this.
	}

	/**
	 * Test handle_settings_save blocks without capability.
	 *
	 * @return void
	 */
	public function test_handle_settings_save_blocks_without_capability(): void {
		$_POST['fa_wpmcp_nonce'] = 'valid-nonce';
		$_POST['action']         = 'fa_wpmcp_save_settings';

		Functions\expect( 'wp_unslash' )
			->andReturnFirstArg();

		Functions\expect( 'sanitize_text_field' )
			->andReturnFirstArg();

		Functions\expect( 'wp_verify_nonce' )
			->once()
			->andReturn( 1 );

		Functions\expect( 'current_user_can' )
			->once()
			->with( 'manage_options' )
			->andReturn( false );

		Functions\expect( 'wp_die' )
			->once()
			->with( containsString( 'permission' ), Mockery::any(), Mockery::any() );

		$registry = $this->create_registry_with_abilities();
		$settings_page = new SettingsPage( $registry );

		$settings_page->handleSettingsSave();

		// Clean up.
		unset( $_POST['fa_wpmcp_nonce'], $_POST['action'] );
	}

	// =========================================================================
	// Asset Enqueuing Tests
	// =========================================================================

	/**
	 * Test enqueues CSS only on plugin pages.
	 *
	 * @return void
	 */
	public function test_enqueues_css_only_on_plugin_pages(): void {
		Functions\expect( 'wp_enqueue_style' )
			->once()
			->with(
				'fa-wpmcp-admin',
				containsString( 'admin.css' ),
				array(),
				FA_WPMCP_VERSION
			);

		Functions\expect( 'wp_enqueue_script' )
			->once();

		$registry = $this->create_registry_with_abilities();
		$settings_page = new SettingsPage( $registry );

		// Simulate being on plugin page.
		$settings_page->enqueueAssets( 'toplevel_page_fa-wpmcp' );
	}

	/**
	 * Test enqueues JS only on plugin pages.
	 *
	 * @return void
	 */
	public function test_enqueues_js_only_on_plugin_pages(): void {
		Functions\expect( 'wp_enqueue_style' )
			->once();

		Functions\expect( 'wp_enqueue_script' )
			->once()
			->with(
				'fa-wpmcp-admin',
				containsString( 'admin.js' ),
				array( 'jquery' ),
				FA_WPMCP_VERSION,
				true
			);

		$registry = $this->create_registry_with_abilities();
		$settings_page = new SettingsPage( $registry );

		// Simulate being on plugin page.
		$settings_page->enqueueAssets( 'toplevel_page_fa-wpmcp' );
	}

	/**
	 * Test does not enqueue on other admin pages.
	 *
	 * @return void
	 */
	public function test_does_not_enqueue_on_other_admin_pages(): void {
		Functions\expect( 'wp_enqueue_style' )
			->never();

		Functions\expect( 'wp_enqueue_script' )
			->never();

		$registry = $this->create_registry_with_abilities();
		$settings_page = new SettingsPage( $registry );

		// Simulate being on a different admin page.
		$settings_page->enqueueAssets( 'edit.php' );
		$settings_page->enqueueAssets( 'plugins.php' );
		$settings_page->enqueueAssets( 'options-general.php' );
	}

	/**
	 * Test enqueues assets on submenu pages.
	 *
	 * @return void
	 */
	public function test_enqueues_assets_on_submenu_pages(): void {
		Functions\expect( 'wp_enqueue_style' )
			->times( 3 );

		Functions\expect( 'wp_enqueue_script' )
			->times( 3 );

		$registry = $this->create_registry_with_abilities();
		$settings_page = new SettingsPage( $registry );

		// Simulate being on plugin subpages.
		$settings_page->enqueueAssets( 'fa-wpmcp_page_fa-wpmcp-permissions' );
		$settings_page->enqueueAssets( 'fa-wpmcp_page_fa-wpmcp-rate-limits' );
		$settings_page->enqueueAssets( 'fa-wpmcp_page_fa-wpmcp-webhooks' );
	}

	// =========================================================================
	// Settings Sanitization Tests
	// =========================================================================

	/**
	 * Test sanitizes permission settings on save.
	 *
	 * @return void
	 */
	public function test_sanitizes_permission_settings_on_save(): void {
		$_POST['fa_wpmcp_nonce']        = 'valid-nonce';
		$_POST['action']                = 'fa_wpmcp_save_permissions';
		$_POST['global_read_enabled']   = '1';
		$_POST['global_write_enabled']  = '0';
		$_POST['category_settings']     = array(
			'posts-pages' => array(
				'enable_read'  => '1',
				'enable_write' => '1',
			),
		);

		Functions\expect( 'wp_unslash' )
			->andReturnFirstArg();

		Functions\expect( 'wp_verify_nonce' )
			->andReturn( 1 );

		Functions\expect( 'current_user_can' )
			->with( 'manage_options' )
			->andReturn( true );

		Functions\expect( 'sanitize_text_field' )
			->andReturnFirstArg();

		Functions\expect( 'map_deep' )
			->andReturnFirstArg();

		Functions\expect( 'update_option' )
			->once()
			->withArgs(
				function ( $option_name, $value ) {
					return 'fa_wpmcp_permissions' === $option_name
						&& true === $value['global_read_enabled']
						&& false === $value['global_write_enabled']
						&& is_array( $value['category_settings'] );
				}
			);

		Functions\expect( 'admin_url' )
			->andReturn( 'http://example.com/wp-admin/admin.php' );

		Functions\expect( 'add_query_arg' )
			->andReturn( 'http://example.com/wp-admin/admin.php?page=fa-wpmcp-permissions&settings-updated=true' );

		Functions\expect( 'wp_safe_redirect' )
			->once();

		$registry = $this->create_registry_with_abilities();
		$settings_page = new SettingsPage( $registry );

		$settings_page->handlePermissionsSave();

		// Clean up.
		unset( $_POST['fa_wpmcp_nonce'], $_POST['action'], $_POST['global_read_enabled'], $_POST['global_write_enabled'], $_POST['category_settings'] );
	}

	/**
	 * Test sanitizes rate limit settings on save.
	 *
	 * @return void
	 */
	public function test_sanitizes_rate_limit_settings_on_save(): void {
		$_POST['fa_wpmcp_nonce']              = 'valid-nonce';
		$_POST['action']                      = 'fa_wpmcp_save_rate_limits';
		$_POST['default_requests_per_minute'] = '100';
		$_POST['default_requests_per_hour']   = '1000';

		Functions\expect( 'wp_unslash' )
			->andReturnFirstArg();

		Functions\expect( 'wp_verify_nonce' )
			->andReturn( 1 );

		Functions\expect( 'current_user_can' )
			->with( 'manage_options' )
			->andReturn( true );

		Functions\expect( 'sanitize_text_field' )
			->andReturnFirstArg();

		Functions\expect( 'absint' )
			->andReturnUsing(
				function ( $value ) {
					return abs( (int) $value );
				}
			);

		Functions\expect( 'update_option' )
			->once()
			->withArgs(
				function ( $option_name, $value ) {
					return 'fa_wpmcp_rate_limits' === $option_name
						&& 100 === $value['default_requests_per_minute']
						&& 1000 === $value['default_requests_per_hour'];
				}
			);

		Functions\expect( 'admin_url' )
			->andReturn( 'http://example.com/wp-admin/admin.php' );

		Functions\expect( 'add_query_arg' )
			->andReturn( 'http://example.com/wp-admin/admin.php?page=fa-wpmcp-rate-limits&settings-updated=true' );

		Functions\expect( 'wp_safe_redirect' )
			->once();

		$registry = $this->create_registry_with_abilities();
		$settings_page = new SettingsPage( $registry );

		$settings_page->handleRateLimitsSave();

		// Clean up.
		unset( $_POST['fa_wpmcp_nonce'], $_POST['action'], $_POST['default_requests_per_minute'], $_POST['default_requests_per_hour'] );
	}

	/**
	 * Test sanitizes webhook URL on save.
	 *
	 * @return void
	 */
	public function test_sanitizes_webhook_url_on_save(): void {
		// Define WordPress salt constants if not already defined (required for encryption).
		if ( ! defined( 'SECURE_AUTH_KEY' ) ) {
			define( 'SECURE_AUTH_KEY', 'test-secure-auth-key-for-phpunit-testing-purposes-only' );
		}
		if ( ! defined( 'LOGGED_IN_KEY' ) ) {
			define( 'LOGGED_IN_KEY', 'test-logged-in-key-for-phpunit-testing-purposes-only' );
		}
		if ( ! defined( 'NONCE_SALT' ) ) {
			define( 'NONCE_SALT', 'test-nonce-salt-for-phpunit-testing-purposes-only' );
		}

		$_POST['fa_wpmcp_nonce']     = 'valid-nonce';
		$_POST['action']             = 'fa_wpmcp_save_webhooks';
		$_POST['webhook_endpoints']  = array(
			array(
				'url'    => 'https://example.com/webhook<script>',
				'events' => array( 'ability.after_execute' ),
			),
		);
		$_POST['webhook_secret']     = 'my-secret-key';

		Functions\expect( 'wp_unslash' )
			->andReturnFirstArg();

		Functions\expect( 'wp_verify_nonce' )
			->andReturn( 1 );

		Functions\expect( 'current_user_can' )
			->with( 'manage_options' )
			->andReturn( true );

		Functions\expect( 'esc_url_raw' )
			->andReturnUsing(
				function ( $url ) {
					return filter_var( $url, FILTER_SANITIZE_URL );
				}
			);

		Functions\expect( 'sanitize_text_field' )
			->andReturnFirstArg();

		// Expect secret to be saved separately (encrypted).
		Functions\expect( 'update_option' )
			->once()
			->with(
				'fa_wpmcp_webhook_secret',
				Mockery::on( function ( $value ) {
					// Should be encrypted (sodium:v1: or openssl:v1: prefix).
					return is_string( $value ) &&
						   ( str_starts_with( $value, 'sodium:v1:' ) ||
							 str_starts_with( $value, 'openssl:v1:' ) );
				})
			);

		// Expect endpoints to be saved (without secret).
		Functions\expect( 'update_option' )
			->once()
			->with( 'fa_wpmcp_webhooks', Mockery::type( 'array' ) );

		Functions\expect( 'admin_url' )
			->andReturn( 'http://example.com/wp-admin/admin.php' );

		Functions\expect( 'add_query_arg' )
			->andReturn( 'http://example.com/wp-admin/admin.php?page=fa-wpmcp-webhooks&settings-updated=true' );

		Functions\expect( 'wp_safe_redirect' )
			->once();

		$registry = $this->create_registry_with_abilities();
		$settings_page = new SettingsPage( $registry );

		$settings_page->handleWebhooksSave();

		// Clean up.
		unset( $_POST['fa_wpmcp_nonce'], $_POST['action'], $_POST['webhook_endpoints'], $_POST['webhook_secret'] );
	}

	// =========================================================================
	// Init and Hook Registration Tests
	// =========================================================================

	/**
	 * Test init registers admin hooks.
	 *
	 * @return void
	 */
	public function test_init_registers_admin_hooks(): void {
		Functions\expect( 'add_action' )
			->once()
			->with( 'admin_menu', Mockery::type( 'array' ) );

		Functions\expect( 'add_action' )
			->once()
			->with( 'admin_enqueue_scripts', Mockery::type( 'array' ) );

		Functions\expect( 'add_action' )
			->once()
			->with( 'admin_post_fa_wpmcp_save_settings', Mockery::type( 'array' ) );

		Functions\expect( 'add_action' )
			->once()
			->with( 'admin_post_fa_wpmcp_save_permissions', Mockery::type( 'array' ) );

		Functions\expect( 'add_action' )
			->once()
			->with( 'admin_post_fa_wpmcp_save_rate_limits', Mockery::type( 'array' ) );

		Functions\expect( 'add_action' )
			->once()
			->with( 'admin_post_fa_wpmcp_save_webhooks', Mockery::type( 'array' ) );

		$registry = $this->create_registry_with_abilities();
		$settings_page = new SettingsPage( $registry );

		$settings_page->init();
	}

	// =========================================================================
	// Success/Error Notice Tests
	// =========================================================================

	/**
	 * Test displays success notice after save.
	 *
	 * @return void
	 */
	public function test_displays_success_notice_after_save(): void {
		$_GET['settings-updated'] = 'true';

		Functions\expect( 'current_user_can' )
			->with( 'manage_options' )
			->andReturn( true );

		Functions\expect( 'get_option' )
			->andReturn( array() );

		Functions\expect( 'wp_nonce_field' )
			->andReturn( '' );

		Functions\expect( 'esc_attr' )
			->andReturnFirstArg();

		Functions\expect( 'esc_html' )
			->andReturnFirstArg();

		Functions\expect( 'admin_url' )
			->andReturn( 'http://example.com/wp-admin/admin-post.php' );

		$registry = $this->create_registry_with_abilities();

		$settings_page = new SettingsPage( $registry );
		$output = $settings_page->renderSettingsPage();

		$this->assertStringContainsString( 'notice-success', $output );
		$this->assertStringContainsString( 'Settings saved', $output );

		// Clean up.
		unset( $_GET['settings-updated'] );
	}

	/**
	 * Test displays error notice on save failure.
	 *
	 * @return void
	 */
	public function test_displays_error_notice_on_save_failure(): void {
		$_GET['settings-error'] = 'true';

		Functions\expect( 'current_user_can' )
			->with( 'manage_options' )
			->andReturn( true );

		Functions\expect( 'get_option' )
			->andReturn( array() );

		Functions\expect( 'wp_nonce_field' )
			->andReturn( '' );

		Functions\expect( 'esc_attr' )
			->andReturnFirstArg();

		Functions\expect( 'esc_html' )
			->andReturnFirstArg();

		Functions\expect( 'admin_url' )
			->andReturn( 'http://example.com/wp-admin/admin-post.php' );

		$registry = $this->create_registry_with_abilities();

		$settings_page = new SettingsPage( $registry );
		$output = $settings_page->renderSettingsPage();

		$this->assertStringContainsString( 'notice-error', $output );

		// Clean up.
		unset( $_GET['settings-error'] );
	}
}
