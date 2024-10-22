<?php

namespace Snadnee\Toolkit\Commands;

use Illuminate\Console\Command;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Process;
use PhpSpellcheck\Spellchecker\Aspell;

class CheckCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'toolkit:check {--packages} {--spelling} {--git-hooks} {--env-variables}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Check things in Laravel project.';

    private array $composer = [];

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        if ($this->option('packages')) {
            $this->checkPackages();

            return Command::SUCCESS;
        }
        if ($this->option('spelling')) {
            $this->checkSpelling();

            return Command::SUCCESS;
        }
        if ($this->option('git-hooks')) {
            $this->checkGitHooks();

            return Command::SUCCESS;
        }
        if ($this->option('env-variables')) {
            $this->checkEnvVariables();

            return Command::SUCCESS;
        }

        $this->checkAll();

        return Command::SUCCESS;
    }

    private function checkAll(): void
    {
        $this->checkPackages();
        $this->checkSpelling();
        $this->checkGitHooks();
        $this->checkEnvVariables();
        $this->checkLazyLoading();
        $this->checkToolkitVersion();
    }

    private function checkToolkitVersion(): void
    {
        $packageName = 'snadnee/toolkit';
        $result = Process::run("composer outdated $packageName --direct --format=json");
        $outdatedInfo = json_decode($result->output(), true);
        if (Arr::last($outdatedInfo['versions']) !== $outdatedInfo['latest']) {
            $this->warn('Toolkit is not up to date. Current version: '.$outdatedInfo['version'].', latest version: '.$outdatedInfo['latest']);
        } else {
            $this->info('Toolkit is up to date.');
        }
    }

    private function checkLazyLoading(): void
    {
        if (Model::preventsLazyLoading()) {
            $this->info('Model::preventLazyLoading() is used');
        } else {
            $this->warn('Model::preventLazyLoading() is not used');
        }
    }

    private function checkEnvVariables(): void
    {
        $required_variables = [
            'GIT_ACCESS_TOKEN',
            'GIT_PROJECT',
        ];

        foreach ($required_variables as $variable) {
            if (! env($variable)) {
                $this->output->error('Missing required ENV variable: '.$variable);
            }
        }
    }

    private function checkGitHooks(): void
    {
        $gitHooksSetupCommand = new GitHooksSetupCommand;
        $gitHooksFolderAlreadyExist = File::exists(GitHooksSetupCommand::GIT_HOOKS_DIRECTORY_NAME);

        if (! $gitHooksFolderAlreadyExist) {
            if ($this->confirm('Do you want to set up git hooks?')) {
                $this->callSilently('git:setup-hooks');
            }
        } else {
            $this->info('Checking git hooks version...');
            // check if git hooks content is same as current version
            $gitHooksSetupCommand->cloneRepositoryWithTemplateGitHooks(true);
            $gitHookFiles = $gitHooksSetupCommand->readGitHookFilesFromClonedRepository(true);
            $same_version = true;

            foreach ($gitHookFiles as $gitHookFile) {
                if (! File::exists(GitHooksSetupCommand::GIT_HOOKS_DIRECTORY_NAME.'/'.$gitHookFile->getFilename())) {
                    $same_version = false;
                    break;
                }
                if (sha1_file($gitHookFile->getPathname()) !== sha1_file(GitHooksSetupCommand::GIT_HOOKS_DIRECTORY_NAME.'/'.$gitHookFile->getFilename())) {
                    $same_version = false;
                    break;
                }
            }

            if (! $same_version) {
                $this->warn('Git hooks are not up to date.');
                if ($this->confirm('Do you want to update git hooks?')) {
                    $gitHooksSetupCommand->saveGitHookFiles($gitHookFiles, true, true);
                    $gitHooksSetupCommand->ensureAllGitHookFilesAreExecutable(true);
                    $gitHooksSetupCommand->setupGitHooksPath(true);

                    $this->info('Git hooks are up to date.');
                }
            } else {
                $this->info('Git hooks are up to date.');
            }

            $gitHooksSetupCommand->removeTempDirectory();
        }

    }

    private function checkSpelling(): void
    {
        $langPaths = config('toolkit.spell-check.langPaths');
        $breakBeforeNextLang = false;

        if (empty($langPaths)) {
            $this->warn('No language files found');

            return;
        }

        foreach ($langPaths as $i => $langPath) {
            try {
                $files = File::files(base_path($langPath));
            } catch (\Exception $e) {
                $this->output->error('No language files found');

                return;
            }
            foreach ($files as $j => $file) {
                if ($i + $j != 0 && $breakBeforeNextLang) {
                    $this->line('');
                }
                $lang = substr($file->getFilename(), 0, 2);
                $langString = config('spellCheck.langString.'.$lang);
                $this->info("Checking spelling for $langString");
                [$misspellingsCounter, $messages] = $this->checkSpellingForLang($file, $langString);

                $this->output->write("\033[1A");
                if ($misspellingsCounter === 0) {
                    $breakBeforeNextLang = false;
                    $this->info("[OK] Checking spelling for $langString");
                } else {
                    $breakBeforeNextLang = true;
                    $this->warn("[NOK] Checking spelling for $langString - $misspellingsCounter misspellings found");
                    foreach ($messages as $message) {
                        $this->line($message);
                    }
                }
            }
        }
    }

    /**
     * Check the spelling for a specific language file.
     *
     * @param  string  $langPath  The path to the language file. - e.g. '/lang/cs.json'
     * @param  string  $langString  The language string. - e.g. 'cs_CZ'
     */
    private function checkSpellingForLang(string $langPath, string $langString): array
    {
        $aspell = Aspell::create();

        $file = File::get($langPath);
        $json = json_decode($file, true);

        $keysIgnoreList = config('spellCheck.ignore-keys.all');
        if (isset(config('spellCheck.ignore-keys')[$langString])) {
            $keysIgnoreList = array_merge($keysIgnoreList, config('spellCheck.ignore-keys')[$langString]);
        }

        // Remove keys from json
        foreach ($keysIgnoreList as $key) {
            if (isset($json[$key])) {
                unset($json[$key]);
            }
        }

        $phrases = array_values($json);

        // Remove variables from phrases
        foreach ($phrases as $key => $value) {
            $phrases[$key] = preg_replace('/:[a-zA-Z_]+/', '', $value);
        }

        $words_ignore_list = config('spellCheck.ignore-words.all');
        if (isset(config('spellCheck.ignore-words')[$langString])) {
            $words_ignore_list = array_merge($words_ignore_list, config('spellCheck.ignore-words')[$langString]);
        }
        $misspellingsCounter = 0;
        $messages = [];
        foreach ($phrases as $word) {
            $misspellings = $aspell->check($word, [$langString], [$langPath]);

            foreach ($misspellings as $misspelling) {
                if (in_array($misspelling->getWord(), $words_ignore_list) || $misspelling->getWord()[0] === ':') {
                    continue;
                }
                $misspellingsCounter++;
                $suggestions = $misspelling->getSuggestions();

                $messages[] = 'Misspelling: '.$misspelling->getWord().'=>'.implode(', ', array_splice($suggestions, 0, 5));
            }
        }

        return [$misspellingsCounter, $messages];

    }

    /**
     * Check if all common packages are installed in the Laravel application.
     */
    private function checkPackages(): void
    {
        $packages = [
            'require' => [
                'snadnee/packages-enums',
                'spatie/ignition',
            ],
            'require-dev' => [
                'barryvdh/laravel-ide-helper',
                'pestphp/pest',
                'tightenco/duster',
                'nunomaduro/larastan',
                'enlightn/enlightn',
                'driftingly/rector-laravel',
            ],
        ];

        $missingPackages = [];
        $this->composer = json_decode(file_get_contents(base_path('composer.json')), true);

        foreach ($packages as $type => $packageList) {
            foreach ($packageList as $packageName) {
                if (! $this->isPackageInstalled($type, $packageName)) {
                    $type_test = $type === 'require' ? 'require-dev' : 'require';

                    if ($this->isPackageInstalled($type_test, $packageName)) {
                        $this->warn("Package $packageName is installed in $type_test, but should be in $type");
                    } else {
                        $missingPackages[$packageName] = $type;
                    }
                }
            }
        }

        if (! empty($missingPackages)) {
            $this->warn('Some packages are missing in your the Laravel application.');

            foreach ($missingPackages as $package => $type) {
                $this->line("$type: $package");
            }

            if ($this->confirm('Do you want to install missing packages?')) {
                foreach ($missingPackages as $package => $type) {
                    $this->info("Installing $package");
                    $command = ['composer', 'require', $package];
                    if ($type !== 'require') {
                        $command[] = '--dev';
                    }

                    Process::run($command, function (string $type, string $buffer) {
                        $this->line($buffer);
                    });
                }
            }

        } else {
            $this->info('All common packages are installed');
        }
    }

    /**
     * Check if a package is installed in the Laravel application.
     *
     * @param  string  $packageName
     */
    private function isPackageInstalled($type, $packageName): bool
    {
        foreach ($this->composer[$type] as $package => $version) {
            if ($package === $packageName) {
                return true;
            }
        }

        return false;
    }
}
