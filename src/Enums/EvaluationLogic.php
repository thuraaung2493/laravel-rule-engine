<?php

namespace Thuraaung\RuleEngine\Enums;

enum EvaluationLogic: string
{
    case ALL = 'all';
    case ANY = 'any';
    case FAIL_FAST = 'fail_fast';

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }

    public static function options(): array
    {
        return [
            self::ALL->value => 'All rules must pass',
            self::ANY->value => 'Any rule must pass',
            self::FAIL_FAST->value => 'Fail as soon as any rule fails',
        ];
    }

    public function is(string|self $logic): bool
    {
        if (is_string($logic)) {
            return $this->value === $logic;
        }

        if ($logic instanceof self) {
            return $this->value === $logic->value;
        }

        return false;
    }
}
