<?php

namespace DidntPot\game\inventories;

use DidntPot\game\inventories\menus\inventory\PracticeBaseInv;
use pocketmine\Player;
use pocketmine\scheduler\Task;

class InventoryTask extends Task
{
    /* @var Player */
    private $player;

    /* @var PracticeBaseInv */
    private $inventory;

    /**
     * InventoryTask constructor.
     * @param Player $player
     * @param PracticeBaseInv $inv
     */
    public function __construct(Player $player, PracticeBaseInv $inv)
    {
        $this->player = $player;
        $this->inventory = $inv;
    }

    /**
     * Actions to execute when run
     *
     * @param int $currentTick
     *
     * @return void
     */
    public function onRun(int $currentTick)
    {
        if ($this->player->isOnline()) {
            $this->inventory->onSendInvSuccess($this->player);
        } else {
            $this->inventory->onSendInvFail($this->player);
        }
    }
}