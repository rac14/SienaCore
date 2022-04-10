<?php

namespace DidntPot\player\tasks\async;

use DidntPot\misc\AbstractAsyncTask;
use DidntPot\player\session\Session;
use DidntPot\PracticeCore;
use DidntPot\utils\Utils;
use JetBrains\PhpStorm\Pure;
use pocketmine\Player;
use pocketmine\Server;

class AsyncPlayerJoin extends AbstractAsyncTask
{
    private string $player;

    #[Pure] public function __construct(Player $player)
    {
        $this->player = $player->getName();
    }

    public function onRun()
    {
    }

    public function onTaskComplete(Server $server, PracticeCore $core): void
    {
        $player = $server->getPlayerExact($this->player);

        if (!$player instanceof Player) return;

        if ($player->isOnline()) {
            Utils::sendRulesForm($player);
            Session::getSession($player)->initializeJoin($player);
        } else {
            $core->getLogger()->critical("[ASYNC_PLAYER_JOIN] > Couldn't load " . $this->player . "'s data.");
        }
    }
}