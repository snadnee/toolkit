<?php

use function Pest\Laravel\artisan;

it('can generate workflow for environment', function (string $environment, string $php, string $node, bool $deployment) {
    artisan('toolkit:install-workflows')
        ->expectsQuestion('For which environments do you want to create a workflow?', $environment)
        ->expectsQuestion('Select PHP version', $php)
        ->expectsQuestion('Select Node version', $node)
        ->expectsConfirmation('Do you want to automate deployments?', $deployment)
        ->expectsOutputToContain("Workflow for $environment environment created successfully.");
})->with('workflow_combinations');

it('can generate workflows for all environments without deployment', function () {
    artisan('toolkit:install-workflows')
        ->expectsQuestion('For which environments do you want to create a workflow?', ['dev', 'staging', 'main'])
        ->expectsQuestion('Select PHP version', '8.3')
        ->expectsQuestion('Select Node version', '20.18.0')
        ->expectsConfirmation('Do you want to automate deployments?', false)
        ->expectsOutputToContain('Workflow for dev, staging and main environments created successfully.');
});

it('can generate workflows for all environments with deployment', function () {
    artisan('toolkit:install-workflows')
        ->expectsQuestion('For which environments do you want to create a workflow?', ['dev', 'staging', 'main'])
        ->expectsQuestion('Select PHP version', '8.3')
        ->expectsQuestion('Select Node version', '20.18.0')
        ->expectsConfirmation('Do you want to automate deployments?', true)
        ->expectsOutputToContain('Workflow for dev, staging and main environments created successfully.');
});

dataset('workflow_combinations', [
    ['environment' => 'dev', 'php' => '8.0', 'node' => '16.20.2', 'deployment' => false],
    ['environment' => 'dev', 'php' => '8.1', 'node' => '16.20.2', 'deployment' => false],
    ['environment' => 'dev', 'php' => '8.2', 'node' => '16.20.2', 'deployment' => false],
    ['environment' => 'dev', 'php' => '8.3', 'node' => '16.20.2', 'deployment' => false],
    ['environment' => 'dev', 'php' => '8.0', 'node' => '18.20.4', 'deployment' => false],
    ['environment' => 'dev', 'php' => '8.1', 'node' => '18.20.4', 'deployment' => false],
    ['environment' => 'dev', 'php' => '8.2', 'node' => '18.20.4', 'deployment' => false],
    ['environment' => 'dev', 'php' => '8.3', 'node' => '18.20.4', 'deployment' => false],
    ['environment' => 'dev', 'php' => '8.0', 'node' => '20.18.0', 'deployment' => false],
    ['environment' => 'dev', 'php' => '8.1', 'node' => '20.18.0', 'deployment' => false],
    ['environment' => 'dev', 'php' => '8.2', 'node' => '20.18.0', 'deployment' => false],
    ['environment' => 'dev', 'php' => '8.3', 'node' => '20.18.0', 'deployment' => false],
    ['environment' => 'dev', 'php' => '8.0', 'node' => '22.10.0', 'deployment' => false],
    ['environment' => 'dev', 'php' => '8.1', 'node' => '22.10.0', 'deployment' => false],
    ['environment' => 'dev', 'php' => '8.2', 'node' => '22.10.0', 'deployment' => false],
    ['environment' => 'dev', 'php' => '8.3', 'node' => '22.10.0', 'deployment' => false],
    ['environment' => 'dev', 'php' => '8.0', 'node' => '16.20.2', 'deployment' => true],
    ['environment' => 'dev', 'php' => '8.1', 'node' => '16.20.2', 'deployment' => true],
    ['environment' => 'dev', 'php' => '8.2', 'node' => '16.20.2', 'deployment' => true],
    ['environment' => 'dev', 'php' => '8.3', 'node' => '16.20.2', 'deployment' => true],
    ['environment' => 'dev', 'php' => '8.0', 'node' => '18.20.4', 'deployment' => true],
    ['environment' => 'dev', 'php' => '8.1', 'node' => '18.20.4', 'deployment' => true],
    ['environment' => 'dev', 'php' => '8.2', 'node' => '18.20.4', 'deployment' => true],
    ['environment' => 'dev', 'php' => '8.3', 'node' => '18.20.4', 'deployment' => true],
    ['environment' => 'dev', 'php' => '8.0', 'node' => '20.18.0', 'deployment' => true],
    ['environment' => 'dev', 'php' => '8.1', 'node' => '20.18.0', 'deployment' => true],
    ['environment' => 'dev', 'php' => '8.2', 'node' => '20.18.0', 'deployment' => true],
    ['environment' => 'dev', 'php' => '8.3', 'node' => '20.18.0', 'deployment' => true],
    ['environment' => 'dev', 'php' => '8.0', 'node' => '22.10.0', 'deployment' => true],
    ['environment' => 'dev', 'php' => '8.1', 'node' => '22.10.0', 'deployment' => true],
    ['environment' => 'dev', 'php' => '8.2', 'node' => '22.10.0', 'deployment' => true],
    ['environment' => 'dev', 'php' => '8.3', 'node' => '22.10.0', 'deployment' => true],
    ['environment' => 'staging', 'php' => '8.0', 'node' => '16.20.2', 'deployment' => false],
    ['environment' => 'staging', 'php' => '8.1', 'node' => '16.20.2', 'deployment' => false],
    ['environment' => 'staging', 'php' => '8.2', 'node' => '16.20.2', 'deployment' => false],
    ['environment' => 'staging', 'php' => '8.3', 'node' => '16.20.2', 'deployment' => false],
    ['environment' => 'staging', 'php' => '8.0', 'node' => '18.20.4', 'deployment' => false],
    ['environment' => 'staging', 'php' => '8.1', 'node' => '18.20.4', 'deployment' => false],
    ['environment' => 'staging', 'php' => '8.2', 'node' => '18.20.4', 'deployment' => false],
    ['environment' => 'staging', 'php' => '8.3', 'node' => '18.20.4', 'deployment' => false],
    ['environment' => 'staging', 'php' => '8.0', 'node' => '20.18.0', 'deployment' => false],
    ['environment' => 'staging', 'php' => '8.1', 'node' => '20.18.0', 'deployment' => false],
    ['environment' => 'staging', 'php' => '8.2', 'node' => '20.18.0', 'deployment' => false],
    ['environment' => 'staging', 'php' => '8.3', 'node' => '20.18.0', 'deployment' => false],
    ['environment' => 'staging', 'php' => '8.0', 'node' => '22.10.0', 'deployment' => false],
    ['environment' => 'staging', 'php' => '8.1', 'node' => '22.10.0', 'deployment' => false],
    ['environment' => 'staging', 'php' => '8.2', 'node' => '22.10.0', 'deployment' => false],
    ['environment' => 'staging', 'php' => '8.3', 'node' => '22.10.0', 'deployment' => false],
    ['environment' => 'staging', 'php' => '8.0', 'node' => '16.20.2', 'deployment' => true],
    ['environment' => 'staging', 'php' => '8.1', 'node' => '16.20.2', 'deployment' => true],
    ['environment' => 'staging', 'php' => '8.2', 'node' => '16.20.2', 'deployment' => true],
    ['environment' => 'staging', 'php' => '8.3', 'node' => '16.20.2', 'deployment' => true],
    ['environment' => 'staging', 'php' => '8.0', 'node' => '18.20.4', 'deployment' => true],
    ['environment' => 'staging', 'php' => '8.1', 'node' => '18.20.4', 'deployment' => true],
    ['environment' => 'staging', 'php' => '8.2', 'node' => '18.20.4', 'deployment' => true],
    ['environment' => 'staging', 'php' => '8.3', 'node' => '18.20.4', 'deployment' => true],
    ['environment' => 'staging', 'php' => '8.0', 'node' => '20.18.0', 'deployment' => true],
    ['environment' => 'staging', 'php' => '8.1', 'node' => '20.18.0', 'deployment' => true],
    ['environment' => 'staging', 'php' => '8.2', 'node' => '20.18.0', 'deployment' => true],
    ['environment' => 'staging', 'php' => '8.3', 'node' => '20.18.0', 'deployment' => true],
    ['environment' => 'staging', 'php' => '8.0', 'node' => '22.10.0', 'deployment' => true],
    ['environment' => 'staging', 'php' => '8.1', 'node' => '22.10.0', 'deployment' => true],
    ['environment' => 'staging', 'php' => '8.2', 'node' => '22.10.0', 'deployment' => true],
    ['environment' => 'staging', 'php' => '8.3', 'node' => '22.10.0', 'deployment' => true],
    ['environment' => 'main', 'php' => '8.0', 'node' => '16.20.2', 'deployment' => false],
    ['environment' => 'main', 'php' => '8.1', 'node' => '16.20.2', 'deployment' => false],
    ['environment' => 'main', 'php' => '8.2', 'node' => '16.20.2', 'deployment' => false],
    ['environment' => 'main', 'php' => '8.3', 'node' => '16.20.2', 'deployment' => false],
    ['environment' => 'main', 'php' => '8.0', 'node' => '18.20.4', 'deployment' => false],
    ['environment' => 'main', 'php' => '8.1', 'node' => '18.20.4', 'deployment' => false],
    ['environment' => 'main', 'php' => '8.2', 'node' => '18.20.4', 'deployment' => false],
    ['environment' => 'main', 'php' => '8.3', 'node' => '18.20.4', 'deployment' => false],
    ['environment' => 'main', 'php' => '8.0', 'node' => '20.18.0', 'deployment' => false],
    ['environment' => 'main', 'php' => '8.1', 'node' => '20.18.0', 'deployment' => false],
    ['environment' => 'main', 'php' => '8.2', 'node' => '20.18.0', 'deployment' => false],
    ['environment' => 'main', 'php' => '8.3', 'node' => '20.18.0', 'deployment' => false],
    ['environment' => 'main', 'php' => '8.0', 'node' => '22.10.0', 'deployment' => false],
    ['environment' => 'main', 'php' => '8.1', 'node' => '22.10.0', 'deployment' => false],
    ['environment' => 'main', 'php' => '8.2', 'node' => '22.10.0', 'deployment' => false],
    ['environment' => 'main', 'php' => '8.3', 'node' => '22.10.0', 'deployment' => false],
    ['environment' => 'main', 'php' => '8.0', 'node' => '16.20.2', 'deployment' => true],
    ['environment' => 'main', 'php' => '8.1', 'node' => '16.20.2', 'deployment' => true],
    ['environment' => 'main', 'php' => '8.2', 'node' => '16.20.2', 'deployment' => true],
    ['environment' => 'main', 'php' => '8.3', 'node' => '16.20.2', 'deployment' => true],
    ['environment' => 'main', 'php' => '8.0', 'node' => '18.20.4', 'deployment' => true],
    ['environment' => 'main', 'php' => '8.1', 'node' => '18.20.4', 'deployment' => true],
    ['environment' => 'main', 'php' => '8.2', 'node' => '18.20.4', 'deployment' => true],
    ['environment' => 'main', 'php' => '8.3', 'node' => '18.20.4', 'deployment' => true],
    ['environment' => 'main', 'php' => '8.0', 'node' => '20.18.0', 'deployment' => true],
    ['environment' => 'main', 'php' => '8.1', 'node' => '20.18.0', 'deployment' => true],
    ['environment' => 'main', 'php' => '8.2', 'node' => '20.18.0', 'deployment' => true],
    ['environment' => 'main', 'php' => '8.3', 'node' => '20.18.0', 'deployment' => true],
    ['environment' => 'main', 'php' => '8.0', 'node' => '22.10.0', 'deployment' => true],
    ['environment' => 'main', 'php' => '8.1', 'node' => '22.10.0', 'deployment' => true],
    ['environment' => 'main', 'php' => '8.2', 'node' => '22.10.0', 'deployment' => true],
    ['environment' => 'main', 'php' => '8.3', 'node' => '22.10.0', 'deployment' => true],
]);
