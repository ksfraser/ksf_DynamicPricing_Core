# Requirements Document - KSF Dynamic Pricing Core

## Business Logic Extracted from WooCommerce Dynamic Pricing

### Functional Requirements

#### FR-1: Pricing Rule Engine
- **FR-1.1**: System must evaluate multiple pricing rules against a product/cart
- **FR-1.2**: Rules must have priority (lower number = higher priority)
- **FR-1.3**: System must support "stop processing" flag (apply first matching rule only)
- **FR-1.4**: Rules must be filterable by: product_id, category_ids, user_roles, quantity, cart_total

#### FR-2: Percentage Discount
- **FR-2.1**: System must apply percentage discount (0-100%) to product price
- **FR-2.2**: Percentage discount must be calculated as: price - (price * percentage / 100)
- **FR-2.3**: System must round final price to 2 decimal places

#### FR-3: Fixed Amount Discount
- **FR-3.1**: System must apply fixed amount discount to product price
- **FR-3.2**: Final price must not be negative (minimum 0)
- **FR-3.3**: Fixed discount can be applied per-item or per-cart

#### FR-4: Bulk/Quantity Pricing
- **FR-4.1**: System must support minimum quantity conditions
- **FR-4.2**: System must support maximum quantity conditions
- **FR-4.3**: Different price tiers based on quantity ranges

#### FR-5: Category-Based Pricing
- **FR-5.1**: System must match product categories against rule conditions
- **FR-5.2**: System must support category hierarchy (parent/child categories)
- **FR-5.3**: Multiple category matches: use min/max cashback based on config

#### FR-6: Cart Total Pricing
- **FR-6.1**: System must evaluate rules against cart subtotal
- **FR-6.2**: System must support minimum cart total conditions
- **FR-6.3**: Discount applies to entire cart, not individual items

### Non-Functional Requirements

#### NFR-1: Performance
- **NFR-1.1**: Pricing calculation for 100 items must complete in <500ms
- **NFR-1.2**: Rule evaluation must short-circuit when stop_processing is true

#### NFR-2: Extensibility
- **NFR-2.1**: New rule types must be addable without modifying core engine
- **NFR-2.2**: Rules must be configurable via array/JSON
- **NFR-2.3**: Context passed to rules must be extensible

#### NFR-3: Compatibility
- **NFR-3.1**: Code must run on PHP 7.3+
- **NFR-3.2**: Framework-agnostic (no WordPress/WooCommerce dependencies)
- **NFR-3.3**: PSR-4 autoloading compliant

### Data Requirements

#### DR-1: Pricing Rule
- id (string)
- type (enum: 'percentage', 'fixed', 'bulk')
- priority (int, default 10)
- stop_processing (bool, default false)
- conditions (array): min_quantity, max_quantity, category_ids, user_roles, min_cart_total
- config (array): percentage, amount, tiers

#### DR-2: Pricing Context
- product_id (int)
- category_ids (array of int)
- quantity (int)
- price (float)
- user_id (int)
- user_roles (array of string)
- cart_total (float)
- cart_items (array of product data)
