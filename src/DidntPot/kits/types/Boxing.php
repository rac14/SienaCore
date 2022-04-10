<?php

namespace DidntPot\kits\types;

use DidntPot\kits\AbstractKit;
use DidntPot\utils\Utils;
use pocketmine\entity\Effect;
use pocketmine\entity\EffectInstance;
use pocketmine\item\enchantment\Enchantment;
use pocketmine\item\enchantment\EnchantmentInstance;
use pocketmine\item\ItemIds;

class Boxing extends AbstractKit
{
    public function __construct(float $xkb = 0.4, float $ykb = 0.4, int $speed = 10)
    {
        parent::__construct('Boxing', [], [], [], $xkb, $ykb, $speed, 'textures/items/diamond_chestplate.png');

        $items = [
            Utils::createItem(ItemIds::DIAMOND_SWORD, 0, 1,
                [new EnchantmentInstance(Enchantment::getEnchantment(17), 10)])
        ];

        $this->items = $items;
        $this->effects = [
            new EffectInstance(Effect::getEffect(Effect::SPEED), Utils::minutesToTicks(10000), 0, false),
            new EffectInstance(Effect::getEffect(Effect::DAMAGE_RESISTANCE), Utils::minutesToTicks(10000), 100, false),
        ];
    }
}