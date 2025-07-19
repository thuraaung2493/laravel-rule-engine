<?php

namespace Thuraaung\RuleEngine\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Thuraaung\RuleEngine\Models\Rule;
use Thuraaung\RuleEngine\Models\RuleGroup;

class RuleFactory extends Factory
{
    protected $model = Rule::class;

    public function definition(): array
    {
        return [
            'rule_group_id' => RuleGroup::factory(),
            'name' => $this->faker->unique()->word() . 'Rule',
            'expression' => 'true',
            'action_type' => $this->faker->boolean(50) ? $this->faker->word() . 'Action' : null,
            'action_value' => $this->faker->boolean(50) ? ['value' => $this->faker->numberBetween(1, 100)] : null,
            'active' => $this->faker->boolean(90),
            'priority' => $this->faker->numberBetween(0, 100),
            'error_message' => $this->faker->boolean(30) ? $this->faker->sentence() : null,
        ];
    }

    public function active(): Factory
    {
        return $this->state(fn(array $attributes) => [
            'active' => true,
        ]);
    }

    public function inactive(): Factory
    {
        return $this->state(fn(array $attributes) => [
            'active' => false,
        ]);
    }

    public function passing(): Factory
    {
        return $this->state(fn(array $attributes) => [
            'expression' => 'true',
        ]);
    }

    public function failing(): Factory
    {
        return $this->state(fn(array $attributes) => [
            'expression' => 'false',
        ]);
    }

    public function withExpression(string $expression): Factory
    {
        return $this->state(fn(array $attributes) => [
            'expression' => $expression,
        ]);
    }

    public function withAction(string $type, array $value = []): Factory
    {
        return $this->state(fn(array $attributes) => [
            'action_type' => $type,
            'action_value' => $value,
        ]);
    }

    public function withErrorMessage(string $message): Factory
    {
        return $this->state(fn(array $attributes) => [
            'error_message' => $message,
        ]);
    }
}
