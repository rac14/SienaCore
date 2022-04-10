<?php

namespace DidntPot\scoreboard;

use DidntPot\parties\PracticeParty;
use DidntPot\player\sessions\PlayerHandler;
use DidntPot\PracticeCore;
use DidntPot\utils\Utils;

class ScoreboardManager
{
    /** @var PracticeCore|null */
    public ?PracticeCore $plugin;

    /**
     * @param PracticeCore $core
     */
    public function __construct(PracticeCore $core)
    {
        $this->plugin = $core;
    }

    /**
     * @param $player
     * @param false $forQueue
     * @param array $duelInfo
     * @param false $forParty
     * @param PracticeParty|null $partyinfo
     */
    public function sendSpawnScoreboard($player, bool $forQueue = false, array $duelInfo = [], bool $forParty = false, PracticeParty $partyinfo = null): void
    {
        $player = Utils::getPlayer($player);

        if(!PlayerHandler::getSession($player)->isScoreboard()) return;

        $online = count($this->plugin->getServer()->getOnlinePlayers());

        $playing = count($this->plugin->getDuelManager()->getDuels(false));
        $queued = $this->plugin->getDuelManager()->getEveryoneInQueues();

        if (ScoreboardUtils::isPlayerSetScoreboard($player)) {
            ScoreboardUtils::removeScoreboard($player);
        }

        ScoreboardUtils::lineTitle($player, Utils::formatTitle());

        ScoreboardUtils::lineCreate($player, 0, "§r§r§r§r§r§r§r");

        ScoreboardUtils::lineCreate($player, 1, " §fOnline: " . Utils::getThemeColor() . $online);
        ScoreboardUtils::lineCreate($player, 2, " §fPlaying: " . Utils::getThemeColor() . $playing);

        if ($forQueue) {
            ScoreboardUtils::lineCreate($player, 3, "§r  ");
            ScoreboardUtils::lineCreate($player, 4, " §fQueue: ");
            ScoreboardUtils::lineCreate($player, 5, "  " . Utils::getThemeColor() . $duelInfo["isRanked"] . " " . $duelInfo["queue"]);
            ScoreboardUtils::$duelqueuescoreboard[$player->getName()] = $player->getName();
        }

        if ($forParty) {
            ScoreboardUtils::lineCreate($player, 3, "§r  ");
            ScoreboardUtils::lineCreate($player, 4, " §fParty: ");
            ScoreboardUtils::lineCreate($player, 5, "  §fLeader: " . Utils::getThemeColor() . $partyinfo->getOwner()->getName());
            ScoreboardUtils::lineCreate($player, 6, "  §fMembers: " . Utils::getThemeColor() . $partyinfo->getPlayers(true) . "§f/" . Utils::getThemeColor() . $partyinfo->getMaxPlayers());
            ScoreboardUtils::$partyscoreboard[$player->getName()] = $player->getName();
        }

        ScoreboardUtils::lineCreate($player, 7, "         ");
        ScoreboardUtils::lineCreate($player, 8,  Utils::getThemeColor() . " " . Utils::getIP());

        ScoreboardUtils::lineCreate($player, 9, "");

        ScoreboardUtils::$scoreboard[$player->getName()] = $player->getName();
        ScoreboardUtils::$spawnscoreboard[$player->getName()] = $player->getName();
    }

    /**
     * @param $player
     * @param bool $inCombat
     * @param int $timer
     */
    public function sendFFAScoreboard($player, bool $inCombat = false, int $timer = 0): void
    {
        $player = Utils::getPlayer($player);
        $session = PlayerHandler::getSession($player);

        if(!$session->isScoreboard()) return;

        if (ScoreboardUtils::isPlayerSetScoreboard($player)) {
            ScoreboardUtils::removeScoreboard($player);
        }

        ScoreboardUtils::lineTitle($player, Utils::formatTitle());

        ScoreboardUtils::lineCreate($player, 0, ("§r§r§r§r§r§r§r"));

        ScoreboardUtils::lineCreate($player, 1, " §fKills: " . Utils::getThemeColor() . $session->getKills());
        ScoreboardUtils::lineCreate($player, 2, " §fDeaths: " . Utils::getThemeColor() . $session->getDeaths());
        ScoreboardUtils::lineCreate($player, 3, " §fStreak: " . Utils::getThemeColor() . $session->getKillstreak());

        if ($inCombat) {
            ScoreboardUtils::lineCreate($player, 4, "§r  ");
            ScoreboardUtils::lineCreate($player, 5, " §fCombat: " . Utils::getThemeColor() . $timer);
        }

        ScoreboardUtils::lineCreate($player, 6, "         ");
        ScoreboardUtils::lineCreate($player, 7, Utils::getThemeColor() . " " . Utils::getIP());

        ScoreboardUtils::lineCreate($player, 8, (""));

        ScoreboardUtils::$scoreboard[$player->getName()] = $player->getName();
        ScoreboardUtils::$ffascoreboard[$player->getName()] = $player->getName();
    }

    /**
     * @param $player
     * @param $opponent
     */
    public function sendDuelScoreboard($player, $opponent): void
    {
        $player = Utils::getPlayer($player);
        $opponent = Utils::getPlayer($opponent);

        $session = PlayerHandler::getSession($player);
        if(!$session->isScoreboard()) return;

        if (ScoreboardUtils::isPlayerSetScoreboard($player)) {
            ScoreboardUtils::removeScoreboard($player);
        }

        ScoreboardUtils::lineTitle($player, Utils::formatTitle());

        ScoreboardUtils::lineCreate($player, 0, ("§r§r§r§r§r§r§r"));

        ScoreboardUtils::lineCreate($player, 1, " §fYour Ping: " . Utils::getThemeColor() . $player->getPing());
        ScoreboardUtils::lineCreate($player, 2, " §fTheir Ping: " . Utils::getThemeColor() . $opponent->getPing());
        ScoreboardUtils::lineCreate($player, 3, "         ");

        ScoreboardUtils::lineCreate($player, 4, Utils::getThemeColor() . " " . Utils::getIP());

        ScoreboardUtils::lineCreate($player, 5, (""));

        ScoreboardUtils::$scoreboard[$player->getName()] = $player->getName();
        ScoreboardUtils::$duelscoreboard[$player->getName()] = $player->getName();
    }

    /*public function sendDuelScoreboard($player, string $type, string $queue, string $opponent):void{
        $player=Utils::getPlayer($player);
        if(Utils::isScoreboardEnabled($player)==false){
            return;
        }
        if($this->isPlayerSetScoreboard($player)){
            $this->removeScoreboard($player);
        }
        $pping = $player->getPing();
        $opponentqueue = $this->plugin->getDuelManager()->getQueuedPlayer($opponent);

        if($opponentqueue->getPlayer() === null) return;
        if(!$opponentqueue->getPlayer()->isOnline()) return;

        $o = $opponentqueue->getPlayer();
        $oping = $o->getPing();
        $this->lineTitle($player, "  "."§r§l§cSiena§r §8| §fPractice ");

        $this->lineCreate($player, 0, ("§r§r§r§r§r§r§r"));

        $this->lineCreate($player, 1, "§c Duel");
        $this->lineCreate($player, 2, " §f Rival: §c" . $opponent);
        $this->lineCreate($player, 3, " §f Duration: §c00:00");
        $this->lineCreate($player, 4, " §f Ping: §a" . $pping . " §7| §c" . $oping);
        $this->lineCreate($player, 5, "      ");
        $this->lineCreate($player, 6, "§7§o ".$this->plugin->getIp());

        $this->lineCreate($player, 7, (""));

        $this->scoreboard[$player->getName()]=$player->getName();
        $this->duel[$player->getName()]=$player->getName();
    }
    public function sendBotDuelScoreboard($player, string $opponent):void{
        $player=Utils::getPlayer($player);
        if(Utils::isScoreboardEnabled($player)==false){
            return;
        }
        if($this->isPlayerSetScoreboard($player)){
            $this->removeScoreboard($player);
        }
        $this->lineTitle($player, "  "."§r§l§cSiena§r §8| §fPractice ");

        $this->lineCreate($player, 0, ("§r§r§r§r§r§r§r"));

        $this->lineCreate($player, 1, "§c Bot Duel");
        $this->lineCreate($player, 2, " §f Rival: §c" . $opponent);
        $this->lineCreate($player, 3, " §f Duration: §c00:00");
        $this->lineCreate($player, 4, "         ");
        $this->lineCreate($player, 5, "§7§o ".$this->plugin->getIp());

        $this->lineCreate($player, 6, (""));

        $this->scoreboard[$player->getName()]=$player->getName();
        $this->botduel[$player->getName()]=$player->getName();
    }
    public function sendDuelSpectateScoreboard($player, string $type, string $queue, string $duelplayer, string $duelopponent):void{
        $player=Utils::getPlayer($player);
        if(Utils::isScoreboardEnabled($player)==false){
            return;
        }
        if($this->isPlayerSetScoreboard($player)){
            $this->removeScoreboard($player);
        }
        $this->lineTitle($player, "  "."§r§l§cSiena§r §8| §fPractice ");

        $this->lineCreate($player, 0, ("§r§r§r§r§r§r§r"));

        $this->lineCreate($player, 1, "§l§c Spectate");
        $this->lineCreate($player, 2, " §f Type: §c".$type." §8| §c".$queue);
        $this->lineCreate($player, 3, "         ");
        $this->lineCreate($player, 4, " §f Player: §a" . $duelplayer);
        $this->lineCreate($player, 5, " §f Opponent: §c" . $duelopponent);
        $this->lineCreate($player, 6, "             ");
        $this->lineCreate($player, 7, "§7§o ".$this->plugin->getIp());

        $this->lineCreate($player, 8, (""));
        $this->scoreboard[$player->getName()]=$player->getName();
        $this->spectator[$player->getName()]=$player->getName();
    }*/
}