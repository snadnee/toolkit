<?php

namespace Snadnee\Toolkit\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Snadnee\Toolkit\Actions\CloneGitRepositoryAction;
use Snadnee\Toolkit\Actions\RemoveFolderAction;
use Symfony\Component\Finder\SplFileInfo;

class GitHooksSetupCommand extends Command
{
    const string GIT_HOOKS_DIRECTORY_NAME = '.githooks';
    const string GIT_HOOKS_TEMPLATE_PROJECT_URL = 'git@github.com:snadnee/Snadnee.git';
    const string GIT_HOOKS_TEMPLATE_PROJECT_BRANCH = 'master';

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'git:setup-hooks';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = "Set up git hooks to the project directory '.githooks'";

    /**
     * Folder name for temporary cloned project with template git-hooks.
     *
     * @var string
     */
    private string $tempGitHooksProjectFolderName;

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->tempGitHooksProjectFolderName = now()->format('Y_m_d') . '_git_hooks_temp_project';
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle(): int
    {
        $gitHooksFolderAlreadyExist = File::exists(self::GIT_HOOKS_DIRECTORY_NAME);

        if (!$gitHooksFolderAlreadyExist) {
            $this->createGitHooksDirectory();
        }

        $this->cloneRepositoryWithTemplateGitHooks();

        $gitHookFiles = $this->readGitHookFilesFromClonedRepository();

        $this->saveGitHookFiles($gitHookFiles);

        $this->removeTempDirectory();

        $this->ensureAllGitHookFilesAreExecutable();

        $this->setupGitHooksPath();

        $successMessage = "All done. Git hooks set up successfully.";
        if (!$gitHooksFolderAlreadyExist) {
            $successMessage .= " Please restart your IDE.";
        }

        $this->info($successMessage);

        return 0;
    }

    private function createGitHooksDirectory()
    {
        $this->info('Creating git hooks directory.');

        File::makeDirectory(self::GIT_HOOKS_DIRECTORY_NAME);
    }

    public function cloneRepositoryWithTemplateGitHooks(): void
    {
        $this->info('Cloning repository with template git hooks.');

        if (File::exists($this->tempGitHooksProjectFolderName)) {
            RemoveFolderAction::make()->run($this->tempGitHooksProjectFolderName);
        }

        CloneGitRepositoryAction::make()->run(
            self::GIT_HOOKS_TEMPLATE_PROJECT_URL,
            self::GIT_HOOKS_TEMPLATE_PROJECT_BRANCH,
            base_path($this->tempGitHooksProjectFolderName),
        );
    }

    /**
     * @return SplFileInfo[]
     */
    public function readGitHookFilesFromClonedRepository($silent = false): array
    {
        if (!$silent) {
            $this->info('Reading template git-hook files from cloned repository.');
        }

        return File::files($this->tempGitHooksProjectFolderName . '/' . self::GIT_HOOKS_DIRECTORY_NAME);
    }

    public function saveGitHookFiles(array $gitHookFiles, $force = false, $silent = false): void
    {
        foreach ($gitHookFiles as $gitHookFile) {
            if (File::exists(self::GIT_HOOKS_DIRECTORY_NAME . '/' . $gitHookFile->getFilename())) {
                if (!$force && !$this->confirm("Git hook file [{$gitHookFile->getFilename()}] already exists. Override?")) {
                    continue;
                }
            }

            $gitHookFileName = self::GIT_HOOKS_DIRECTORY_NAME . '/' . $gitHookFile->getFilename();
            File::put($gitHookFileName, $gitHookFile->getContents());
            $this->addFileToGit($gitHookFileName, $silent);
        }
    }

    private function addFileToGit(string $gitHookFileName, $silent = false): void
    {
        if (!$silent) {
            $this->info("Adding [$gitHookFileName] file to git.");
        }

        exec("git add $gitHookFileName");
    }

    public function ensureAllGitHookFilesAreExecutable($silent = false): void
    {
        if (!$silent) {
            $this->info('Ensuring all git hook files are executable.');
        }

        $currentProjectGitHookFiles = File::files(self::GIT_HOOKS_DIRECTORY_NAME);

        foreach ($currentProjectGitHookFiles as $file) {
            $this->ensureFileIsExecutable($file);
        }
    }

    private function ensureFileIsExecutable(SplFileInfo $file): void
    {
        $realPath = $file->getRealPath();

        exec("test -x $realPath && echo true || echo false", $isExecutable);

        if ($isExecutable[0] !== 'true') {
            exec("chmod +x $realPath");

            $this->info("[{$file->getFilename()}] file made executable. ");
        }
    }

    public function removeTempDirectory(): void
    {
        RemoveFolderAction::make()->run($this->tempGitHooksProjectFolderName);
    }

    public function setupGitHooksPath($silent = false): void
    {
        if (!$silent) {
            $this->info('Setting up git hooks path.');
        }

        exec("git rev-parse --git-path hooks", $gitHooksPath);
        if ($gitHooksPath[0] !== self::GIT_HOOKS_DIRECTORY_NAME) {
            exec('git config core.hooksPath ' . self::GIT_HOOKS_DIRECTORY_NAME);

            if (!$silent) {
                $this->info('Git hooks path set up successfully');
            }
        } else {
            if (!$silent) {
                $this->info('Git hooks path already set up correctly');
            }
        }
    }
}
