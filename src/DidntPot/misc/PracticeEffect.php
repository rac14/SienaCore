<?php

namespace DidntPot\misc;

use JetBrains\PhpStorm\Pure;
use pocketmine\entity\Effect;
use pocketmine\entity\EffectInstance;
use pocketmine\Player;

class PracticeEffect
{
    private int $duration;

    private Effect $effect;

    private int $amplifier;

    public function __construct(Effect $effect, int $duration, int $amp)
    {
        $this->effect = $effect;
        $this->duration = $duration;
        $this->amplifier = $amp;
    }

    public static function getEffectFrom(string $line): PracticeEffect
    {
        $split = explode(":", $line);
        $id = intval($split[0]);
        $amp = intval($split[1]);
        $duration = intval($split[2]);
        $effect = Effect::getEffect($id);
        return new PracticeEffect($effect, $duration, $amp);
    }

    public function getEffect(): Effect
    {
        return $this->effect;
    }

    public function getDuration(): int
    {
        return $this->duration;
    }

    public function getAmplifier(): int
    {
        return $this->amplifier;
    }

    public function applyTo($player): void
    {
        if ($player instanceof Player) {
            $effect = new EffectInstance($this->effect, $this->duration * 20, $this->amplifier, false);
            $player->addEffect($effect);
        }
    }

    #[Pure] public function toString(): string
    {
        $id = $this->effect->getId();
        return $id . ":" . $this->amplifier . ":" . $this->duration;
    }
}