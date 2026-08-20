# WooCommerce Abilities V2 - Implementation Summary

**Generated:** 2026-02-11  
**Source Document:** `/Users/stephenfeather/Development/fa-wpmcp/docs/V2_WOOCOMMERCE_ABILITIES.md`

---

## Executive Summary

The WooCommerce V2 roadmap outlines **127 total abilities** across 32 categories, providing comprehensive AI agent access to WooCommerce functionality through the WordPress Abilities API.

---

## Scope Overview

### Total Abilities by Priority

| Priority | Categories | Abilities | Focus Area |
|----------|-----------|-----------|------------|
| **High** | 4 | 20 | Core commerce (customers, products, orders, variations) |
| **Medium** | 14 | 66 | Catalog, operations, shipping, payments, webhooks |
| **Low** | 14 | 41 | Reviews, tools, admin, HPOS/PALT migration |

### Category Breakdown (Top 10 by Importance)

1. **Customer** (5 abilities) - Full CRUD for customer management
2. **Product** (5 abilities) - Full CRUD for products
3. **Shop Order** (5 abilities) - Full CRUD for orders
4. **Product Variation** (5 abilities) - Full CRUD for product variants
5. **Shop Coupon** (5 abilities) - Full CRUD for coupons
6. **Product Category** (5 abilities) - Full CRUD for categories
7. **Product Tag** (5 abilities) - Full CRUD for tags
8. **Product Attribute** (5 abilities) - Full CRUD for attributes
9. **Tax** (5 abilities) - Full CRUD for tax rates
10. **Shipping Zone** (5 abilities) - Full CRUD for shipping zones

---

## Critical Implementation Requirements

### 1. AbstractWooCommerceAbility Base Class

**MUST CREATE** a base class that all WooCommerce abilities extend.

```php
namespace FeatherArms\WpMcp\Abilities\WooCommerce;

use FeatherArms\WpMcp\Abilities\AbstractAbility;
use FeatherArms\WpMcp\ValueObjects\ExecutionResult;

abstract class AbstractWooCommerceAbility extends AbstractAbility
{
    /**
     * Verify WooCommerce is active before executing any ability.
     */
    protected function verifyWooCommerceActive(): ?ExecutionResult
    {
        if (!class_exists('WooCommerce') || !function_exists('WC')) {
            return ExecutionResult::failure(
                'WooCommerce is not installed or not active',
                'woocommerce_not_active'
            );
        }
        
        return null; // No error
    }
    
    /**
     * Template method - all WooCommerce abilities MUST check activation.
     */
    final public function execute(array $args): ExecutionResult
    {
        $error = $this->verifyWooCommerceActive();
        if ($error !== null) {
            return $error;
        }
        
        return $this->doExecute($args);
    }
    
    /**
     * Subclasses implement their specific logic here.
     */
    abstract protected function doExecute(array $args): ExecutionResult;
}
```

**Rationale:** Similar to multisite-only abilities checking `is_multisite()`, this ensures consistent error handling when WooCommerce isn't available.

---

### 2. Naming Convention

**Pattern:** `wc.{category}.{action}`

Examples:
- `wc.customer.create`
- `wc.product.list`
- `wc.order.update`
- `wc.product-variation.delete`

**Mapping Rules:**
- `shop_order` → `wc.order`
- `shop_coupon` → `wc.coupon`
- `shop_order_refund` → `wc.refund`
- `product_cat` → `wc.product-cat`
- `product_tag` → `wc.product-tag`
- Underscores in WP-CLI become hyphens in ability names

---

### 3. WordPress 6.9 Abilities API Integration

**Critical Hook Priority Ordering:**

```php
// In Plugin class:

// Step 1: Register WooCommerce category at priority 5
add_action('wp_abilities_api_categories_init', [$this, 'registerWooCommerceCategory'], 5);

// Step 2: McpAdapter runs at priority 10 (built-in)

// Step 3: Register WooCommerce abilities at priority 15
add_action('wp_abilities_api_init', [$this, 'registerWooCommerceAbilities'], 15);
```

**Why:** Categories MUST be registered before abilities, or WordPress triggers DoingItWrong warnings.

---

### 4. WooCommerce REST API Considerations

**Key Points:**
- WP-CLI `wp wc` commands use WooCommerce REST API internally
- Abilities should leverage `WC_REST_*_Controller` classes when possible
- Some operations require consumer key/secret for authentication
- REST API endpoints follow pattern: `/wp-json/wc/v3/{resource}`

**Implementation Options:**

**Option A: Direct WooCommerce Classes**
```php
$product = wc_get_product($args['id']);
if (!$product) {
    return ExecutionResult::failure('Product not found', 'not_found');
}
return ExecutionResult::success($product->get_data());
```

**Option B: REST Controller Reuse**
```php
$controller = new WC_REST_Products_Controller();
$request = new WP_REST_Request('GET', '/wc/v3/products/' . $args['id']);
$response = $controller->get_item($request);
return ExecutionResult::success($response->get_data());
```

**Recommendation:** Use Option A (direct classes) for simpler code, better type safety, and avoiding REST API authentication concerns.

---

### 5. HPOS (High-Performance Order Storage) Support

**Context:** Modern WooCommerce (3.0+) uses HPOS instead of traditional post tables for orders.

**Implementation Pattern:**
```php
// Check if HPOS is enabled
if (class_exists('Automattic\WooCommerce\Utilities\OrderUtil') && 
    OrderUtil::custom_orders_table_usage_is_enabled()) {
    // Use HPOS methods
    $order = wc_get_order($order_id);
} else {
    // Fallback to posts table
    $order = wc_get_order($order_id);
}
```

**Good News:** `wc_get_order()` already abstracts this, so most abilities won't need special handling.

---

## Phased Implementation Roadmap

### Phase 1: Core Commerce (High Priority)
**Target:** 20 abilities  
**Timeline:** First release

| Category | Abilities | Rationale |
|----------|-----------|-----------|
| Customer | create, delete, get, list, update | User management foundation |
| Product | create, delete, get, list, update | Catalog management |
| Shop Order | create, delete, get, list, update | Order processing |
| Product Variation | create, delete, get, list, update | Product variants |

**Example Ability Names:**
- `wc.customer.create`, `wc.customer.list`
- `wc.product.create`, `wc.product.get`
- `wc.order.create`, `wc.order.update`
- `wc.product-variation.list`

---

### Phase 2: Catalog Management (Medium Priority)
**Target:** 20 abilities  
**Timeline:** Second release

| Category | Abilities | Rationale |
|----------|-----------|-----------|
| Product Category | create, delete, get, list, update | Taxonomy management |
| Product Tag | create, delete, get, list, update | Taxonomy management |
| Product Attribute | create, delete, get, list, update | Product features (color, size) |
| Product Attribute Term | create, delete, get, list, update | Attribute values (red, large) |

---

### Phase 3: Commerce Operations (Medium Priority)
**Target:** 23 abilities  
**Timeline:** Third release

| Category | Abilities | Rationale |
|----------|-----------|-----------|
| Shop Coupon | create, delete, get, list, update | Discount management |
| Tax | create, delete, get, list, update | Tax configuration |
| Tax Class | create, delete, get, list | Tax categories (no update available) |
| Order Note | create, delete, get, list | Order comments (immutable - no update) |
| Shop Order Refund | create, delete, get, list | Refund processing (immutable - no update) |

**Special Considerations:**
- Order notes and refunds are **immutable** (no update command exists)
- Tax classes have no update capability in WooCommerce

---

### Phase 4: Shipping Configuration (Medium Priority)
**Target:** 13 abilities  
**Timeline:** Fourth release

| Category | Abilities | Rationale |
|----------|-----------|-----------|
| Shipping Zone | create, delete, get, list, update | Geographic shipping rules |
| Shipping Zone Method | create, delete, get, list, update | Zone-specific shipping methods |
| Payment Gateway | get, list, update | Payment config (no create/delete) |

**Notes:**
- Payment gateways are registered by plugins, not created via API
- Only configuration (update) is possible

---

### Phase 5: Integrations (Medium-Low Priority)
**Target:** 11 abilities  
**Timeline:** Fifth release

| Category | Abilities | Rationale |
|----------|-----------|-----------|
| Webhook | create, delete, get, list, update | Event notifications |
| Webhook Delivery | get, list | Delivery logs (read-only) |
| Shipping Method | get, list | Available methods (read-only) |
| Shipping Zone Location | list | Zone coverage (read-only) |

---

### Phase 6: Administrative (Low Priority)
**Target:** 4 abilities  
**Timeline:** Sixth release

| Category | Abilities | Rationale |
|----------|-----------|-----------|
| Tool | list, run | System tools (destructive - needs safeguards) |
| Customer Download | list | Download permissions (read-only) |
| Tracker | snapshot | Analytics data |

**Safety Constraints for Tool Abilities:**
- `wc.tool.run` can clear caches, reset data
- **Recommendation:** Require explicit confirmation parameter
- Consider read-only mode or dry-run option

---

### Phase 7: Migration/Internal (Low Priority - Admin Only)
**Target:** 37 abilities  
**Timeline:** Final release or optional

| Category | Abilities | Risk Level | Start With |
|----------|-----------|------------|------------|
| HPOS | 11 abilities | High | `wc.hpos.status`, `wc.hpos.compatibility-info` (read-only) |
| PALT | 9 abilities | Medium | `wc.palt.info` (read-only) |
| Blueprint | 2 abilities | High | `wc.blueprint.export` (read-only) |
| Update | 1 ability | Critical | Dry-run support, backup warning |
| COT | 5 abilities | Medium | **Deprecated** - HPOS supersedes this |
| COM | 3 abilities | Low | Requires interactive auth |

**Critical Safety Notes:**

| Ability | Risk | Required Safeguard |
|---------|------|-------------------|
| `wc.tool.run` | Data loss | Explicit confirmation parameter |
| `wc.update.run-db-updates` | DB migration | Dry-run option, backup warning |
| `wc.hpos.enable/disable` | Storage change | Sync verification required |
| `wc.blueprint.import` | Settings overwrite | Backup warning |
| `wc.refund.create` | Financial impact | Amount validation, confirmation |
| `wc.order.delete` | Data loss | Prefer soft delete (trash status) |

---

## Implementation Checklist

### Before Starting Development

- [ ] Create `AbstractWooCommerceAbility` base class in `src/Abilities/WooCommerce/`
- [ ] Add WooCommerce category registration to `Plugin::registerAbilityCategories()` at priority 5
- [ ] Add WooCommerce ability registration method to `Plugin::registerWooCommerceAbilities()` at priority 15
- [ ] Document naming convention in `docs/` directory
- [ ] Add WooCommerce detection to plugin requirements

### Per-Ability Implementation

- [ ] Extend `AbstractWooCommerceAbility`
- [ ] Follow naming convention: `wc.{category}.{action}`
- [ ] Use direct WooCommerce functions (`wc_get_order()`, `wc_get_product()`) over REST controllers
- [ ] Handle HPOS/legacy order storage (usually automatic with `wc_get_order()`)
- [ ] Write PHPUnit tests in `tests/phpunit/Abilities/WooCommerce/{Category}/`
- [ ] Add permission mappings in permission system
- [ ] Document in ability registry

### Testing Strategy

**Unit Tests:**
```php
// Example test structure
namespace FeatherArms\WpMcp\Tests\Abilities\WooCommerce\Customer;

use Brain\Monkey\Functions;
use FeatherArms\WpMcp\Abilities\WooCommerce\Customer\CreateCustomerAbility;

class CreateCustomerAbilityTest extends \PHPUnit\Framework\TestCase
{
    public function test_fails_when_woocommerce_not_active(): void
    {
        Functions\when('class_exists')->with('WooCommerce')->justReturn(false);
        
        $ability = new CreateCustomerAbility();
        $result = $ability->execute(['email' => 'test@example.com']);
        
        $this->assertFalse($result->isSuccess());
        $this->assertEquals('woocommerce_not_active', $result->getErrorCode());
    }
}
```

**REST API Tests:**
```bash
# Template for MCP testing
SESSION=$(curl -s -X POST \
  -H "Authorization: Basic $(echo -n 'admin:password' | base64)" \
  -H "Content-Type: application/json" \
  -d '{"jsonrpc":"2.0","id":1,"method":"initialize",...}' \
  "http://localhost/wp-json/mcp/mcp-adapter-default-server" -i 2>/dev/null | \
  grep -i "Mcp-Session-Id" | cut -d' ' -f2 | tr -d '\r')

# Test WooCommerce ability
curl -s -X POST \
  -H "Authorization: Basic $(echo -n 'admin:password' | base64)" \
  -H "Mcp-Session-Id: $SESSION" \
  -d '{"jsonrpc":"2.0","id":3,"method":"tools/call","params":{"name":"wc.customer.list","arguments":{}}}' \
  "http://localhost/wp-json/mcp/mcp-adapter-default-server" | jq .
```

---

## Architecture Patterns

### File Structure
```
src/Abilities/WooCommerce/
├── AbstractWooCommerceAbility.php
├── Customer/
│   ├── CreateCustomerAbility.php
│   ├── DeleteCustomerAbility.php
│   ├── GetCustomerAbility.php
│   ├── ListCustomersAbility.php
│   └── UpdateCustomerAbility.php
├── Product/
│   ├── CreateProductAbility.php
│   ├── DeleteProductAbility.php
│   ├── GetProductAbility.php
│   ├── ListProductsAbility.php
│   └── UpdateProductAbility.php
└── Order/
    ├── CreateOrderAbility.php
    ├── DeleteOrderAbility.php
    ├── GetOrderAbility.php
    ├── ListOrdersAbility.php
    └── UpdateOrderAbility.php
```

### Registration Pattern
```php
// In Plugin class
protected function registerWooCommerceAbilities(): void
{
    if (!class_exists('WooCommerce')) {
        return; // Skip registration if WooCommerce not active
    }
    
    // Register customer abilities
    $this->abilityRegistry->register(new CreateCustomerAbility());
    $this->abilityRegistry->register(new ListCustomersAbility());
    // ... etc
}
```

---

## Special Considerations

### 1. Authentication
- WooCommerce REST API uses consumer key/secret for external clients
- Internal abilities use standard WordPress authentication
- Abilities should leverage WordPress user capabilities (`manage_woocommerce`, etc.)

### 2. Pagination
- Most `list` abilities should support pagination parameters
- Use WooCommerce's built-in pagination: `per_page`, `page`, `offset`
- Return pagination metadata (total, pages, current_page)

### 3. Error Handling
- Map WooCommerce error codes to `ExecutionResult::failure()`
- Include WC error messages in response
- Handle `WP_Error` returns from WooCommerce functions

### 4. Data Sanitization
- Use WordPress sanitization functions (`sanitize_text_field()`, etc.)
- Validate email addresses, URLs, numeric values
- Apply WooCommerce validation rules (product SKU uniqueness, etc.)

### 5. Webhooks
- WooCommerce abilities should respect existing webhook triggers
- Don't duplicate webhook functionality (use WooCommerce's built-in system)
- Consider adding webhook events for ability executions

---

## Risk Assessment

### High-Risk Abilities (Require Extra Safeguards)

| Ability | Impact | Mitigation |
|---------|--------|------------|
| `wc.order.delete` | Revenue data loss | Implement trash status first, permanent delete requires confirmation |
| `wc.product.delete` | Catalog data loss | Check for existing orders using product before deletion |
| `wc.refund.create` | Financial impact | Validate amount ≤ order total, require confirmation |
| `wc.tool.run` | System integrity | Whitelist safe tools, require explicit confirmation for destructive tools |
| `wc.update.run-db-updates` | Database schema | Backup verification, dry-run mode |
| `wc.hpos.enable` | Data storage migration | Verify sync status, warn about plugin compatibility |

### Medium-Risk Abilities (Standard Validation)

- Customer CRUD (PII handling)
- Coupon CRUD (discount integrity)
- Tax configuration (calculation accuracy)
- Payment gateway updates (transaction flow)

### Low-Risk Abilities (Read-Only or Non-Critical)

- List operations
- Get operations
- Status/info queries
- Webhook delivery logs
- Customer downloads

---

## Dependencies

### Required PHP Extensions/Classes
- WooCommerce plugin (3.0+)
- PHP 8.1+
- WordPress 6.9+

### Optional Enhancements
- WooCommerce REST API Authentication (for external access)
- Action Scheduler (for background processing)
- WooCommerce Admin (for analytics integration)

---

## Success Metrics

### Phase 1 Completion Criteria
- [ ] 20 core commerce abilities implemented
- [ ] All abilities pass PHPUnit tests
- [ ] REST API testing completed
- [ ] Permission system configured
- [ ] Documentation written
- [ ] Rate limiting configured

### Overall Project Success
- [ ] 127 total abilities across 7 phases
- [ ] Zero DoingItWrong warnings
- [ ] 100% test coverage for critical paths
- [ ] Documentation for all abilities
- [ ] Safety constraints enforced
- [ ] HPOS/legacy compatibility verified

---

## Next Steps

1. **Create branch:** `feature/woocommerce-abilities-phase-1`
2. **Implement base class:** `AbstractWooCommerceAbility`
3. **Register category:** Add to `Plugin::registerAbilityCategories()`
4. **Build Phase 1:** Customer, Product, Order, ProductVariation abilities
5. **Write tests:** Full PHPUnit coverage
6. **Document:** Update `docs/MCP_ABILITY_TESTS.md` with WooCommerce abilities
7. **Test MCP integration:** Verify abilities work through MCP server
8. **PR review:** Code standards, security, architecture

---

## Questions for User

Before proceeding with implementation:

1. **Priority confirmation:** Should Phase 1 focus on all 4 categories (20 abilities), or start smaller?
2. **Read-only first?:** Should we implement `get`/`list` abilities before `create`/`update`/`delete`?
3. **Permission strategy:** Use WooCommerce's built-in caps (`manage_woocommerce`) or custom ability permissions?
4. **HPOS priority:** Should HPOS support be Phase 1 or can it wait until Phase 7?
5. **Testing environment:** Do we have a WooCommerce test instance for REST API testing?

---

**End of Implementation Summary**
