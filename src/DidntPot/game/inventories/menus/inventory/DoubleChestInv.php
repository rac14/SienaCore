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

class DoubleChestInv extends PracticeBaseInv
{
    const CHEST_SIZE = 54;

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
        $block = Block::get(BlockIds::CHEST)->setComponents($data->getPos()->x, $data->getPos()->y, $data->getPos()->z);
        $block2 = Block::get(BlockIds::CHEST)->setComponents($data->getPos()->x + 1, $data->getPos()->y, $data->getPos()->z);

        $player->getLevel()->sendBlocks([$player], [$block, $block2]);

        $tag = new CompoundTag();

        if (!is_null($data->getCustomName())) {
            $tag->setString('CustomName', $data->getCustomName());
        }

        $tag->setInt('pairz', $block->z);

        $tag->setInt('pairx', $block->x + 1);
        $this->sendTileEntity($player, $block, $tag);

        $tag->setInt('pairx', $block->x);
        $this->sendTileEntity($player, $block2, $tag);

        if ($player instanceof Player) $this->onInventorySend($player);
    }

    function sendPublicInv(Player $player, PracticeHolderData $data): void
    {
        $player->getLevel()->sendBlocks([$player], [$data->getPos(), $data->getPos()->add(1, 0, 0)]);
    }

    /**
     * @param Player $player
     * @return int
     */
    public function getSendDelay(Player $player): int
    {
        $ping = $player->getPing();

        return $ping < 280 ? 5 : 2;
    }
}