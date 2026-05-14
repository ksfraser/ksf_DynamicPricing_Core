<?php
namespace Ksfraser\DynamicPricing;

/**
 * Pricing Rule interface
 */
interface PricingRuleInterface {
    
    /**
     * Check if rule applies to the given context
     * 
     * @param array $context
     * @return bool
     */
    public function isApplicable(array $context): bool;
    
    /**
     * Apply the rule to adjust price
     * 
     * @param float $price
     * @param array $context
     * @return float Adjusted price
     */
    public function apply(float $price, array $context): float;
    
    /**
     * Get rule priority (lower = higher priority)
     * 
     * @return int
     */
    public function getPriority(): int;
    
    /**
     * Should stop processing further rules after this one?
     * 
     * @return bool
     */
    public function shouldStopProcessing(): bool;
}