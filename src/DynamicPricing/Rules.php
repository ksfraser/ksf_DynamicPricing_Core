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

/**
 * Percentage discount rule
 */
class PercentageDiscountRule implements PricingRuleInterface {
    
    private $percentage;
    private $priority;
    private $stopProcessing;
    private $conditions;
    
    /**
     * @param float $percentage Discount percentage (0-100)
     * @param int $priority
     * @param bool $stopProcessing
     * @param array $conditions ['min_quantity'=>int, 'max_quantity'=>int, 'category_ids'=>array, 'user_roles'=>array]
     */
    public function __construct(float $percentage, int $priority = 10, bool $stopProcessing = false, array $conditions = []) {
        $this->percentage = min(100, max(0, $percentage));
        $this->priority = $priority;
        $this->stopProcessing = $stopProcessing;
        $this->conditions = $conditions;
    }
    
    public function isApplicable(array $context): bool {
        // Check quantity conditions
        if (isset($this->conditions['min_quantity']) && ($context['quantity'] ?? 1) < $this->conditions['min_quantity']) {
            return false;
        }
        if (isset($this->conditions['max_quantity']) && ($context['quantity'] ?? 1) > $this->conditions['max_quantity']) {
            return false;
        }
        
        // Check category conditions
        if (!empty($this->conditions['category_ids'])) {
            $productCategories = $context['category_ids'] ?? [];
            if (empty(array_intersect($this->conditions['category_ids'], $productCategories))) {
                return false;
            }
        }
        
        return true;
    }
    
    public function apply(float $price, array $context): float {
        $discount = $price * ($this->percentage / 100);
        return $price - $discount;
    }
    
    public function getPriority(): int { return $this->priority; }
    
    public function shouldStopProcessing(): bool { return $this->stopProcessing; }
}

/**
 * Fixed amount discount rule
 */
class FixedDiscountRule implements PricingRuleInterface {
    
    private $amount;
    private $priority;
    private $stopProcessing;
    private $conditions;
    
    public function __construct(float $amount, int $priority = 10, bool $stopProcessing = false, array $conditions = []) {
        $this->amount = max(0, $amount);
        $this->priority = $priority;
        $this->stopProcessing = $stopProcessing;
        $this->conditions = $conditions;
    }
    
    public function isApplicable(array $context): bool {
        // Same conditions as percentage rule
        if (isset($this->conditions['min_quantity']) && ($context['quantity'] ?? 1) < $this->conditions['min_quantity']) {
            return false;
        }
        
        if (isset($this->conditions['min_cart_total']) && ($context['cart_total'] ?? 0) < $this->conditions['min_cart_total']) {
            return false;
        }
        
        return true;
    }
    
    public function apply(float $price, array $context): float {
        return max(0, $price - $this->amount);
    }
    
    public function getPriority(): int { return $this->priority; }
    
    public function shouldStopProcessing(): bool { return $this->stopProcessing; }
}
