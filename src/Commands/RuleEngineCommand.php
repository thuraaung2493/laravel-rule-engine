<?php

namespace Thuraaung\RuleEngine\Commands;

use Illuminate\Console\Command;

class RuleEngineCommand extends Command
{
    public $signature = 'laravel-rule-engine';

    public $description = 'My command';

    public function handle(): int
    {
        $this->comment('All done');

        return self::SUCCESS;
    }
}
