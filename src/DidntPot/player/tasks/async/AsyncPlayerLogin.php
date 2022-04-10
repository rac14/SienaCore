<?php

namespace DidntPot\player\tasks\async;

use DidntPot\misc\AbstractAsyncTask;
use DidntPot\player\session\Session;
use DidntPot\player\session\StatsSession;
use DidntPot\PracticeCore;
use JetBrains\PhpStorm\Pure;
use pocketmine\Player;
use pocketmine\Server;

class AsyncPlayerLogin extends AbstractAsyncTask
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

        if ($player->isOnline()) {
            Session::createSession($player);
            Session::getSession($player)->initializeLogin();
        } else {
            $core->getLogger()->critical("[ASYNC_PLAYER_LOGIN] > Couldn't load " . $this->player . "'s data.");
        }
    }
}