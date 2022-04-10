<?php

namespace DidntPot\game\entities;

use pocketmine\entity\Entity;
use pocketmine\entity\projectile\Projectile;
use pocketmine\event\entity\EntityDamageEvent;
use pocketmine\event\entity\ProjectileHitEntityEvent;
use pocketmine\event\entity\ProjectileHitEvent;
use pocketmine\level\Level;
use pocketmine\level\sound\EndermanTeleportSound;
use pocketmine\nbt\tag\CompoundTag;
use pocketmine\network\mcpe\protocol\LevelEventPacket;
use pocketmine\Player;
use pocketmine\utils\Random;

class EnderPearl extends Projectile
{
    /**
     * @var int
     */
    public const NETWORK_ID = self::ENDER_PEARL;

    /**
     * @var float
     */
    public $height = 0.2;
    /**
     * @var float
     */
    public $width = 0.2;
    /**
     * @var float
     */
    protected $gravity = 0.1;

    /**
     * @param Level $level
     * @param CompoundTag $nbt
     * @param Entity|null $owner
     */
    public function __construct(Level $level, CompoundTag $nbt, ?Entity $owner = null)
    {
        parent::__construct($level, $nbt, $owner);

        if ($owner instanceof Player) {
            $this->setPosition($this->add(0, $owner->getEyeHeight()));
            $this->setMotion($owner->getDirectionVector()->multiply(1));
            $this->handleMotion($this->motion->x, $this->motion->y, $this->motion->z, 1.3, 1);
        }
    }

    /**
     * @param float $x
     * @param float $y
     * @param float $z
     * @param float $f1
     * @param float $f2
     */
    public function handleMotion(float $x, float $y, float $z, float $f1, float $f2)
    {
        $rand = new Random();

        $f = sqrt($x * $x + $y * $y + $z * $z);

        $x = $x / $f;
        $y = $y / $f;
        $z = $z / $f;

        $x = $x + $rand->nextSignedFloat() * 0.007499999832361937 * $f2;
        $y = $y + $rand->nextSignedFloat() * 0.008599999832361937 * $f2;
        $z = $z + $rand->nextSignedFloat() * 0.007499999832361937 * $f2;

        $x = $x * $f1;
        $y = $y * $f1;
        $z = $z * $f1;

        $this->motion->x += $x;
        $this->motion->y += $y * 1.40;
        $this->motion->z += $z;
    }

    /**
     * @return int
     */
    public function getResultDamage(): int
    {
        return -1;
    }

    /**
     * @param int $tickDiff
     * @return bool
     */
    public function entityBaseTick(int $tickDiff = 1): bool
    {
        $hasUpdate = parent::entityBaseTick($tickDiff);
        $owner = $this->getOwningEntity();

        if ($owner === null or !$owner->isAlive() or $owner->isClosed() or $this->isCollided) {
            $this->flagForDespawn();
        }

        return $hasUpdate;
    }

    public function close(): void
    {
        parent::close();
    }

    public function applyGravity(): void
    {
        if ($this->isUnderwater()) {
            $this->motion->y += $this->gravity;
        } else {
            parent::applyGravity();
        }
    }

    protected function initEntity(): void
    {
        parent::initEntity();
    }

    /**
     * @param ProjectileHitEvent $event
     */
    protected function onHit(ProjectileHitEvent $event): void
    {
        $owner = $this->getOwningEntity();
        //$target = $event->getEntity();

        if ($owner !== null) {
            $this->level->broadcastLevelEvent($owner, LevelEventPacket::EVENT_PARTICLE_ENDERMAN_TELEPORT);
            $this->level->addSound(new EndermanTeleportSound($owner));
            //$owner->teleport($event->getRayTraceResult()->getHitVector());

            $target = $event->getRayTraceResult()->getHitVector();
            $x = ($target->x - $owner->getPosition()->getX());
            $z = ($target->z - $owner->getPosition()->getZ());
            $y = ($target->y - $owner->getPosition()->getY());
            $owner->move($x, $y, $z);

            if($owner instanceof Player){
                $location = $owner->getLocation();
                $owner->sendPosition($location, $location->getYaw(), $location->getPitch());
            }

            $this->level->addSound(new EndermanTeleportSound($owner));

            if ($event instanceof ProjectileHitEntityEvent) {
                $player = $event->getEntityHit();

                if ($player instanceof Player and $owner instanceof Player) {
                    if ($player->getName() !== $owner->getName()) {
                        $player->attack(new EntityDamageEvent($player, EntityDamageEvent::CAUSE_PROJECTILE, 1));
                        $deltaX = $player->x - $this->x;
                        $deltaZ = $player->z - $this->z;
                        $player->knockBack($owner, 1, $deltaX, $deltaZ, 0.350);
                    }
                }
            }
        }
    }
}