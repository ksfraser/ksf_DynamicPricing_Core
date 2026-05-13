# Use Case Specification - Dynamic Pricing Engine (ksf_DynamicPricing_Core)

**Module:** ksf_DynamicPricing_Core  
**Version:** 1.0.0  
**Date:** May 2026  

---

## 1. Use Case Overview

| ID | Use Case | Actor | Priority |
|----|----------|-------|----------|
| UC-001 | Calculate Single Item Price | Platform Adapter, Service Layer | HIGH |
| UC-002 | Apply Bulk Discount Rule | PricingEngine | HIGH |
| UC-003 | Prevent Negative Pricing | PricingEngine | HIGH |
| UC-004 | Evaluate Quantity Conditions | PricingRule | MEDIUM |
| UC-005 | Evaluate Category Conditions | PricingRule | MEDIUM |
| UC-006 | Evaluate Cart Total Conditions | PricingRule | MEDIUM |
| UC-007 | Calculate Cart Total | Platform Adapter, Service Layer | HIGH |
| UC-008 | Stop Rule Processing | PricingRule | MEDIUM |
| UC-009 | Process Multiple Rules Sequentially | PricingEngine | HIGH |

---

## 2. Use Case Definitions

### UC-001: Calculate Single Item Price

**Actor:** Platform Adapter (WooCommerce, FA, etc.)  
**Precondition:** PricingEngine has rules registered  
**Trigger:** Product price needs to be calculated for display or cart

**Steps:**
1. Platform adapter calls `PricingEngine::calculatePrice(basePrice, context)`
2. Engine sorts registered rules by priority (ascending)
3. Engine initializes `adjustedPrice = basePrice`
4. For each rule in priority order:
   a. Engine calls `rule.isApplicable(context)`
   b. If true, engine calls `rule.apply(adjustedPrice, context)`
   c. Engine updates `adjustedPrice` with return value
   d. Engine checks `rule.shouldStopProcessing()`
   e. If true, break loop
5. Engine applies price floor: `max(0, adjustedPrice)`
6. Engine rounds to 2 decimals: `round(price, 2)`
7. Engine returns final price

**Postcondition:** Returned price reflects all applicable rule applications

**Success Scenario:**
```
Input: basePrice=100.00, context=[quantity:5, category_ids:[10]]
Rules: [PercentageRule(10%, priority:5), FixedRule($5, priority:10)]

Result: 85.00
(100 - 10%) = 90, (90 - 5) = 85
```

**Failure Scenarios:**
- No rules applicable: return base price unchanged
- All rules return not applicable: return base price unchanged

---

### UC-002: Apply Bulk Discount Rule

**Actor:** PercentageDiscountRule  
**Precondition:** Rule added to engine with conditions  
**Trigger:** Rule is evaluated during price calculation

**Steps:**
1. Engine calls `PercentageDiscountRule::isApplicable(context)`
2. Rule checks min_quantity condition:
   a. Compare context['quantity'] with conditions['min_quantity']
   b. If insufficient, return false
3. Rule checks max_quantity condition (if exists):
   a. Compare context['quantity'] with conditions['max_quantity']
   b. If exceeds, return false
4. Rule checks category_ids condition (if exists):
   a. Intersect context['category_ids'] with conditions['category_ids']
   b. If empty intersection, return false
5. If all checks pass, return true
6. Engine calls `PercentageDiscountRule::apply(price, context)`
7. Rule calculates: `price * (percentage / 100)`
8. Rule returns: `price - discount`

**Postcondition:** Price reduced by percentage amount

**Success Scenario:**
```
Conditions: min_quantity: 5, category_ids: [10, 20]
Context: quantity: 7, category_ids: [10, 30]
Rule: percentage: 15%

Result: isApplicable=true
apply: 100 - (100 * 0.15) = 85
```

**Failure Scenarios:**
- Quantity 3, min_quantity 5: return not applicable
- Categories [30, 40], required [10, 20]: return not applicable

---

### UC-003: Prevent Negative Pricing

**Actor:** PricingEngine  
**Precondition:** Rule would reduce price below zero  
**Trigger:** Rule apply() returns negative value or base price is negative

**Steps:**
1. After each rule `apply()` call, engine receives price
2. Engine applies floor check: `if (price < 0) price = 0`
3. This happens after each rule application
4. Final return also has floor: `max(0, round(adjustedPrice, 2))`

**Postcondition:** Price never negative; minimum is 0.00

**Success Scenario:**
```
Input: basePrice=10.00, rules=[FixedRule($15)]
FixedRule.apply(10, context) = -5
Engine floor: max(0, -5) = 0

Result: 0.00
```

---

### UC-004: Evaluate Quantity Conditions

**Actor:** PricingRule (any type with quantity condition)  
**Precondition:** Rule has quantity conditions configured  
**Trigger:** Rule.isApplicable() called

**Steps:**
1. Extract quantity from context: `$qty = $context['quantity'] ?? 1`
2. If conditions['min_quantity'] exists:
   a. If $qty < conditions['min_quantity'], return false
3. If conditions['max_quantity'] exists:
   a. If $qty > conditions['max_quantity'], return false
4. Return true (all quantity checks passed)

**Postcondition:** Returns whether quantity meets rule requirements

**Success Scenario:**
```
Context: quantity=7
Condition: min_quantity=5, max_quantity=10
Result: true (7 >= 5 and 7 <= 10)
```

**Failure Scenario:**
```
Context: quantity=2
Condition: min_quantity=5
Result: false (2 < 5)
```

---

### UC-005: Evaluate Category Conditions

**Actor:** PricingRule (any type with category condition)  
**Precondition:** Rule has category_ids condition  
**Trigger:** Rule.isApplicable() called

**Steps:**
1. Extract category_ids from context: `$cats = $context['category_ids'] ?? []`
2. If conditions['category_ids'] is empty, return true
3. If $cats is empty, return false (no match possible)
4. Calculate intersection: `array_intersect($cats, conditions['category_ids'])`
5. If intersection is non-empty, return true
6. If intersection is empty, return false

**Postcondition:** Returns whether product matches any required category

**Success Scenario:**
```
Context: category_ids=[10, 30, 50]
Condition: category_ids=[10, 20]
Intersection: [10]
Result: true (10 is in both)
```

**Failure Scenario:**
```
Context: category_ids=[30, 40]
Condition: category_ids=[10, 20]
Intersection: []
Result: false (no common categories)
```

---

### UC-006: Evaluate Cart Total Conditions

**Actor:** FixedDiscountRule  
**Precondition:** Rule has min_cart_total condition  
**Trigger:** Rule.isApplicable() called

**Steps:**
1. Extract cart_total from context: `$total = $context['cart_total'] ?? 0`
2. If conditions['min_cart_total'] exists:
   a. If $total < conditions['min_cart_total'], return false
3. Return true (cart total requirement met)

**Postcondition:** Returns whether cart meets minimum threshold

**Success Scenario:**
```
Context: cart_total=250.00
Condition: min_cart_total=200.00
Result: true (250 >= 200)
```

**Failure Scenario:**
```
Context: cart_total=150.00
Condition: min_cart_total=200.00
Result: false (150 < 200)
```

---

### UC-007: Calculate Cart Total

**Actor:** Platform Adapter (e-commerce checkout)  
**Precondition:** Cart has multiple items  
**Trigger:** Cart total needed for checkout/summary

**Steps:**
1. Adapter calls `PricingEngine::calculateCartTotal(cartItems, context)`
2. Engine initializes: `subtotal=0, totalDiscount=0, itemDetails=[]`
3. For each item in cartItems:
   a. Merge base context with item-specific context:
      - Add product_id, category_ids, quantity from item
   b. Get original price: `$original = $item['price']`
   c. Call `calculatePrice(original, itemContext)`
   d. Calculate item totals:
      - itemDiscount = (original - final) * quantity
      - itemTotal = final * quantity
   e. Accumulate subtotal, discount, item details
4. Calculate final total: `total = subtotal - discount`
5. Round all values to 2 decimals
6. Return result structure

**Postcondition:** Returns complete cart breakdown with per-item pricing

**Success Scenario:**
```
Cart Items: [
  {product_id:1, price:50.00, qty:2, cats:[10]},
  {product_id:2, price:30.00, qty:1, cats:[20]}
]
Rules: [BulkRule(10%, min_qty:3, cats:[10])]

Result:
{
  subtotal: 130.00,
  discount: 10.00,        // (50-45)*2
  total: 120.00,
  item_details: [
    {product_id:1, original:50.00, final:45.00, qty:2, discount:10.00, total:90.00},
    {product_id:2, original:30.00, final:30.00, qty:1, discount:0.00, total:30.00}
  ]
}
```

---

### UC-008: Stop Rule Processing

**Actor:** PricingRule (with stop flag enabled)  
**Precondition:** Rule configured with shouldStopProcessing=true  
**Trigger:** Rule is applicable and applied

**Steps:**
1. Rule is applicable and applied
2. Engine checks `rule.shouldStopProcessing()`
3. If returns true:
   a. Engine breaks out of rule loop
   b. No further rules are evaluated
4. Engine proceeds to floor check and return

**Postcondition:** Only rules evaluated before stop rule apply

**Success Scenario:**
```
Rules: [
  VIPRule(5%, priority:1, stop:true),    // VIP gets exclusive discount
  StandardRule(10%, priority:5)           // Never applied to VIPs
]

Context: user_roles=['vip']

VIPRule is applicable, applied, shouldStop=true
StandardRule is NOT evaluated

Result: Only VIP discount applies
```

---

### UC-009: Process Multiple Rules Sequentially

**Actor:** PricingEngine  
**Precondition:** Multiple rules registered with varying priorities  
**Trigger:** Price calculation with multiple applicable rules

**Steps:**
1. Rules sorted by priority (lower number = higher priority)
2. First rule evaluated, applied if applicable
3. Price updated with first rule's adjustment
4. Second rule evaluated (applies to already-adjusted price)
5. Continue until all rules evaluated or stop triggered
6. Return final adjusted price

**Postcondition:** All applicable rules have cumulative effect

**Success Scenario:**
```
Rules: [
  RuleA: priority=1, applies 10%
  RuleB: priority=5, applies $5
  RuleC: priority=10, applies 5%
]

Base Price: $100.00

RuleA: 100 * 0.9 = $90.00
RuleB: 90 - 5 = $85.00
RuleC: 85 * 0.95 = $80.75

Result: $80.75
```

---

## 3. Use Case Matrix

| Use Case | Actor | Trigger | Precondition | Postcondition |
|----------|-------|---------|--------------|---------------|
| UC-001 | Platform Adapter | Calculate item price | Rules registered | Price returned |
| UC-002 | PercentageDiscountRule | Evaluate bulk discount | Conditions set | Discount applied |
| UC-003 | PricingEngine | Prevent negative | Rule result negative | Price floored at 0 |
| UC-004 | PricingRule | Check quantity | Condition exists | Boolean returned |
| UC-005 | PricingRule | Check categories | Condition exists | Boolean returned |
| UC-006 | FixedDiscountRule | Check cart total | Condition exists | Boolean returned |
| UC-007 | Platform Adapter | Get cart total | Cart has items | Full breakdown returned |
| UC-008 | PricingRule | Stop chain | Stop flag true | Loop broken |
| UC-009 | PricingEngine | Multi-rule eval | Multiple rules | Cumulative result |

---

## 4. Error Handling

| Scenario | Use Case | Handling |
|----------|----------|----------|
| Empty rules array | UC-001 | Return base price unchanged |
| No applicable rules | UC-001 | Return base price unchanged |
| Empty context | UC-001 | Use defaults (qty:1, no cats) |
| Missing context keys | UC-002 to UC-006 | Treat as condition failed |
| Rule throws exception | UC-001 | Propagate to caller |
| Division by zero | UC-002 | Not possible (% is percentage) |

---

## 5. Alternative Flows

### AF-001: Empty Rule Set

**Trigger:** `calculatePrice()` with no rules added

**Flow:**
1. Rules array is empty
2. Loop executes 0 times
3. Return `max(0, round(basePrice, 2))`
4. Result: base price unchanged

### AF-002: Context Missing Optional Keys

**Trigger:** Context without category_ids or quantity

**Flow:**
1. Missing key accessed with null coalescing: `$context['key'] ?? default`
2. Defaults: quantity=1, category_ids=[], cart_total=0
3. Condition checks use defaults
4. Result: same as if key was present with default value

### AF-003: All Rules Not Applicable

**Trigger:** Context doesn't match any rule conditions

**Flow:**
1. Each rule returns false from isApplicable()
2. No apply() calls made
3. Return base price unchanged

---

*Document Version: 1.0*  
*Last Updated: May 2026*