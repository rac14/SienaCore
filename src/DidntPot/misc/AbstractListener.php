<?php

namespace DidntPot\misc;

use DidntPot\PracticeCore;
use pocketmine\event\Listener;

abstract class AbstractListener implements Listener
{
    /**
     * @param PracticeCore $core
     */
    public static PracticeCore $core;

    /**
     * @param PracticeCore $core
     */
    public function __construct(PracticeCore $core)
    {
        $core->getServer()->getPluginManager()->registerEvents($this, $core);
        self::$core = $core;
    }

    /**
     * @return PracticeCore
     */
    public function getInstance(): PracticeCore
    {
        return self::$core;
    }
}