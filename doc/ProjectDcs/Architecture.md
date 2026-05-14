# DynamicPricing_Core - Architecture

**Document ID:** ARCH-DYNPRICE-001  
**Module:** ksf_DynamicPricing_Core  
**Version:** 1.0.0  

---

## 1. Module Overview

DynamicPricing_Core implements a rule-based pricing engine with pluggable adapters for different platforms. The architecture emphasizes extensibility through strategy patterns.

## 2. Component Diagram

```
┌─────────────────────────────────────────────────────────────┐
│                  DynamicPricingEngine                        │
├─────────────────────────────────────────────────────────────┤
│ - rules: PricingRule[]                                       │
│ - customer: CustomerContext                                   │
│ - calculator: PriceCalculator                                │
├─────────────────────────────────────────────────────────────┤
│ + calculatePrice(productId, qty, customerId): PriceResult    │
│ + addRule(rule: PricingRule): self                           │
│ + removeRule(ruleId): self                                  │
│ + evaluateRules(context): Adjustment[]                       │
└─────────────────────────────────────────────────────────────┘
                              │
                              │ 1..*
                              ▼
┌─────────────────────────────────────────────────────────────┐
│                    PricingRule                               │
├─────────────────────────────────────────────────────────────┤
│ - id: string                                                 │
│ - type: RuleType                                             │
│ - conditions: Condition[]                                    │
│ - adjustment: Adjustment                                     │
│ - priority: int                                              │
├─────────────────────────────────────────────────────────────┤
│ + matches(context): bool                                      │
│ + apply(price): Money                                         │
└─────────────────────────────────────────────────────────────┘

┌─────────────────────────────────────────────────────────────┐
│                 PriceCalculator                              │
├─────────────────────────────────────────────────────────────┤
│ - strategy: CalculationStrategy                               │
├─────────────────────────────────────────────────────────────┤
│ + calculate(basePrice, adjustments[]): Money                   │
│ + applyStackBehavior(adjustments[], mode): Money              │
└─────────────────────────────────────────────────────────────┘
```

## 3. Directory Structure

```
ksf_DynamicPricing_Core/
├── src/Ksfraser/DynamicPricing/
│   ├── Engine/
│   │   └── DynamicPricingEngine.php
│   ├── Rule/
│   │   ├── PricingRule.php
│   │   ├── RuleCondition.php
│   │   └── RuleAdjustment.php
│   ├── Calculator/
│   │   └── PriceCalculator.php
│   └── Context/
│       └── PricingContext.php
├── tests/
└── doc/ProjectDcs/
```

## 4. Key Design Patterns

| Pattern | Implementation |
|---------|-----------------|
| Strategy | Price calculation strategies (stack, override) |
| Chain of Responsibility | Rule evaluation chain |
| Factory | Rule creation |
| Observer | Price change notifications |

## 5. Technology Stack

| Component | Technology |
|-----------|------------|
| Language | PHP 7.3+ |
| Testing | PHPUnit 9.0+ |
| Pattern | Strategy, Chain of Responsibility |