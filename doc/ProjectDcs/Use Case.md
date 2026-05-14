# DynamicPricing_Core - Use Cases

**Document ID:** UC-DYNPRICE-001  
**Module:** ksf_DynamicPricing_Core  
**Version:** 1.0.0  

---

## 1. Use Case Overview

### UC-001: Calculate Product Price

**Description:** System calculates final price for a product considering all applicable pricing rules.

**Primary Flow:**
1. Customer selects product
2. System retrieves base price
3. System creates pricing context
4. System evaluates all matching rules
5. System calculates final price
6. System returns price result

### UC-002: Create Pricing Rule

**Description:** Pricing Manager creates a new pricing rule.

**Primary Flow:**
1. Pricing Manager accesses rule editor
2. Pricing Manager selects rule type
3. Pricing Manager defines conditions
4. Pricing Manager defines adjustment
5. Pricing Manager sets priority
6. System validates rule
7. System saves rule

### UC-003: Apply Volume Discount

**Description:** System applies tiered pricing based on quantity.

**Primary Flow:**
1. Customer adds item to cart with quantity
2. System retrieves volume pricing tiers
3. System selects applicable tier
4. System calculates discounted price
5. System returns tiered price

## 2. Actors

| Actor | Role |
|-------|------|
| Pricing Manager | Creates and manages pricing rules |
| Customer | Receives calculated prices |
| System | Evaluates rules and calculates prices |