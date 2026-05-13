# Architecture - Dynamic Pricing Engine (ksf_DynamicPricing_Core)

**Module:** ksf_DynamicPricing_Core  
**Version:** 1.0.0  
**Date:** May 2026  

---

## 1. Architecture Overview

The Dynamic Pricing Engine follows a **Strategy Pattern** combined with a **Chain of Responsibility Pattern**, allowing pricing rules to be evaluated in priority order while enabling rules to terminate the processing chain. This design ensures flexibility, testability, and extensibility.

### 1.1 High-Level Architecture

```
┌─────────────────────────────────────────────────────────────────────┐
│                        PricingEngine                                │
│  ┌─────────────────────────────────────────────────────────────┐   │
│  │  Rules Collection (Priority-Sorted)                         │   │
│  │  ┌──────────────┐ ┌──────────────┐ ┌──────────────┐        │   │
│  │  │Rule Priority │ │Rule Priority │ │Rule Priority │  ...    │   │
│  │  │     1       │ │     5       │ │    10       │        │   │
│  │  └──────────────┘ └──────────────┘ └──────────────┘        │   │
│  └─────────────────────────────────────────────────────────────┘   │
│                              │                                      │
│                              ▼                                      │
│  ┌─────────────────────────────────────────────────────────────┐   │
│  │  Context Object                                            │   │
│  │  { product_id, category_ids, user_id, cart_total, qty }    │   │
│  └─────────────────────────────────────────────────────────────┘   │
│                              │                                      │
│          ┌───────────────────┼───────────────────┐                  │
│          ▼                   ▼                   ▼                  │
│  ┌──────────────┐    ┌──────────────┐    ┌──────────────┐        │
│  │  Rule #1     │    │  Rule #2     │    │  Rule #3     │        │
│  │ isApplicable?│    │ isApplicable?│    │ isApplicable?│        │
│  │   apply()    │    │   apply()    │    │   apply()    │        │
│  └──────────────┘    └──────────────┘    └──────────────┘        │
└─────────────────────────────────────────────────────────────────────┘
```

---

## 2. Class Diagram

### 2.1 Core Classes

```
┌─────────────────────────────────────────────────────────────────┐
│                        <<interface>>                              │
│                   PricingRuleInterface                           │
├─────────────────────────────────────────────────────────────────┤
│ + isApplicable(context: array): bool                            │
│ + apply(price: float, context: array): float                    │
│ + getPriority(): int                                            │
│ + shouldStopProcessing(): bool                                   │
└─────────────────────────────────────────────────────────────────┘
                              △
                              │
        ┌─────────────────────┴─────────────────────┐
        │                   │                       │
        ▼                   ▼                       ▼
┌──────────────┐   ┌──────────────────┐   ┌──────────────┐
│Percentage    │   │    Fixed         │   │  [Future:    │
│DiscountRule  │   │  DiscountRule    │   │  TieredRule] │
├──────────────┤   ├──────────────────┤   ├──────────────┤
│- percentage  │   │- amount         │   │             │
│- priority    │   │- priority       │   │             │
│- conditions  │   │- conditions     │   │             │
├──────────────┤   ├──────────────────┤   ├──────────────┤
│+ isAppl...   │   │+ isAppl...      │   │             │
│+ apply()     │   │+ apply()       │   │             │
│+ getPriority │   │+ getPriority   │   │             │
│+ shouldStop  │   │+ shouldStop    │   │             │
└──────────────┘   └──────────────────┘   └──────────────┘

┌─────────────────────────────────────────────────────────────────┐
│                       PricingEngine                              │
├─────────────────────────────────────────────────────────────────┤
│ - rules: PricingRuleInterface[]                                  │
├─────────────────────────────────────────────────────────────────┤
│ + addRule(rule: PricingRuleInterface): void                      │
│ + calculatePrice(basePrice: float, context: array): float         │
│ + calculateCartTotal(cartItems: array, context: array): array    │
└─────────────────────────────────────────────────────────────────┘
```

### 2.2 Relationship Diagram

```
┌─────────────────┐          1         *    ┌─────────────────────┐
│  PricingEngine  │───────────────────────→│ PricingRuleInterface │
└─────────────────┘         "contains"      └─────────────────────┘
                                                    △
                                                    │ implements
                    ┌────────────────────────────────┴───────┐
                    │                                    │
           ┌────────┴────────┐                  ┌──────────┴──────────┐
           │ Percentage      │                  │  FixedDiscountRule   │
           │ DiscountRule   │                  │                      │
           └─────────────────┘                  └─────────────────────┘
```

---

## 3. Data Flow

### 3.1 Single Item Price Calculation

```
User Request: calculatePrice($99.99, ['quantity' => 5, 'category_ids' => [10]])
                    │
                    ▼
┌─────────────────────────────────────────────────────────────────┐
│                        PricingEngine                             │
│                                                                  │
│  Step 1: Sort Rules by Priority                                  │
│  ┌────────────────────────────────────────────────────────────┐ │
│  │ Rule 1 (priority 1) → Rule 5 (priority 5) → Rule 10 (10)   │ │
│  └────────────────────────────────────────────────────────────┘ │
│                              │                                    │
│  Step 2: Evaluate Each Rule                                      │
│  ┌────────────────────────────────────────────────────────────┐ │
│  │ for each rule:                                              │ │
│  │   if rule.isApplicable(context):                            │ │
│  │       price = rule.apply(price, context)                    │ │
│  │       if rule.shouldStopProcessing():                       │ │
│  │           break                                             │ │
│  └────────────────────────────────────────────────────────────┘ │
│                              │                                    │
│  Step 3: Round and Return                                        │
│  ┌────────────────────────────────────────────────────────────┐ │
│  │ return max(0, round(price, 2))                              │ │
│  └────────────────────────────────────────────────────────────┘ │
│                              │                                    │
└──────────────────────────────┴──────────────────────────────────┘
                              │
                              ▼
                         $89.99
```

### 3.2 Cart Total Calculation Flow

```
┌──────────────────────────────────────────────────────────────────────────┐
│                    calculateCartTotal() Flow                               │
├──────────────────────────────────────────────────────────────────────────┤
│                                                                          │
│  Input: cartItems = [                                                    │
│    {product_id: 1, price: 50.00, quantity: 2, category_ids: [10]},       │
│    {product_id: 2, price: 30.00, quantity: 1, category_ids: [20]}        │
│  ]                                                                        │
│  context = ['user_id' => 123, 'cart_total' => 130.00]                    │
│                                                                          │
│  ┌────────────────────────────────────────────────────────────────────┐  │
│  │ PASS 1: Calculate Individual Item Prices                          │  │
│  │                                                                    │  │
│  │ Item 1: calculatePrice(50.00, context + [product_id:1, qty:2])    │  │
│  │   → $45.00 (10% bulk discount applied)                            │  │
│  │                                                                    │  │
│  │ Item 2: calculatePrice(30.00, context + [product_id:2, qty:1])    │  │
│  │   → $30.00 (no rules applicable)                                  │  │
│  └────────────────────────────────────────────────────────────────────┘  │
│                              │                                          │
│                              ▼                                          │
│  ┌────────────────────────────────────────────────────────────────────┐  │
│  │ PASS 2: Calculate Totals and Discounts                             │  │
│  │                                                                    │  │
│  │ Original Subtotal: 50*2 + 30*1 = $130.00                          │  │
│  │ Item 1 Discount: (50-45)*2 = $10.00                               │  │
│  │ Total Discount: $10.00                                            │  │
│  │ Final Total: $130.00 - $10.00 = $120.00                           │  │
│  └────────────────────────────────────────────────────────────────────┘  │
│                              │                                          │
└──────────────────────────────┴──────────────────────────────────────────┘
                              │
                              ▼
┌──────────────────────────────────────────────────────────────────────────┐
│ Output: {                                                                │
│   subtotal: 130.00,                                                      │
│   discount: 10.00,                                                       │
│   total: 120.00,                                                         │
│   item_details: [                                                       │
│     {product_id: 1, original: 50.00, final: 45.00, qty: 2, discount: 10},│
│     {product_id: 2, original: 30.00, final: 30.00, qty: 1, discount: 0} │
│   ]                                                                      │
│ }                                                                        │
└──────────────────────────────────────────────────────────────────────────┘
```

---

## 4. Context Data Structure

### 4.1 Context Schema

```php
$context = [
    // Product Information
    'product_id'      => 123,              // Required for single-item pricing
    'category_ids'    => [10, 20, 30],      // Product category assignments
    'quantity'        => 5,                // Units in cart
    
    // Customer Information
    'user_id'         => 456,              // Logged-in user ID
    'user_roles'      => ['vip', 'premium'],// Customer segment memberships
    'customer_tier'   => 'gold',           // Loyalty tier
    
    // Cart Information
    'cart_total'      => 250.00,            // Cart subtotal before this item
    'item_count'      => 7,                 // Total items in cart
    
    // Time-based Context
    'current_date'    => '2026-05-13',     // For time-based rules
    'current_time'    => '14:30:00',        // For flash sales
    
    // Custom Context (platform-specific)
    'subscription_active' => true,           // Subscription status
    'promo_code'         => 'SAVE20',      // Applied promo code
];
```

### 4.2 Context Propagation in Cart Calculation

```
┌─────────────────────────────────────────────────────────────────┐
│ Context Merging for Individual Items                             │
│                                                                  │
│ Base Context:        { user_id: 123, cart_total: 200.00 }       │
│                      │                                           │
│              ┌───────┴───────┐                                   │
│              ▼               ▼                                   │
│  ┌──────────────────┐  ┌──────────────────┐                    │
│  │ Item 1 Context   │  │ Item 2 Context   │                    │
│  │ {                │  │ {                │                    │
│  │   product_id: 1,│  │   product_id: 2, │                    │
│  │   category_ids,  │  │   category_ids,  │                    │
│  │   quantity: 3,  │  │   quantity: 1,   │                    │
│  │   ...base ctx... │  │   ...base ctx... │                    │
│  │ }                │  │ }                │                    │
│  └──────────────────┘  └──────────────────┘                    │
└─────────────────────────────────────────────────────────────────┘
```

---

## 5. Class Interactions

### 5.1 PricingEngine Method Sequence

#### calculatePrice() Sequence

```
Client              PricingEngine           PercentageDiscountRule
   │                      │                         │
   │─calculatePrice()────>│                         │
   │                      │                         │
   │                      │─usort(rules)──────────>│
   │                      │<─sorted rules───────────│
   │                      │                         │
   │                      │─isApplicable(context)──>│
   │                      │<─true──────────────────│
   │                      │                         │
   │                      │─apply(price, context)──>│
   │                      │<─adjusted_price─────────│
   │                      │                         │
   │                      │─shouldStopProcessing()─>│
   │                      │<─false──────────────────│
   │                      │                         │
   │                      │─isApplicable(context)──>│  (next rule)
   │                      │<─false─────────────────│
   │                      │                         │
   │                      │─round(max(price,0),2)──>│
   │                      │<─final_price───────────│
   │                      │                         │
   │<─final_price─────────│                         │
   │                      │                         │
```

#### calculateCartTotal() Sequence

```
Client           PricingEngine           [Per-Item Rules]
   │                  │                         │
   │─calculateCartTotal()────>│                   │
   │                  │                         │
   │                  │─foreach cart_items:      │
   │                  │   │                     │
   │                  │   ├─merge context       │
   │                  │   │                     │
   │                  │   └─calculatePrice()───>│
   │                  │      │                 │
   │                  │      │                 │
   │                  │<─────│                  │
   │                  │   (repeat per item)     │
   │                  │                         │
   │                  │─aggregate results       │
   │                  │                         │
   │<─result_array─────│                         │
   │                  │                         │
```

---

## 6. Error Handling

### 6.1 Price Floor

The engine enforces a minimum price of zero:

```php
return max(0, round($adjustedPrice, 2));
```

This prevents negative pricing scenarios while maintaining precision.

### 6.2 Invalid Context Handling

Rules must handle missing context fields gracefully:

```php
// Quantity defaults to 1 if not provided
$quantity = $context['quantity'] ?? 1;

if (isset($this->conditions['min_quantity']) && $quantity < $this->conditions['min_quantity']) {
    return false;
}
```

### 6.3 Percentage Bounds

Percentage values are clamped between 0 and 100:

```php
$this->percentage = min(100, max(0, $percentage));
```

---

## 7. Extension Points

### 7.1 Adding New Rule Types

To add a new rule type (e.g., TieredPricingRule):

```php
namespace Ksfraser\DynamicPricing;

class TieredPricingRule implements PricingRuleInterface {
    private $tiers;  // e.g., [1 => 0, 5 => 5, 10 => 10] (% off)
    
    public function isApplicable(array $context): bool {
        return isset($context['quantity']);
    }
    
    public function apply(float $price, array $context): float {
        $qty = $context['quantity'];
        $discount = $this->getTierDiscount($qty);
        return $price * (1 - $discount / 100);
    }
    
    public function getPriority(): int { return 5; }
    public function shouldStopProcessing(): bool { return false; }
}
```

### 7.2 Custom Condition Checkers

Extend condition checking by subclassing:

```php
class CustomerRoleRule extends PercentageDiscountRule {
    protected function checkConditions(array $context): bool {
        if (!parent::checkConditions($context)) {
            return false;
        }
        
        // Add customer role check
        $userRoles = $context['user_roles'] ?? [];
        $requiredRoles = $this->conditions['user_roles'] ?? [];
        
        return !empty(array_intersect($requiredRoles, $userRoles));
    }
}
```

---

## 8. Package Structure

```
ksf_DynamicPricing_Core/
├── composer.json
├── src/
│   └── Ksfraser/
│       └── DynamicPricing/
│           ├── PricingEngine.php      # Main engine class
│           ├── PricingRuleInterface.php # Rule contract
│           ├── Rules.php              # Concrete rule implementations
│           └── Exception/
│               └── PricingException.php  # Domain exceptions
├── tests/
│   └── Unit/
│       ├── PricingEngineTest.php
│       └── RulesTest.php
├── docs/
│   └── README.md
└── ProjectDcs/
    ├── 01_Business_Requirements.md
    ├── 02_Architecture.md
    ├── 03_Functional_Requirements.md
    ├── 04_Use_Case.md
    ├── 05_Test_Plan.md
    └── 06_UAT_Plan.md
```

---

## 9. Namespace Convention

All classes follow PSR-4 autoloading standards:

```json
{
    "autoload": {
        "psr-4": {
            "Ksfraser\\DynamicPricing\\": "src/Ksfraser/DynamicPricing/"
        }
    }
}
```

---

## 10. Design Patterns Applied

| Pattern | Application | Benefit |
|---------|-------------|---------|
| Strategy | PricingRuleInterface | Interchangeable algorithms |
| Chain of Responsibility | Rule evaluation with stop flag | Controlled processing |
| Template Method | isApplicable() in rules | Consistent interface |
| Builder (future) | Rule construction | Fluent API |

---

*Document Version: 1.0*  
*Last Updated: May 2026*