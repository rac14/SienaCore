<?php

namespace DidntPot\kits\types;

use DidntPot\kits\AbstractKit;
use DidntPot\utils\Utils;
use pocketmine\entity\Effect;
use pocketmine\entity\EffectInstance;
use pocketmine\item\enchantment\Enchantment;
use pocketmine\item\enchantment\EnchantmentInstance;
use pocketmine\item\ItemIds;

class Spleef extends AbstractKit
{
    public function __construct(float $xkb = 0.4, float $ykb = 0.4, int $speed = 10)
    {
        parent::__construct('Spleef',
            [
                Utils::createItem(ItemIds::DIAMOND_SHOVEL, 0, 1, [
                    new EnchantmentInstance(Enchantment::getEnchantment(Enchantment::EFFICIENCY), 10),
                    new EnchantmentInstance(Enchantment::getEnchantment(Enchantment::UNBREAKING), 10)
                ]),

                Utils::createItem(ItemIds::STEAK, 0, 64)],
            [],
            [new EffectInstance(Effect::getEffect(Effect::RESISTANCE), Utils::hoursToTicks(10000), 10, false)],
            $xkb, $ykb, $speed, 'textures/items/diamond_shovel.png');

        $this->duelOnly = true;
        $this->damageOthers = false;
        $this->replaysEnabled = false;
        $this->worldType = "type_spleef";
    }
}