<?php

namespace DidntPot\game\inventories\menus;

use DidntPot\game\inventories\menus\inventory\DoubleChestInv;
use pocketmine\inventory\transaction\action\SlotChangeAction;
use pocketmine\Player;
use pocketmine\utils\TextFormat;

class PostMatchInv extends BaseMenu
{
    /**
     * PostMatchInv constructor.
     * @param DuelInfo $info
     */
    public function __construct(DuelInfo $info)
    {
        parent::__construct(new DoubleChestInv($this));
        $playerName = $info->getPlayerName();

        $name = $lang->formWindow(Language::DUEL_INVENTORY_TITLE, [
            "name" => TextFormat::BLUE . $playerName
        ]);

        $this->setName($name);
        $this->setEdit(false);

        $allItems = [];

        $count = 0;

        $row = 0;

        $maxRows = 3;

        $items = $info->getItems();

        foreach ($items as $item) {
            $currentRow = $maxRows - $row;
            $v = ($currentRow + 1) * 9;

            if ($row === 0) {
                $v = $v - 9;
                $val = intval(($count % 9) + $v);
            } else $val = $count - 9;

            if ($val != -1) $allItems[$val] = $item;

            $count++;

            if ($count % 9 == 0 and $count != 0) $row++;
        }

        $row = $maxRows + 1;
        $lastRowIndex = ($row + 1) * 9;
        $secLastRowIndex = $row * 9;

        $armorItems = $info->getArmor();

        foreach ($armorItems as $armor) {
            $allItems[$secLastRowIndex] = $armor;
            $secLastRowIndex++;
        }

        $statsItems = $info->getStatsItems();

        foreach ($statsItems as $statsItem) {
            $allItems[$lastRowIndex] = $statsItem;
            $lastRowIndex++;
        }

        $keys = array_keys($allItems);

        foreach ($keys as $index) {
            $index = intval($index);
            $item = $allItems[$index];
            $this->getInventory()->setItem($index, $item);
        }
    }

    /**
     * @param Player $p
     * @param SlotChangeAction $action
     */
    public function onItemMoved(Player $p, SlotChangeAction $action): void
    {
    }

    /**
     * @param Player $player
     */
    public function onInventoryClosed(Player $player): void
    {
        MineceitCore::getPlayerHandler()->setOpenInventoryID($player);
    }

    /**
     * @param Player $player
     */
    public function sendTo(Player $player): void
    {
        if ($player->isOnline()) $this->send($player);
    }
}