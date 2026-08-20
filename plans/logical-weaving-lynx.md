# WooCommerce Abilities Implementation Plan

## Overview

Add WooCommerce abilities to fa-wpmcp, starting with high-priority commerce operations (Products, Orders, Customers) and expanding to full coverage of 127 abilities across 32 categories.

## Scope

### Phase 1: Foundation + High Priority (v2.0-alpha)
- **20 abilities** across 4 categories
- Products (5), Orders (5), Customers (5), Product Variations (5)

### Phase 2-6: Medium Priority (v2.0-beta through v2.0)
- **67 abilities** across 18 categories
- Coupons, Categories, Tags, Attributes, Tax, Shipping, Payments, Webhooks, Refunds, Order Notes

### Phase 7: Low Priority (v2.1+)
- **40 abilities** across 10 categories
- HPOS migration, Blueprint, WooCommerce.com connection

---

## Phase 1 Implementation Details

### 1. Create Base Infrastructure

#### 1.1 AbstractWooCommerceAbility Base Class

**File:** `src/Abilities/WooCommerce/AbstractWooCommerceAbility.php`

```php
abstract class AbstractWooCommerceAbility extends AbstractAbility
{
    final public function doExecute(array $input): array
    {
        $this->ensureWooCommerceActive();
        return $this->doWooCommerceExecute($input);
    }

    abstract protected function doWooCommerceExecute(array $input): array;

    protected function ensureWooCommerceActive(): void
    {
        if (!class_exists('WooCommerce') || !function_exists('WC')) {
            throw new PluginNotActiveException(
                'WooCommerce is not installed or active',
                'woocommerce_not_active'
            );
        }
    }
}
```

#### 1.2 Register WooCommerce Categories

**File:** `src/Plugin.php` (modify `registerAbilityCategories()`)

Add categories at priority 5:
- `woocommerce-products` - Product management
- `woocommerce-orders` - Order management
- `woocommerce-customers` - Customer management

#### 1.3 Exception Classes

**File:** `src/Exceptions/WooCommerce/`
- `PluginNotActiveException.php`
- `ProductNotFoundException.php`
- `OrderNotFoundException.php`
- `CustomerNotFoundException.php`

### 2. Implement Product Abilities (5)

**Directory:** `src/Abilities/WooCommerce/Products/`

| Ability | Name | Capability | Priority |
|---------|------|------------|----------|
| ListProducts | `fa-wpmcp/wc-list-products` | `edit_products` | High |
| GetProduct | `fa-wpmcp/wc-get-product` | `edit_products` | High |
| CreateProduct | `fa-wpmcp/wc-create-product` | `edit_products` | High |
| UpdateProduct | `fa-wpmcp/wc-update-product` | `edit_products` | High |
| DeleteProduct | `fa-wpmcp/wc-delete-product` | `delete_products` | High |

**Implementation approach:** Use `wc_get_product()`, `wc_get_products()` functions directly rather than REST API.

### 3. Implement Order Abilities (5)

**Directory:** `src/Abilities/WooCommerce/Orders/`

| Ability | Name | Capability | Priority |
|---------|------|------------|----------|
| ListOrders | `fa-wpmcp/wc-list-orders` | `view_orders` | High |
| GetOrder | `fa-wpmcp/wc-get-order` | `view_orders` | High |
| CreateOrder | `fa-wpmcp/wc-create-order` | `edit_orders` | High |
| UpdateOrder | `fa-wpmcp/wc-update-order` | `edit_orders` | High |
| DeleteOrder | `fa-wpmcp/wc-delete-order` | `delete_orders` | High |

**Implementation approach:** Use `wc_get_order()`, `wc_get_orders()` - automatically handles HPOS.

### 4. Implement Customer Abilities (5)

**Directory:** `src/Abilities/WooCommerce/Customers/`

| Ability | Name | Capability | Priority |
|---------|------|------------|----------|
| ListCustomers | `fa-wpmcp/wc-list-customers` | `list_users` | High |
| GetCustomer | `fa-wpmcp/wc-get-customer` | `list_users` | High |
| CreateCustomer | `fa-wpmcp/wc-create-customer` | `create_users` | High |
| UpdateCustomer | `fa-wpmcp/wc-update-customer` | `edit_users` | High |
| DeleteCustomer | `fa-wpmcp/wc-delete-customer` | `delete_users` | High |

**Implementation approach:** Use `WC_Customer` class and `wc_create_new_customer()`.

### 5. Implement Product Variation Abilities (5)

**Directory:** `src/Abilities/WooCommerce/Variations/`

| Ability | Name | Capability | Priority |
|---------|------|------------|----------|
| ListVariations | `fa-wpmcp/wc-list-variations` | `edit_products` | High |
| GetVariation | `fa-wpmcp/wc-get-variation` | `edit_products` | High |
| CreateVariation | `fa-wpmcp/wc-create-variation` | `edit_products` | High |
| UpdateVariation | `fa-wpmcp/wc-update-variation` | `edit_products` | High |
| DeleteVariation | `fa-wpmcp/wc-delete-variation` | `delete_products` | High |

---

## Test Infrastructure

### 1. Create WooCommerce Test Base Class

**File:** `tests/phpunit/TestCase/AbstractWooCommerceAbilityTest.php`

- Extends `BrainMonkeyTestCase`
- Auto-mocks WooCommerce active state
- Provides `mockWooCommerceInactive()` for negative tests

### 2. Create WooCommerce Mock Factory

**File:** `tests/phpunit/Factories/WooCommerceFactory.php`

Factory methods:
- `product(array $data)` - Returns mocked `WC_Product`
- `order(array $data)` - Returns mocked `WC_Order`
- `customer(array $data)` - Returns mocked `WC_Customer`
- `variation(array $data)` - Returns mocked `WC_Product_Variation`

### 3. Update Test Bootstrap

**File:** `tests/phpunit/bootstrap.php`

Add stubs for:
- `WC_Product`, `WC_Order`, `WC_Customer` classes
- `WC()`, `wc_get_product()`, `wc_get_order()` functions

### 4. Integration Test Environment

**File:** `docker/docker-compose.test.yml`

Add WooCommerce plugin installation to setup script.

---

## File Structure

```
src/Abilities/WooCommerce/
├── AbstractWooCommerceAbility.php
├── Products/
│   ├── ListProducts.php
│   ├── GetProduct.php
│   ├── CreateProduct.php
│   ├── UpdateProduct.php
│   └── DeleteProduct.php
├── Orders/
│   ├── ListOrders.php
│   ├── GetOrder.php
│   ├── CreateOrder.php
│   ├── UpdateOrder.php
│   └── DeleteOrder.php
├── Customers/
│   ├── ListCustomers.php
│   ├── GetCustomer.php
│   ├── CreateCustomer.php
│   ├── UpdateCustomer.php
│   └── DeleteCustomer.php
└── Variations/
    ├── ListVariations.php
    ├── GetVariation.php
    ├── CreateVariation.php
    ├── UpdateVariation.php
    └── DeleteVariation.php

src/Exceptions/WooCommerce/
├── PluginNotActiveException.php
├── ProductNotFoundException.php
├── OrderNotFoundException.php
└── CustomerNotFoundException.php

tests/phpunit/
├── TestCase/
│   └── AbstractWooCommerceAbilityTest.php
├── Factories/
│   └── WooCommerceFactory.php
└── Abilities/WooCommerce/
    ├── Products/
    ├── Orders/
    ├── Customers/
    └── Variations/
```

---

## Implementation Order

### Week 1: Foundation
1. [ ] Create `PluginNotActiveException`
2. [ ] Create `AbstractWooCommerceAbility` base class
3. [ ] Register WooCommerce categories in Plugin.php
4. [ ] Create test infrastructure (base class, factory, bootstrap stubs)

### Week 2: Products
5. [ ] Implement `GetProduct` + tests
6. [ ] Implement `ListProducts` + tests
7. [ ] Implement `CreateProduct` + tests
8. [ ] Implement `UpdateProduct` + tests
9. [ ] Implement `DeleteProduct` + tests

### Week 3: Orders
10. [ ] Implement `GetOrder` + tests
11. [ ] Implement `ListOrders` + tests
12. [ ] Implement `CreateOrder` + tests
13. [ ] Implement `UpdateOrder` + tests
14. [ ] Implement `DeleteOrder` + tests

### Week 4: Customers + Variations
15. [ ] Implement Customer abilities (5) + tests
16. [ ] Implement Variation abilities (5) + tests
17. [ ] Integration tests with WooCommerce
18. [ ] Documentation updates

---

## Critical Files to Modify

| File | Changes |
|------|---------|
| `src/Plugin.php` | Add WooCommerce categories at priority 5 |
| `src/Abilities/AbilityRegistrar.php` | Add `registerWooCommerceAbilities()` method |
| `tests/phpunit/bootstrap.php` | Add WC class/function stubs |
| `docker/scripts/setup-wordpress.sh` | Install WooCommerce for integration tests |
| `docs/MCP_DOCUMENTATION.md` | Document new WooCommerce abilities |
| `README.md` | Update ability count and feature list |

---

## Verification

### Unit Tests
```bash
composer test -- --filter WooCommerce
```

### Integration Tests
```bash
# Start Docker environment with WooCommerce
docker-compose -f docker/docker-compose.test.yml up -d

# Run WooCommerce integration tests
./vendor/bin/phpunit --bootstrap tests/integration/bootstrap.php --filter WooCommerce
```

### Manual REST Testing
```bash
# Get session
SESSION=$(curl -s -X POST ...)

# Test product list
curl -s -X POST \
  -H "Authorization: Basic ..." \
  -H "Mcp-Session-Id: $SESSION" \
  -d '{"jsonrpc":"2.0","id":1,"method":"tools/call","params":{"name":"fa-wpmcp-wc-list-products","arguments":{}}}' \
  "http://localhost:8080/wp-json/mcp/mcp-adapter-default-server" | jq .
```

### Code Quality
```bash
composer phpcs
composer phpstan
```

---

## Safety Considerations

1. **Delete operations** - Implement trash-by-default for products/orders
2. **Order modifications** - Validate status transitions
3. **Customer deletion** - Handle order reassignment
4. **HPOS compatibility** - Use `wc_get_order()` not direct post queries

---

## Risk Mitigations (Pre-Mortem 2026-02-11)

### Tigers Addressed

1. **[HIGH] Conditional category registration**
   - Wrap WooCommerce category registration in `is_plugin_active('woocommerce/woocommerce.php')` check
   - Added to: Week 1, Task 3 (category registration)
   - Pattern:
   ```php
   if (is_plugin_active('woocommerce/woocommerce.php')) {
       wp_register_ability_category('woocommerce-products', [...]);
   }
   ```

2. **[MEDIUM] WooCommerce version check**
   - Add minimum version requirement (WC 8.0+) in `ensureWooCommerceActive()`
   - Added to: Week 1, Task 2 (AbstractWooCommerceAbility)
   - Pattern:
   ```php
   if (version_compare(WC()->version, '8.0', '<')) {
       throw new PluginNotActiveException('WooCommerce 8.0+ required');
   }
   ```

### Elephants Addressed

1. **Docker test environment**
   - Update `docker/scripts/setup-wordpress.sh` to install WooCommerce
   - Add `wp plugin install woocommerce --activate` to setup script
   - Added to: Week 1, Task 4

2. **PII in order data**
   - Update `PrivacyRedactor` with WC-specific fields
   - Fields: `billing_*`, `shipping_*`, `payment_method_title`, `customer_ip_address`
   - Added to: Week 2 (before Order abilities)

### Pre-Mortem Summary
- **Date:** 2026-02-11
- **Mode:** deep
- **Tigers:** 2 (both mitigated)
- **Elephants:** 2 (both mitigated)

---

## Success Criteria

- [ ] 20 abilities implemented and passing unit tests
- [ ] PHPStan level 8 passing
- [ ] PHPCS PSR-12 compliant
- [ ] Integration tests passing with WooCommerce installed
- [ ] Documentation updated
- [ ] README ability count updated (112 → 132)
