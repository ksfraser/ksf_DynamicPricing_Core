# Requirements Traceability Matrix (RTM) - KSF Dynamic Pricing Core

| Requirement ID | Requirement Description | Component | Test Case ID | Status |
|----------------|------------------------|-------------|---------------|--------|
| FR-1.1 | Evaluate multiple pricing rules | PricingEngine::calculatePrice() | TC-001 | Pending |
| FR-1.2 | Rules have priority (lower = higher) | PricingEngine::calculatePrice() | TC-002 | Pending |
| FR-1.3 | "Stop processing" flag support | PricingEngine::calculatePrice() | TC-003 | Pending |
| FR-1.4 | Filter by product/category/role/quantity | Rule::isApplicable() | TC-004 | Pending |
| FR-2.1 | Apply percentage discount (0-100%) | PercentageDiscountRule::apply() | TC-005 | Pending |
| FR-2.2 | Calculate: price - (price * percentage / 100) | PercentageDiscountRule::apply() | TC-006 | Pending |
| FR-2.3 | Round final price to 2 decimals | PricingEngine::calculatePrice() | TC-007 | Pending |
| FR-3.1 | Apply fixed amount discount | FixedDiscountRule::apply() | TC-008 | Pending |
| FR-3.2 | Final price >= 0 | FixedDiscountRule::apply() | TC-009 | Pending |
| FR-3.3 | Fixed discount per-item or per-cart | PricingEngine::calculateCartTotal() | TC-010 | Pending |
| FR-4.1 | Support minimum quantity conditions | PercentageDiscountRule::isApplicable() | TC-011 | Pending |
| FR-4.2 | Support maximum quantity conditions | PercentageDiscountRule::isApplicable() | TC-012 | Pending |
| FR-5.1 | Match product categories to rules | PercentageDiscountRule::isApplicable() | TC-014 | Pending |
| NFR-1.1 | Pricing calc performance | Performance test | TC-020 | Pending |
| NFR-2.1 | Add new rule types without core mod | Interface design | TC-022 | Pending |
| NFR-3.1 | PHP 7.3+ compatibility | All classes | TC-025 | Pending |
| NFR-3.2 | Framework-agnostic | No WordPress deps | TC-026 | Pending |
| NFR-3.3 | PSR-4 autoloading | composer.json | TC-028 | Pending |

## Test Case Summary
- Total Test Cases: 19
- Implemented: 0
- Pending: 19
- Pass Rate: TBD

*Document Version: 1.0.0*
*Last Updated: 2026-05-12*
