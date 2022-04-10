<?php

namespace DidntPot\tasks\types\internal;

use DidntPot\player\sessions\PlayerHandler;
use DidntPot\PracticeCore;
use pocketmine\scheduler\Task;

class SessionTask extends Task
{
    /** @var PracticeCore */
    private PracticeCore $plugin;

    /**
     * @param PracticeCore $plugin
     */
    public function __construct(PracticeCore $plugin)
    {
        $this->plugin = $plugin;
    }

    /**
     * @param int $currentTick
     * @return void
     */
    public function onRun(int $currentTick)
    {
        foreach ($this->plugin->getServer()->getOnlinePlayers() as $player)
        {
            if (!$player->isOnline()) return;
            if (!PlayerHandler::hasSession($player)) return;

            $session = PlayerHandler::getSession($player);

            $session->updatePlayer();
            $session->updateCps();
        }
    }
}