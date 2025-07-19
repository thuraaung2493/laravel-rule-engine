# Laravel Rule Engine

[![Latest Version on Packagist](https://img.shields.io/packagist/v/thuraaung2493/laravel-rule-engine.svg?style=flat-square)](https://packagist.org/packages/thuraaung2493/laravel-rule-engine)
[![GitHub Tests Action Status](https://img.shields.io/github/actions/workflow/status/thuraaung2493/laravel-rule-engine/run-tests.yml?branch=main&label=tests&style=flat-square)](https://github.com/thuraaung2493/laravel-rule-engine/actions?query=workflow%3Arun-tests+branch%3Amain)
[![GitHub Code Style Action Status](https://img.shields.io/github/actions/workflow/status/thuraaung2493/laravel-rule-engine/fix-php-code-style-issues.yml?branch=main&label=code%20style&style=flat-square)](https://github.com/thuraaung2493/laravel-rule-engine/actions?query=workflow%3A"Fix+PHP+code+style+issues"+branch%3Amain)
[![Total Downloads](https://img.shields.io/packagist/dt/thuraaung2493/laravel-rule-engine.svg?style=flat-square)](https://packagist.org/packages/thuraaung2493/laravel-rule-engine)

A powerful and extensible rule engine for Laravel applications. Define dynamic business rules in the database, evaluate them with flexible logic, and trigger custom actions.

## Features

-   Dynamic rule evaluation using Symfony Expression Language
-   Database-driven rules and rule groups
-   Flexible evaluation logic (fail-fast or evaluate all)
-   Custom action handlers
-   Expression function providers
-   Comprehensive error handling
-   Built-in support for common PHP functions
-   Array operation support

## Installation

You can install the package via composer:

```bash
composer require thuraaung2493/laravel-rule-engine
```

Publish and run the migrations:

```bash
php artisan vendor:publish --provider="Thuraaung\\RuleEngine\\RuleEngineServiceProvider"
php artisan migrate
```

## Usage

### Basic Usage

1. Create a Rule Group:

```php
use Thuraaung\RuleEngine\Models\RuleGroup;

$group = RuleGroup::create([
    'name' => 'pricing_rules',
    'evaluation_logic' => 'fail_fast' // or 'evaluate_all'
]);
```

2. Add Rules to the Group:

```php
use Thuraaung\RuleEngine\Models\Rule;

$group->rules()->create([
    'name' => 'minimum_order',
    'expression' => 'total >= 100',
    'action_type' => 'apply_discount',
    'action_value' => ['percentage' => 10],
    'error_message' => 'Order total must be at least $100'
]);
```

3. Evaluate Rules:

```php
use Thuraaung\RuleEngine\Facades\RuleEngine;
use Thuraaung\RuleEngine\Dtos\EvaluationOptions;

$result = RuleEngine::evaluateGroup(new EvaluationOptions(
    groupNames: ['pricing_rules'],
    data: ['total' => 150]
));

if ($result->passed) {
    // Rules passed, actions will be executed
    foreach ($result->actions as $action) {
        // Handle actions
    }
} else {
    // Rules failed
    foreach ($result->failedRules as $rule) {
        echo $rule->error_message;
    }
}
```

### Custom Action Handlers

1. Create an Action Handler:

```php
use Thuraaung\RuleEngine\Actions\Contracts\ActionHandlerInterface;
use Thuraaung\RuleEngine\Actions\ActionResult;

class DiscountHandler implements ActionHandlerInterface
{
    public function supports(string $actionType): bool
    {
        return $actionType === 'apply_discount';
    }

    public function handle(array $actionValue, array $context): ActionResult
    {
        $percentage = $actionValue['percentage'] ?? 0;
        $total = $context['total'] ?? 0;

        $discount = $total * ($percentage / 100);

        return ActionResult::success([
            'discount_amount' => $discount,
            'final_total' => $total - $discount
        ]);
    }
}
```

2. Register the Handler:

```php
// In a service provider
RuleEngine::register('apply_discount', DiscountHandler::class);
```

### Working with Evaluation Results

The rule engine provides several helper classes to work with evaluation results:

#### EvaluationResult

This class represents the result of evaluating a single rule group:

```php
use Thuraaung\RuleEngine\Facades\RuleEngine;
use Thuraaung\RuleEngine\Dtos\EvaluationOptions;

$result = RuleEngine::evaluateGroup(new EvaluationOptions(
    groupNames: ['pricing_rules'],
    data: ['total' => 150]
));

// Check if all rules passed
if ($result->passed) {
    // Get all actions from passed rules
    foreach ($result->actions as $action) {
        // Handle action
    }
}

// Get failed rules
foreach ($result->failedRules as $rule) {
    echo $rule->error_message;
}

// Get rules that had exceptions during evaluation
foreach ($result->rulesWithExceptions as $rule) {
    echo $rule->error;
}

// Format failed rules as string
echo $result->failedRulesAsString(); // Default separator: ', '
echo $result->failedRulesAsString(' | '); // Custom separator
```

#### MultiGroupEvaluationResult

When evaluating multiple rule groups at once, you'll get a MultiGroupEvaluationResult:

```php
$result = RuleEngine::evaluateGroups(new EvaluationOptions(
    groupNames: ['pricing_rules', 'shipping_rules'],
    data: [
        'total' => 150,
        'weight' => 2.5
    ]
));

// Check if any groups failed
if ($result->hasFailedGroups()) {
    // Get failed groups and their results
    foreach ($result->failedGroups as $groupName => $groupResult) {
        echo "Group {$groupName} failed:";
        echo $groupResult->failedRulesAsString();
    }
}

// Access individual group results
$pricingResult = $result->groupResults['pricing_rules'];
$shippingResult = $result->groupResults['shipping_rules'];
```

### Evaluation Logic

The `EvaluationLogic` enum defines how rules within a group are evaluated:

```php
use Thuraaung\RuleEngine\Enums\EvaluationLogic;
use Thuraaung\RuleEngine\Models\RuleGroup;

// Create a group that stops on first failure
$group = RuleGroup::create([
    'name' => 'strict_rules',
    'evaluation_logic' => EvaluationLogic::FAIL_FAST->value
]);

// Create a group that evaluates all rules
$group = RuleGroup::create([
    'name' => 'validation_rules',
    'evaluation_logic' => EvaluationLogic::EVALUATE_ALL->value
]);

// Get available logic types
$options = EvaluationLogic::options(); // ['fail_fast', 'evaluate_all']
$values = EvaluationLogic::values(); // [EvaluationLogic::FAIL_FAST, EvaluationLogic::EVALUATE_ALL]
```

### Expression Functions and Providers

The rule engine uses Symfony's Expression Language and provides several ways to extend its functionality:

1. Built-in Expression Providers:

```php
// String manipulation provider (included)
use Thuraaung\RuleEngine\Providers\StringExpressionLanguageProvider;

// Example usage in rules
$rule = Rule::create([
    'name' => 'check_username',
    'expression' => 'lowercase(username) == "admin"',
]);
```

2. Custom Function Providers:

Add custom providers in your `config/rule-engine.php`:

```php
return [
    'expression_providers' => [
        App\RuleEngine\CustomFunctionsProvider::class,
        App\RuleEngine\DateFunctionsProvider::class,
    ],
];
```

Create your provider:

```php
use Symfony\Component\ExpressionLanguage\ExpressionFunction;
use Symfony\Component\ExpressionLanguage\ExpressionFunctionProviderInterface;

class CustomFunctionsProvider implements ExpressionFunctionProviderInterface
{
    public function getFunctions()
    {
        return [
            // Simple function
            new ExpressionFunction('multiply',
                function ($a, $b) { return "$a * $b"; },
                function ($arguments, $a, $b) { return $a * $b; }
            ),

            // Complex function with error handling
            new ExpressionFunction('parseDate',
                function ($str) { return "strtotime($str)"; },
                function ($arguments, $str) {
                    $timestamp = strtotime($str);
                    if ($timestamp === false) {
                        throw new \InvalidArgumentException('Invalid date string');
                    }
                    return $timestamp;
                }
            ),
        ];
    }
}
```

3. Working with Complex Data:

The rule engine supports evaluating complex nested data structures:

```php
$rule = Rule::create([
    'name' => 'check_user_permissions',
    'expression' => 'user.roles[0].permissions contains "admin" and user.settings.isActive == true',
    'error_message' => 'Insufficient permissions or inactive user'
]);

// Evaluate with nested data
$result = RuleEngine::evaluateGroup(new EvaluationOptions(
    groupNames: ['admin_rules'],
    data: [
        'user' => [
            'roles' => [
                ['permissions' => ['admin', 'user']],
            ],
            'settings' => ['isActive' => true]
        ]
    ]
));
```

### Advanced Action Handling

The rule engine provides a robust system for handling actions when rules pass:

1. Action Results with Context:

```php
use Thuraaung\RuleEngine\Actions\ActionResult;
use Thuraaung\RuleEngine\Actions\Contracts\ActionHandlerInterface;

class DiscountCalculator implements ActionHandlerInterface
{
    public function supports(string $actionType): bool
    {
        return $actionType === 'calculate_discount';
    }

    public function handle(array $actionValue, array $context): ActionResult
    {
        try {
            $amount = $context['amount'] ?? 0;
            $percentage = $actionValue['percentage'] ?? 0;

            if ($percentage <= 0 || $percentage > 100) {
                return ActionResult::failure('Invalid discount percentage');
            }

            $discount = $amount * ($percentage / 100);

            return ActionResult::success([
                'original_amount' => $amount,
                'discount_amount' => $discount,
                'final_amount' => $amount - $discount,
                'applied_percentage' => $percentage
            ]);
        } catch (\Exception $e) {
            return ActionResult::failure("Failed to calculate discount: {$e->getMessage()}");
        }
    }
}
```

2. Error Handling and Logging:

```php
// Register with error handling
$registry->register('calculate_discount', new DiscountCalculator());

// Usage in rules
$rule = Rule::create([
    'name' => 'vip_discount',
    'expression' => 'user.isVIP && amount >= 1000',
    'action_type' => 'calculate_discount',
    'action_value' => ['percentage' => 15],
    'error_message' => 'Not eligible for VIP discount'
]);

// Evaluate with error handling
try {
    $result = RuleEngine::evaluateGroup(new EvaluationOptions(
        groupNames: ['discount_rules'],
        data: ['user' => ['isVIP' => true], 'amount' => 1500]
    ));

    if ($result->passed) {
        foreach ($result->actions as $action) {
            if ($action->success) {
                $finalAmount = $action->context['final_amount'];
                // Process the discounted amount
            } else {
                // Handle action failure
                Log::warning("Discount calculation failed: {$action->error}");
            }
        }
    }
} catch (\Exception $e) {
    Log::error("Rule evaluation failed: {$e->getMessage()}");
}
```

## Available Functions

The rule engine comes with several built-in PHP functions:

-   Array: `count`, `array_filter`
-   String: `strlen`, `substr`, `trim`, `strtolower`, `strtoupper`, `str_contains`
-   Math: `pow`, `sqrt`, `floor`, `ceil`, `round`
-   Date: `strtotime`, `date`
-   Type checking: `is_null`, `empty`, `isset`

Additionally, you can use any function registered through Expression Providers as described in the Expression Functions section.

## Testing

```bash
composer test
```

## Changelog

Please see [CHANGELOG](CHANGELOG.md) for more information on what has changed recently.

## Contributing

Please see [CONTRIBUTING](CONTRIBUTING.md) for details.

## Security Vulnerabilities

Please review [our security policy](../../security/policy) on how to report security vulnerabilities.

## Credits

-   [Thura Aung](https://github.com/thuraaung2493)
-   [All Contributors](../../contributors)

## License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.

---
