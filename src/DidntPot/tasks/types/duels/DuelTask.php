<?php

namespace DidntPot\tasks\types\duels;

use DidntPot\PracticeCore;
use pocketmine\scheduler\Task;

class DuelTask extends Task
{
    /** @var PracticeCore */
    private PracticeCore $plugin;

    /**
     * @param PracticeCore $plugin
     */
    public function __construct(PracticeCore $plugin)
    {
        $this->plugin = $plugin;
    }

    /**
     * @param int $currentTick
     * @return void
     */
    public function onRun(int $currentTick)
    {
        PracticeCore::getDuelManager()->update();
    }
}