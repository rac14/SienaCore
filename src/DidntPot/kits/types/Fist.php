<?php

namespace DidntPot\kits\types;

use DidntPot\kits\AbstractKit;
use pocketmine\item\Item;
use pocketmine\item\ItemIds;

class Fist extends AbstractKit
{
    public function __construct(float $xkb = 0.4, float $ykb = 0.4, int $speed = 10)
    {
        parent::__construct('Fist', [Item::get(ItemIds::STEAK, 0, 64)], [], [], $xkb, $ykb, $speed, 'textures/items/beef_cooked.png');
    }
}