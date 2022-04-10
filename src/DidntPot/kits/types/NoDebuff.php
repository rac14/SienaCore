<?php

namespace DidntPot\kits\types;

use DidntPot\kits\AbstractKit;
use DidntPot\utils\Utils;
use pocketmine\entity\Effect;
use pocketmine\entity\EffectInstance;
use pocketmine\item\enchantment\Enchantment;
use pocketmine\item\enchantment\EnchantmentInstance;

class NoDebuff extends AbstractKit
{
    public function __construct(float $xkb = 0.4, float $ykb = 0.4, int $speed = 10)
    {
        parent::__construct('NoDebuff', [], [], [], $xkb, $ykb, $speed, 'textures/items/potion_bottle_splash_heal.png');

        $items = [
            Utils::createItem(276, 0, 1,
                [new EnchantmentInstance(Enchantment::getEnchantment(9), 2),
                    new EnchantmentInstance(Enchantment::getEnchantment(17), 3)]),
            Utils::createItem(368, 0, 16)
        ];

        // Disabled (speed pots in hotbar).
        for ($i = 0; $i < 7; $i++) {
            $id = 438;
            $meta = 22;
            $items[] = Utils::createItem($id, $meta, 1);
        }

        // Disabled (steak in hotbar).
        //$items[] = Item::get(ItemIds::STEAK, 0, 64);

        // Disabled (speed pots in inventory).
        $arr = [];

        for($i = 0; $i < 27; $i++) {
            $id = (isset($arr[$i])) ? 373 : 438;
            $meta = (isset($arr[$i])) ? 14 : 22;
            $items[] = Utils::createItem($id, $meta, 1);
        }

        $this->items = $items;

        $e1 = new EnchantmentInstance(Enchantment::getEnchantment(0), 2);

        $e2 = new EnchantmentInstance(Enchantment::getEnchantment(17), 3);

        $helmet = Utils::createItem(310, 0, 1, [$e1, $e2]);

        $chest = Utils::createItem(311, 0, 1, [$e1, $e2]);

        $legs = Utils::createItem(312, 0, 1, [$e1, $e2]);

        $boots = Utils::createItem(313, 0, 1,
            [$e1, $e2, new EnchantmentInstance(Enchantment::getEnchantment(2), 4)]);

        $this->armor = [$helmet, $chest, $legs, $boots];

        $this->effects = [new EffectInstance(Effect::getEffect(Effect::SPEED), Utils::minutesToTicks(10000), 0, false)];
    }
}