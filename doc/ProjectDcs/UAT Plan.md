# DynamicPricing_Core - UAT Plan

**Document ID:** UAT-DYNPRICE-001  
**Module:** ksf_DynamicPricing_Core  
**Version:** 1.0.0  

---

## 1. UAT Objectives

Verify that:
1. Pricing rules apply correctly in various scenarios
2. Volume discounts calculate correctly
3. Customer-specific prices override defaults
4. Time-based promotions activate/deactivate properly

## 2. Test Scenarios

| Scenario | Expected | Tester |
|----------|----------|--------|
| UAT-001: Apply percentage discount | Price reduced by X% | Pricing Manager |
| UAT-002: Apply fixed discount | Price reduced by $X | Pricing Manager |
| UAT-003: Order exceeds volume threshold | Volume discount applied | Customer |
| UAT-004: Gold tier customer | Gold-specific pricing | Sales Manager |
| UAT-005: Order within promotion dates | Promotional price | Customer |
| UAT-006: Order after promotion ends | Regular price | Customer |

## 3. Sign-Off

| Role | Name | Date |
|------|------|------|
| Pricing Manager | | |
| QA Lead | | |