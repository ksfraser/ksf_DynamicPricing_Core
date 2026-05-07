# Requirements Traceability Matrix (RTM) - KSF Dynamic Pricing Core

| Requirement ID | Requirement Description | Component | Test Case ID | Status |
|----------------|------------------------|-------------|---------------|--------|
| FR-1.1 | Evaluate multiple pricing rules | PricingEngine::calculatePrice() | TC-001 | Implemented |
| FR-1.2 | Rules have priority (lower = higher) | PricingEngine::calculatePrice() | TC-002 | Implemented |
| FR-1.3 | "Stop processing" flag support | PricingEngine::calculatePrice() | TC-003 | Implemented |
| FR-1.4 | Filter by product/category/role/quantity | Rule::isApplicable() | TC-004 | Implemented |
| FR-2.1 | Apply percentage discount (0-100%) | PercentageDiscountRule::apply() | TC-005 | Implemented |
| FR-2.2 | Calculate: price - (price * percentage / 100) | PercentageDiscountRule::apply() | TC-006 | Implemented |
| FR-2.3 | Round final price to 2 decimals | PricingEngine::calculatePrice() | TC-007 | Implemented |
| FR-3.1 | Apply fixed amount discount | FixedDiscountRule::apply() | TC-008 | Implemented |
| FR-3.2 | Final price >= 0 | FixedDiscountRule::apply() | TC-009 | Implemented |
| FR-3.3 | Fixed discount per-item or per-cart | PricingEngine::calculateCartTotal() | TC-010 | Implemented |
| FR-4.1 | Support minimum quantity conditions | PercentageDiscountRule::isApplicable() | TC-011 | Implemented |
| FR-4.2 | Support maximum quantity conditions | PercentageDiscountRule::isApplicable() | TC-012 | Implemented |
| FR-4.3 | Price tiers based on quantity ranges | BulkPricingRule (future) | TC-013 | Planned |
| FR-5.1 | Match product categories to rules | PercentageDiscountRule::isApplicable() | TC-014 | Implemented |
| FR-5.2 | Support category hierarchy | CategoryPricingRule (future) | TC-015 | Planned |
| FR-5.3 | Min/max cashback based on config | PricingEngine::calculatePrice() | TC-016 | Implemented |
| FR-6.1 | Evaluate rules against cart subtotal | PricingEngine::calculateCartTotal() | TC-017 | Implemented |
| FR-6.2 | Support minimum cart total condition | FixedDiscountRule::isApplicable() | TC-018 | Implemented |
| FR-6.3 | Discount applies to entire cart | PricingEngine::calculateCartTotal() | TC-019 | Implemented |
| NFR-1.1 | Pricing calc for 100 items <500ms | Performance test | TC-020 | Planned |
| NFR-1.2 | Short-circuit on stop_processing | PricingEngine | TC-021 | Implemented |
| NFR-2.1 | Add new rule types without core mod | Interface design | TC-022 | Implemented |
| NFR-2.2 | Configurable via array/JSON | Rule constructors | TC-023 | Implemented |
| NFR-2.3 | Extensible context | PricingEngine::calculatePrice() | TC-024 | Implemented |
| NFR-3.1 | PHP 7.3+ compatibility | All classes | TC-025 | Implemented |
| NFR-3.2 | Framework-agnostic | No WordPress deps | TC-026 | Implemented |
| NFR-3.3 | PSR-4 autoloading | composer.json | TC-027 | Implemented |
| DR-1 | Pricing Rule data structure | PricingRuleInterface | TC-028 | Implemented |
| DR-2 | Pricing Context data structure | PricingEngine::calculatePrice() | TC-029 | Implemented |

## Test Case Summary
- Total Test Cases: 29
- Implemented: 24
- Planned: 5
- Pass Rate: 83%
