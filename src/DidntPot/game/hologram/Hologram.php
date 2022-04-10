<?php

namespace DidntPot\game\hologram;

use DidntPot\PracticeCore;
use JetBrains\PhpStorm\Pure;
use pocketmine\level\Level;
use pocketmine\level\particle\FloatingTextParticle;
use pocketmine\math\Vector3;

abstract class Hologram
{
    /* @var Vector3 */
    protected $vec3;

    /* @var Level */
    protected $level;

    /* @var string[]|array */
    protected $leaderboardKeys;

    /* @var FloatingTextParticle|null */
    protected $floatingText;

    /* @var int */
    protected $currentKey;

    /** @var HologramHandler */
    protected $leaderboards;

    #[Pure] public function __construct(Vector3 $vec3, Level $level, HologramHandler $leaderboards = null)
    {
        $this->level = $level;
        $this->vec3 = $vec3;
        $this->leaderboards = $leaderboards ?? PracticeCore::getHologramHandler();
        $this->leaderboardKeys = [];
        $this->floatingText = null;
        $this->currentKey = 0;
    }

    /**
     *
     * @param bool $updateKey
     *
     * Places the hologram down into the world.
     */
    abstract protected function placeFloatingHologram(bool $updateKey = true): void;

    /**
     * Updates the floating hologram.
     */
    public function updateHologram(): void
    {
        $this->placeFloatingHologram();
    }

    /**
     * @param Vector3 $position
     * @param Level $level
     *
     * Moves the hologram from one spot to another.
     */
    public function moveHologram(Vector3 $position, Level $level): void
    {
        $originalLevel = $this->level;

        if($level === null)
        {
            return;
        }

        $this->level = $level;
        $this->vec3 = $position;

        if($this->floatingText !== null)
        {
            $this->floatingText->setInvisible(true);
            $pkts = $this->floatingText->encode();

            if(!is_array($pkts))
            {
                $pkts = [$pkts];
            }

            if(count($pkts) > 0)
            {
                foreach($pkts as $pkt)
                {
                    $originalLevel->broadcastPacketToViewers($this->vec3, $pkt);
                }
            }
        }

        $this->placeFloatingHologram(false);
    }
}