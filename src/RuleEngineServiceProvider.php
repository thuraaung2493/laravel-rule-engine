<?php

namespace Thuraaung\RuleEngine;

use Spatie\LaravelPackageTools\Package;
use Spatie\LaravelPackageTools\PackageServiceProvider;
use Thuraaung\RuleEngine\Actions\ActionRegistry;
use Thuraaung\RuleEngine\Actions\RuleActionHandler;
use Thuraaung\RuleEngine\Commands\RuleEngineCommand;

class RuleEngineServiceProvider extends PackageServiceProvider
{
    public function configurePackage(Package $package): void
    {
        /*
         * This class is a Package Service Provider
         *
         * More info: https://github.com/spatie/laravel-package-tools
         */
        $package
            ->name('laravel-rule-engine')
            ->hasConfigFile()
            ->hasTranslations()
            ->hasViews()
            ->hasMigration('create_laravel_rule_engine_table')
            ->hasCommand(RuleEngineCommand::class);
    }

    public function packageRegistered(): void
    {
        $this->app->singleton(ActionRegistry::class, function ($app) {
            $registry = new ActionRegistry;

            foreach (config('rule-engine.custom_handlers', []) as $type => $handlerClass) {
                $handler = $app->make($handlerClass);
                $registry->register($type, $handler);
            }

            return $registry;
        });

        $this->app->bind(RuleActionHandler::class, function ($app) {
            return new RuleActionHandler(
                $app->make(ActionRegistry::class),
                config('rule-engine.throw_on_error', false)
            );
        });

        $this->app->singleton(RuleEngineManager::class, function ($app) {
            return new RuleEngineManager(
                $app->make(RuleEngine::class),
                $app->make(RuleEngineRegistrar::class)
            );
        });
    }
}
