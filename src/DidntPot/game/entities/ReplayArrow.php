<?php

namespace DidntPot\game\entities;

use pocketmine\entity\projectile\Arrow;

class ReplayArrow extends Arrow
{
    /* @var bool
     * Determines whether the arrow is paused.
     */
    private $paused = false;

    public function onUpdate(int $currentTick): bool
    {
        if ($this->closed or $this->paused)
            return false;

        return parent::onUpdate($currentTick);
    }

    /**
     * @param bool $paused
     */
    public function setPaused(bool $paused): void
    {
        $this->paused = $paused;
    }
}