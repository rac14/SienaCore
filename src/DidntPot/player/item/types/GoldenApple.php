<?php

namespace DidntPot\player\item\types;

use DidntPot\utils\Utils;
use pocketmine\entity\Effect;
use pocketmine\entity\EffectInstance;
use pocketmine\item\GoldenApple as PMGoldenApple;
use pocketmine\item\Item;
use pocketmine\item\ItemIds;
use pocketmine\utils\TextFormat;

class GoldenApple extends PMGoldenApple
{
    public function __construct()
    {
        parent::__construct(2);
    }

    /**
     *
     * @param bool $head
     * @param int $count
     * @return Item
     *
     * Creates a Golden Head.
     */
    public static function create(bool $head = true, int $count = 1): Item
    {
        return Item::get(ItemIds::GOLDEN_APPLE, !$head ? 0 : 1, $count)->setCustomName(TextFormat::GOLD . "Golden Head");
    }

    public function getAdditionalEffects(): array
    {
        if ($this->getDamage() === 0) {
            return [
                new EffectInstance(Effect::getEffect(Effect::REGENERATION), 100, 1),
                new EffectInstance(Effect::getEffect(Effect::ABSORPTION), Utils::minutesToTicks(2))
            ];
        } else {
            return [
                new EffectInstance(Effect::getEffect(Effect::REGENERATION), Utils::secondsToTicks(10), 1),
                new EffectInstance(Effect::getEffect(Effect::ABSORPTION), Utils::minutesToTicks(2))
            ];
        }
    }
}