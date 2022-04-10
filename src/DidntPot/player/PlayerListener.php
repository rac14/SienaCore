<?php

namespace DidntPot\player;

use DidntPot\kits\AbstractKit;
use DidntPot\kits\DefaultKits;
use DidntPot\misc\AbstractListener;
use DidntPot\player\sessions\PlayerHandler;
use DidntPot\player\tasks\async\AsyncPlayerRespawn;
use DidntPot\PracticeCore;
use DidntPot\scoreboard\ScoreboardUtils;
use DidntPot\tasks\types\scoreboard\DelayedScoreboardUpdate;
use DidntPot\utils\Utils;
use Error;
use pocketmine\entity\Attribute;
use pocketmine\entity\Effect;
use pocketmine\entity\projectile\EnderPearl as ProjectileEnderPearl;
use pocketmine\event\entity\EntityDamageByEntityEvent;
use pocketmine\event\entity\EntityDamageEvent;
use pocketmine\event\entity\EntityMotionEvent;
use pocketmine\event\entity\ProjectileLaunchEvent;
use pocketmine\event\player\PlayerChatEvent;
use pocketmine\event\player\PlayerCreationEvent;
use pocketmine\event\player\PlayerDropItemEvent;
use pocketmine\event\player\PlayerExhaustEvent;
use pocketmine\event\player\PlayerJoinEvent;
use pocketmine\event\player\PlayerQuitEvent;
use pocketmine\event\player\PlayerRespawnEvent;
use pocketmine\item\Item;
use pocketmine\item\ItemIds;
use pocketmine\level\Position;
use pocketmine\math\Vector3;
use pocketmine\Player;
use pocketmine\Server;

class PlayerListener extends AbstractListener
{
    /** @var array */
    public array $cooldown;
    /** @var PracticeCore */
    private PracticeCore $plugin;

    /** @var Vector3|null */
    private $initialKnockbackMotion = false, $shouldCancelKBMotion = false;

    /**
     * @param PracticeCore $core
     */
    public function __construct(PracticeCore $core)
    {
        $this->plugin = $core;

        parent::__construct($core);
    }

    /**
     * @param PlayerCreationEvent $ev
     */
    public function onCreation(PlayerCreationEvent $ev)
    {
        $ev->setPlayerClass(Human::class);
    }

    /**
     * @param PlayerJoinEvent $ev
     */
    public function onJoin(PlayerJoinEvent $ev)
    {
        $player = $ev->getPlayer();

        Utils::loadPlayer($player);

        $ev->setJoinMessage("§8[§a+§8] §a" . $player->getName());
    }

    /**
     * @param PlayerQuitEvent $ev
     */
    public function onQuit(PlayerQuitEvent $ev)
    {
        $player = $ev->getPlayer();

        Utils::savePlayer($player);

        $ev->setQuitMessage("§8[§c-§8] §c" . $player->getName());

        $this->plugin->getScheduler()->scheduleDelayedTask(new DelayedScoreboardUpdate(), 3);
    }

    /**
     * @param PlayerExhaustEvent $ev
     */
    public function onExhaust(PlayerExhaustEvent $ev)
    {
        $player = $ev->getPlayer();

        $ev->setCancelled(true);

        $player->setFood($player->getMaxFood());
        $player->setSaturation(Attribute::getAttribute(Attribute::SATURATION)->getMaxValue());
    }

    /**
     * @param PlayerDropItemEvent $ev
     */
    public function onDrop(PlayerDropItemEvent $ev)
    {
        $player = $ev->getPlayer();

        if (!PlayerHandler::hasSession($player)) {
            return;
        }

        $session = PlayerHandler::getSession($player);

        if ($session->isInDuel()) {
            $item = $ev->getItem();

            if ($item !== Item::get(ItemIds::GLASS_BOTTLE)) {
                $ev->setCancelled(true);
            }
        } else
            $ev->setCancelled(true);
    }

    /**
     * @param ProjectileLaunchEvent $ev
     */
    public function onLaunch(ProjectileLaunchEvent $ev)
    {
        $projectile = $ev->getEntity();
        $player = $projectile->getOwningEntity();

        if ($player instanceof Player)
            $session = PlayerHandler::getSession($player);
        else
            return;

        if ($projectile instanceof ProjectileEnderPearl) {
            $ev->setCancelled();

            if ($session->canThrowPearl() === true)
            {
                Utils::createEnderPearl($player);
                $session->setThrowPearl(false);
            }
        }
    }

    /**
     * @param PlayerChatEvent $ev
     */
    public function onChat(PlayerChatEvent $ev)
    {
        $player = $ev->getPlayer();
        $session = PlayerHandler::getSession($player);

        $msg = $ev->getMessage();

        if ($ev->isCancelled()) return;

        if($player->hasPermission("practice.staff.chat") and $msg[0] === "!")
        {
            $ev->setCancelled();

            foreach($this->plugin->getServer()->getOnlinePlayers() as $online)
            {
                if($online->hasPermission("practice.staff.chat"))
                {
                    $msg = str_replace("!", "", $msg);
                    $online->sendMessage("§8[STAFF] §8[§7" . $session->getRank() . "§8] §7" . $player->getName().": §e".$msg);
                }
            }
        }

        if (!$session->canChat())
        {
            $player->sendMessage(Utils::getPrefix() . "Please refrain from spamming.");
            $ev->setCancelled(true);
        }

        $format = Utils::formatChat($player);
        $format = str_replace("%division%", Utils::formatDivision($player), $format);
        $format = str_replace("%name%", Utils::getPlayerName($player), $format);
        $format = str_replace("%msg%", $ev->getMessage(), $format);

        $ev->setFormat($format);
    }

    /**
     * @param EntityDamageEvent $ev
     */
    public function onEntityDamage(EntityDamageEvent $ev)
    {
        $player = $ev->getEntity();
        $cause = $ev->getCause();

        if(!$player instanceof Player) return;

        $playerSession = PlayerHandler::getSession($player);

        if(
            $ev->getModifier($ev::MODIFIER_PREVIOUS_DAMAGE_COOLDOWN) < 0.0
            || $cause === $ev::CAUSE_FALL
            || $playerSession->isInSpawn()
            || $player->isImmobile()
            || $playerSession->isFrozen()
            || $playerSession->isVanished()
        ) {
            $ev->setCancelled();
        }

        if ($ev->isCancelled()) return;

        if ($player instanceof Player)
        {
            $session = PlayerHandler::getSession($player);

            $cause = $ev->getCause();
            $damage = $ev->getBaseDamage();
            $level = $player->getLevel()->getName();

            if (Utils::areLevelsEqual($player->getLevel(), Server::getInstance()->getLevelByName(PracticeCore::LOBBY)))
            {
                if ($cause === EntityDamageEvent::CAUSE_VOID)
                {
                    if ($session->hasParty() === true)
                    {
                        $ev->setCancelled(true);

                        $session->teleportPlayer($player, "lobby", false, false);
                        DefaultKits::sendPartyKit($player);

                        return;
                    }

                    if (PracticeCore::getDuelManager()->isInQueue($player))
                    {
                        $ev->setCancelled(true);

                        $session->teleportPlayer($player, "lobby", false, false);
                        DefaultKits::sendQueueKit($player);

                        return;
                    }

                    $session->teleportPlayer($player, "lobby", true, true);
                }

                $ev->setCancelled(true);
                return;
            }

            if ($cause === EntityDamageEvent::CAUSE_VOID and $level !== PracticeCore::LOBBY)
            {
                if(Utils::areLevelsEqual($player->getLevel(), Server::getInstance()->getLevelByName("sumo-ffa")))
                {
                    $ev->setCancelled();

                    $session->onDeath();
                    PlayerExtensions::clearAll($player);
                    $session->respawn();
                    return;
                }

                if(Utils::areLevelsEqual($player->getLevel(), Server::getInstance()->getLevelByName("build-ffa")))
                {
                    $ev->setCancelled();

                    $session->onDeath();
                    PlayerExtensions::clearAll($player);
                    $session->respawn();
                    return;
                }
            }

            if ($ev instanceof EntityDamageByEntityEvent) {
                $damager = $ev->getDamager();

                if ($session->isFrozen()) {
                    $ev->setCancelled();
                    return;
                }

                if ($session->isVanished()) {
                    $ev->setCancelled();
                    return;
                }

                if ($damager instanceof Player) {

                    if(
                        !$damager->isSprinting()
                        && !$damager->isFlying()
                        && $damager->fallDistance > 0
                        && !$damager->hasEffect(Effect::BLINDNESS)
                        && !$damager->isUnderwater()
                        && ($ev->getFinalDamage() / 2) < $player->getHealth()
                    ){
                        $player->setHealth($player->getHealth() + ($ev->getFinalDamage() / 2));
                    }

                    if(PlayerHandler::getSession($player)->isInDuel() and PlayerHandler::getSession($damager)->isInDuel())
                    {
                        $duelManager = PracticeCore::getInstance()->getDuelManager();
                        $kitManager = PracticeCore::getInstance()->getKits();

                        $duel = $duelManager->getDuel($damager->getPlayer());
                        $kit = $duel->getQueue();

                        if(!$kitManager->getKit($kit)->canDamageOthers())
                        {
                            $ev->setCancelled(true);
                            return;
                        }

                        $attackDelay = $kitManager->getKit($kit)->getSpeed();

                        $ev->setAttackCooldown($attackDelay);
                        $ev->setKnockBack(0.0);
                        $this->knockBack($player, $damager, $kitManager->getKit($kit));

                        $duel->addHitTo($damager->getPlayer(), $ev->getFinalDamage());
                        return;
                    }

                    foreach ([$player, $damager] as $players)
                    {
                        $level = $players->getLevel()->getName();
                        $playerSession = PlayerHandler::getSession($player);
                        $damagerSession = PlayerHandler::getSession($damager);

                        if ($level === PracticeCore::LOBBY) {
                            $ev->setCancelled();
                            return;
                        }

                        try {
                            if (!PlayerHandler::getSession($players)->isInDuel()) {
                                if($playerSession->hasTarget() === false && $damagerSession->hasTarget() === false){
                                    $playerSession->setTarget($damager->getName());
                                    $damagerSession->setTarget($player->getName());

                                    if (!PlayerHandler::getSession($players)->isCombat()) {
                                        PlayerHandler::getSession($players)->setCombat(true, true);
                                    }
                                }elseif($playerSession->hasTarget() === true && $damagerSession->hasTarget() === false){
                                    $ev->setCancelled();
                                    $damager->sendMessage(Utils::getPrefix() . "§cInterrupting is not allowed.");
                                    return;
                                }elseif($damagerSession->hasTarget() && $damagerSession->getTarget()->getName() !== $player->getName()){
                                    $ev->setCancelled();
                                    $damager->sendMessage(Utils::getPrefix() . "§cYour opponent is " . $damagerSession->getTarget()->getDisplayName() . ".");
                                    return;
                                }

                                PlayerHandler::getSession($players)->setCombat(true, false);
                                PracticeCore::getScoreboardManager()->sendFFAScoreboard($players, true, PlayerHandler::getSession($players)->combatSecs);
                            }
                        }catch (Error $error){}
                    }

                    $kb = $ev->getKnockBack();
                    $attackDelay = $ev->getAttackCooldown();

                    // TODO:
                    if(Utils::areLevelsEqual($damager->getLevel(), Server::getInstance()->getLevelByName("nodebuff-ffa")))
                    {
                        $kitManager = PracticeCore::getInstance()->getKits();
                        $attackDelay = $kitManager->getKit("nodebuff")->getSpeed();

                        $ev->setAttackCooldown($attackDelay);

                        $ev->setKnockBack(0.0);
                        $this->knockBack($player, $damager, $kitManager->getKit("nodebuff"));
                    }

                    if(Utils::areLevelsEqual($damager->getLevel(), Server::getInstance()->getLevelByName("resistance-ffa")))
                    {
                        $kitManager = PracticeCore::getInstance()->getKits();
                        $attackDelay = $kitManager->getKit("resistance")->getSpeed();

                        $ev->setAttackCooldown($attackDelay);

                        $ev->setKnockBack(0.0);
                        $this->knockBack($player, $damager, $kitManager->getKit("resistance"));
                    }

                    if(Utils::areLevelsEqual($damager->getLevel(), Server::getInstance()->getLevelByName("sumo-ffa")))
                    {
                        $kitManager = PracticeCore::getInstance()->getKits();
                        $attackDelay = $kitManager->getKit("sumo")->getSpeed();

                        $ev->setAttackCooldown($attackDelay);

                        $ev->setKnockBack(0.0);
                        $this->knockBack($player, $damager, $kitManager->getKit("sumo"));
                    }
                }
            }
        }
    }

    /**
     * @param EntityDamageEvent $ev
     * @return void
     */
    public function onDeath(EntityDamageEvent $ev)
    {
        if($ev->isCancelled())
        {
            return;
        }

        $player = $ev->getEntity();

        if($player instanceof Player && $player->getHealth() - $ev->getFinalDamage() <= 0)
        {
            $playerSession = PlayerHandler::getSession($player);

            $ev->setCancelled();

            PlayerExtensions::clearAll($player);

            $playerSession->onDeath();
            $playerSession->respawn();
        }
    }

    /**
     * @param EntityMotionEvent $ev
     */
    public function onEntityMotion(EntityMotionEvent $ev): void
    {
        $player = $ev->getEntity();

        if($player instanceof Player)
        {
            if($this->initialKnockbackMotion)
            {
                $this->initialKnockbackMotion = false;
                $this->shouldCancelKBMotion = true;
            }elseif($this->shouldCancelKBMotion)
            {
                $this->shouldCancelKBMotion = false;
                $ev->setCancelled();
            }
        }
    }

    /*public function onDeath(PlayerDeathEvent $ev)
    {
        $player = $ev->getPlayer();
        $session = PlayerHandler::getSession($player);
        $finaldamagecause = $player->getLastDamageCause();

        $ev->setDeathMessage('');
        $ev->setDrops([]);

        // TODO: Add this as a setting.
        foreach($ev->getDrops() as $item)
        {
            $delay = 100;
            $close = 21;
            $entity = $player->level->dropItem($player->add(0, 0.2, 0), $item, null, $delay);

            $this->plugin->getScheduler()->scheduleDelayedTask(new CloseEntityTask($this->plugin, $entity), $close);
            $ev->setDrops([]);
        }

        if ($player instanceof Player)
        {
            if ($session->isCombat()) $session->setCombat(false);
        }

        ScoreboardUtils::removeScoreboard($player);

        $cause = $player->getLastDamageCause();

        if ($cause instanceof EntityDamageByEntityEvent and $cause->getDamager() !== null) {
            $killer = $cause->getDamager();

            $killerSession = PlayerHandler::getSession($killer);
            $playerSession = PlayerHandler::getSession($player);

            $killerSession->setKills($killerSession->getKills() + 1);
            $killerSession->setKillstreak($killerSession->getKillstreak() + 1);

            $playerSession->setDeaths($playerSession->getDeaths() + 1);
            $playerSession->setKillstreak(0);

            if ($player instanceof Player and $killer instanceof Player) {
                $duelManager = PracticeCore::getDuelManager();

                // TODO:
                if (Utils::willUpgradeDivision($player) === true) {
                    $player->sendMessage(Utils::getPrefix() . "§eYou have §ltiered-up§r §eto " . Utils::formatDivision($player) . "§e.§r");
                    $player->sendTitle("§r§a§l§k||§r §c§eTIERED-UP§r §9S3 §r§a§l§k||§r", "§r§8[" . Utils::formatDivision($player) . "§8]", 20, 40, 20);
                }

                if ($playerSession->isInDuel()) {
                    $duel = $duelManager->getDuel($player);
                    $winner = Utils::getPlayerName($killer);

                    $duel->broadcastDeathMessage($killer, $player);

                    $duel->setEnded($this->plugin->getServer()->getPlayer($winner));

                    $ev->setDrops([]);

                    $ev->setDeathMessage('');
                } else {
                    if (PlayerHandler::getSession($player)->isCombat()) PlayerHandler::getSession($player)->setCombat(false);
                    if (PlayerHandler::getSession($killer)->isCombat()) PlayerHandler::getSession($killer)->setCombat(false);

                    Utils::spawnLightning($player);

                    if($killerSession->isBloodfx())
                    {
                        for ($i = 0; $i < 5; $i++)
                        {
                            $player->getLevel()->addParticle(new DestroyBlockParticle($player->add(mt_rand(-50, 50) / 100, 1 + mt_rand(-50, 50) / 100, mt_rand(-50, 50) / 100), Block::get(BlockIds::REDSTONE_BLOCK)));
                        }
                    }

                    $finalhealth = round($killer->getHealth(), 1);
                    $weapon = $killer->getInventory()->getItemInHand()->getName();

                    // TODO: USE THIS ->

                     * duel-death-messages:
                     * - "%killed% has been memed by %killer%"
                     * - "%killed% has been OOFed by %killer%"
                     * - "%killed% has been shat on by %killer%"
                    $randMsg =
                        [
                            ""
                        ];

                    $msg = (!is_null($randMsg)) ? $randMsg : "";

                    $messages = ["quickied", "railed", "clapped", "given an L", "killed", "botted", "utterly defeated", "smashed", "OwOed", "UwUed", "swept off their feet", "sent to the heavens", "smacked", "betrayed", "poked with a sharp object", "yeeted"];

                    $potsA = 0;
                    $potsB = 0;

                    foreach ($player->getInventory()->getContents() as $pots) {
                        if ($pots instanceof SplashPotion) $potsA++;
                    }

                    foreach ($killer->getInventory()->getContents() as $pots) {
                        if ($pots instanceof SplashPotion) $potsB++;
                    }

                    if ($killer->getLevel()->getName() === "nodebuff-ffa") {
                        $dm = "§r§l§8»§r §7" . $player->getDisplayName() . " §8[§c" . $potsA . " Pots§8] §7was " . $messages[array_rand($messages)] . " by " . $killer->getDisplayName() . " §8[§c" . $potsB . " Pots - " . $finalhealth . "HP§8]§r";
                    } else {
                        $dm = "§r§l§8»§r §7" . $player->getDisplayName() . " §7was " . $messages[array_rand($messages)] . " by " . $killer->getDisplayName() . " §8[§c" . $finalhealth . " HP§8]§r";
                    }

                    $ev->setDeathMessage('');

                    $player->sendMessage($dm);
                    $killer->sendMessage($dm);

                    $killer->setHealth($killer->getMaxHealth());
                    PlayerHandler::getSession($player)->teleportPlayer($player, "lobby", true, true);

                    // TODO:
                    //$this->plugin->getServer()->getAsyncPool()->submitTask(new AsyncPlayerDeath($player, $killer));

                    $klevel = $killer->getLevel()->getName();

                    if ($klevel === "build-ffa") {
                        if ($killer->isOnline()) {
                            $kitManager = $this->plugin->getKits();

                            $kitManager->getKit(Kits::BUILDUHC)->giveTo($player, false);
                        }
                    }
                    // TODO: Add AutoRekit functions.
                }
            }
        }
    }*/

    /**
     * @param PlayerRespawnEvent $ev
     */
    public function onRespawn(PlayerRespawnEvent $ev)
    {
        $player = $ev->getPlayer();

        $ev->setRespawnPosition(new Position(PracticeCore::LOBBY_X, PracticeCore::LOBBY_Y, PracticeCore::LOBBY_Z, $this->plugin->getServer()->getLevelByName(PracticeCore::LOBBY)));
        $this->plugin->getServer()->getAsyncPool()->submitTask(new AsyncPlayerRespawn($player));
    }

    /**
     * @param Player $player
     * @param Player $damager
     * @param AbstractKit|null $kit
     */
    private function knockBack(Player $player, Player $damager, ?AbstractKit $kit)
    {
        $xzKb = $kit->getXKb();
        $yKb = $kit->getYKb();

        $x = $player->getX() - $damager->x;
        $z = $player->getZ() - $damager->z;

        $f = sqrt($x * $x + $z * $z);

        if($f <= 0)
        {
            return;
        }

        if(mt_rand() / mt_getrandmax() > $player->getAttributeMap()->getAttribute(Attribute::KNOCKBACK_RESISTANCE)->getValue()){
            $f = 1 / $f;
            $motion = clone $player->getMotion();
            $motion->x /= 2;
            $motion->y /= 2;
            $motion->z /= 2;
            $motion->x += $x * $f * $xzKb;
            $motion->y += $yKb;
            $motion->z += $z * $f * $xzKb;
            if($motion->y > $yKb){
                $motion->y = $yKb;
            }
            $this->initialKnockbackMotion = true;
            $player->setMotion($motion);
        }
    }
}