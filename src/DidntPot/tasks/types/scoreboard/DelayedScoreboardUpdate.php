<?php

namespace DidntPot\tasks\types\scoreboard;

use DidntPot\PracticeCore;
use DidntPot\scoreboard\ScoreboardUtils;
use pocketmine\scheduler\Task;
use pocketmine\Server;

class DelayedScoreboardUpdate extends Task
{
    /**
     * @param int $currentTick
     * @return void
     */
    public function onRun(int $currentTick)
    {
        foreach(Server::getInstance()->getOnlinePlayers() as $players)
        {
            if(ScoreboardUtils::isPlayerSetSpawnScoreboard($players))
            {
                PracticeCore::getScoreboardManager()->sendSpawnScoreboard($players);
            }
        }
    }
}