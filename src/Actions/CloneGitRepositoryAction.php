<?php

namespace Snadnee\Toolkit\Actions;

use Illuminate\Filesystem\Filesystem;

class CloneGitRepositoryAction extends Action
{
    public function run(string $url, string $branch, string $target): void
    {
        $filesSystem = new Filesystem;

        $filesSystem->ensureDirectoryExists($target);

        // @TODO: Untestable. Replace with Process facade
        exec("git clone -b $branch $url $target");
    }
}
