<?php

namespace Snadnee\Toolkit;

use Snadnee\Toolkit\Commands\CheckCommand;
use Snadnee\Toolkit\Commands\ExtractTranslationsCommand;
use Snadnee\Toolkit\Commands\GitHooksSetupCommand;
use Snadnee\Toolkit\Commands\InstallWorkflowsCommand;
use Spatie\LaravelPackageTools\Package;
use Spatie\LaravelPackageTools\PackageServiceProvider;

class ToolkitServiceProvider extends PackageServiceProvider
{
    public function configurePackage(Package $package): void
    {
        /*
         * This class is a Package Service Provider
         *
         * More info: https://github.com/spatie/laravel-package-tools
         */
        $package
            ->name('toolkit')
            ->hasConfigFile()
            ->hasViews()
            ->hasCommands([
                GitHooksSetupCommand::class,
                ExtractTranslationsCommand::class,
                CheckCommand::class,
                InstallWorkflowsCommand::class,
            ]);
    }
}
