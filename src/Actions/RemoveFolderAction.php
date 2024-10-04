<?php

namespace Snadnee\Toolkit\Actions;

class RemoveFolderAction extends Action
{
    public function run(string $path): void
    {
        exec('rm -rf '.$path);
    }
}
