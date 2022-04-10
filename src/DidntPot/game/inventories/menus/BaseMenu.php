<?php

namespace DidntPot\game\inventories\menus;

use DidntPot\game\inventories\menus\inventory\PracticeBaseInv;
use JetBrains\PhpStorm\Pure;
use pocketmine\inventory\transaction\action\SlotChangeAction;
use pocketmine\Player;

abstract class BaseMenu
{
    private $edit;

    private $name;

    private $inv;

    #[Pure] public function __construct(PracticeBaseInv $inv)
    {
        $this->inv = $inv;
        $this->edit = true;
        $this->name = $inv->getName();
    }

    public function setEdit(bool $edit): BaseMenu
    {
        $this->edit = $edit;
        return $this;
    }

    public function canEdit(): bool
    {
        return $this->edit;
    }

    public function send(Player $player, ?string $customName = null): bool
    {
        return $this->getInventory()->send($player, ($customName !== null ? $customName : $this->getName()));
    }

    public function getInventory(): PracticeBaseInv
    {
        return $this->inv;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function setName(string $name): BaseMenu
    {
        $this->name = $name;
        return $this;
    }

    abstract public function onItemMoved(Player $p, SlotChangeAction $action): void;

    abstract public function onInventoryClosed(Player $player): void;

    abstract public function sendTo(Player $player): void;
}