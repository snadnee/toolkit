<?php

namespace Snadnee\Toolkit\Commands;

use Illuminate\Console\Command;
use Illuminate\Filesystem\Filesystem;

use function Laravel\Prompts\confirm;
use function Laravel\Prompts\info;
use function Laravel\Prompts\multiselect;
use function Laravel\Prompts\select;
use Illuminate\Support\Str;

class InstallWorkflowsCommand extends Command
{
    protected $signature = 'toolkit:install-workflows';

    protected $description = "Install and configure workflows for Github Actions";

    public function __construct(
        protected Filesystem $filesystem,
    ) {
        parent::__construct();
    }

    public function handle(): int
    {
        $environments = multiselect(
            label: 'For which environments do you want to create a workflow?',
            options: ['dev', 'staging', 'main'],
        );

        $phpVersion = select(
            label: 'Select PHP version',
            options: ['8.0', '8.1', '8.2', '8.3'],  
        );

        $nodeVersion = select(
            label: 'Select Node version',
            options: ['16.20.2', '18.20.4', '20.18.0', '22.10.0'],
        );

        $withDeployment = confirm(
            label: 'Do you want to automate deployments?',
            default: false,
            hint: 'If so, check the generated workflows and add Vapor API token to Github',
        );

        foreach ($environments as $environment) {
            $path = $this->getPath($environment);

            $this->makeDirectory($path);

            $this->filesystem->put($path, $this->buildWorkflow($environment, $phpVersion, $nodeVersion, $withDeployment));
        }

        $message = sprintf(
            'Workflow for %s %s created successfully.',
            Str::replaceLast(', ', ' and ', implode(', ', $environments)),
            Str::plural('environment', count($environments)),
        );

        info($message);

        return self::SUCCESS;
    }

    protected function buildWorkflow(string $environment, string $phpVersion, string $nodeVersion, bool $withDeployment): string
    {
        $replace = [
            '{{ phpVersion }}' => $phpVersion,
            '{{ nodeVersion }}' => $nodeVersion,
        ];

        return str_replace(
            search: array_keys($replace),
            replace: array_values($replace),
            subject: $this->filesystem->get($this->getStub($environment, $withDeployment)),
        );
    }

    protected function makeDirectory(string $path): ?string
    {
        if (! $this->filesystem->isDirectory(dirname($path))) {
            $this->filesystem->makeDirectory(dirname($path), 0777, true, true);
        }

        return $path;
    }

    protected function getPath(string $name): string
    {
        $basePath = dirname($this->laravel['path']) . '/.github/workflows/';

        return $basePath . str_replace('\\', '/', $name) . '.yml';
    }

    protected function getStub(string $environment, bool $withDeployment): string
    {
        $rootPath = dirname(dirname(dirname(__FILE__)));

        if ($withDeployment) {
            return $rootPath . '/stubs/workflows/' . $environment . '-with-deployment.stub';
        } else {
            return $rootPath . '/stubs/workflows/' . $environment . '.stub';
        }
    }
}
