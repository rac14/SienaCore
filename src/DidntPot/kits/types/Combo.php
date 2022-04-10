<?php

namespace DidntPot\kits\types;

use DidntPot\kits\AbstractKit;
use DidntPot\utils\Utils;
use pocketmine\entity\Effect;
use pocketmine\entity\EffectInstance;
use pocketmine\item\enchantment\Enchantment;
use pocketmine\item\enchantment\EnchantmentInstance;
use pocketmine\item\Item;
use pocketmine\item\ItemIds;

class Combo extends AbstractKit
{
    public function __construct(float $xkb = 0.4, float $ykb = 0.4, int $speed = 10)
    {
        parent::__construct('Combo',
            [],
            [],
            [], $xkb, $ykb, $speed, 'textures/items/fish_pufferfish_raw.png');

        $sword = Utils::createItem(276, 0, 1,
            [new EnchantmentInstance(Enchantment::getEnchantment(9), 5), new EnchantmentInstance(Enchantment::getEnchantment(17), 3)]);

        $e = new EnchantmentInstance(Enchantment::getEnchantment(0), 3);

        $helmet = Utils::createItem(310, 0, 1, [$e]);

        $chest = Utils::createItem(311, 0, 1, [$e]);

        $legs = Utils::createItem(312, 0, 1,
            [$e, new EnchantmentInstance(Enchantment::getEnchantment(2), 4)]);

        $boots = Utils::createItem(313, 0, 1, [$e]);

        $this->items = [
            $sword,
            Item::get(ItemIds::APPLEENCHANTED, 0, 64),
            27 => $helmet,
            28 => $chest,
            29 => $legs,
            30 => $boots
        ];

        $this->effects = [
            new EffectInstance(Effect::getEffect(Effect::SPEED), Utils::minutesToTicks(10000), 0, false)
        ];

        $this->armor = [$helmet, $chest, $legs, $boots];
    }
}