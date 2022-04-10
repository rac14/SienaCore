<?php

namespace DidntPot\tasks\types\internal;

use DidntPot\PracticeCore;
use pocketmine\entity\Entity;
use pocketmine\scheduler\Task;

class CloseEntityTask extends Task
{
    /** @var Entity */
    private Entity $entity;
    /** @var PracticeCore */
    private PracticeCore $plugin;

    /**
     * @param PracticeCore $plugin
     * @param Entity $entity
     */
    public function __construct(PracticeCore $plugin, Entity $entity)
    {
        $this->plugin = $plugin;
        $this->entity = $entity;
    }

    /**
     * @param int $currentCurrentCurrentTick
     */
    public function onRun(int $currentCurrentCurrentTick): void
    {
        if (!$this->entity->isClosed()) {
            $this->entity->close();
        }
    }
}