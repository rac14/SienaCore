<?php

namespace DidntPot\player\info;

use DidntPot\PracticeCore;
use DidntPot\utils\Utils;
use JetBrains\PhpStorm\Pure;
use pocketmine\Player;

class PlayerClicksInfo
{
    /**
     * @var PracticeCore
     */
    public PracticeCore $plugin;
    /**
     * @var array
     */
    private array $clicks = [];

    /**
     * @param PracticeCore $core
     */
    public function __construct(PracticeCore $core)
    {
        $this->plugin = $core;
    }

    /**
     * @param Player $player
     */
    public function addToArray(Player $player)
    {
        if (!$this->isInArray($player)) {
            $this->clicks[$player->getName()] = [];
        }
    }

    /**
     * @param $player
     * @return bool
     */
    #[Pure] public function isInArray($player): bool
    {
        $name = Utils::getPlayerName($player);

        return ($name !== null) and isset($this->clicks[$name]);
    }

    /**
     * @param Player $player
     */
    public function removeFromArray(Player $player)
    {
        if ($this->isInArray($player)) {
            $this->clicks[$player->getName()] = null;
            unset($this->clicks[$player->getName()]);
        }
    }

    /**
     * @param Player $player
     */
    public function addClick(Player $player)
    {
        array_unshift($this->clicks[$player->getName()], microtime(true));

        //TODO: if(Utils::isCpsCounterEnabled($player) == true)
        $player->sendTip("§r" . Utils::getThemeColor() . $this->getCps($player));
    }

    /**
     * @param Player $player
     * @param float $deltaTime
     * @param int $roundPrecision
     * @return float
     */
    public function getCps(Player $player, float $deltaTime = 1.0, int $roundPrecision = 1): float
    {
        if (!$this->isInArray($player) or empty($this->clicks[$player->getName()])) {
            return 0.0;
        }

        $mt = microtime(true);

        return round(count(array_filter($this->clicks[$player->getName()], static function (float $t) use ($deltaTime, $mt): bool {
                return ($mt - $t) <= $deltaTime;
            })) / $deltaTime, $roundPrecision);
    }
}