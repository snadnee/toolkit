<?php

namespace Snadnee\Toolkit\Actions;

class RemoveFolderAction extends Action
{
    public function run(string $path): void
    {
        // @TODO: Untestable. Replace with Process facade
        exec('rm -rf '.$path);
    }
}
