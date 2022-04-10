<?php

namespace DidntPot\misc;

use DidntPot\PracticeCore;
use pocketmine\scheduler\AsyncTask;
use pocketmine\Server;

abstract class AbstractAsyncTask extends AsyncTask
{
    /**
     * Called when the task is completed.
     */
    public function onCompletion(Server $server): void
    {
        $core = $server->getPluginManager()->getPlugin(PracticeCore::NAME);
        if ($core instanceof PracticeCore && $core->isEnabled()) {
            $this->onTaskComplete($server, $core);
        }
    }

    /**
     * @param Server $server
     * @param PracticeCore $core
     *
     * Called when the task has been completed.
     */
    protected function onTaskComplete(Server $server, PracticeCore $core): void
    {
    }
}