<?php

namespace DidntPot\player\item;

use pocketmine\item\Item;

class PracticeItem
{
    private string $localizedName;

    private int $slot;

    private Item $item;

    private string $itemName;

    private bool $onlyExecuteInLobby;

    private string $texture;

    public function __construct(string $name, int $slot, Item $item, string $texture, bool $exec = true)
    {
        $this->localizedName = $name;
        $this->slot = $slot;
        $this->item = $item;
        $this->itemName = $item->getName();
        $this->onlyExecuteInLobby = $exec;
        $this->texture = $texture;
    }

    public function getTexture(): string
    {
        return $this->texture;
    }

    public function canOnlyUseInLobby(): bool
    {
        return $this->onlyExecuteInLobby;
    }

    public function getItem(): Item
    {
        return $this->item;
    }

    public function setItem(Item $item): self
    {
        $this->item = $item;
        $this->itemName = $item->getName();
        return $this;
    }

    public function getName(): string
    {
        return $this->itemName;
    }

    public function getLocalizedName(): string
    {
        return $this->localizedName;
    }

    public function getSlot(): int
    {
        return $this->slot;
    }
}