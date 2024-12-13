<?php

namespace Snadnee\Toolkit;

use Illuminate\Contracts\Queue\ShouldQueue;
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

        $this->registerPestPresets();
    }

    private function registerPestPresets(): void
    {
        pest()->presets()->custom('snadnee', function () {
            return [
                expect(['dd', 'dump', 'ray'])->not->toBeUsed(),
                expect('App\Jobs')->toImplement(ShouldQueue::class),
                expect('App\Notifications')->toImplement(ShouldQueue::class),
                expect('App\Notifications')->toHaveSuffix('Notification'),
                expect('App\Jobs')->toHaveSuffix('Job'),
                expect('App\Actions')->toHaveSuffix('Action'),
                expect('App\Actions')->toExtend('\App\Actions\Action')->ignoring('App\Actions\Action'),
                expect('App\Http\Requests')->toHaveSuffix('Request'),
                expect('App\Policies')->toHaveSuffix('Policy'),
                expect('App\Enums')->toBeEnums()->ignoring('App\Enums\Attributes'),
                expect('App')->traits()->not->toHaveSuffix('Trait'),
            ];
        });
    }
}
