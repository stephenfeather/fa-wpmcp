# FA-WPMCP V2: WooCommerce Abilities Roadmap

This document outlines the WooCommerce abilities planned for Version 2 of the FA-WPMCP plugin.

**Generated:** 2026-01-26
**WP-CLI Source:** `wp wc` commands from WooCommerce CLI

---

## Prerequisites

### WooCommerce Installation Check

**All WooCommerce abilities MUST verify that WooCommerce is installed and active before execution.**

Implementation pattern:

```php
public function execute(array $args): ExecutionResult
{
    // Check WooCommerce is active
    if (!class_exists('WooCommerce') || !function_exists('WC')) {
        return ExecutionResult::failure(
            'WooCommerce is not installed or not active',
            'woocommerce_not_active'
        );
    }

    // Proceed with ability execution...
}
```

Alternative check using WordPress plugin API:
```php
if (!is_plugin_active('woocommerce/woocommerce.php')) {
    return ExecutionResult::failure(
        'WooCommerce plugin is not active',
        'woocommerce_not_active'
    );
}
```

**Recommendation:** Create a base `AbstractWooCommerceAbility` class that performs this check automatically, similar to how multisite-only abilities check `is_multisite()`.

---

## Summary

| Category | Subcommands | Abilities Count | Priority |
|----------|-------------|-----------------|----------|
| **Customer** | create, delete, get, list, update | 5 | High |
| **Product** | create, delete, get, list, update | 5 | High |
| **Shop Order** | create, delete, get, list, update | 5 | High |
| **Shop Coupon** | create, delete, get, list, update | 5 | Medium |
| **Product Variation** | create, delete, get, list, update | 5 | High |
| **Product Category** | create, delete, get, list, update | 5 | Medium |
| **Product Tag** | create, delete, get, list, update | 5 | Medium |
| **Product Brand** | create, delete, get, list, update | 5 | Low |
| **Product Attribute** | create, delete, get, list, update | 5 | Medium |
| **Product Attribute Term** | create, delete, get, list, update | 5 | Medium |
| **Product Shipping Class** | create, delete, get, list, update | 5 | Low |
| **Product Review** | create, delete, get, list, update | 5 | Low |
| **Tax** | create, delete, get, list, update | 5 | Medium |
| **Tax Class** | create, delete, get, list | 4 | Medium |
| **Shipping Zone** | create, delete, get, list, update | 5 | Medium |
| **Shipping Zone Method** | create, delete, get, list, update | 5 | Medium |
| **Shipping Zone Location** | list | 1 | Low |
| **Shipping Method** | get, list | 2 | Low |
| **Payment Gateway** | get, list, update | 3 | Medium |
| **Webhook** | create, delete, get, list, update | 5 | Medium |
| **Webhook Delivery** | get, list | 2 | Low |
| **Order Note** | create, delete, get, list | 4 | Medium |
| **Shop Order Refund** | create, delete, get, list | 4 | Medium |
| **Customer Download** | list | 1 | Low |
| **Tool** | list, run | 2 | Low |
| **Tracker** | snapshot | 1 | Low |
| **Update** | (runs DB updates) | 1 | Low |
| **HPOS** | backfill, cleanup, compatibility-info, etc. | 11 | Low |
| **COT** | count_unmigrated, disable, enable, sync, verify | 5 | Low |
| **PALT** | abort, cleanup, disable, enable, info, etc. | 9 | Low |
| **Blueprint** | export, import | 2 | Low |
| **COM** | connect, disconnect, extension | 3 | Low |

**Total Potential Abilities: ~127**

---

## Category Details

### 1. Customer (wc.customer)

**WP-CLI:** `wp wc customer <command>`

| Subcommand | Proposed Ability | Description |
|------------|------------------|-------------|
| create | `wc.customer.create` | Create a new customer |
| delete | `wc.customer.delete` | Delete an existing customer |
| get | `wc.customer.get` | Get a single customer by ID |
| list | `wc.customer.list` | List all customers |
| update | `wc.customer.update` | Update an existing customer |

---

### 2. Product (wc.product)

**WP-CLI:** `wp wc product <command>`

| Subcommand | Proposed Ability | Description |
|------------|------------------|-------------|
| create | `wc.product.create` | Create a new product |
| delete | `wc.product.delete` | Delete an existing product |
| get | `wc.product.get` | Get a single product by ID |
| list | `wc.product.list` | List all products |
| update | `wc.product.update` | Update an existing product |

---

### 3. Shop Order (wc.order)

**WP-CLI:** `wp wc shop_order <command>`

| Subcommand | Proposed Ability | Description |
|------------|------------------|-------------|
| create | `wc.order.create` | Create a new order |
| delete | `wc.order.delete` | Delete an existing order |
| get | `wc.order.get` | Get a single order by ID |
| list | `wc.order.list` | List all orders |
| update | `wc.order.update` | Update an existing order |

---

### 4. Shop Coupon (wc.coupon)

**WP-CLI:** `wp wc shop_coupon <command>`

| Subcommand | Proposed Ability | Description |
|------------|------------------|-------------|
| create | `wc.coupon.create` | Create a new coupon |
| delete | `wc.coupon.delete` | Delete an existing coupon |
| get | `wc.coupon.get` | Get a single coupon by ID |
| list | `wc.coupon.list` | List all coupons |
| update | `wc.coupon.update` | Update an existing coupon |

---

### 5. Product Variation (wc.product-variation)

**WP-CLI:** `wp wc product_variation <command>`

| Subcommand | Proposed Ability | Description |
|------------|------------------|-------------|
| create | `wc.product-variation.create` | Create a new product variation |
| delete | `wc.product-variation.delete` | Delete an existing variation |
| get | `wc.product-variation.get` | Get a single variation by ID |
| list | `wc.product-variation.list` | List all variations for a product |
| update | `wc.product-variation.update` | Update an existing variation |

---

### 6. Product Category (wc.product-cat)

**WP-CLI:** `wp wc product_cat <command>`

| Subcommand | Proposed Ability | Description |
|------------|------------------|-------------|
| create | `wc.product-cat.create` | Create a new product category |
| delete | `wc.product-cat.delete` | Delete an existing category |
| get | `wc.product-cat.get` | Get a single category by ID |
| list | `wc.product-cat.list` | List all product categories |
| update | `wc.product-cat.update` | Update an existing category |

---

### 7. Product Tag (wc.product-tag)

**WP-CLI:** `wp wc product_tag <command>`

| Subcommand | Proposed Ability | Description |
|------------|------------------|-------------|
| create | `wc.product-tag.create` | Create a new product tag |
| delete | `wc.product-tag.delete` | Delete an existing tag |
| get | `wc.product-tag.get` | Get a single tag by ID |
| list | `wc.product-tag.list` | List all product tags |
| update | `wc.product-tag.update` | Update an existing tag |

---

### 8. Product Brand (wc.product-brand)

**WP-CLI:** `wp wc product_brand <command>`

| Subcommand | Proposed Ability | Description |
|------------|------------------|-------------|
| create | `wc.product-brand.create` | Create a new product brand |
| delete | `wc.product-brand.delete` | Delete an existing brand |
| get | `wc.product-brand.get` | Get a single brand by ID |
| list | `wc.product-brand.list` | List all product brands |
| update | `wc.product-brand.update` | Update an existing brand |

---

### 9. Product Attribute (wc.product-attribute)

**WP-CLI:** `wp wc product_attribute <command>`

| Subcommand | Proposed Ability | Description |
|------------|------------------|-------------|
| create | `wc.product-attribute.create` | Create a new attribute |
| delete | `wc.product-attribute.delete` | Delete an existing attribute |
| get | `wc.product-attribute.get` | Get a single attribute by ID |
| list | `wc.product-attribute.list` | List all product attributes |
| update | `wc.product-attribute.update` | Update an existing attribute |

---

### 10. Product Attribute Term (wc.product-attribute-term)

**WP-CLI:** `wp wc product_attribute_term <command>`

| Subcommand | Proposed Ability | Description |
|------------|------------------|-------------|
| create | `wc.product-attribute-term.create` | Create a new attribute term |
| delete | `wc.product-attribute-term.delete` | Delete an existing term |
| get | `wc.product-attribute-term.get` | Get a single term by ID |
| list | `wc.product-attribute-term.list` | List all attribute terms |
| update | `wc.product-attribute-term.update` | Update an existing term |

---

### 11. Product Shipping Class (wc.shipping-class)

**WP-CLI:** `wp wc product_shipping_class <command>`

| Subcommand | Proposed Ability | Description |
|------------|------------------|-------------|
| create | `wc.shipping-class.create` | Create a new shipping class |
| delete | `wc.shipping-class.delete` | Delete an existing class |
| get | `wc.shipping-class.get` | Get a single class by ID |
| list | `wc.shipping-class.list` | List all shipping classes |
| update | `wc.shipping-class.update` | Update an existing class |

---

### 12. Product Review (wc.review)

**WP-CLI:** `wp wc product_review <command>`

| Subcommand | Proposed Ability | Description |
|------------|------------------|-------------|
| create | `wc.review.create` | Create a new product review |
| delete | `wc.review.delete` | Delete an existing review |
| get | `wc.review.get` | Get a single review by ID |
| list | `wc.review.list` | List all product reviews |
| update | `wc.review.update` | Update an existing review |

---

### 13. Tax (wc.tax)

**WP-CLI:** `wp wc tax <command>`

| Subcommand | Proposed Ability | Description |
|------------|------------------|-------------|
| create | `wc.tax.create` | Create a new tax rate |
| delete | `wc.tax.delete` | Delete an existing tax rate |
| get | `wc.tax.get` | Get a single tax rate by ID |
| list | `wc.tax.list` | List all tax rates |
| update | `wc.tax.update` | Update an existing tax rate |

---

### 14. Tax Class (wc.tax-class)

**WP-CLI:** `wp wc tax_class <command>`

| Subcommand | Proposed Ability | Description |
|------------|------------------|-------------|
| create | `wc.tax-class.create` | Create a new tax class |
| delete | `wc.tax-class.delete` | Delete an existing tax class |
| get | `wc.tax-class.get` | Get a single tax class by slug |
| list | `wc.tax-class.list` | List all tax classes |

**Note:** No update command available for tax classes.

---

### 15. Shipping Zone (wc.shipping-zone)

**WP-CLI:** `wp wc shipping_zone <command>`

| Subcommand | Proposed Ability | Description |
|------------|------------------|-------------|
| create | `wc.shipping-zone.create` | Create a new shipping zone |
| delete | `wc.shipping-zone.delete` | Delete an existing zone |
| get | `wc.shipping-zone.get` | Get a single zone by ID |
| list | `wc.shipping-zone.list` | List all shipping zones |
| update | `wc.shipping-zone.update` | Update an existing zone |

---

### 16. Shipping Zone Method (wc.shipping-zone-method)

**WP-CLI:** `wp wc shipping_zone_method <command>`

| Subcommand | Proposed Ability | Description |
|------------|------------------|-------------|
| create | `wc.shipping-zone-method.create` | Create a new shipping method |
| delete | `wc.shipping-zone-method.delete` | Delete an existing method |
| get | `wc.shipping-zone-method.get` | Get a single method by ID |
| list | `wc.shipping-zone-method.list` | List methods in a zone |
| update | `wc.shipping-zone-method.update` | Update an existing method |

---

### 17. Shipping Zone Location (wc.shipping-zone-location)

**WP-CLI:** `wp wc shipping_zone_location <command>`

| Subcommand | Proposed Ability | Description |
|------------|------------------|-------------|
| list | `wc.shipping-zone-location.list` | List locations in a shipping zone |

---

### 18. Shipping Method (wc.shipping-method)

**WP-CLI:** `wp wc shipping_method <command>`

| Subcommand | Proposed Ability | Description |
|------------|------------------|-------------|
| get | `wc.shipping-method.get` | Get a single shipping method |
| list | `wc.shipping-method.list` | List all shipping methods |

**Note:** Read-only - no create/update/delete available.

---

### 19. Payment Gateway (wc.payment-gateway)

**WP-CLI:** `wp wc payment_gateway <command>`

| Subcommand | Proposed Ability | Description |
|------------|------------------|-------------|
| get | `wc.payment-gateway.get` | Get a single payment gateway |
| list | `wc.payment-gateway.list` | List all payment gateways |
| update | `wc.payment-gateway.update` | Update gateway settings |

**Note:** No create/delete - gateways are registered by plugins.

---

### 20. Webhook (wc.webhook)

**WP-CLI:** `wp wc webhook <command>`

| Subcommand | Proposed Ability | Description |
|------------|------------------|-------------|
| create | `wc.webhook.create` | Create a new webhook |
| delete | `wc.webhook.delete` | Delete an existing webhook |
| get | `wc.webhook.get` | Get a single webhook by ID |
| list | `wc.webhook.list` | List all webhooks |
| update | `wc.webhook.update` | Update an existing webhook |

---

### 21. Webhook Delivery (wc.webhook-delivery)

**WP-CLI:** `wp wc webhook_delivery <command>`

| Subcommand | Proposed Ability | Description |
|------------|------------------|-------------|
| get | `wc.webhook-delivery.get` | Get a single delivery log |
| list | `wc.webhook-delivery.list` | List webhook delivery logs |

**Note:** Read-only log of webhook deliveries.

---

### 22. Order Note (wc.order-note)

**WP-CLI:** `wp wc order_note <command>`

| Subcommand | Proposed Ability | Description |
|------------|------------------|-------------|
| create | `wc.order-note.create` | Create a new order note |
| delete | `wc.order-note.delete` | Delete an existing note |
| get | `wc.order-note.get` | Get a single note by ID |
| list | `wc.order-note.list` | List all notes for an order |

**Note:** No update command - notes are immutable.

---

### 23. Shop Order Refund (wc.refund)

**WP-CLI:** `wp wc shop_order_refund <command>`

| Subcommand | Proposed Ability | Description |
|------------|------------------|-------------|
| create | `wc.refund.create` | Create a new refund |
| delete | `wc.refund.delete` | Delete an existing refund |
| get | `wc.refund.get` | Get a single refund by ID |
| list | `wc.refund.list` | List all refunds for an order |

**Note:** No update command - refunds are immutable.

---

### 24. Customer Download (wc.customer-download)

**WP-CLI:** `wp wc customer_download <command>`

| Subcommand | Proposed Ability | Description |
|------------|------------------|-------------|
| list | `wc.customer-download.list` | List customer download permissions |

**Note:** Read-only access to download permissions.

---

### 25. Tool (wc.tool)

**WP-CLI:** `wp wc tool <command>`

| Subcommand | Proposed Ability | Description |
|------------|------------------|-------------|
| list | `wc.tool.list` | List available WooCommerce tools |
| run | `wc.tool.run` | Run a WooCommerce tool |

**Safety Notes:**
- Tools can be destructive (clear transients, reset data, etc.)
- Consider read-only mode or confirmation requirements

---

### 26. Tracker (wc.tracker)

**WP-CLI:** `wp wc tracker <command>`

| Subcommand | Proposed Ability | Description |
|------------|------------------|-------------|
| snapshot | `wc.tracker.snapshot` | Get WooCommerce tracker data snapshot |

---

### 27. Update (wc.update)

**WP-CLI:** `wp wc update`

| Subcommand | Proposed Ability | Description |
|------------|------------------|-------------|
| (none) | `wc.update.run-db-updates` | Run pending WooCommerce database updates |

**Safety Notes:**
- This runs database migrations
- Should require explicit confirmation
- Consider dry-run option if available

---

## Administrative/Internal Categories

These are lower-priority categories for internal WooCommerce management.

### 28. HPOS (High-Performance Order Storage)

**WP-CLI:** `wp wc hpos <command>`

| Subcommand | Proposed Ability | Description |
|------------|------------------|-------------|
| backfill | `wc.hpos.backfill` | Backfill order from HPOS or posts |
| cleanup | `wc.hpos.cleanup` | Remove redundant postmeta for migrated orders |
| compatibility-info | `wc.hpos.compatibility-info` | Show plugin HPOS compatibility |
| compatibility-mode | `wc.hpos.compatibility-mode` | Toggle compatibility mode |
| count_unmigrated | `wc.hpos.count-unmigrated` | Count orders not yet migrated |
| diff | `wc.hpos.diff` | Show differences between HPOS and posts |
| disable | `wc.hpos.disable` | Disable HPOS |
| enable | `wc.hpos.enable` | Enable HPOS |
| status | `wc.hpos.status` | Show HPOS status summary |
| sync | `wc.hpos.sync` | Sync order data between datastores |
| verify_data | `wc.hpos.verify-data` | Verify migrated order data |

**Safety Notes:**
- Most of these are administrative/migration commands
- Can significantly affect order storage
- Recommend read-only abilities (status, count, diff, compatibility-info) first

---

### 29. COT (Custom Order Tables - Legacy)

**WP-CLI:** `wp wc cot <command>`

**Note:** This appears to be the legacy version of HPOS commands.

| Subcommand | Proposed Ability | Description |
|------------|------------------|-------------|
| count_unmigrated | `wc.cot.count-unmigrated` | Count unmigrated orders |
| disable | `wc.cot.disable` | Disable custom order tables |
| enable | `wc.cot.enable` | Enable custom order tables |
| sync | `wc.cot.sync` | Sync order data |
| verify_cot_data | `wc.cot.verify` | Verify custom order table data |

---

### 30. PALT (Product Attributes Lookup Table)

**WP-CLI:** `wp wc palt <command>`

| Subcommand | Proposed Ability | Description |
|------------|------------------|-------------|
| abort_regeneration | `wc.palt.abort` | Abort background regeneration |
| cleanup_regeneration_progress | `wc.palt.cleanup` | Delete temporary regeneration data |
| disable | `wc.palt.disable` | Disable lookup table usage |
| enable | `wc.palt.enable` | Enable lookup table usage |
| info | `wc.palt.info` | Get lookup table information |
| initiate_regeneration | `wc.palt.initiate` | Start background regeneration |
| regenerate | `wc.palt.regenerate` | Regenerate immediately (not background) |
| regenerate_for_product | `wc.palt.regenerate-product` | Regenerate for single product |
| resume_regeneration | `wc.palt.resume` | Resume aborted regeneration |

---

### 31. Blueprint

**WP-CLI:** `wp wc blueprint <command>`

| Subcommand | Proposed Ability | Description |
|------------|------------------|-------------|
| export | `wc.blueprint.export` | Export WooCommerce settings blueprint |
| import | `wc.blueprint.import` | Import WooCommerce settings blueprint |

**Safety Notes:**
- Import can overwrite store settings
- Consider read-only (export) first

---

### 32. COM (WooCommerce.com Connection)

**WP-CLI:** `wp wc com <command>`

| Subcommand | Proposed Ability | Description |
|------------|------------------|-------------|
| connect | `wc.com.connect` | Connect to WooCommerce.com |
| disconnect | `wc.com.disconnect` | Disconnect from WooCommerce.com |
| extension | `wc.com.extension` | Manage WCCOM extensions |

**Safety Notes:**
- Involves authentication with WooCommerce.com
- May require interactive setup

---

## Implementation Priorities

### Phase 1: Core Commerce (High Priority)
1. **Customer** - Full CRUD
2. **Product** - Full CRUD
3. **Shop Order** - Full CRUD
4. **Product Variation** - Full CRUD

### Phase 2: Catalog Management (Medium Priority)
5. **Product Category** - Full CRUD
6. **Product Tag** - Full CRUD
7. **Product Attribute** - Full CRUD
8. **Product Attribute Term** - Full CRUD

### Phase 3: Commerce Operations (Medium Priority)
9. **Shop Coupon** - Full CRUD
10. **Tax** - Full CRUD
11. **Tax Class** - CRUD (no update)
12. **Order Note** - CRD (no update)
13. **Shop Order Refund** - CRD (no update)

### Phase 4: Shipping Configuration (Medium Priority)
14. **Shipping Zone** - Full CRUD
15. **Shipping Zone Method** - Full CRUD
16. **Payment Gateway** - Read + Update

### Phase 5: Integrations (Medium-Low Priority)
17. **Webhook** - Full CRUD
18. **Webhook Delivery** - Read only
19. **Shipping Method** - Read only
20. **Shipping Zone Location** - Read only

### Phase 6: Administrative (Low Priority)
21. **Tool** - List + Run (with safety constraints)
22. **Customer Download** - Read only
23. **Tracker** - Snapshot only

### Phase 7: Internal/Migration (Low Priority - Admin Only)
24. **HPOS** - Status/Info commands first
25. **PALT** - Info command first
26. **Blueprint** - Export first
27. **Update** - With confirmation
28. **COT** - Legacy, may skip
29. **COM** - Requires interactive auth

---

## Safety Constraints

### Destructive Operations
The following operations should have additional safeguards:

| Operation | Risk | Safeguard |
|-----------|------|-----------|
| `wc.tool.run` | Can clear caches, reset data | Require explicit confirmation |
| `wc.update.run-db-updates` | Database migrations | Dry-run option, backup warning |
| `wc.hpos.enable/disable` | Changes order storage | Sync verification required |
| `wc.blueprint.import` | Overwrites settings | Backup warning |
| `wc.refund.create` | Financial impact | Amount validation |
| `wc.order.delete` | Data loss | Soft delete preference |

### Read-Only Recommendations
Start with read-only abilities for sensitive areas:
- HPOS: status, count_unmigrated, diff, compatibility-info
- PALT: info
- Tool: list (not run)
- Webhook Delivery: get, list
- Customer Download: list

---

## Notes

1. **WP-CLI REST API Layer**: WooCommerce CLI uses the REST API internally, so abilities may need to work through WC REST endpoints.

2. **Authentication**: WooCommerce REST API requires consumer key/secret for some operations.

3. **HPOS Consideration**: Modern WooCommerce uses HPOS (High-Performance Order Storage). Order abilities should work with both legacy and HPOS datastores.

4. **Deprecation Warning**: The `wc cot` commands appear to be deprecated in favor of `wc hpos`.

5. **WP-CLI Deprecation Warnings**: There are strlen() deprecation warnings from `wp-cli/restful` that should be addressed upstream.
