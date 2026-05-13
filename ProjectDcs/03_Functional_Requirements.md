# Functional Requirements - Dynamic Pricing Engine (ksf_DynamicPricing_Core)

**Module:** ksf_DynamicPricing_Core  
**Version:** 1.0.0  
**Date:** May 2026  

---

## 1. Introduction

This document specifies the functional requirements for the Dynamic Pricing Engine module. All requirements are categorized by priority and traced to corresponding use cases.

---

## 2. Requirements Specification

### 2.1 Core Engine Requirements

| ID | Requirement | Priority | Type |
|----|-------------|----------|------|
| **FR-001** | The engine shall accept any class implementing `PricingRuleInterface` via the `addRule()` method | MUST | Functional |
| **FR-002** | The engine shall sort rules by priority (ascending) before evaluation | MUST | Functional |
| **FR-003** | The engine shall evaluate rules in priority order | MUST | Functional |
| **FR-004** | The engine shall return the adjusted price after all applicable rules have been processed | MUST | Functional |
| **FR-005** | The engine shall enforce a minimum price of zero (prices cannot go negative) | MUST | Functional |
| **FR-006** | The engine shall round all final prices to 2 decimal places | MUST | Functional |

#### FR-001: Add Rule Capability

**Description:** The engine must accept rule implementations through dependency injection.

**Acceptance Criteria:**
- `PricingEngine::addRule(PricingRuleInterface $rule)` accepts any rule implementation
- Rules are stored in the order they were added
- Multiple calls to `addRule()` accumulate rules (no replacement)
- No exception is thrown for valid rule implementations

**Test Scenarios:**
- Add single rule and verify it's stored
- Add multiple rules and verify count
- Add rule of different types (percentage, fixed) and verify both stored

#### FR-002: Rule Sorting by Priority

**Description:** Rules must be evaluated in priority order where lower numeric value means higher priority.

**Acceptance Criteria:**
- When rules have priorities 10, 5, and 1, priority 1 rule is evaluated first
- Sorting happens at calculation time (not at addRule time)
- Original order is preserved when priorities are equal
- Sorting is stable (consistent results on repeated calls)

**Test Scenarios:**
- Rules with priorities 1, 5, 10 sorted as 1, 5, 10
- Rules with equal priorities maintain relative order
- Mixed positive integer priorities handled correctly

#### FR-003: Rule Evaluation Order

**Description:** Each rule is evaluated in sorted order; `isApplicable()` is called before `apply()`.

**Acceptance Criteria:**
- For each rule: `isApplicable()` is called with full context
- If `isApplicable()` returns true, `apply()` is called with current price
- If `isApplicable()` returns false, rule is skipped
- Price is updated after each applicable rule's `apply()`

**Test Scenarios:**
- Rule A (priority 1) applies 10% discount, Rule B (priority 5) applies $5 fixed
- Verify price goes: base → after A → after B
- Verify no double-application of same rule

#### FR-004: Adjusted Price Return

**Description:** Final returned price reflects all rule applications.

**Acceptance Criteria:**
- Return value represents the price after all applicable rules
- Return type is float
- Return value is positive (≥ 0)

**Test Scenarios:**
- Single 20% discount on $100 returns $80
- Multiple sequential discounts calculated correctly
- Zero rules applicable returns base price unchanged

#### FR-005: Price Floor Enforcement

**Description:** The engine must prevent negative prices.

**Acceptance Criteria:**
- If rule would result in negative price, final price is 0
- `max(0, adjustedPrice)` is applied to final result
- Fixed amount discounts cannot reduce price below 0
- Percentage discounts cannot reduce price below 0

**Test Scenarios:**
- $10 price with $15 fixed discount returns $0 (not -$5)
- $50 price with 150% discount returns $0 (not negative)
- Multiple discounts that would exceed original price return $0

#### FR-006: Price Rounding

**Description:** Final prices are rounded to 2 decimal places.

**Acceptance Criteria:**
- `round($price, 2)` is applied to final result
- $10.555 returns $10.56
- $10.554 returns $10.55
- $10.5 returns $10.50

**Test Scenarios:**
- Verify 3 decimal input rounds to 2 decimals
- Verify 1 decimal input gets trailing zero
- Verify whole numbers work correctly

---

### 2.2 Rule Interface Requirements

| ID | Requirement | Priority | Type |
|----|-------------|----------|------|
| **FR-010** | All rules must implement `PricingRuleInterface` | MUST | Functional |
| **FR-011** | `isApplicable()` must accept context array and return boolean | MUST | Functional |
| **FR-012** | `apply()` must accept current price and context, return adjusted price | MUST | Functional |
| **FR-013** | `getPriority()` must return integer priority value | MUST | Functional |
| **FR-014** | `shouldStopProcessing()` must return boolean indicating chain break | MUST | Functional |

#### FR-011: isApplicable() Contract

**Description:** Rules must evaluate their conditions against the provided context.

**Acceptance Criteria:**
- Method signature: `public function isApplicable(array $context): bool`
- Returns true if rule conditions are met
- Returns false if any condition fails
- Must not throw exceptions for missing context keys

**Test Scenarios:**
- Condition with quantity >= min_quantity returns true
- Condition with quantity < min_quantity returns false
- Empty context handled gracefully (no exception)

#### FR-012: apply() Contract

**Description:** Rules must calculate and return adjusted price.

**Acceptance Criteria:**
- Method signature: `public function apply(float $price, array $context): float`
- Returns the modified price (not cumulative with previous modifications)
- Price modifications are additive across multiple rules
- Must not return negative values

**Test Scenarios:**
- Percentage rule returns lower price
- Fixed rule returns lower price (capped at 0)
- Context can influence calculation (e.g., tiered pricing)

---

### 2.3 PercentageDiscountRule Requirements

| ID | Requirement | Priority | Type |
|----|-------------|----------|------|
| **FR-020** | Constructor accepts percentage, priority, stop flag, and conditions | MUST | Functional |
| **FR-021** | Percentage is clamped between 0 and 100 | MUST | Functional |
| **FR-022** | Minimum quantity condition checked | MUST | Functional |
| **FR-023** | Maximum quantity condition checked | MUST | Functional |
| **FR-024** | Category ID intersection checked | MUST | Functional |
| **FR-025** | Discount calculated as percentage of current price | MUST | Functional |

#### FR-020: Constructor Parameters

**Description:** Rule accepts all configuration via constructor.

**Acceptance Criteria:**
- `new PercentageDiscountRule(15.0, 10, false, [])`
- Percentage: float, required
- Priority: int, defaults to 10
- Stop processing: bool, defaults to false
- Conditions: array, defaults to empty

**Test Scenarios:**
- All parameters provided
- Only percentage provided (defaults for others)
- Empty conditions array accepted

#### FR-022: Minimum Quantity Check

**Description:** Rule applies only if quantity meets minimum threshold.

**Acceptance Criteria:**
- Condition key: `'min_quantity'`
- If context quantity < min_quantity, `isApplicable()` returns false
- If no min_quantity condition, check passes

**Test Scenarios:**
- Condition min_quantity: 5, context quantity: 7 → applicable
- Condition min_quantity: 5, context quantity: 3 → not applicable
- No min_quantity condition → always applicable (other checks pass)

#### FR-024: Category ID Check

**Description:** Rule applies only if product belongs to specified categories.

**Acceptance Criteria:**
- Condition key: `'category_ids'` (array of category IDs)
- Uses `array_intersect()` to check overlap
- If any context category matches condition categories, check passes
- If no category_ids condition, check passes

**Test Scenarios:**
- Condition category_ids: [10, 20], context category_ids: [10, 30] → applicable
- Condition category_ids: [10, 20], context category_ids: [30, 40] → not applicable
- No category condition → always applicable

---

### 2.4 FixedDiscountRule Requirements

| ID | Requirement | Priority | Type |
|----|-------------|----------|------|
| **FR-030** | Constructor accepts amount, priority, stop flag, and conditions | MUST | Functional |
| **FR-031** | Amount is ensured to be non-negative | MUST | Functional |
| **FR-032** | Minimum quantity condition checked | MUST | Functional |
| **FR-033** | Minimum cart total condition checked | MUST | Functional |
| **FR-034** | Discount deducted from price with floor at zero | MUST | Functional |

#### FR-033: Minimum Cart Total Check

**Description:** Fixed discounts can require minimum cart value.

**Acceptance Criteria:**
- Condition key: `'min_cart_total'`
- Context must have cart_total >= condition value
- If no cart_total in context, check fails

**Test Scenarios:**
- Condition min_cart_total: 100, context cart_total: 150 → applicable
- Condition min_cart_total: 100, context cart_total: 50 → not applicable
- No cart_total in context → not applicable

---

### 2.5 Cart Calculation Requirements

| ID | Requirement | Priority | Type |
|----|-------------|----------|------|
| **FR-040** | Cart calculation accepts array of cart items | MUST | Functional |
| **FR-041** | Each item processed with merged context | MUST | Functional |
| **FR-042** | Return includes subtotal, discount, total, item details | MUST | Functional |
| **FR-043** | Per-item discount calculated correctly | MUST | Functional |
| **FR-044** | Item context includes product_id, category_ids, quantity | MUST | Functional |
| **FR-045** | Item context merged with base context | MUST | Functional |

#### FR-042: Return Structure

**Description:** Cart calculation returns comprehensive breakdown.

**Return Format:**
```php
[
    'subtotal' => 130.00,        // Sum of original prices * quantities
    'discount' => 10.00,         // Total discount amount
    'total' => 120.00,           // Final amount to charge
    'item_details' => [          // Per-item breakdown
        [
            'product_id' => 1,
            'original_price' => 50.00,
            'final_price' => 45.00,
            'quantity' => 2,
            'discount' => 10.00,
            'total' => 90.00
        ],
        // ... more items
    ]
]
```

**Acceptance Criteria:**
- All numeric values are floats rounded to 2 decimals
- item_details is an array (empty if no items)
- subtotal equals sum of (original_price * quantity)
- total equals subtotal - discount

**Test Scenarios:**
- Single item cart returns correct structure
- Multiple items with varying discounts
- Items with no applicable rules (no discount)

---

## 3. Data Handling Requirements

### 3.1 Input Validation

| ID | Requirement | Priority | Type |
|----|-------------|----------|------|
| **FR-050** | Base price must be non-negative | MUST | Functional |
| **FR-051** | Negative base prices result in 0 | MUST | Functional |
| **FR-052** | Invalid context structure handled gracefully | MUST | Functional |

### 3.2 Output Specifications

| ID | Requirement | Priority | Type |
|----|-------------|----------|------|
| **FR-060** | All monetary values returned as float | MUST | Functional |
| **FR-061** | All monetary values rounded to 2 decimal places | MUST | Functional |
| **FR-062** | No null values in return structure | MUST | Functional |

---

## 4. Edge Cases

| Case | Expected Behavior |
|------|------------------|
| No rules added | Returns base price unchanged |
| Empty context | Rules with no conditions apply; rules with conditions skip |
| Zero quantity | Treated as quantity 0; min_quantity conditions fail |
| Zero cart total | min_cart_total conditions fail |
| Missing category_ids | Category conditions fail (empty intersection) |
| Multiple applicable rules | All apply in priority order |

---

## 5. Requirements Traceability Matrix

| Requirement ID | Use Case | Test Case |
|--------------|----------|-----------|
| FR-001 | UC-001 | TC-001 |
| FR-002 | UC-001 | TC-002 |
| FR-003 | UC-002 | TC-003 |
| FR-004 | UC-001 | TC-001 |
| FR-005 | UC-003 | TC-004 |
| FR-006 | UC-001 | TC-005 |
| FR-020 | UC-002 | TC-006 |
| FR-022 | UC-004 | TC-007 |
| FR-024 | UC-005 | TC-008 |
| FR-031 | UC-003 | TC-004 |
| FR-033 | UC-006 | TC-009 |
| FR-042 | UC-007 | TC-010 |

---

## 6. Constraints

| Constraint | Description |
|-----------|-------------|
| PHP Version | >= 7.3 (8.0+ recommended) |
| No External Dependencies | Framework-agnostic; no third-party libs |
| Single File per Class | Each class in its own file |

---

*Document Version: 1.0*  
*Last Updated: May 2026*