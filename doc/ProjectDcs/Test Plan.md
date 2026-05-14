# DynamicPricing_Core - Test Plan

**Document ID:** TP-DYNPRICE-001  
**Module:** ksf_DynamicPricing_Core  
**Version:** 1.0.0  

---

## 1. Test Scope

- Pricing rule evaluation
- Price calculation accuracy
- Volume discount tiers
- Customer-specific pricing
- Time-based rule activation

## 2. Test Cases

| ID | Test | Input | Expected |
|----|------|-------|----------|
| TC-001 | testPercentageDiscount | 10% off $100 | $90 |
| TC-002 | testFixedDiscount | $10 off $100 | $90 |
| TC-003 | testVolumePricing | qty=10, tier 5-10: 15% | $85 |
| TC-004 | testCustomerPrice | customer tier=gold | Gold-specific price |
| TC-005 | testTimeBasedActive | current date in range | Rule applies |
| TC-006 | testTimeBasedExpired | current date past end | Rule ignored |
| TC-007 | testStackBehavior_Sum | rules A: -10%, B: -$5 | Combined adjustment |
| TC-008 | testStackBehavior_Max | rules A: -10%, B: -$20 | Better deal applied |