<?php

namespace DidntPot\game\inventories\menus\inventory;

use DidntPot\game\inventories\menus\BaseMenu;
use DidntPot\game\inventories\menus\data\PracticeHolderData;
use pocketmine\block\Block;
use pocketmine\block\BlockIds;
use pocketmine\nbt\tag\CompoundTag;
use pocketmine\network\mcpe\protocol\types\WindowTypes;
use pocketmine\Player;
use pocketmine\tile\Tile;

class SingleChestInv extends PracticeBaseInv
{
    const CHEST_SIZE = 27;

    public function __construct(BaseMenu $menu, array $items = [])
    {
        parent::__construct($menu, $items, self::CHEST_SIZE, null);
    }

    public function getName(): string
    {
        return 'Practice Chest';
    }

    /**
     * Returns the Minecraft PE inventory type used to show the inventory window to clients.
     * @return int
     */
    public function getNetworkType(): int
    {
        return WindowTypes::CONTAINER;
    }

    public function getTEId(): string
    {
        return Tile::CHEST;
    }

    function sendPrivateInv(Player $player, PracticeHolderData $data): void
    {
        $block = $this->getBlock()->setComponents($data->getPos()->x, $data->getPos()->y, $data->getPos()->z);
        $player->getLevel()->sendBlocks([$player], [$block]);
        $tag = new CompoundTag();

        if (!is_null($data->getCustomName())) {
            $tag->setString('CustomName', $data->getCustomName());
        }

        $this->sendTileEntity($player, $block, $tag);

        if ($player instanceof Player) $this->onInventorySend($player);
    }

    private function getBlock(): Block
    {
        return Block::get(BlockIds::CHEST);
    }

    function sendPublicInv(Player $player, PracticeHolderData $data): void
    {
        $player->getLevel()->sendBlocks([$player], [$data->getPos()]);
    }
}