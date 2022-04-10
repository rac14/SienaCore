<?php

namespace DidntPot\game\entities;

use pocketmine\block\BlockFactory;
use pocketmine\block\BlockIds;
use pocketmine\entity\Living;
use pocketmine\entity\projectile\SplashPotion as Pot;
use pocketmine\event\entity\ProjectileHitBlockEvent;
use pocketmine\event\entity\ProjectileHitEntityEvent;
use pocketmine\event\entity\ProjectileHitEvent;
use pocketmine\item\Potion;
use pocketmine\network\mcpe\protocol\LevelEventPacket;
use pocketmine\network\mcpe\protocol\LevelSoundEventPacket;
use pocketmine\utils\Color;

class SplashPotion extends Pot
{
    /**
     * @param ProjectileHitEvent $event
     *
     * Called when the projectile hits something.
     */
    protected function onHit(ProjectileHitEvent $event): void
    {
        $effects = $this->getPotionEffects();
        $hasEffects = true;

        if (empty($effects)) {
            // TODO: Add color per player pot settings.
            $colors = [
                //new Color(0x38, 0x5d, 0xc6)
                new Color(0, 0, 0)
            ];
            $hasEffects = false;
        } else {
            $colors = [];
            foreach ($effects as $effect) {
                $level = $effect->getEffectLevel();
                for ($j = 0; $j < $level; ++$j) {
                    $colors[] = $effect->getColor();
                }
            }
        }

        $this->level->broadcastLevelEvent($this, LevelEventPacket::EVENT_PARTICLE_SPLASH, Color::mix(...$colors)->toARGB());
        $this->level->broadcastLevelSoundEvent($this, LevelSoundEventPacket::SOUND_GLASS);

        if ($hasEffects) {
            foreach ($this->level->getNearbyEntities($this->boundingBox->expandedCopy(4.125, 2.125, 4.125), $this) as $entity) {
                if ($entity instanceof Living and $entity->isAlive()) {
                    $distanceSquared = $entity->add(0, $entity->getEyeHeight(), 0)->distanceSquared($this);
                    if ($distanceSquared > 16) {
                        continue;
                    }

                    $distanceMultiplier = 1.45 - (sqrt($distanceSquared) / 4);
                    if ($event instanceof ProjectileHitEntityEvent and $entity === $event->getEntityHit()) {
                        $distanceMultiplier = 1.0;
                    }

                    foreach ($this->getPotionEffects() as $effect) {
                        if (!$effect->getType()->isInstantEffect()) {
                            $newDuration = (int)round($effect->getDuration() * 0.75 * $distanceMultiplier);
                            if ($newDuration < 20) {
                                continue;
                            }
                            $effect->setDuration($newDuration);
                            $entity->addEffect($effect);
                        } else {
                            $effect->getType()->applyEffect($entity, $effect, $distanceMultiplier, $this, $this->getOwningEntity());
                        }
                    }
                }
            }
        } elseif ($event instanceof ProjectileHitBlockEvent and $this->getPotionId() === Potion::WATER) {
            $blockIn = $event->getBlockHit()->getSide($event->getRayTraceResult()->getHitFace());

            if ($blockIn->getId() === BlockIds::FIRE) {
                $this->level->setBlock($blockIn, BlockFactory::get(BlockIds::AIR));
            }
            foreach ($blockIn->getHorizontalSides() as $horizontalSide) {
                if ($horizontalSide->getId() === BlockIds::FIRE) {
                    $this->level->setBlock($horizontalSide, BlockFactory::get(BlockIds::AIR));
                }
            }
        }
    }
}