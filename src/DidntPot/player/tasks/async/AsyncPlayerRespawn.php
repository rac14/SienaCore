<?php

namespace DidntPot\player\tasks\async;

use DidntPot\misc\AbstractAsyncTask;
use DidntPot\player\sessions\PlayerHandler;
use DidntPot\PracticeCore;
use JetBrains\PhpStorm\Pure;
use pocketmine\Player;
use pocketmine\Server;

class AsyncPlayerRespawn extends AbstractAsyncTask
{
    private string $player;

    #[Pure] public function __construct(Player $player)
    {
        $this->player = $player->getName();
    }

    public function onRun(): void{}

    public function onTaskComplete(Server $server, PracticeCore $core): void
    {
        $player = $server->getPlayerExact($this->player);

        if (!$player instanceof Player) return;

        $session = PlayerHandler::getSession($player);
        $session->teleportPlayer($player, "lobby", true, true);
    }
}