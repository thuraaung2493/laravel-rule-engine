<?php

namespace Thuraaung\RuleEngine\Models;

use Thuraaung\RuleEngine\Enums\EvaluationLogic;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class RuleGroup extends Model
{
    /** @use HasFactory<\Database\Factories\RuleGroupFactory> */
    use HasFactory;

    protected $guarded = [];

    protected $casts = [
        'evaluation_logic' => EvaluationLogic::class,
    ];

    public function rules(): HasMany
    {
        return $this->hasMany(Rule::class)
            ->where('active', true)
            ->orderByDesc('priority');
    }
}
