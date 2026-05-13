# UAT Plan - Dynamic Pricing Engine (ksf_DynamicPricing_Core)

**Module:** ksf_DynamicPricing_Core  
**Version:** 1.0.0  
**Date:** May 2026  

---

## 1. UAT Objectives

### 1.1 Primary Objectives

1. **Verify Pricing Accuracy**: Ensure all pricing rules produce correct calculations
2. **Validate Integration Readiness**: Confirm module can be integrated into platform adapters
3. **Confirm Edge Case Handling**: Test boundary conditions and error scenarios
4. **Validate Performance**: Ensure pricing calculations complete within acceptable time

### 1.2 Success Criteria

| Metric | Target |
|--------|--------|
| All UAT scenarios pass | 100% |
| Pricing calculation accuracy | 100% (matches expected) |
| Integration tests pass | 100% |
| No regressions | Zero breaking changes |
| Performance | < 100ms for 50-item cart |

---

## 2. UAT Scenarios

### 2.1 Scenario Set A: Single Item Pricing

#### UAT-S001: Basic Percentage Discount

**Scenario:** Apply 10% discount to single item  
**Test Data:**
- Base Price: $100.00
- Rule: PercentageDiscountRule(10%)
- Context: []

**Steps:**
1. Create PricingEngine
2. Add PercentageDiscountRule(10%)
3. Call calculatePrice(100.00, [])
4. Verify result is 90.00

**Expected Result:** 90.00 (± $0.01)

**Pass Criteria:**
- [ ] Result equals 90.00
- [ ] No exceptions thrown
- [ ] Return type is float

---

#### UAT-S002: Fixed Amount Discount

**Scenario:** Deduct $25 from item price  
**Test Data:**
- Base Price: $150.00
- Rule: FixedDiscountRule(25.00)
- Context: []

**Steps:**
1. Create PricingEngine
2. Add FixedDiscountRule(25.00)
3. Call calculatePrice(150.00, [])
4. Verify result is 125.00

**Expected Result:** 125.00

**Pass Criteria:**
- [ ] Result equals 125.00
- [ ] No exceptions thrown

---

#### UAT-S003: Multiple Sequential Rules

**Scenario:** Apply multiple rules in sequence  
**Test Data:**
- Base Price: $200.00
- Rule 1: PercentageDiscountRule(20%, priority: 1)
- Rule 2: FixedDiscountRule(10.00, priority: 5)
- Context: []

**Steps:**
1. Create PricingEngine
2. Add Rule 1, then Rule 2
3. Call calculatePrice(200.00, [])
4. Verify result

**Expected Result:** $150.00  
**Calculation:** 200 → (200 - 20%) = 160 → (160 - 10) = 150

**Pass Criteria:**
- [ ] Result equals 150.00
- [ ] Both rules applied
- [ ] Priority order correct (20% first, then $10)

---

#### UAT-S004: Negative Price Prevention

**Scenario:** Ensure price cannot go negative  
**Test Data:**
- Base Price: $30.00
- Rule: FixedDiscountRule(50.00)

**Steps:**
1. Create PricingEngine
2. Add FixedDiscountRule(50.00)
3. Call calculatePrice(30.00, [])
4. Verify result is not negative

**Expected Result:** 0.00

**Pass Criteria:**
- [ ] Result equals 0.00 (not negative)
- [ ] No warnings or errors

---

#### UAT-S005: Stop Processing Flag

**Scenario:** Rule stops further rule processing  
**Test Data:**
- Base Price: $100.00
- Rule 1: PercentageDiscountRule(15%, priority: 1, stop: true)
- Rule 2: PercentageDiscountRule(20%, priority: 5)
- Context: []

**Steps:**
1. Create PricingEngine
2. Add Rule 1 (stop flag true)
3. Add Rule 2
4. Call calculatePrice(100.00, [])
5. Verify only Rule 1 applied

**Expected Result:** 85.00 (only 15% applied, not 20%)

**Pass Criteria:**
- [ ] Result equals 85.00 (not 68.00)
- [ ] Rule 2 never applied
- [ ] shouldStopProcessing() returns true for Rule 1

---

### 2.2 Scenario Set B: Condition Evaluation

#### UAT-S006: Min Quantity Condition

**Scenario:** Discount applies only when quantity meets minimum  
**Test Data:**
- Base Price: $100.00
- Rule: PercentageDiscountRule(10%, conditions: [min_quantity: 5])
- Context: {quantity: 7}

**Steps:**
1. Create PricingEngine with rule
2. Call calculatePrice(100.00, [quantity: 7])
3. Verify discount applied

**Expected Result:** 90.00 (10% applied)

**Pass Criteria:**
- [ ] Result equals 90.00 when quantity = 7
- [ ] Result equals 100.00 when quantity = 3

---

#### UAT-S007: Category Condition

**Scenario:** Discount applies only to specific categories  
**Test Data:**
- Base Price: $100.00
- Rule: PercentageDiscountRule(10%, conditions: [category_ids: [10, 20]])
- Context: {category_ids: [10]}

**Steps:**
1. Create PricingEngine with rule
2. Test with context containing matching category
3. Test with context containing non-matching category

**Expected Result:** 90.00 when category matches, 100.00 when no match

**Pass Criteria:**
- [ ] Discount applies when category in list
- [ ] No discount when category not in list
- [ ] Multiple category matching works

---

#### UAT-S008: Min Cart Total Condition

**Scenario:** Fixed discount requires minimum cart value  
**Test Data:**
- Base Price: $50.00
- Rule: FixedDiscountRule(10.00, conditions: [min_cart_total: 100.00])
- Context: {cart_total: 150.00}

**Steps:**
1. Create PricingEngine with rule
2. Test with cart_total >= min
3. Test with cart_total < min

**Expected Result:** $40.00 when cart_total >= 100, $50.00 when < 100

**Pass Criteria:**
- [ ] Discount applies when cart_total = 150.00
- [ ] No discount when cart_total = 50.00

---

### 2.3 Scenario Set C: Cart Calculations

#### UAT-S009: Multi-Item Cart Total

**Scenario:** Calculate total for cart with multiple items  
**Test Data:**
- Cart Items:
  - Item 1: price=50.00, qty=2, cats=[10]
  - Item 2: price=30.00, qty=1, cats=[20]
- Rule: PercentageDiscountRule(10%, conditions: [category_ids: [10]])
- Context: []

**Steps:**
1. Create PricingEngine with rule
2. Call calculateCartTotal(cartItems, context)
3. Verify subtotal, discount, total

**Expected Result:**
- Subtotal: $130.00
- Discount: $10.00 (10% of item 1 only)
- Total: $120.00

**Pass Criteria:**
- [ ] Subtotal equals 130.00
- [ ] Discount equals 10.00
- [ ] Total equals 120.00
- [ ] item_details has 2 entries

---

#### UAT-S010: Context Propagation Per Item

**Scenario:** Each cart item gets merged context with product-specific data  
**Test Data:**
- Base Context: {user_id: 123}
- Cart Items:
  - Item 1: cats=[10], qty=1
  - Item 2: cats=[20], qty=2

**Steps:**
1. Create rule with category condition
2. Call calculateCartTotal with base context
3. Verify each item evaluated with its own cats

**Expected Result:**
- Item 1 (cats [10]): discounted
- Item 2 (cats [20]): not discounted

**Pass Criteria:**
- [ ] Item 1 has lower price (category match)
- [ ] Item 2 has original price (no match)
- [ ] Base context available in each item context

---

### 2.4 Scenario Set D: Edge Cases

#### UAT-S011: Empty Context

**Scenario:** Calculate price with empty context array  
**Test Data:**
- Base Price: $100.00
- Rule: PercentageDiscountRule(10%)
- Context: []

**Steps:**
1. Create PricingEngine with rule
2. Call calculatePrice(100.00, [])
3. Verify no exceptions

**Expected Result:** 90.00 (default context used)

**Pass Criteria:**
- [ ] No exceptions thrown
- [ ] Result equals 90.00
- [ ] Defaults applied correctly

---

#### UAT-S012: No Rules Added

**Scenario:** Calculate price with no rules  
**Test Data:**
- Base Price: $99.99
- Rules: (none)
- Context: []

**Steps:**
1. Create PricingEngine (no rules)
2. Call calculatePrice(99.99, [])
3. Verify unchanged price returned

**Expected Result:** 99.99

**Pass Criteria:**
- [ ] Result equals 99.99
- [ ] No rules evaluated

---

#### UAT-S013: All Rules Not Applicable

**Scenario:** No rules match context conditions  
**Test Data:**
- Base Price: $100.00
- Rule: PercentageDiscountRule(10%, conditions: [min_quantity: 10])
- Context: {quantity: 3}

**Steps:**
1. Create PricingEngine with rule
2. Call calculatePrice(100.00, [quantity: 3])
3. Verify no discount applied

**Expected Result:** 100.00

**Pass Criteria:**
- [ ] Result equals 100.00
- [ ] isApplicable() returned false

---

#### UAT-S014: Zero Base Price

**Scenario:** Calculate with $0 base price  
**Test Data:**
- Base Price: $0.00
- Rule: PercentageDiscountRule(10%)

**Steps:**
1. Create PricingEngine with rule
2. Call calculatePrice(0.00, [])
3. Verify zero result

**Expected Result:** 0.00

**Pass Criteria:**
- [ ] Result equals 0.00
- [ ] No negative value returned

---

#### UAT-S015: Price Rounding Accuracy

**Scenario:** Verify proper rounding to 2 decimals  
**Test Data:**
- Base Price: $100.555
- Rule: PercentageDiscountRule(10%)

**Steps:**
1. Create PricingEngine with rule
2. Call calculatePrice(100.555, [])
3. Verify rounded result

**Expected Result:** 90.50 (standard rounding)

**Pass Criteria:**
- [ ] Result equals 90.50
- [ ] Not 90.55 (down) or 90.56 (banker's)

---

## 3. UAT Test Execution

### 3.1 Execution Matrix

| Scenario | Tester | Date | Result | Sign-off |
|----------|--------|------|--------|----------|
| UAT-S001 | | | | |
| UAT-S002 | | | | |
| UAT-S003 | | | | |
| UAT-S004 | | | | |
| UAT-S005 | | | | |
| UAT-S006 | | | | |
| UAT-S007 | | | | |
| UAT-S008 | | | | |
| UAT-S009 | | | | |
| UAT-S010 | | | | |
| UAT-S011 | | | | |
| UAT-S012 | | | | |
| UAT-S013 | | | | |
| UAT-S014 | | | | |
| UAT-S015 | | | | |

### 3.2 Issue Log

| Issue ID | Scenario | Description | Severity | Status |
|----------|----------|-------------|----------|--------|
| | | | | |

---

## 4. Sign-off Criteria

### 4.1 Prerequisites for Sign-off

- [ ] All 15 scenarios executed
- [ ] All scenarios pass (100% pass rate)
- [ ] No critical or high severity issues open
- [ ] Code coverage >= 95%
- [ ] Performance within acceptable limits

### 4.2 Sign-off Declaration

| Role | Name | Date | Signature |
|------|------|------|-----------|
| UAT Lead | | | |
| Technical Lead | | | |
| Product Owner | | | |

---

## 5. Known Limitations

| Limitation | Impact | Workaround |
|------------|--------|------------|
| No persistence layer | Rules not saved between requests | Platform adapter implements storage |
| No admin UI | Cannot configure rules via UI | Configure rules in code |
| Limited rule types | Only percentage and fixed | Add custom rule implementations |

---

## 6. Defect Severity Definitions

| Severity | Definition | Example |
|----------|------------|---------|
| Critical | System unusable, data corruption | Negative prices returned |
| High | Major functionality broken | Rules not applied |
| Medium | Minor functionality affected | Rounding incorrect |
| Low | Cosmetic issue | Minor display issues |

---

*Document Version: 1.0*  
*Last Updated: May 2026*