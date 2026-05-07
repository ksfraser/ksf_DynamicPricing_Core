# BABOK Documentation - KSF Dynamic Pricing Core

## Business Analysis Body of Knowledge Mapping

### Business Goals
- **BG-1**: Increase sales through dynamic discount strategies
- **BG-2**: Reward customer loyalty with targeted pricing
- **BG-3**: Clear inventory through bulk/quantity discounts
- **BG-4**: Maximize revenue with cart-level promotions

### Stakeholders
| Role | Description | Priority |
|------|-------------|----------|
| Store Owners | Configure pricing rules and promotions | High |
| Customers | Receive discounts based on rules | High |
| Developers | Integrate pricing engine with FA/other systems | Medium |
| Marketing | Design promotion campaigns | Medium |

### Business Requirements (BABOK Task: Define Business Case)

#### BR-1: Flexible Pricing Rules
- **BR-1.1**: System must support percentage-based discounts
- **BR-1.2**: System must support fixed amount discounts
- **BR-1.3**: Rules must have priority ordering (lower = higher priority)
- **BR-1.4**: Rules must support "stop processing" flag (first match wins)

#### BR-2: Rule Conditions
- **BR-2.1**: Rules must filter by product category
- **BR-2.2**: Rules must filter by quantity (min/max)
- **BR-2.3**: Rules must filter by cart total
- **BR-2.4**: Rules must filter by user roles (future)

#### BR-3: Bulk/Quantity Pricing
- **BR-3.1**: System must support tiered pricing (different prices at different quantity levels)
- **BR-3.2**: Quantity conditions must be per-product and per-cart
- **BR-3.3**: System must calculate optimal price when multiple tiers match

#### BR-4: Cart-Level Promotions
- **BR-4.1**: System must apply discounts to entire cart subtotal
- **BR-4.2**: Cart total threshold must be configurable
- **BR-4.3**: System must calculate total discount across all items

### Solution Assessment (BABOK Task: Assess Proposed Solution)

#### Current State (WooCommerce Dynamic Pricing Analysis)
- ✅ Mature plugin with extensive rule types
- ✅ Supports product, category, and cart-based rules
- ❌ Tightly coupled to WooCommerce cart/order objects
- ❌ Admin UI embedded in WooCommerce settings
- ❌ Limited to WooCommerce ecosystem

#### Future State (KSF Dynamic Pricing Core)
- ✅ Framework-agnostic (PSR-4, PHP 7.3+)
- ✅ Context-based rule evaluation (array-based, not object-dependent)
- ✅ Interface-based design (PricingRuleInterface)
- ✅ Supports cart total calculation with item details
- ✅ FA integration via separate ksf_FA_DynamicPricing module

### Transition Requirements
- **TR-1**: FA integration module must pass FA cart/order data as context array
- **TR-2**: Pricing rules must be stored in FA database (new table: fa_pricing_rules)
- **TR-3**: Admin UI for rule management must be built in FA
- **TR-4**: Rule priority must be configurable via admin interface

### Risk Analysis
| Risk | Impact | Probability | Mitigation |
|------|--------|-------------|------------|
| Incorrect discount calculation | High | Low | TDD with PHPUnit test coverage |
| Rule conflict (multiple matches) | Medium | Medium | Priority system + stop_processing flag |
| Performance degradation (100+ items) | Medium | Low | Short-circuit evaluation, rule caching |
| Cart total mismatch | High | Low | Clear context structure, validation |

### Performance Metrics
- Pricing calculation: <500ms for 100 items (target)
- Rule evaluation: O(n) where n = number of rules
- Short-circuit: Stops on first match when stop_processing=true

### Compliance Requirements
- **Tax Compliance**: Discounts must not affect tax calculation (handled separately)
- **Audit**: All applied rules must be logged for order audit trail
- **Transparency**: Customers must see applied discounts on invoice
