<?php

namespace DidntPot\player\tasks\async;

use DidntPot\misc\AbstractAsyncTask;
use DidntPot\player\session\Session;
use DidntPot\player\session\StatsSession;
use DidntPot\PracticeCore;
use JetBrains\PhpStorm\Pure;
use pocketmine\Player;
use pocketmine\Server;

class AsyncPlayerQuit extends AbstractAsyncTask
{
    private string $player;
    private string $reason;

    #[Pure] public function __construct(Player $player, string $reason)
    {
        $this->player = $player->getName();
        $this->reason = $reason;
    }

    public function onRun()
    {
    }

    public function onTaskComplete(Server $server, PracticeCore $core): void
    {
        $player = $server->getPlayerExact($this->player);

        Session::getSession($player)->initializeQuit($player);
        echo "Init Quit Done for " . $player->getDisplayName();

        // TODO:
        /*if(Session::getSession($player)->isTagged($player)){
            if($this->reason === "client disconnect"){
                Utils::updateStats($player, 2);
                $player->kill();
            }

            SessionFactory::getSession($player)->setTagged($player, false);
        }*/

        // TODO:
        /*if($this->reason === "timeout")
        {
            foreach($server->getOnlinePlayers() as $online){
                if($online->hasPermission("practice.staff.notifications")){
                    $format = Core::getInstance()->getStaffUtils()->sendStaffNoti("timeout");
                    $format = str_replace("{name}", $player->getName(), $format);
                    $online->sendMessage($format);
                }
            }
        }*/

        StatsSession::removeSession($player);
        Session::removeSession($player);
    }
}