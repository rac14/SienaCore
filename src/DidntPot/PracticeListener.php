<?php

namespace DidntPot;

use DidntPot\misc\AbstractListener;
use DidntPot\player\sessions\PlayerHandler;
use DidntPot\player\sessions\Session;
use DidntPot\utils\Utils;
use pocketmine\event\block\BlockBreakEvent;
use pocketmine\event\block\BlockBurnEvent;
use pocketmine\event\block\BlockPlaceEvent;
use pocketmine\event\block\LeavesDecayEvent;
use pocketmine\event\entity\EntityDeathEvent;
use pocketmine\event\entity\ExplosionPrimeEvent;
use pocketmine\event\inventory\CraftItemEvent;
use pocketmine\event\inventory\InventoryTransactionEvent;
use pocketmine\event\player\PlayerInteractEvent;
use pocketmine\event\server\DataPacketReceiveEvent;
use pocketmine\event\server\DataPacketSendEvent;
use pocketmine\event\server\QueryRegenerateEvent;
use pocketmine\item\ItemIds;
use pocketmine\network\mcpe\protocol\AvailableCommandsPacket;
use pocketmine\network\mcpe\protocol\EmotePacket;
use pocketmine\network\mcpe\protocol\InventoryTransactionPacket;
use pocketmine\network\mcpe\protocol\LevelSoundEventPacket;
use pocketmine\network\mcpe\protocol\LoginPacket;
use pocketmine\Player;

class PracticeListener extends AbstractListener
{
    /**
     * @var PracticeCore
     */
    public PracticeCore $plugin;

    /**
     * @param PracticeCore $core
     */
    public function __construct(PracticeCore $core)
    {
        $this->plugin = $core;

        parent::__construct($core);
    }

    /**
     * @param CraftItemEvent $ev
     */
    public function onCraft(CraftItemEvent $ev)
    {
        $ev->setCancelled();
    }

    /**
     * @param InventoryTransactionEvent $ev
     */
    public function onSlotChange(InventoryTransactionEvent $ev)
    {
        $player = null;

        $transaction = $ev->getTransaction();
        $player = $transaction->getSource();
        $level = $player->getLevelNonNull()->getName();

        if ($level === PracticeCore::LOBBY and !$player->isCreative(true)) {
            $ev->setCancelled(true);
        }
    }

    /**
     * @param LeavesDecayEvent $ev
     */
    public function onLeaveDecay(LeavesDecayEvent $ev)
    {
        $ev->setCancelled(true);
    }

    /**
     * @param BlockBurnEvent $ev
     */
    public function onBurn(BlockBurnEvent $ev)
    {
        $ev->setCancelled(true);
    }

    /**
     * @param ExplosionPrimeEvent $ev
     */
    public function onPrimedExplosion(ExplosionPrimeEvent $ev)
    {
        $ev->setBlockBreaking(false);
    }

    /**
     * @param EntityDeathEvent $ev
     */
    public function onEntityDeath(EntityDeathEvent $ev)
    {
        $entity = $ev->getEntity();

        if (!$entity instanceof Player) {
            Utils::spawnLightning($entity);
            $ev->setDrops([]);
        }
    }

    /**
     * @param DataPacketSendEvent $ev
     */
    public function onPacketSend(DataPacketSendEvent $ev)
    {
        $pk = $ev->getPacket();

        $cmdList = ["ping", "spawn", "fly"];
        $commands = [];

        foreach ($cmdList as $cmds) {
            $commands[strtolower($cmds)] = null;
        }

        if ($pk instanceof AvailableCommandsPacket) {
            $pk->commandData = array_intersect_key($pk->commandData, $commands);
        }
    }

    /**
     * @param DataPacketReceiveEvent $ev
     */
    public function onPacketReceived(DataPacketReceiveEvent $ev)
    {
        $pk = $ev->getPacket();
        $player = $ev->getPlayer();

        if ($pk instanceof LoginPacket and $player instanceof Player)
        {
            $clientData = $pk->clientData;

            $deviceModel = $clientData["DeviceModel"];
            $deviceOS = $clientData["DeviceOS"];

            if (trim($deviceModel) === "")
            {
                switch ($deviceOS)
                {
                    case Session::ANDROID:
                        $deviceOS = Session::LINUX;
                        $deviceModel = "Linux";
                        break;

                    case Session::XBOX:
                        $deviceModel = "Xbox One";
                        break;
                }
            }

            if($pk->clientData["CurrentInputMode"] !== null and $pk->clientData["DeviceOS"] !== null and $pk->clientData["DeviceModel"] !== null)
            {
                $this->plugin->getPlayerDeviceInfo()->controls[$pk->username ?? "Unknown"] = $pk->clientData["CurrentInputMode"];
                $this->plugin->getPlayerDeviceInfo()->os[$pk->username ?? "Unknown"] = $deviceOS;
                $this->plugin->getPlayerDeviceInfo()->device[$pk->username ?? "Unknown"] = $deviceModel;
            }
        }

        if ($player->isOnline()) {
            $session = PlayerHandler::getSession($player);
        } else return;

        if ($this->plugin->getPlayerClicksInfo()->isInArray($player)) {
            if ($pk instanceof InventoryTransactionPacket and $pk->trData->getTypeId() === InventoryTransactionPacket::TYPE_USE_ITEM_ON_ENTITY) {
                $session->addCps(false);
            }
        }

        if ($pk::NETWORK_ID === LevelSoundEventPacket::NETWORK_ID and $pk->sound === LevelSoundEventPacket::SOUND_ATTACK_NODAMAGE) {
            if ($this->plugin->getPlayerClicksInfo()->isInArray($player)) {
                $session->addCps(false);
            }
        }

        if ($pk instanceof EmotePacket) {
            $this->plugin->getServer()->broadcastPacket($player->getViewers(), EmotePacket::create($player->getId(), $pk->getEmoteId(), 1 << 0));
        }
    }

    /**
     * @param QueryRegenerateEvent $ev
     */
    public function onQuery(QueryRegenerateEvent $ev)
    {
        $ev->setMaxPlayerCount($ev->getPlayerCount() + 1);

        $ev->setWorld("sienamc.net : 19132");
    }

    /**
     * @param BlockPlaceEvent $ev
     */
    public function onPlace(BlockPlaceEvent $ev)
    {
        $player = $ev->getPlayer();
        $block = $ev->getBlock();

        $x = $block->getX();
        $y = $block->getY();
        $z = $block->getZ();

        if ($ev->isCancelled()) return;

        if(PlayerHandler::getSession($player)->isInDuel()) {
            $duel = PracticeCore::getDuelManager()->getDuel($player);

            $cancel = !$duel->canPlaceBlock($block);

            $ev->setCancelled($cancel);
            return;
        }

        if ($player->getLevel()->getName() === "build-ffa") {
            if ($player->getGamemode() == 0) {
                // TODO: Add this task with proper block replacement (SandStone => RedSandStone => Break)
                //$this->plugin->getScheduler()->scheduleDelayedTask(new BlockReset($block, $x, $y, $z), 100);
                $ev->setCancelled(false);
            }

        } elseif ($player->getGamemode() == 0) {
            if ($player->isOp()) {
                $ev->setCancelled(true);
            } else {
                $ev->setCancelled(true);
            }
        }
    }

    /**
     * @param BlockBreakEvent $ev
     */
    public function onBreak(BlockBreakEvent $ev)
    {
        $player = $ev->getPlayer();
        $block = $ev->getBlock();

        if ($ev->isCancelled()) return;

        if (PlayerHandler::getSession($player)->isInDuel()) {
            $duel = PracticeCore::getDuelManager()->getDuel($player);
            $cancel = !$duel->canPlaceBlock($block, true);

            if (!$cancel) {
                if ($duel->isSpleef()) {
                    $drops = $ev->getDrops();
                    foreach ($drops as $drop) {
                        $player->getInventory()->addItem($drop);
                    }
                    $ev->setDrops([]);
                }
            }

            $ev->setCancelled($cancel);
            return;
        }

        if ($player->getLevel()->getName() === "build-ffa") {
            $blockID = $block->getID();

            if ($player->getGamemode() == 0) {
                if ($blockID === 24) {
                    $ev->setCancelled(false);
                } else {
                    $ev->setCancelled(true);
                }
            }

        } elseif ($player->getGamemode() === 0) {
            if ($player->isOp()) {
                $ev->setCancelled(true);
            } else {
                $ev->setCancelled(true);
            }
        }
    }

    /**
     * @param PlayerInteractEvent $ev
     */
    public function onInteract(PlayerInteractEvent $ev)
    {
        $player = $ev->getPlayer();
        $session = PlayerHandler::getSession($player);
        $action = $ev->getAction();

        $item = $ev->getItem();
        $itemInHand = $player->getInventory()->getItemInHand();
        $id = $item->getId();
        $meta = $item->getDamage();

        if($ev->isCancelled()) return;

        if ($itemInHand->getId() === ItemIds::ENDER_PEARL) {
            if ($session->canThrowPearl() === false) {
                $ev->setCancelled();
            }
        }

        if ($itemInHand->getId() === ItemIds::SPLASH_POTION)
        {
            if(PlayerHandler::getSession($player)->isPe() and $action === PlayerInteractEvent::RIGHT_CLICK_BLOCK)
            {
                $ev->setCancelled();
                Utils::throwPotion($player, $itemInHand);
                return;
            }

            if($action !== PlayerInteractEvent::RIGHT_CLICK_BLOCK and $action !== PlayerInteractEvent::LEFT_CLICK_BLOCK)
            {
                $ev->setCancelled();
                Utils::throwPotion($player, $itemInHand);
                return;
            }
        }
    }
}