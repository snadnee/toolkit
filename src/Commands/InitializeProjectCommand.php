<?php

namespace Snadnee\Toolkit\Commands;

use Illuminate\Console\Command;

use function Laravel\Prompts\multiselect;

class InitializeProjectCommand extends Command
{
    protected $signature = 'toolkit:init';

    protected $description = 'Select which toolkit features you want to use in this project';

    public function handle(): int
    {
        multiselect(
            label: 'Select which toolkit features you want to use in this project',
            options: [
                'githooks' => 'Pre-commit hooks',
                'workflows' => 'Github workflows',
                'be_translations' => 'Backend translations',
                'fe_translations' => 'Frontend translations',
            ]
        );

        // @TODO: Implement logic for initializing each part

        return 0;
    }
}
