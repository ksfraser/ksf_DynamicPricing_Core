<?php
namespace Ksf\DynamicPricing\Tests;

use PHPUnit\Framework\TestCase;
use Ksf\DynamicPricing\PricingEngine;
use Ksf\DynamicPricing\PercentageDiscountRule;
use Ksf\DynamicPricing\FixedDiscountRule;

class PricingEngineTest extends TestCase {
    
    private $engine;
    
    protected function setUp(): void {
        $this->engine = new PricingEngine();
    }
    
    public function testPercentageDiscount(): void {
        $rule = new PercentageDiscountRule(10, 10, false);
        $this->engine->addRule($rule);
        
        $finalPrice = $this->engine->calculatePrice(100, ['product_id' => 1, 'quantity' => 1]);
        
        $this->assertEquals(90, $finalPrice);
    }
    
    public function testFixedDiscount(): void {
        $rule = new FixedDiscountRule(15, 10, false);
        $this->engine->addRule($rule);
        
        $finalPrice = $this->engine->calculatePrice(100, ['product_id' => 1, 'quantity' => 1]);
        
        $this->assertEquals(85, $finalPrice);
    }
    
    public function testMultipleRulesWithPriority(): void {
        // Higher priority (lower number) should apply first
        $rule1 = new PercentageDiscountRule(20, 20, false); // 20% off
        $rule2 = new FixedDiscountRule(10, 10, false); // $10 off, higher priority
        
        $this->engine->addRule($rule1);
        $this->engine->addRule($rule2);
        
        $finalPrice = $this->engine->calculatePrice(100, ['product_id' => 1, 'quantity' => 1]);
        
        // Rule2 applies first (priority 10), then rule1 (priority 20)
        $this->assertEquals(70, $finalPrice); // 100 - 10 - 20 = 70
    }
    
    public function testStopProcessing(): void {
        $rule1 = new FixedDiscountRule(20, 10, true); // Stop after this
        $rule2 = new PercentageDiscountRule(10, 20, false);
        
        $this->engine->addRule($rule1);
        $this->engine->addRule($rule2);
        
        $finalPrice = $this->engine->calculatePrice(100, ['product_id' => 1, 'quantity' => 1]);
        
        // Only rule1 applies
        $this->assertEquals(80, $finalPrice);
    }
    
    public function testQuantityCondition(): void {
        $rule = new PercentageDiscountRule(10, 10, false, ['min_quantity' => 5]);
        $this->engine->addRule($rule);
        
        // Quantity 3 - rule not applicable
        $price1 = $this->engine->calculatePrice(100, ['product_id' => 1, 'quantity' => 3]);
        $this->assertEquals(100, $price1);
        
        // Quantity 5 - rule applicable
        $price2 = $this->engine->calculatePrice(100, ['product_id' => 1, 'quantity' => 5]);
        $this->assertEquals(90, $price2);
    }
    
    public function testCartTotalCalculation(): void {
        $this->engine->addRule(new PercentageDiscountRule(10, 10, false));
        
        $cartItems = [
            ['product_id' => 1, 'price' => 50, 'quantity' => 2, 'category_ids' => [10]],
            ['product_id' => 2, 'price' => 30, 'quantity' => 1, 'category_ids' => [20]]
        ];
        
        $result = $this->engine->calculateCartTotal($cartItems, ['user_id' => 1]);
        
        $this->assertEquals(130, $result['subtotal']); // 50*2 + 30*1
        $this->assertEquals(13, $result['discount']); // 10% of 130
        $this->assertEquals(117, $result['total']);
    }
    
    public function testPriceNeverNegative(): void {
        $rule = new FixedDiscountRule(150, 10, false);
        $this->engine->addRule($rule);
        
        $finalPrice = $this->engine->calculatePrice(100, ['product_id' => 1, 'quantity' => 1]);
        
        $this->assertEquals(0, $finalPrice); // Not negative
    }
}
