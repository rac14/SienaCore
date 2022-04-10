<?php

namespace DidntPot\game\entities;

use DidntPot\PracticeCore;
use pocketmine\block\BlockIds;
use pocketmine\entity\object\ItemEntity;
use pocketmine\event\inventory\InventoryPickupItemEvent;
use pocketmine\item\ItemIds;
use pocketmine\network\mcpe\protocol\TakeItemActorPacket;
use pocketmine\Player;

class PracticeItemEntity extends ItemEntity
{
    public function onCollideWithPlayer(Player $player): void
    {
        if ($this->getPickupDelay() !== 0)
            return;

        if ($player instanceof Player and $player->isSpectator())
            return;

        $item = $this->getItem();
        $playerInventory = $player->getInventory();

        if ($player->isSurvival() and !$playerInventory->canAddItem($item)) {
            return;
        }

        $ev = new InventoryPickupItemEvent($playerInventory, $this);
        $ev->call();

        if ($ev->isCancelled()) {
            return;
        }

        switch ($item->getId()) {
            case BlockIds::WOOD:
                $player->awardAchievement("mineWood");
                break;
            case ItemIds::DIAMOND:
                $player->awardAchievement("diamond");
                break;
        }

        $pk = new TakeItemActorPacket();
        $pk->eid = $player->getId();
        $pk->target = $this->getId();
        $this->server->broadcastPacket($this->getViewers(), $pk);

        $playerInventory->addItem(clone $item);

        if ($player instanceof Player and PracticeCore::getDuelManager()->getDuel($player) !== null) {
            $duelHandler = PracticeCore::getDuelManager()->getDuel($player);
            $duelHandler->setPickupItem($player, $this->getItem());
        }

        $this->flagForDespawn();
    }
}