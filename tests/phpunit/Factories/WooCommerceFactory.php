<?php

/**
 * Factory for creating WooCommerce mock objects.
 *
 * @package FAWpmcp\Tests\Factories
 */

declare(strict_types=1);

namespace FAWpmcp\Tests\Factories;

use Mockery;

/**
 * Factory class for creating WooCommerce mock objects in tests.
 *
 * Provides helper methods to create properly configured mocks of
 * WooCommerce classes like WC_Product, WC_Order, WC_Customer, etc.
 *
 * @package FAWpmcp\Tests\Factories
 */
class WooCommerceFactory
{
    /**
     * Default product data.
     *
     * @var array<string, mixed>
     */
    private const DEFAULT_PRODUCT = [
        'id' => 1,
        'name' => 'Test Product',
        'slug' => 'test-product',
        'type' => 'simple',
        'status' => 'publish',
        'sku' => 'TEST-001',
        'price' => '19.99',
        'regular_price' => '24.99',
        'sale_price' => '19.99',
        'stock_quantity' => 100,
        'stock_status' => 'instock',
        'manage_stock' => true,
        'description' => 'Test product description',
        'short_description' => 'Short description',
    ];

    /**
     * Default order data.
     *
     * @var array<string, mixed>
     */
    private const DEFAULT_ORDER = [
        'id' => 1,
        'status' => 'processing',
        'currency' => 'USD',
        'total' => '99.99',
        'subtotal' => '89.99',
        'shipping_total' => '10.00',
        'tax_total' => '0.00',
        'discount_total' => '0.00',
        'payment_method' => 'stripe',
        'payment_method_title' => 'Credit Card (Stripe)',
        'customer_id' => 1,
        'billing_first_name' => 'John',
        'billing_last_name' => 'Doe',
        'billing_email' => 'john@example.com',
    ];

    /**
     * Default customer data.
     *
     * @var array<string, mixed>
     */
    private const DEFAULT_CUSTOMER = [
        'id' => 1,
        'email' => 'customer@example.com',
        'first_name' => 'Jane',
        'last_name' => 'Smith',
        'username' => 'janesmith',
        'role' => 'customer',
        'billing_first_name' => 'Jane',
        'billing_last_name' => 'Smith',
        'billing_email' => 'customer@example.com',
        'billing_phone' => '555-1234',
        'billing_address_1' => '123 Main St',
        'billing_city' => 'Anytown',
        'billing_state' => 'CA',
        'billing_postcode' => '12345',
        'billing_country' => 'US',
    ];

    /**
     * Default variation data.
     *
     * @var array<string, mixed>
     */
    private const DEFAULT_VARIATION = [
        'id' => 10,
        'parent_id' => 1,
        'sku' => 'TEST-001-SM',
        'price' => '19.99',
        'regular_price' => '24.99',
        'sale_price' => '19.99',
        'stock_quantity' => 50,
        'stock_status' => 'instock',
        'manage_stock' => true,
        'attributes' => ['pa_size' => 'small'],
    ];

    /**
     * Create a mock WC_Product object.
     *
     * @param array<string, mixed> $data Product data to override defaults.
     * @return object Mock WC_Product object.
     */
    public static function product(array $data = []): object
    {
        $data = array_merge(self::DEFAULT_PRODUCT, $data);

        $product = Mockery::mock('WC_Product');
        $product->shouldReceive('get_id')->andReturn($data['id']);
        $product->shouldReceive('get_name')->andReturn($data['name']);
        $product->shouldReceive('get_slug')->andReturn($data['slug']);
        $product->shouldReceive('get_type')->andReturn($data['type']);
        $product->shouldReceive('get_status')->andReturn($data['status']);
        $product->shouldReceive('get_sku')->andReturn($data['sku']);
        $product->shouldReceive('get_price')->andReturn($data['price']);
        $product->shouldReceive('get_regular_price')->andReturn($data['regular_price']);
        $product->shouldReceive('get_sale_price')->andReturn($data['sale_price']);
        $product->shouldReceive('get_stock_quantity')->andReturn($data['stock_quantity']);
        $product->shouldReceive('get_stock_status')->andReturn($data['stock_status']);
        $product->shouldReceive('get_manage_stock')->andReturn($data['manage_stock']);
        $product->shouldReceive('get_description')->andReturn($data['description']);
        $product->shouldReceive('get_short_description')->andReturn($data['short_description']);
        $product->shouldReceive('is_in_stock')->andReturn($data['stock_status'] === 'instock');
        $product->shouldReceive('is_on_sale')->andReturn(!empty($data['sale_price']));
        $product->shouldReceive('get_data')->andReturn($data);

        return $product;
    }

    /**
     * Create a mock WC_Order object.
     *
     * @param array<string, mixed> $data Order data to override defaults.
     * @return object Mock WC_Order object.
     */
    public static function order(array $data = []): object
    {
        $data = array_merge(self::DEFAULT_ORDER, $data);

        $order = Mockery::mock('WC_Order');
        $order->shouldReceive('get_id')->andReturn($data['id']);
        $order->shouldReceive('get_status')->andReturn($data['status']);
        $order->shouldReceive('get_currency')->andReturn($data['currency']);
        $order->shouldReceive('get_total')->andReturn($data['total']);
        $order->shouldReceive('get_subtotal')->andReturn($data['subtotal']);
        $order->shouldReceive('get_shipping_total')->andReturn($data['shipping_total']);
        $order->shouldReceive('get_total_tax')->andReturn($data['tax_total']);
        $order->shouldReceive('get_discount_total')->andReturn($data['discount_total']);
        $order->shouldReceive('get_payment_method')->andReturn($data['payment_method']);
        $order->shouldReceive('get_payment_method_title')->andReturn($data['payment_method_title']);
        $order->shouldReceive('get_customer_id')->andReturn($data['customer_id']);
        $order->shouldReceive('get_billing_first_name')->andReturn($data['billing_first_name']);
        $order->shouldReceive('get_billing_last_name')->andReturn($data['billing_last_name']);
        $order->shouldReceive('get_billing_email')->andReturn($data['billing_email']);
        $order->shouldReceive('get_items')->andReturn([]);
        $order->shouldReceive('get_data')->andReturn($data);

        return $order;
    }

    /**
     * Create a mock WC_Customer object.
     *
     * @param array<string, mixed> $data Customer data to override defaults.
     * @return object Mock WC_Customer object.
     */
    public static function customer(array $data = []): object
    {
        $data = array_merge(self::DEFAULT_CUSTOMER, $data);

        $customer = Mockery::mock('WC_Customer');
        $customer->shouldReceive('get_id')->andReturn($data['id']);
        $customer->shouldReceive('get_email')->andReturn($data['email']);
        $customer->shouldReceive('get_first_name')->andReturn($data['first_name']);
        $customer->shouldReceive('get_last_name')->andReturn($data['last_name']);
        $customer->shouldReceive('get_username')->andReturn($data['username']);
        $customer->shouldReceive('get_role')->andReturn($data['role']);
        $customer->shouldReceive('get_billing_first_name')->andReturn($data['billing_first_name']);
        $customer->shouldReceive('get_billing_last_name')->andReturn($data['billing_last_name']);
        $customer->shouldReceive('get_billing_email')->andReturn($data['billing_email']);
        $customer->shouldReceive('get_billing_phone')->andReturn($data['billing_phone']);
        $customer->shouldReceive('get_billing_address_1')->andReturn($data['billing_address_1']);
        $customer->shouldReceive('get_billing_city')->andReturn($data['billing_city']);
        $customer->shouldReceive('get_billing_state')->andReturn($data['billing_state']);
        $customer->shouldReceive('get_billing_postcode')->andReturn($data['billing_postcode']);
        $customer->shouldReceive('get_billing_country')->andReturn($data['billing_country']);
        $customer->shouldReceive('get_data')->andReturn($data);

        return $customer;
    }

    /**
     * Create a mock WC_Product_Variation object.
     *
     * @param array<string, mixed> $data Variation data to override defaults.
     * @return object Mock WC_Product_Variation object.
     */
    public static function variation(array $data = []): object
    {
        $data = array_merge(self::DEFAULT_VARIATION, $data);

        $variation = Mockery::mock('WC_Product_Variation');
        $variation->shouldReceive('get_id')->andReturn($data['id']);
        $variation->shouldReceive('get_parent_id')->andReturn($data['parent_id']);
        $variation->shouldReceive('get_sku')->andReturn($data['sku']);
        $variation->shouldReceive('get_price')->andReturn($data['price']);
        $variation->shouldReceive('get_regular_price')->andReturn($data['regular_price']);
        $variation->shouldReceive('get_sale_price')->andReturn($data['sale_price']);
        $variation->shouldReceive('get_stock_quantity')->andReturn($data['stock_quantity']);
        $variation->shouldReceive('get_stock_status')->andReturn($data['stock_status']);
        $variation->shouldReceive('get_manage_stock')->andReturn($data['manage_stock']);
        $variation->shouldReceive('get_attributes')->andReturn($data['attributes']);
        $variation->shouldReceive('is_in_stock')->andReturn($data['stock_status'] === 'instock');
        $variation->shouldReceive('get_data')->andReturn($data);

        return $variation;
    }

    /**
     * Create an array of mock products.
     *
     * @param int                        $count Number of products to create.
     * @param array<int, array<string, mixed>> $overrides Per-product overrides by index.
     * @return array<object> Array of mock WC_Product objects.
     */
    public static function products(int $count = 3, array $overrides = []): array
    {
        $products = [];
        for ($i = 0; $i < $count; $i++) {
            $data = array_merge(
                ['id' => $i + 1, 'name' => "Test Product " . ($i + 1), 'sku' => 'TEST-00' . ($i + 1)],
                $overrides[$i] ?? []
            );
            $products[] = self::product($data);
        }
        return $products;
    }

    /**
     * Create an array of mock orders.
     *
     * @param int                        $count Number of orders to create.
     * @param array<int, array<string, mixed>> $overrides Per-order overrides by index.
     * @return array<object> Array of mock WC_Order objects.
     */
    public static function orders(int $count = 3, array $overrides = []): array
    {
        $orders = [];
        $statuses = ['pending', 'processing', 'completed'];
        for ($i = 0; $i < $count; $i++) {
            $data = array_merge(
                ['id' => $i + 1, 'status' => $statuses[$i % 3]],
                $overrides[$i] ?? []
            );
            $orders[] = self::order($data);
        }
        return $orders;
    }

    /**
     * Create an array of mock customers.
     *
     * @param int                        $count Number of customers to create.
     * @param array<int, array<string, mixed>> $overrides Per-customer overrides by index.
     * @return array<object> Array of mock WC_Customer objects.
     */
    public static function customers(int $count = 3, array $overrides = []): array
    {
        $customers = [];
        for ($i = 0; $i < $count; $i++) {
            $data = array_merge(
                [
                    'id' => $i + 1,
                    'email' => "customer" . ($i + 1) . "@example.com",
                    'username' => 'customer' . ($i + 1),
                ],
                $overrides[$i] ?? []
            );
            $customers[] = self::customer($data);
        }
        return $customers;
    }
}
