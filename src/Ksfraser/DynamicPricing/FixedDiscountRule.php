<?php
namespace Ksfraser\DynamicPricing;

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