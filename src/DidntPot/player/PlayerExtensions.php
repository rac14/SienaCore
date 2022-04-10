<?php

namespace DidntPot\player;

use pocketmine\entity\Attribute;
use pocketmine\network\mcpe\protocol\types\GameMode;
use pocketmine\Player;

class PlayerExtensions
{
    public static function clearAll(Player $player): void
    {
        $player->extinguish();
        $player->setGamemode(GameMode::SURVIVAL);
        $player->setImmobile(false);
        $player->removeAllEffects();

        self::revivePlayer($player);
        self::clearInventory($player);
        self::enableFlying($player, false);
        self::setXpAndProgress($player, 0, 0.0);
    }

    public static function revivePlayer(Player $player): void
    {
        $player->setHealth(20);
        $player->setFood($player->getMaxFood());
        $player->setSaturation($player->getAttributeMap()->getAttribute(Attribute::SATURATION)->getMaxValue());
    }

    public static function clearInventory(Player $player): void
    {
        $player->getInventory()->clearAll();
        $player->getCursorInventory()->clearAll();
        $player->getArmorInventory()->clearAll();
        $player->getCursorInventory()->clearAll();
    }

    public static function enableFlying(Player $player, bool $flying): void
    {
        $player->setAllowFlight($flying);
        $player->setFlying($flying);
    }

    public static function setXpAndProgress(Player $player, int $level, float $progress): void
    {
        $player->setXpLevel($level);
        $player->setXpProgress($progress);
    }
}