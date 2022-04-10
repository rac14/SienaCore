<?php

namespace DidntPot\kits\types;

use DidntPot\kits\AbstractKit;
use DidntPot\utils\Utils;
use pocketmine\entity\Effect;
use pocketmine\entity\EffectInstance;
use pocketmine\item\ItemIds;

class Sumo extends AbstractKit
{
    public function __construct(float $xkb = 0.4, float $ykb = 0.4, int $speed = 10)
    {
        parent::__construct('Sumo',
            [Utils::createItem(ItemIds::STEAK, 0, 64)],
            [],
            [new EffectInstance(Effect::getEffect(Effect::RESISTANCE), Utils::hoursToTicks(10000), 100, false)],
            $xkb, $ykb, $speed, 'textures/items/slimeball.png');

        $this->duelOnly = true;
        $this->worldType = "type_sumo";
    }
}