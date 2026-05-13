# Test Plan - Dynamic Pricing Engine (ksf_DynamicPricing_Core)

**Module:** ksf_DynamicPricing_Core  
**Version:** 1.0.0  
**Date:** May 2026  

---

## 1. Test Overview

### 1.1 Test Objectives

- Verify all functional requirements are met
- Achieve 100% code coverage on core classes
- Validate edge cases and boundary conditions
- Ensure regression safety for future modifications

### 1.2 Test Scope

| Component | Coverage Target |
|-----------|----------------|
| PricingEngine | 100% |
| PercentageDiscountRule | 100% |
| FixedDiscountRule | 100% |
| PricingRuleInterface | 100% |
| Edge cases | All documented |

---

## 2. Test Cases

### 2.1 PricingEngine Tests

#### TC-001: Add Single Rule

**Test ID:** TC-001  
**Requirement:** FR-001  
**Description:** Engine stores a single rule correctly

**Setup:**
```php
$engine = new PricingEngine();
$rule = new PercentageDiscountRule(10.0);
```

**Execution:**
```php
$engine->addRule($rule);
```

**Verification:**
- Rule count in engine: 1
- Rule can be retrieved

**Expected Result:** Rule added successfully

---

#### TC-002: Add Multiple Rules

**Test ID:** TC-002  
**Requirement:** FR-001  
**Description:** Engine stores multiple rules

**Setup:**
```php
$engine = new PricingEngine();
$rule1 = new PercentageDiscountRule(10.0, 1);
$rule2 = new FixedDiscountRule(5.0, 5);
$rule3 = new PercentageDiscountRule(15.0, 10);
```

**Execution:**
```php
$engine->addRule($rule1);
$engine->addRule($rule2);
$engine->addRule($rule3);
```

**Verification:**
- Rule count: 3
- All rules retrievable

**Expected Result:** All rules stored

---

#### TC-003: Rule Sorting by Priority

**Test ID:** TC-003  
**Requirement:** FR-002, FR-003  
**Description:** Rules evaluated in priority order

**Setup:**
```php
$engine = new PricingEngine();
$ruleA = new PercentageDiscountRule(10.0, 10);  // priority 10
$ruleB = new PercentageDiscountRule(20.0, 5);   // priority 5
$ruleC = new PercentageDiscountRule(30.0, 1);   // priority 1
```

**Execution:**
```php
$engine->addRule($ruleA);
$engine->addRule($ruleB);
$engine->addRule($ruleC);
$price = $engine->calculatePrice(100.0, ['quantity' => 1]);
```

**Verification:**
- Final price accounts for priority order (1, 5, 10)
- Rule with priority 1 applies first

**Expected Result:** Rules applied in order: 30% (priority 1), then 20% (priority 5), then 10% (priority 10)  
**Calculation:** 100 → 70 → 56 → 50.40

---

#### TC-004: Negative Price Prevention

**Test ID:** TC-004  
**Requirement:** FR-005  
**Description:** Engine prevents negative prices

**Setup:**
```php
$engine = new PricingEngine();
$rule = new FixedDiscountRule(50.0);  // More than base price
```

**Execution:**
```php
$engine->addRule($rule);
$price = $engine->calculatePrice(30.0, []);
```

**Verification:**
- Return value >= 0

**Expected Result:** 0.00

---

#### TC-005: Price Rounding

**Test ID:** TC-005  
**Requirement:** FR-006  
**Description:** Final prices rounded to 2 decimals

**Test Data:**
| Input Price | Rule | Expected Output |
|-------------|------|-----------------|
| 100.555 | 10% off | 90.50 |
| 100.554 | 10% off | 90.50 |
| 100.005 | 10% off | 90.00 |
| 100.999 | 10% off | 90.90 |

**Execution:**
```php
$engine->addRule(new PercentageDiscountRule(10.0));
$price = $engine->calculatePrice($inputPrice, []);
```

**Verification:**
- Output has exactly 2 decimal places
- Standard rounding (0.555 → 0.56, 0.554 → 0.55)

**Expected Result:** All outputs rounded correctly

---

#### TC-006: Empty Context Handling

**Test ID:** TC-006  
**Requirement:** FR-051  
**Description:** Engine handles empty context gracefully

**Setup:**
```php
$engine = new PricingEngine();
$rule = new PercentageDiscountRule(10.0);
$engine->addRule($rule);
```

**Execution:**
```php
$price = $engine->calculatePrice(100.0, []);
```

**Verification:**
- No exceptions thrown
- Default context values used

**Expected Result:** 90.00 (rule applies with defaults)

---

#### TC-007: Calculate Cart Total

**Test ID:** TC-007  
**Requirement:** FR-040, FR-041, FR-042  
**Description:** Cart calculation returns correct structure

**Setup:**
```php
$engine = new PricingEngine();
$cartItems = [
    ['product_id' => 1, 'price' => 50.00, 'quantity' => 2, 'category_ids' => [10]],
    ['product_id' => 2, 'price' => 30.00, 'quantity' => 1, 'category_ids' => [20]],
];
$rule = new PercentageDiscountRule(10.0, 10, false, ['category_ids' => [10]]);
$engine->addRule($rule);
```

**Execution:**
```php
$result = $engine->calculateCartTotal($cartItems, []);
```

**Verification:**
- subtotal = 130.00
- discount = 10.00
- total = 120.00
- item_details count = 2

**Expected Result:** Correct totals and structure

---

### 2.2 PercentageDiscountRule Tests

#### TC-008: Basic Percentage Discount

**Test ID:** TC-008  
**Requirement:** FR-025  
**Description:** Percentage discount calculated correctly

**Setup:**
```php
$rule = new PercentageDiscountRule(15.0, 10);
```

**Execution:**
```php
$context = ['quantity' => 1];
$resultPrice = $rule->apply(100.0, $context);
```

**Verification:**
- 100 - (100 * 0.15) = 85.00

**Expected Result:** 85.00

---

#### TC-009: Min Quantity Condition

**Test ID:** TC-009  
**Requirement:** FR-022  
**Description:** Min quantity condition enforced

**Setup:**
```php
$rule = new PercentageDiscountRule(20.0, 10, false, ['min_quantity' => 5]);
```

**Test Scenarios:**

| Context Quantity | Expected isApplicable |
|-----------------|----------------------|
| 3 | false |
| 4 | false |
| 5 | true |
| 10 | true |

**Execution:**
```php
$result = $rule->isApplicable(['quantity' => $qty]);
```

**Expected Result:** false for qty < 5, true for qty >= 5

---

#### TC-010: Max Quantity Condition

**Test ID:** TC-010  
**Requirement:** FR-023  
**Description:** Max quantity condition enforced

**Setup:**
```php
$rule = new PercentageDiscountRule(10.0, 10, false, ['max_quantity' => 3]);
```

**Test Scenarios:**

| Context Quantity | Expected isApplicable |
|-----------------|----------------------|
| 1 | true |
| 3 | true |
| 4 | false |
| 10 | false |

**Expected Result:** true for qty <= 3, false for qty > 3

---

#### TC-011: Category ID Condition

**Test ID:** TC-011  
**Requirement:** FR-024  
**Description:** Category matching works correctly

**Setup:**
```php
$rule = new PercentageDiscountRule(10.0, 10, false, ['category_ids' => [10, 20]]);
```

**Test Scenarios:**

| Context Categories | Expected Result |
|-------------------|-----------------|
| [10] | true (10 in condition) |
| [20] | true (20 in condition) |
| [10, 30] | true (10 in intersection) |
| [30, 40] | false (no intersection) |
| [] | false (empty intersection) |

**Expected Result:** Correct boolean based on intersection

---

#### TC-012: Percentage Clamping

**Test ID:** TC-012  
**Requirement:** FR-021  
**Description:** Percentage values clamped to 0-100

**Test Data:**
| Input Percentage | Stored Percentage |
|-----------------|-------------------|
| -10 | 0 |
| 0 | 0 |
| 50 | 50 |
| 100 | 100 |
| 150 | 100 |

**Execution:**
```php
$rule = new PercentageDiscountRule($input);
```

**Verification:**
- Internal percentage property

**Expected Result:** Negative → 0, Over 100 → 100

---

### 2.3 FixedDiscountRule Tests

#### TC-013: Basic Fixed Discount

**Test ID:** TC-013  
**Requirement:** FR-034  
**Description:** Fixed amount deducted from price

**Setup:**
```php
$rule = new FixedDiscountRule(25.0, 10);
```

**Execution:**
```php
$resultPrice = $rule->apply(100.0, []);
```

**Verification:**
- 100 - 25 = 75.00

**Expected Result:** 75.00

---

#### TC-014: Fixed Discount Floor

**Test ID:** TC-014  
**Requirement:** FR-034  
**Description:** Fixed discount cannot reduce price below zero

**Setup:**
```php
$rule = new FixedDiscountRule(50.0, 10);
```

**Execution:**
```php
$resultPrice = $rule->apply(30.0, []);
```

**Verification:**
- max(0, 30 - 50) = max(0, -20) = 0

**Expected Result:** 0.00

---

#### TC-015: Min Cart Total Condition

**Test ID:** TC-015  
**Requirement:** FR-033  
**Description:** Cart total minimum enforced

**Setup:**
```php
$rule = new FixedDiscountRule(20.0, 10, false, ['min_cart_total' => 100.00]);
```

**Test Scenarios:**

| Context Cart Total | Expected isApplicable |
|-------------------|----------------------|
| 50.00 | false |
| 99.99 | false |
| 100.00 | true |
| 150.00 | true |

**Expected Result:** true only when cart_total >= 100

---

#### TC-016: Amount Non-Negative

**Test ID:** TC-016  
**Requirement:** FR-031  
**Description:** Fixed amount ensured non-negative

**Test Data:**
| Input Amount | Stored Amount |
|-------------|----------------|
| -20 | 0 |
| 0 | 0 |
| 25 | 25 |
| -100 | 0 |

**Expected Result:** Negative input → 0 stored

---

### 2.4 Stop Processing Tests

#### TC-017: Rule Stops Processing

**Test ID:** TC-017  
**Requirement:** UC-008  
**Description:** Rule with stop flag halts rule chain

**Setup:**
```php
$engine = new PricingEngine();
$rule1 = new PercentageDiscountRule(20.0, 1, true);   // stops
$rule2 = new PercentageDiscountRule(30.0, 5, false);  // never reached
$engine->addRule($rule1);
$engine->addRule($rule2);
```

**Execution:**
```php
$price = $engine->calculatePrice(100.0, []);
```

**Verification:**
- Only rule1 applied (20% off)
- rule2 never evaluated

**Expected Result:** 80.00 (not 56.00)

---

#### TC-018: No Stop Flag

**Test ID:** TC-018  
**Requirement:** UC-008  
**Description:** Rules without stop flag don't halt chain

**Setup:**
```php
$engine = new PricingEngine();
$rule1 = new PercentageDiscountRule(20.0, 1, false);  // no stop
$rule2 = new PercentageDiscountRule(10.0, 5, false);  // applies
$engine->addRule($rule1);
$engine->addRule($rule2);
```

**Execution:**
```php
$price = $engine->calculatePrice(100.0, []);
```

**Verification:**
- Both rules apply sequentially

**Expected Result:** 72.00 (100 → 80 → 72)

---

### 2.5 Edge Case Tests

#### TC-019: No Rules Added

**Test ID:** TC-019  
**Description:** Calculate price with no rules

**Setup:**
```php
$engine = new PricingEngine();
```

**Execution:**
```php
$price = $engine->calculatePrice(99.99, []);
```

**Expected Result:** 99.99 (unchanged)

---

#### TC-020: All Rules Not Applicable

**Test ID:** TC-020  
**Description:** Context doesn't match any conditions

**Setup:**
```php
$engine = new PricingEngine();
$rule = new PercentageDiscountRule(10.0, 10, false, ['min_quantity' => 10]);
$engine->addRule($rule);
```

**Execution:**
```php
$price = $engine->calculatePrice(100.0, ['quantity' => 3]);
```

**Expected Result:** 100.00 (no discount applied)

---

#### TC-021: Zero Base Price

**Test ID:** TC-021  
**Description:** Handle zero base price

**Setup:**
```php
$engine = new PricingEngine();
$rule = new PercentageDiscountRule(10.0);
$engine->addRule($rule);
```

**Execution:**
```php
$price = $engine->calculatePrice(0.0, []);
```

**Expected Result:** 0.00

---

#### TC-022: Zero Quantity Context

**Test ID:** TC-022  
**Description:** Context with quantity=0

**Setup:**
```php
$rule = new PercentageDiscountRule(10.0, 10, false, ['min_quantity' => 5]);
```

**Execution:**
```php
$result = $rule->isApplicable(['quantity' => 0]);
```

**Expected Result:** false (0 < 5)

---

#### TC-023: Missing Quantity in Context

**Test ID:** TC-023  
**Description:** Context without quantity key

**Setup:**
```php
$rule = new PercentageDiscountRule(10.0, 10, false, ['min_quantity' => 5]);
```

**Execution:**
```php
$result = $rule->isApplicable([]);  // no quantity
```

**Expected Result:** false (defaults to 1, which is < 5)

---

#### TC-024: Cart Item Merged Context

**Test ID:** TC-024  
**Description:** Verify item-specific context merged correctly

**Setup:**
```php
$engine = new PricingEngine();
$rule = new PercentageDiscountRule(10.0, 10, false, ['category_ids' => [10]]);
$engine->addRule($rule);

$cartItems = [
    ['product_id' => 1, 'price' => 100.00, 'quantity' => 2, 'category_ids' => [10]],
    ['product_id' => 2, 'price' => 100.00, 'quantity' => 3, 'category_ids' => [20]],
];
```

**Execution:**
```php
$result = $engine->calculateCartTotal($cartItems, []);
```

**Verification:**
- Item 1: category [10] matches, 10% off
- Item 2: category [20] doesn't match, no discount

**Expected Result:** Item 1: 90.00 each, Item 2: 100.00 each

---

## 3. Test Data Matrix

| Test ID | Input | Context | Rules | Expected |
|---------|-------|---------|-------|----------|
| TC-001 | - | - | 1 rule | Rule stored |
| TC-003 | 100.00 | qty:1 | 3 rules priority 1,5,10 | 50.40 |
| TC-004 | 30.00 | - | Fixed($50) | 0.00 |
| TC-005 | varies | - | 10% | Rounded correctly |
| TC-007 | cart items | - | 10% on cats [10] | Subtotal:130, Discount:10 |
| TC-009 | 100.00 | qty varies | min_qty:5 | false if qty<5 |
| TC-013 | 100.00 | - | Fixed($25) | 75.00 |
| TC-015 | 100.00 | cart_total varies | min_cart:100 | false if <100 |
| TC-017 | 100.00 | - | 20% stop, 30% | 80.00 |
| TC-018 | 100.00 | - | 20%, 10% | 72.00 |

---

## 4. Pass Criteria

| Criterion | Measurement |
|-----------|-------------|
| All test cases pass | 100% pass rate |
| Code coverage | >= 95% |
| No regressions | All previous tests still pass |
| Edge cases handled | Zero exceptions on edge inputs |

---

## 5. Test Execution

### 5.1 Execution Environment

- PHP 8.2 (or highest available)
- PHPUnit 10.x
- No external dependencies (isolated testing)

### 5.2 Command

```bash
cd /home/kevin/Documents/ksf_DynamicPricing_Core
./vendor/bin/phpunit
```

### 5.3 CI Integration

Tests run on every push via GitHub Actions or local CI hook.

---

*Document Version: 1.0*  
*Last Updated: May 2026*