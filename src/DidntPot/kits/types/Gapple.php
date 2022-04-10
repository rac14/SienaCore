<?php

namespace DidntPot\kits\types;

use DidntPot\kits\AbstractKit;
use pocketmine\item\Item;
use pocketmine\item\ItemIds;

class Gapple extends AbstractKit
{
    public function __construct(float $xkb = 0.4, float $ykb = 0.4, int $speed = 10)
    {
        parent::__construct('Gapple',
            [Item::get(ItemIds::DIAMOND_SWORD), Item::get(ItemIds::GOLDEN_APPLE, 0, 64), Item::get(ItemIds::STEAK, 0, 64)],
            [Item::get(ItemIds::DIAMOND_HELMET), Item::get(ItemIds::DIAMOND_CHESTPLATE), Item::get(ItemIds::DIAMOND_LEGGINGS), Item::get(ItemIds::DIAMOND_BOOTS)],
            [], $xkb, $ykb, $speed, 'textures/items/apple_golden.png');
    }
}