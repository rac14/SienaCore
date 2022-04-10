<?php

namespace DidntPot\game\level;

use pocketmine\plugin\PluginException;
use pocketmine\scheduler\AsyncTask;

class AsyncDeleteLevel extends AsyncTask
{
    /* @var string */
    private $directory;

    public function __construct(string $path)
    {
        $this->directory = $path;
    }

    /**
     * Actions to execute when run
     *
     * @return void
     */
    public function onRun()
    {
        $this->removeDirectory($this->directory);
    }

    private function removeDirectory(string $dir): void
    {
        if (!is_dir($dir)) {
            throw new PluginException("$dir must be a directory");
        }

        if (!str_ends_with($dir, '/')) {
            $dir .= '/';
        }

        $files = glob($dir . '*', GLOB_MARK);

        foreach ($files as $file) {
            if (is_dir($file)) {
                $this->removeDirectory($file);
            } else {
                unlink($file);
            }
        }

        rmdir($dir);
    }
}