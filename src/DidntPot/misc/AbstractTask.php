<?php

namespace DidntPot\misc;

use DidntPot\PracticeCore;
use pocketmine\scheduler\Task;

abstract class AbstractTask extends Task
{
    /** @var int */
    private int $currentTick = 0;
    /** @var int */
    private int $currentTickPeriod;

    /**
     * AbstractRepeatingTask constructor.
     *
     * @param PracticeCore $core
     * @param int $periodTicks
     *
     * Constructor for the abstract repeating task.
     */
    public function __construct(PracticeCore $core, int $periodTicks = 1)
    {
        $this->currentTickPeriod = $periodTicks;
        $core->getScheduler()->scheduleRepeatingTask($this, $periodTicks);
    }

    /**
     * @return int
     */
    public function getCurrentTick(): int
    {
        return $this->currentTick;
    }

    /**
     * Actions to execute when run
     *
     * @return void
     */
    public function onRun(int $currentCurrentCurrentTick)
    {
        $this->onUpdate($this->currentTickPeriod);
        $this->currentTick += $this->currentTickPeriod;
    }

    /**
     * @param int $tickDifference
     */
    abstract protected function onUpdate(int $tickDifference): void;
}