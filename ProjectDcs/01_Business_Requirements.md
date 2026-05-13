# Business Requirements - Dynamic Pricing Engine (ksf_DynamicPricing_Core)

**Module:** ksf_DynamicPricing_Core  
**Version:** 1.0.0  
**Date:** May 2026  
**Author:** Ksfraser Development Team  

---

## 1. Executive Summary

The Dynamic Pricing Engine is a platform-agnostic pricing rules engine designed to provide flexible, rule-based pricing functionality across multiple e-commerce and business platforms. This module extracts pricing logic from WooCommerce integration into a reusable, testable business logic layer that can be adapted for WordPress, FrontAccounting, or any other PHP-based platform.

The engine enables businesses to implement complex pricing strategies including percentage discounts, fixed amount reductions, quantity-based pricing, customer segmentation, and cart-level promotions—all configurable without code changes.

---

## 2. Problem Statement

### 2.1 Current Challenges

Organizations face significant challenges when implementing dynamic pricing strategies:

1. **Pricing Logic Duplication**: Pricing rules are often hardcoded within platform-specific implementations, leading to duplicated code across WooCommerce, WordPress, FrontAccounting, and custom applications.

2. **Lack of Reusability**: Business users cannot share pricing rules between different sales channels (e-commerce, POS, B2B portal) without custom development.

3. **Maintenance Complexity**: When pricing strategies change, developers must modify code in multiple locations, increasing the risk of inconsistencies and bugs.

4. **Limited Flexibility**: Existing pricing systems often support only basic discount types (percentage off or fixed amount), leaving complex scenarios like tiered pricing, customer-segment-based pricing, and time-sensitive promotions requiring custom code.

5. **Testing Difficulties**: Embedded pricing logic is difficult to unit test in isolation, leading to integration bugs and reduced confidence in pricing calculations.

### 2.2 Business Impact

- Revenue leakage from unapplied promotional pricing
- Operational inefficiency from manual pricing updates
- Customer dissatisfaction from inconsistent pricing across channels
- Developer time wasted on repeated pricing implementation work

---

## 3. Project Scope

### 3.1 In Scope

| Component | Description |
|-----------|-------------|
| Pricing Engine Core | Main orchestrator that evaluates and applies rules |
| PricingRuleInterface | Contract for all rule implementations |
| PercentageDiscountRule | Percentage-based discount calculation |
| FixedDiscountRule | Fixed amount deduction from price |
| Rule Priority System | Sorting and processing order control |
| Stop Processing Flag | Ability to halt rule chain |
| Cart Calculation | Full cart total computation with discount breakdown |
| Context Propagation | Pass-through of contextual data (product, user, cart) |
| Unit Tests | Comprehensive test coverage for core functionality |

### 3.2 Out of Scope

- User interface for rule configuration (platform-specific)
- Persistent rule storage (database adapters)
- Admin panel for rule management
- Integration with specific platforms (handled by platform adapters)
- Caching mechanisms
- Rule versioning

---

## 4. Features and Capabilities

### 4.1 Core Features

#### 4.1.1 Rule-Based Pricing Architecture

The engine follows an open/closed principle where new rule types can be added without modifying existing code:

```php
interface PricingRuleInterface {
    public function isApplicable(array $context): bool;
    public function apply(float $price, array $context): float;
    public function getPriority(): int;
    public function shouldStopProcessing(): bool;
}
```

**Key Capabilities:**
- Rules are self-contained units of pricing logic
- Each rule declares its applicability conditions
- Rules can halt further rule processing when appropriate
- Priority system ensures predictable rule application order

#### 4.1.2 Multi-Condition Support

Rules support flexible condition matching:

| Condition Type | Description | Example |
|---------------|-------------|---------|
| `min_quantity` | Minimum item quantity | Buy 5+ items for discount |
| `max_quantity` | Maximum item quantity | First 3 items at discount |
| `category_ids` | Product category match | Electronics category discount |
| `user_roles` | Customer role matching | VIP customer pricing |
| `min_cart_total` | Cart subtotal threshold | Spend $100+ for discount |

#### 4.1.3 Pricing Engine Methods

**addRule(PricingRuleInterface $rule): void**
- Registers a new pricing rule with the engine
- Rules are stored in order of addition for later sorting by priority

**calculatePrice(float $basePrice, array $context): float**
- Evaluates all applicable rules against the given context
- Returns the final price after all applicable rules have been applied
- Minimum return value is 0 (price cannot go negative)

**calculateCartTotal(array $cartItems, array $context): array**
- Calculates pricing for entire shopping cart
- Returns subtotal, discount amount, final total, and per-item breakdown
- Each item in cart can have different pricing based on its context

### 4.2 Rule Types

#### 4.2.1 PercentageDiscountRule

Applies a percentage discount to the current price:

```php
$rule = new PercentageDiscountRule(
    percentage: 15.0,
    priority: 10,
    stopProcessing: false,
    conditions: [
        'min_quantity' => 3,
        'category_ids' => [10, 20, 30]
    ]
);
```

**Calculation:** `final_price = original_price - (original_price * percentage / 100)`

#### 4.2.2 FixedDiscountRule

Deducts a fixed amount from the current price:

```php
$rule = new FixedDiscountRule(
    amount: 25.00,
    priority: 5,
    stopProcessing: false,
    conditions: [
        'min_cart_total' => 200.00
    ]
);
```

**Calculation:** `final_price = max(0, original_price - fixed_amount)`

---

## 5. Use Cases

### 5.1 Bulk Purchase Discount

**Scenario:** Customers purchasing 5 or more items in a category receive 10% off

```
Context: { product_id: 123, category_ids: [10], quantity: 7, user_id: 456 }

Rule: PercentageDiscountRule
  - percentage: 10
  - conditions: { min_quantity: 5, category_ids: [10] }

Result: 10% discount applied, $70.00 -> $63.00
```

### 5.2 Tiered Pricing

**Scenario:** Progressive discounts based on cart total

```
Context: { product_id: 789, cart_total: 250.00 }

Rules (evaluated by priority):
  - FixedDiscountRule (priority: 1): $50 off if cart >= $200
  - PercentageDiscountRule (priority: 10): 5% off all items

Result: Both rules apply sequentially
```

### 5.3 Customer Segment Pricing

**Scenario:** VIP customers receive additional 5% discount

```
Context: { product_id: 456, user_roles: ['vip', 'preferred'], quantity: 1 }

Rules:
  - PercentageDiscountRule (priority: 5): 5% VIP discount
    (stops processing additional rules)

Result: VIP discount applied, no further discounts
```

---

## 6. Integration Dependencies

### 6.1 Required Dependencies

| Package | Purpose | Version Constraint |
|---------|---------|-------------------|
| `php` | Runtime | >= 7.3 (8.0+ recommended) |

### 6.1 Internal Dependencies

| Module | Purpose |
|--------|---------|
| `Ksfraser\DynamicPricing\PricingEngine` | Core engine class |
| `Ksfraser\DynamicPricing\PricingRuleInterface` | Rule contract |

### 6.2 Platform Adapter Dependencies

Platform-specific adapters are required to integrate with actual systems:

| Platform | Adapter Module | Purpose |
|----------|----------------|---------|
| WooCommerce | ksf_WOO_DynamicPricing | WooCommerce integration |
| WordPress | ksf_WP_DynamicPricing | WordPress plugin integration |
| FrontAccounting | ksf_FA_DynamicPricing | FA module integration |

---

## 7. Technical Constraints

### 7.1 PHP Version Requirements

- **Minimum:** PHP 7.3
- **Recommended:** PHP 8.0+ (for named arguments, union types)
- **Target:** PHP 8.2

### 7.2 Performance Requirements

- Rule evaluation must complete within 100ms for typical cart (≤50 items)
- Memory footprint must remain under 10MB for standard operations
- Engine must support at least 100 rules per cart without performance degradation

### 7.3 Data Handling

- All monetary values are represented as floats, rounded to 2 decimal places
- Zero or negative prices are prevented (floor at 0.00)
- Cart calculations include per-item and cart-level breakdowns

---

## 8. Success Criteria

| Criterion | Measurement |
|-----------|-------------|
| All unit tests pass | 100% pass rate on CI |
| Rule priority sorting works | Verified by test suite |
| Context propagation correct | All context fields accessible in rules |
| Cart calculation accurate | Match expected results in test data |
| No breaking changes | Backward compatible with 1.0.0 |

---

## 9. Non-Functional Requirements

### 9.1 Maintainability
- All classes have complete PHPDoc documentation
- Interface-based design allows easy extension
- Single Responsibility Principle applied (each rule type handles one discount type)

### 9.2 Testability
- 100% unit test coverage on core classes
- Mockable dependencies through constructor injection
- No static state that prevents parallel test execution

### 9.3 Reusability
- Framework-agnostic design (no WordPress, WooCommerce, or FA dependencies)
- Namespaced under `Ksfraser\DynamicPricing\` for clear ownership
- No global state or singletons

---

## 10. Future Roadmap

| Version | Feature | Description |
|---------|---------|-------------|
| 1.1.0 | TieredPricingRule | Quantity-based tier pricing |
| 1.2.0 | TimeBasedRule | Date/time-sensitive pricing |
| 1.3.0 | CustomerGroupRule | Segment-based pricing |
| 2.0.0 | RuleRepository | Database-backed rule storage |
| 2.0.0 | RuleBuilder | Fluent API for rule construction |

---

## 11. Glossary

| Term | Definition |
|------|------------|
| Pricing Rule | A self-contained unit of pricing logic that can be evaluated and applied |
| Context | Collection of data (product, user, cart) passed to rules for evaluation |
| Priority | Integer value determining rule processing order (lower = higher priority) |
| Stop Processing | Flag indicating no further rules should be evaluated |
| Base Price | Original price before any rules are applied |
| Adjusted Price | Final price after rules have been evaluated |
| Cart Item | Single line item in a shopping cart with price and quantity |

---

*Document Version: 1.0*  
*Last Updated: May 2026*