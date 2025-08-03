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
            'name' => $this->faker->unique()->word().'Rule',
            'expression' => 'true',
            'action_type' => $this->faker->boolean(50) ? $this->faker->word().'Action' : null,
            'action_value' => $this->faker->boolean(50) ? ['value' => $this->faker->numberBetween(1, 100)] : null,
            'active' => $this->faker->boolean(90),
            'priority' => $this->faker->numberBetween(0, 100),
            'error_message' => $this->faker->boolean(30) ? $this->faker->sentence() : null,
        ];
    }

    public function active(): static
    {
        return $this->state(fn (array $attributes) => [
            'active' => true,
        ]);
    }

    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'active' => false,
        ]);
    }

    public function passing(): static
    {
        return $this->state(fn (array $attributes) => [
            'expression' => 'true',
        ]);
    }

    public function failing(): static
    {
        return $this->state(fn (array $attributes) => [
            'expression' => 'false',
        ]);
    }

    public function withExpression(string $expression): static
    {
        return $this->state(fn (array $attributes) => [
            'expression' => $expression,
        ]);
    }

    public function withAction(string $type, array $value = []): static
    {
        return $this->state(fn (array $attributes) => [
            'action_type' => $type,
            'action_value' => $value,
        ]);
    }

    public function noAction(?array $value = null): static
    {
        return $this->state(fn (array $attributes) => [
            'action_type' => null,
            'action_value' => $value,
        ]);
    }

    public function withPriority(int $priority): static
    {
        return $this->state(fn (array $attributes) => [
            'priority' => $priority,
        ]);
    }

    public function withErrorMessage(string $message): static
    {
        return $this->state(fn (array $attributes) => [
            'error_message' => $message,
        ]);
    }
}
