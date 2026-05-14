<?php
namespace Ksfraser\DynamicPricing;

/**
 * Dynamic Pricing Engine - Framework Agnostic
 * Extracted from WooCommerce Dynamic Pricing logic
 */
class PricingEngine {
    
    /** @var array */
    private $rules = [];
    
    /**
     * Add a pricing rule
     * 
     * @param PricingRuleInterface $rule
     */
    public function addRule(PricingRuleInterface $rule): void {
        $this->rules[] = $rule;
    }
    
    /**
     * Calculate price for a product based on rules
     * 
     * @param float $basePrice
     * @param array $context ['product_id', 'category_ids', 'user_id', 'cart_total', 'quantity']
     * @return float Final price after applying all applicable rules
     */
    public function calculatePrice(float $basePrice, array $context): float {
        $adjustedPrice = $basePrice;
        
        // Sort rules by priority (lower number = higher priority)
        $sortedRules = $this->rules;
        usort($sortedRules, function($a, $b) {
            return $a->getPriority() <=> $b->getPriority();
        });
        
        foreach ($sortedRules as $rule) {
            if ($rule->isApplicable($context)) {
                $adjustedPrice = $rule->apply($adjustedPrice, $context);
                
                // If rule says stop processing further rules
                if ($rule->shouldStopProcessing()) {
                    break;
                }
            }
        }
        
        return max(0, round($adjustedPrice, 2));
    }
    
    /**
     * Calculate cart total with dynamic pricing
     * 
     * @param array $cartItems [['product_id'=>int, 'price'=>float, 'quantity'=>int, 'category_ids'=>array], ...]
     * @param array $context Additional context ['user_id', 'cart_total']
     * @return array ['subtotal', 'discount', 'total', 'item_details']
     */
    public function calculateCartTotal(array $cartItems, array $context = []): array {
        $subtotal = 0;
        $totalDiscount = 0;
        $itemDetails = [];
        
        // First pass: calculate individual item prices
        foreach ($cartItems as $item) {
            $itemContext = array_merge($context, [
                'product_id' => $item['product_id'],
                'category_ids' => $item['category_ids'] ?? [],
                'quantity' => $item['quantity']
            ]);
            
            $originalPrice = $item['price'];
            $finalPrice = $this->calculatePrice($originalPrice, $itemContext);
            $itemTotal = $finalPrice * $item['quantity'];
            $itemDiscount = ($originalPrice - $finalPrice) * $item['quantity'];
            
            $subtotal += $originalPrice * $item['quantity'];
            $totalDiscount += $itemDiscount;
            
            $itemDetails[] = [
                'product_id' => $item['product_id'],
                'original_price' => $originalPrice,
                'final_price' => $finalPrice,
                'quantity' => $item['quantity'],
                'discount' => $itemDiscount,
                'total' => $itemTotal
            ];
        }
        
        return [
            'subtotal' => round($subtotal, 2),
            'discount' => round($totalDiscount, 2),
            'total' => round($subtotal - $totalDiscount, 2),
            'item_details' => $itemDetails
        ];
    }
}
