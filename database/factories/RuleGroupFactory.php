<?php

namespace Thuraaung\RuleEngine\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Thuraaung\RuleEngine\Enums\EvaluationLogic;
use Thuraaung\RuleEngine\Models\RuleGroup;

class RuleGroupFactory extends Factory
{
    protected $model = RuleGroup::class;

    public function definition(): array
    {
        return [
            'name' => $this->faker->unique()->word().'Group',
            'priority' => $this->faker->numberBetween(0, 100),
            'evaluation_logic' => $this->faker->randomElement(EvaluationLogic::values()),
        ];
    }
}
