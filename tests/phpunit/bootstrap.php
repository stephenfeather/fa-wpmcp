<?php

/**
 * PHPUnit bootstrap for FA WPMCP plugin tests.
 *
 * @package FAWpmcp\Tests
 */

declare(strict_types=1);

// Load Composer autoloader.
$autoloader = dirname(__DIR__, 2) . '/vendor/autoload.php';

if (! file_exists($autoloader)) {
    die("Composer autoloader not found. Run 'composer install' first.\n");
}

require_once $autoloader;

// Define WordPress constants for testing.
if (! defined('ABSPATH')) {
    define('ABSPATH', '/tmp/wordpress/');
}

// Ensure ABSPATH directory exists for tests that write files.
if (! is_dir(ABSPATH)) {
    mkdir(ABSPATH, 0755, true);
}

// Define plugin constants manually for testing (instead of loading the plugin file which has hooks).
if (! defined('FA_WPMCP_VERSION')) {
    define('FA_WPMCP_VERSION', '1.0.0-alpha.2');
}

if (! defined('FA_WPMCP_PATH')) {
    define('FA_WPMCP_PATH', dirname(__DIR__, 2) . '/');
}

if (! defined('FA_WPMCP_URL')) {
    define('FA_WPMCP_URL', 'http://localhost/wp-content/plugins/fa-wpmcp/');
}

if (! defined('FA_WPMCP_BASENAME')) {
    define('FA_WPMCP_BASENAME', 'fa-wpmcp/fa-wpmcp.php');
}

// Define WordPress authentication keys and salts for testing.
// These are required by SodiumSecretEncryption and OpenSslSecretEncryption.
if (! defined('SECURE_AUTH_KEY')) {
    define('SECURE_AUTH_KEY', 'test-secure-auth-key-for-phpunit-testing-only-32chars!');
}

if (! defined('LOGGED_IN_KEY')) {
    define('LOGGED_IN_KEY', 'test-logged-in-key-for-phpunit-testing-only-32chars!');
}

if (! defined('NONCE_SALT')) {
    define('NONCE_SALT', 'test-nonce-salt-for-phpunit-testing-only-32characters!');
}

// phpcs:disable Squiz.Classes.ValidClassName.NotCamelCaps, PSR1.Classes.ClassDeclaration.MissingNamespace
if (! class_exists('WP_Error')) {
    /**
     * Stub WP_Error class for unit tests.
     *
     * WordPress WP_Error is not available in unit test context.
     */
    class WP_Error
    {
        /** @var array<string, array<string>> */
        public $errors = array();

        /** @var string */
        public $code = '';

        /** @var string */
        public $message = '';

        /**
         * Constructor.
         *
         * @param string $code    Error code.
         * @param string $message Error message.
         */
        public function __construct(string $code = '', string $message = '')
        {
            $this->code    = $code;
            $this->message = $message;
            $this->errors  = array( $code => array( $message ) );
        }
    }
}
// phpcs:enable Squiz.Classes.ValidClassName.NotCamelCaps

if (! function_exists('user_can')) {
    /**
     * Test stub for user_can.
     *
     * @param int    $user_id    User ID.
     * @param string $capability Capability name.
     * @return bool
     */
    function user_can(int $user_id, string $capability): bool
    {
        $overrides = $GLOBALS['fa_wpmcp_user_can'] ?? array();
        if (isset($overrides[ $user_id ]) && array_key_exists($capability, $overrides[ $user_id ])) {
            return (bool) $overrides[ $user_id ][ $capability ];
        }

        return true;
    }
}

// WooCommerce stubs for unit testing.
// These are minimal stubs to allow tests to run without WooCommerce installed.
// Brain\Monkey mocking is used for actual test behavior.

// phpcs:disable Squiz.Classes.ValidClassName.NotCamelCaps, PSR1.Classes.ClassDeclaration.MultipleClasses
if (! class_exists('WooCommerce')) {
    /**
     * Stub WooCommerce class for unit tests.
     *
     * Real behavior is mocked via Brain\Monkey in tests.
     */
    class WooCommerce
    {
        /** @var string */
        public $version = '8.5.0';

        /** @var self|null */
        private static $instance = null;

        /**
         * Get singleton instance.
         *
         * @return self
         */
        public static function instance(): self
        {
            if (null === self::$instance) {
                self::$instance = new self();
            }
            return self::$instance;
        }
    }
}

if (! class_exists('WC_Product')) {
    /**
     * Stub WC_Product class for unit tests.
     *
     * This stub is functional enough to test CreateProduct and UpdateProduct abilities.
     * It stores properties and returns them via getters.
     */
    class WC_Product
    {
        /** @var int */
        protected $id = 0;

        /** @var array<string, mixed> */
        protected $data = [];

        /** @var int */
        private static $nextId = 1;

        /**
         * Get product ID.
         *
         * @return int
         */
        public function get_id(): int
        {
            return $this->id;
        }

        /**
         * Save the product.
         *
         * @return int Product ID.
         */
        public function save(): int
        {
            if ($this->id === 0) {
                $this->id = self::$nextId++;
            }
            return $this->id;
        }

        /**
         * Set product name.
         *
         * @param string $name Product name.
         * @return void
         */
        public function set_name(string $name): void
        {
            $this->data['name'] = $name;
        }

        /**
         * Get product name.
         *
         * @return string
         */
        public function get_name(): string
        {
            return $this->data['name'] ?? '';
        }

        /**
         * Set status.
         *
         * @param string $status Status.
         * @return void
         */
        public function set_status(string $status): void
        {
            $this->data['status'] = $status;
        }

        /**
         * Get status.
         *
         * @return string
         */
        public function get_status(): string
        {
            return $this->data['status'] ?? 'draft';
        }

        /**
         * Set SKU.
         *
         * @param string $sku SKU.
         * @return void
         */
        public function set_sku(string $sku): void
        {
            $this->data['sku'] = $sku;
        }

        /**
         * Set regular price.
         *
         * @param string $price Price.
         * @return void
         */
        public function set_regular_price(string $price): void
        {
            $this->data['regular_price'] = $price;
        }

        /**
         * Set sale price.
         *
         * @param string $price Price.
         * @return void
         */
        public function set_sale_price(string $price): void
        {
            $this->data['sale_price'] = $price;
        }

        /**
         * Set description.
         *
         * @param string $description Description.
         * @return void
         */
        public function set_description(string $description): void
        {
            $this->data['description'] = $description;
        }

        /**
         * Set short description.
         *
         * @param string $description Short description.
         * @return void
         */
        public function set_short_description(string $description): void
        {
            $this->data['short_description'] = $description;
        }

        /**
         * Set manage stock.
         *
         * @param bool $manage Manage stock.
         * @return void
         */
        public function set_manage_stock(bool $manage): void
        {
            $this->data['manage_stock'] = $manage;
        }

        /**
         * Set stock quantity.
         *
         * @param int $quantity Quantity.
         * @return void
         */
        public function set_stock_quantity(int $quantity): void
        {
            $this->data['stock_quantity'] = $quantity;
        }

        /**
         * Set stock status.
         *
         * @param string $status Status.
         * @return void
         */
        public function set_stock_status(string $status): void
        {
            $this->data['stock_status'] = $status;
        }

        /**
         * Set category IDs.
         *
         * @param array<int> $ids Category IDs.
         * @return void
         */
        public function set_category_ids(array $ids): void
        {
            $this->data['category_ids'] = $ids;
        }

        /**
         * Set tag IDs.
         *
         * @param array<int> $ids Tag IDs.
         * @return void
         */
        public function set_tag_ids(array $ids): void
        {
            $this->data['tag_ids'] = $ids;
        }

        /**
         * Set virtual.
         *
         * @param bool $virtual Virtual.
         * @return void
         */
        public function set_virtual(bool $virtual): void
        {
            $this->data['virtual'] = $virtual;
        }

        /**
         * Set downloadable.
         *
         * @param bool $downloadable Downloadable.
         * @return void
         */
        public function set_downloadable(bool $downloadable): void
        {
            $this->data['downloadable'] = $downloadable;
        }

        /**
         * Delete the product.
         *
         * @param bool $force Force permanent delete.
         * @return bool True on success.
         */
        public function delete(bool $force = false): bool
        {
            // In stub, always succeeds.
            return true;
        }

        /**
         * Reset the ID counter (for test isolation).
         *
         * @return void
         */
        public static function resetIdCounter(): void
        {
            self::$nextId = 1;
        }
    }
}

if (! class_exists('WC_Product_Simple')) {
    /**
     * Stub WC_Product_Simple class for unit tests.
     */
    class WC_Product_Simple extends WC_Product
    {
    }
}

if (! class_exists('WC_Product_Variable')) {
    /**
     * Stub WC_Product_Variable class for unit tests.
     */
    class WC_Product_Variable extends WC_Product
    {
    }
}

if (! class_exists('WC_Product_Grouped')) {
    /**
     * Stub WC_Product_Grouped class for unit tests.
     */
    class WC_Product_Grouped extends WC_Product
    {
    }
}

if (! class_exists('WC_Product_External')) {
    /**
     * Stub WC_Product_External class for unit tests.
     */
    class WC_Product_External extends WC_Product
    {
    }
}

if (! class_exists('WC_Product_Variation')) {
    /**
     * Stub WC_Product_Variation class for unit tests.
     */
    class WC_Product_Variation extends WC_Product
    {
    }
}

if (! class_exists('WC_Order')) {
    /**
     * Stub WC_Order class for unit tests.
     */
    class WC_Order
    {
        /** @var int */
        protected $id = 0;

        /**
         * Get order ID.
         *
         * @return int
         */
        public function get_id(): int
        {
            return $this->id;
        }
    }
}

if (! class_exists('WC_Customer')) {
    /**
     * Stub WC_Customer class for unit tests.
     */
    class WC_Customer
    {
        /** @var int */
        protected $id = 0;

        /**
         * Get customer ID.
         *
         * @return int
         */
        public function get_id(): int
        {
            return $this->id;
        }
    }
}
// phpcs:enable Squiz.Classes.ValidClassName.NotCamelCaps

// Note: WC() function is NOT stubbed here because Brain\Monkey/Patchwork
// needs to be able to redefine it during tests. Tests should mock WC() using
// Functions\when('WC')->justReturn($mockWc) which will define the function.

// Note: WooCommerce functions (wc_get_product, wc_get_products, wc_get_order,
// wc_get_orders, wc_create_new_customer) are NOT stubbed here because
// Brain\Monkey/Patchwork needs to be able to define them during tests.
// Tests should mock these using Functions\when('wc_get_product')->justReturn(...)
