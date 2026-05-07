# UML Documentation - KSF Dynamic Pricing Core

## Class Diagram

```
+---------------------+          +---------------------+
|   PricingEngine     |          |   PricingRule      |
+---------------------+          |   Interface         |
| -rules: array       |          +---------------------+
+---------------------+          | +isApplicable()    |
| +addRule()          |          | +apply()           |
| +calculatePrice()   |          | +getPriority()     |
| +calculateCartTotal()|         | +shouldStopProc()  |
+---------------------+          +---------------------+
          |                               ^
          | uses rules                      |
          v                               |
+---------------------+          +---------+---------+
|   Rule Context      |          |                   |
+---------------------+          |                   |
| -product_id: int    |   +------+------+   +--------+-------+
| -category_ids: array|   | Percentage  |   | Fixed          |
| -quantity: int      |   | Discount    |   | Discount       |
| -price: float       |   | Rule        |   | Rule           |
| -user_id: int       |   +-------------+   +----------------+
| -user_roles: array  |   | -percentage |   | -amount        |
| -cart_total: float  |   | -priority   |   | -priority      |
+---------------------+   | -stop_proc  |   | -stop_proc     |
                            +-------------+   +----------------+
```

## Sequence Diagram: Calculate Product Price

```
User -> PricingEngine: calculatePrice(basePrice, context)
PricingEngine -> PricingEngine: sort rules by priority
loop for each rule in sortedRules
    PricingEngine -> PricingRule: isApplicable(context)
    PricingRule --> PricingEngine: true|false
    alt rule is applicable
        PricingEngine -> PricingRule: apply(currentPrice, context)
        PricingRule --> PricingEngine: adjustedPrice
        PricingEngine -> PricingEngine: update currentPrice
        PricingEngine -> PricingRule: shouldStopProcessing()
        PricingRule --> PricingEngine: true
        alt stopProcessing = true
            PricingEngine -> PricingEngine: break loop
        end
    end
end
PricingEngine --> User: finalPrice (rounded to 2 decimals)
```

## Sequence Diagram: Calculate Cart Total

```
User -> PricingEngine: calculateCartTotal(cartItems, context)
PricingEngine -> PricingEngine: subtotal = 0
PricingEngine -> PricingEngine: totalDiscount = 0
loop for each item in cartItems
    PricingEngine -> PricingEngine: itemContext = merge(context, item)
    PricingEngine -> PricingEngine: originalPrice = item.price
    PricingEngine -> PricingEngine: finalPrice = calculatePrice(originalPrice, itemContext)
    PricingEngine -> PricingEngine: itemDiscount = (originalPrice - finalPrice) * qty
    PricingEngine -> PricingEngine: subtotal += originalPrice * qty
    PricingEngine -> PricingEngine: totalDiscount += itemDiscount
end
PricingEngine --> User: {subtotal, discount, total, item_details}
```

## State Diagram: Pricing Rule Lifecycle

```
[CREATED] --> [ACTIVE] : addRule()
[ACTIVE] --> [APPLICABLE] : isApplicable() returns true
[ACTIVE] --> [NOT_APPLICABLE] : isApplicable() returns false
[APPLICABLE] --> [APPLIED] : apply() called
[APPLIED] --> [STOP] : shouldStopProcessing() returns true
[APPLIED] --> [CONTINUE] : shouldStopProcessing() returns false
[STOP] --> [ACTIVE] : next item/cart calculation
[CONTINUE] --> [ACTIVE] : next rule evaluation
```

## Database Schema (FA Tables - for ksf_FA_DynamicPricing)

```
fa_pricing_rules
---------------
- id (INT, PK, AUTO_INCREMENT)
- rule_type (ENUM('percentage','fixed','bulk'), NOT NULL)
- title (VARCHAR(255))
- priority (INT, DEFAULT 10)
- stop_processing (TINYINT, DEFAULT 0)
- conditions (JSON)  -- {min_quantity, max_quantity, category_ids, user_roles, min_cart_total}
- config (JSON)  -- {percentage, amount, tiers}
- enabled (TINYINT, DEFAULT 1)
- created_at (DATETIME)
- updated_at (DATETIME)

fa_pricing_applied (audit trail)
----------------------
- id (INT, PK, AUTO_INCREMENT)
- order_id (INT, NOT NULL)
- product_id (INT)
- rule_id (INT, NOT NULL)
- original_price (DECIMAL(10,2))
- final_price (DECIMAL(10,2))
- discount_amount (DECIMAL(10,2))
- applied_at (DATETIME)
```

## Activity Diagram: Apply Percentage Discount Rule

```
[Start] --> [Check rule applicable]
[Check rule applicable] -->|No| [Return original price]
[Check rule applicable] -->|Yes| [Get rule percentage]
[Get rule percentage] --> [Calculate discount = price * (percentage/100)]
[Calculate discount] --> [Check max cashback limit]
[Check max cashback limit] -->|Exceeds limit| [Set discount = max_cashback]
[Check max cashback limit] -->|Within limit| [Keep calculated discount]
[Set discount = max_cashback] --> [Final price = price - discount]
[Keep calculated discount] --> [Final price = price - discount]
[Final price = price - discount] --> [Return final price]
```
