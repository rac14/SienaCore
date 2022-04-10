<?php

namespace DidntPot\player\tasks;

use pocketmine\Player;
use pocketmine\scheduler\Task;

class PlayerKickTask extends Task
{
    /** @var Player */
    private Player $player;

    public function __construct(Player $player)
    {
        $this->player = $player;
    }

    /**
     * @param int $currentCurrentCurrentTick
     */
    public function onRun(int $currentCurrentCurrentTick)
    {
        if ($this->player->isOnline()) {
            $this->player->kick("§r");
        }
    }
}