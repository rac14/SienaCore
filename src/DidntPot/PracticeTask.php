<?php

namespace DidntPot;

use DidntPot\player\sessions\PlayerHandler;
use DidntPot\scoreboard\ScoreboardUtils;
use DidntPot\utils\Utils;
use pocketmine\scheduler\ClosureTask;
use pocketmine\scheduler\Task;
use pocketmine\Server;
use pocketmine\utils\TextFormat;

class PracticeTask extends Task
{
    /** @var PracticeCore */
    private PracticeCore $plugin;

    /** @var int */
    private int $seconds = 60 * 60 * 3;

    private static int $counter = 20;

    /** @var int */
    private int $currentTick;

    /**
     * @param PracticeCore $plugin
     */
    public function __construct(PracticeCore $plugin)
    {
        $this->plugin = $plugin;
        $this->currentTick = 0;
    }

    /**
     * @param int $currentTick
     */
    public function onRun(int $currentTick)
    {
        if($currentTick !== 0 && $currentTick % Utils::hoursToTicks(3) === 0)
        {
            $this->restartServer();
        }

        $lbSecs = Utils::secondsToTicks(5);

        if($this->currentTick % $lbSecs === 0 or $this->currentTick === 0)
        {
            $leaderboards = PracticeCore::getHologramHandler();

            $leaderboards->reloadEloLeaderboards();
            $leaderboards->reloadStatsLeaderboards();
        }

        $this->updatePlayers($currentTick);
        $this->updateDuels($currentTick);

        $this->currentTick++;
    }

    /**
     * @param int $currentTick
     */
    private function updatePlayers(int $currentTick)
    {
        $update = $currentTick % 20 === 0;
        $players = $this->plugin->getServer()->getOnlinePlayers();

        foreach ($players as $player) {
            if (!$player->isOnline()) return;
            if (!PlayerHandler::hasSession($player)) return;

            $session = PlayerHandler::getSession($player);

            if ($update === true) {
                $session->updatePlayer();
            }

            $session->updateCps();
            $session->updatePearlcooldown();
        }
    }

    /**
     * @param int $currentTick
     */
    private function updateDuels(int $currentTick)
    {
        /*PracticeCore::getEventManager()->update();
        PracticeCore::getPartyManager()->getEventManager()->update();*/
        PracticeCore::getDuelManager()->update();
    }

    public function restartServer()
    {
        PracticeCore::getInstance()->getScheduler()->scheduleRepeatingTask(new ClosureTask(function(): void
        {
            if(self::$counter > 0){
                $msg = Utils::getPrefix() . TextFormat::YELLOW . "Practice is restarting in " . self::$counter . "...";
                $players = Server::getInstance()->getOnlinePlayers();

                foreach($players as $player)
                {
                    if($player->isOnline())
                    {
                        $player->sendMessage($msg);
                    }
                }
            }elseif(self::$counter === 0)
            {
                $players = Server::getInstance()->getOnlinePlayers();

                foreach($players as $player)
                {
                    $player->kick(TextFormat::YELLOW . "Network Restart");
                }

                //PracticeCore::getInstance()->getDatabase()->getDatabase()->waitAll();
                Server::getInstance()->shutdown();
            }elseif(self::$counter === -60)
            {
                Server::getInstance()->shutdown();
            }

            self::$counter--;

        }), Utils::secondsToTicks(1));
    }
}