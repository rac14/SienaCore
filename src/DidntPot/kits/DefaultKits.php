<?php

namespace DidntPot\kits;

use DidntPot\parties\PracticeParty;
use DidntPot\player\PlayerExtensions;
use DidntPot\PracticeCore;
use DidntPot\utils\ItemNameUtils;
use pocketmine\item\Durable;
use pocketmine\item\Item;
use pocketmine\item\ItemIds;
use pocketmine\nbt\tag\ListTag;
use pocketmine\network\mcpe\protocol\types\GameMode;
use pocketmine\Player;

class DefaultKits
{
    public static function sendSpawnKit(?Player $player)
    {
        if ($player instanceof Player and $player->isOnline()) {
            PlayerExtensions::clearAll($player);

            $unranked = Item::get(ItemIds::IRON_SWORD, 0, 1);
            $unranked->setCustomName(ItemNameUtils::SPAWN_ITEM_DUELS_UNRANKED);
            $unranked->setNamedTagEntry(new ListTag("ench"));

            $ranked = Item::get(ItemIds::DIAMOND_SWORD, 0, 1);
            $ranked->setCustomName(ItemNameUtils::SPAWN_ITEM_DUELS_RANKED);
            $ranked->setNamedTagEntry(new ListTag("ench"));

            $spectate = Item::get(ItemIds::EMERALD, 0, 1);
            $spectate->setCustomName(ItemNameUtils::SPAWN_ITEM_DUELS_SPECTATE);
            $spectate->setNamedTagEntry(new ListTag("ench"));

            $botduels = Item::get(ItemIds::STONE_SWORD, 0, 1);
            $botduels->setCustomName(ItemNameUtils::SPAWN_ITEM_DUELS_BOT);
            $botduels->setNamedTagEntry(new ListTag("ench"));

            $ffa = Item::get(ItemIds::GOLDEN_SWORD, 0, 1);
            $ffa->setCustomName(ItemNameUtils::SPAWN_ITEM_FFA);
            $ffa->setNamedTagEntry(new ListTag("ench"));

            $party = Item::get(ItemIds::NAME_TAG, 0, 1);
            $party->setCustomName(ItemNameUtils::SPAWN_ITEM_PARTY);
            $party->setNamedTagEntry(new ListTag("ench"));

            $events = Item::get(ItemIds::ENDER_EYE, 0, 1);
            $events->setCustomName(ItemNameUtils::SPAWN_ITEM_EVENTS);
            $events->setNamedTagEntry(new ListTag("ench"));

            $settings = Item::get(ItemIds::SKULL, 0, 1);
            $settings->setCustomName(ItemNameUtils::SPAWN_ITEM_SETTINGS);
            $settings->setNamedTagEntry(new ListTag("ench"));

            //$player->getInventory()->setItem(0, $botduels);

            $player->getInventory()->setItem(0, $ffa);
            $player->getInventory()->setItem(1, $unranked);
            $player->getInventory()->setItem(2, $ranked);

            $player->getInventory()->setItem(4, $party);

            $player->getInventory()->setItem(6, $events);
            $player->getInventory()->setItem(7, $spectate);
            $player->getInventory()->setItem(8, $settings);

            foreach ($player->getInventory()->getContents() as $items) {
                if ($items instanceof Durable) $items->setUnbreakable(true);
            }

            $player->getInventory()->setHeldItemIndex(0);
        }
    }

    public static function sendSpecKit(?Player $player)
    {
        if ($player instanceof Player and $player !== null and $player->isOnline()) {
            PlayerExtensions::clearAll($player);

            $player->setGamemode(GameMode::SURVIVAL_VIEWER);
            PlayerExtensions::enableFlying($player, true);

            $leave = Item::get(331, 0, 1);
            $leave->setCustomName(ItemNameUtils::SPECTATE_ITEM_LEAVE);
            $leave->setNamedTagEntry(new ListTag("ench"));

            $player->getInventory()->setItem(4, $leave);

            foreach ($player->getInventory()->getContents() as $items) {
                if ($items instanceof Durable) $items->setUnbreakable(true);
            }

            $player->getInventory()->setHeldItemIndex(4);
        }
    }

    public static function sendQueueKit(?Player $player)
    {
        if ($player instanceof Player and $player !== null and $player->isOnline()) {
            PlayerExtensions::clearAll($player);
            PlayerExtensions::enableFlying($player, false);

            $player->setGamemode(GameMode::SURVIVAL);

            $leave = Item::get(331, 0, 1);
            $leave->setCustomName(ItemNameUtils::SPAWN_ITEM_LEAVE_QUEUE);
            $leave->setNamedTagEntry(new ListTag("ench"));

            $player->getInventory()->setItem(4, $leave);

            foreach ($player->getInventory()->getContents() as $items) {
                if ($items instanceof Durable) $items->setUnbreakable(true);
            }

            $player->getInventory()->setHeldItemIndex(4);
        }
    }

    public static function sendPartyKit(Player $player)
    {
        if ($player instanceof Player and $player !== null and $player->isOnline()) {
            PlayerExtensions::clearAll($player);
            PlayerExtensions::enableFlying($player, false);

            $player->setGamemode(GameMode::SURVIVAL);

            $partyDuel = Item::get(ItemIds::GOLD_SWORD, 0, 1);
            $partyDuel->setCustomName(ItemNameUtils::SPAWN_ITEM_PARTY_VS_PARTY_DUEL);
            $partyDuel->setNamedTagEntry(new ListTag("ench"));

            $settings = Item::get(ItemIds::NETHER_STAR, 0, 1);
            $settings->setCustomName(ItemNameUtils::SPAWN_ITEM_PARTY_SETTINGS);
            $settings->setNamedTagEntry(new ListTag("ench"));


            $leave = Item::get(ItemIds::NETHER_STAR, 0, 1);
            $leave->setCustomName(ItemNameUtils::SPAWN_ITEM_LEAVE_PARTY);
            $leave->setNamedTagEntry(new ListTag("ench"));

            $player->getInventory()->setItem(0, $partyDuel);

            if (PracticeCore::getPartyManager()
                    ->getPartyFromPlayer($player)
                    ->getOwner()->getName() === $player->getName()
            ) $player->getInventory()->setItem(7, $settings);

            $player->getInventory()->setItem(8, $leave);

            foreach ($player->getInventory()->getContents() as $items) {
                if ($items instanceof Durable) $items->setUnbreakable(true);
            }

            $player->getInventory()->setHeldItemIndex(0);
        }
    }

    public static function sendPartyQueueKit(PracticeParty $party)
    {
        foreach ($party->getPlayers() as $player) {
            if ($player instanceof Player and $player !== null and $player->isOnline()) {
                PlayerExtensions::clearAll($player);
                PlayerExtensions::enableFlying($player, false);

                $player->setGamemode(GameMode::SURVIVAL);

                $leave = Item::get(331, 0, 1);
                $leave->setCustomName(ItemNameUtils::SPAWN_ITEM_LEAVE_PARTY_QUEUE);
                $leave->setNamedTagEntry(new ListTag("ench"));

                $player->getInventory()->setItem(4, $leave);

                foreach ($player->getInventory()->getContents() as $items) {
                    if ($items instanceof Durable) $items->setUnbreakable(true);
                }

                $player->getInventory()->setHeldItemIndex(4);
            }
        }
    }
}