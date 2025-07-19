<?php

namespace Thuraaung\RuleEngine\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Rule extends Model
{
    /** @use HasFactory<\Database\Factories\RuleFactory> */
    use HasFactory;

    protected $guarded = [];

    protected $casts = [
        'action_value' => 'array',
        'active' => 'boolean',
    ];

    public function ruleGroup(): BelongsTo
    {
        return $this->belongsTo(RuleGroup::class);
    }
}
