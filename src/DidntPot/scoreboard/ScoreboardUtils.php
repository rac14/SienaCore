<?php

namespace DidntPot\scoreboard;

use DidntPot\utils\Utils;
use JetBrains\PhpStorm\Pure;
use pocketmine\network\mcpe\protocol\RemoveObjectivePacket;
use pocketmine\network\mcpe\protocol\SetDisplayObjectivePacket;
use pocketmine\network\mcpe\protocol\SetScorePacket;
use pocketmine\network\mcpe\protocol\types\ScorePacketEntry;

class ScoreboardUtils
{
    /**
     * @var array
     */
    public static array $scoreboard = [];
    /**
     * @var array
     */
    public static array $spawnscoreboard = [];
    /**
     * @var array
     */
    public static array $duelqueuescoreboard = [];
    /**
     * @var array
     */
    public static array $partyscoreboard = [];
    /**
     * @var array
     */
    public static array $partyqueuescoreboard = [];
    /**
     * @var array
     */
    public static array $duelscoreboard = [];
    /**
     * @var array
     */
    public static array $spectatorscoreboard = [];
    /**
     * @var array
     */
    public static array $ffascoreboard = [];
    /**
     * @var array
     */
    public static array $botduelscoreboard = [];

    /**
     * @param $player
     * @return bool
     */
    #[Pure] public static function isPlayerSetScoreboard($player): bool
    {
        $name = Utils::getPlayerName($player);
        return ($name !== null) and isset(self::$scoreboard[$name]);
    }

    /**
     * @param $player
     * @return bool
     */
    #[Pure] public static function isPlayerSetSpawnScoreboard($player): bool
    {
        $name = Utils::getPlayerName($player);
        return ($name !== null) and isset(self::$spawnscoreboard[$name]);
    }

    /**
     * @param $player
     * @return bool
     */
    #[Pure] public static function isPlayerSetDuelQueueScoreboard($player): bool
    {
        $name = Utils::getPlayerName($player);
        return ($name !== null) and isset(self::$duelqueuescoreboard[$name]);
    }

    /**
     * @param $player
     * @return bool
     */
    #[Pure] public static function isPlayerSetPartyScoreboard($player): bool
    {
        $name = Utils::getPlayerName($player);
        return ($name !== null) and isset(self::$partyscoreboard[$name]);
    }

    /**
     * @param $player
     * @return bool
     */
    #[Pure] public static function isPlayerSetPartyQueueScoreboard($player): bool
    {
        $name = Utils::getPlayerName($player);
        return ($name !== null) and isset(self::$partyqueuescoreboard[$name]);
    }

    /**
     * @param $player
     * @return bool
     */
    #[Pure] public static function isPlayerSetDuelScoreboard($player): bool
    {
        $name = Utils::getPlayerName($player);
        return ($name !== null) and isset(self::$duelscoreboard[$name]);
    }

    /**
     * @param $player
     * @return bool
     */
    #[Pure] public static function isPlayerSetFFAScoreboard($player): bool
    {
        $name = Utils::getPlayerName($player);
        return ($name !== null) and isset(self::$ffascoreboard[$name]);
    }

    /**
     * @param $player
     * @return bool
     */
    #[Pure] public static function isPlayerSetBotDuelScoreboard($player): bool
    {
        $name = Utils::getPlayerName($player);
        return ($name !== null) and isset(self::$botduelscoreboard[$name]);
    }

    /**
     * @param $player
     * @return bool
     */
    #[Pure] public static function isPlayerSetSpectatorScoreboard($player): bool
    {
        $name = Utils::getPlayerName($player);
        return ($name !== null) and isset(self::$spectatorscoreboard[$name]);
    }

    /**
     * @param $player
     * @param string $title
     */
    public static function lineTitle($player, string $title)
    {
        $player = Utils::getPlayer($player);

        /*if(Utils::isScoreboardEnabled($player) === false)
        {
            return;
        }*/

        $packet = new SetDisplayObjectivePacket();

        $packet->displaySlot = "sidebar";
        $packet->objectiveName = "objective";
        $packet->displayName = $title;
        $packet->criteriaName = "dummy";
        $packet->sortOrder = 0;

        $player->sendDataPacket($packet);
    }

    /**
     * @param $player
     */
    public static function removeScoreboard($player)
    {
        $player = Utils::getPlayer($player);

        $packet = new RemoveObjectivePacket();

        $packet->objectiveName = "objective";

        $player->sendDataPacket($packet);

        unset(self::$scoreboard[$player->getName()]);
        unset(self::$spawnscoreboard[$player->getName()]);
        unset(self::$duelscoreboard[$player->getName()]);
        unset(self::$spectatorscoreboard[$player->getName()]);
        unset(self::$ffascoreboard[$player->getName()]);
        unset(self::$botduelscoreboard[$player->getName()]);
        unset(self::$partyscoreboard[$player->getName()]);
        unset(self::$partyqueuescoreboard[$player->getName()]);
        unset(self::$duelqueuescoreboard[$player->getName()]);
    }

    /**
     * @param $player
     * @param int $line
     * @param string $content
     */
    public static function lineCreate($player, int $line, string $content)
    {
        $player = Utils::getPlayer($player);

        /*if(Utils::isScoreboardEnabled($player) === false)
        {
            return;
        }*/

        $packetline = new ScorePacketEntry();

        $packetline->objectiveName = "objective";
        $packetline->type = ScorePacketEntry::TYPE_FAKE_PLAYER;
        $packetline->customName = $content;
        $packetline->score = $line;
        $packetline->scoreboardId = $line;

        $packet = new SetScorePacket();

        $packet->type = SetScorePacket::TYPE_CHANGE;
        $packet->entries[] = $packetline;

        $player->sendDataPacket($packet);
    }

    /**
     * @param $player
     * @param int $line
     */
    public static function lineRemove($player, int $line)
    {
        $player = Utils::getPlayer($player);

        /*if(Utils::isScoreboardEnabled($player) === false)
        {
            return;
        }*/

        $entry = new ScorePacketEntry();

        $entry->objectiveName = "objective";
        $entry->score = $line;
        $entry->scoreboardId = $line;

        $packet = new SetScorePacket();
        $packet->type = SetScorePacket::TYPE_REMOVE;
        $packet->entries[] = $entry;

        $player->sendDataPacket($packet);
    }
}