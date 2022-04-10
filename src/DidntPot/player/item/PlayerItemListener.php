<?php

namespace DidntPot\player\item;

use DidntPot\arenas\FFAArena;
use DidntPot\forms\types\CustomForm;
use DidntPot\forms\types\ModalForm;
use DidntPot\forms\types\SimpleForm;
use DidntPot\misc\AbstractListener;
use DidntPot\parties\PracticeParty;
use DidntPot\player\Human;
use DidntPot\player\sessions\PlayerHandler;
use DidntPot\PracticeCore;
use DidntPot\utils\ItemNameUtils;
use DidntPot\utils\Utils;
use pocketmine\entity\Effect;
use pocketmine\entity\EffectInstance;
use pocketmine\event\player\PlayerInteractEvent;
use pocketmine\Player;
use pocketmine\Server;
use pocketmine\utils\TextFormat;

class PlayerItemListener extends AbstractListener
{
    /** @var PracticeCore */
    private PracticeCore $plugin;

    /** @var array */
    private array $cooldown;

    /**
     * @param PracticeCore $core
     */
    public function __construct(PracticeCore $core)
    {
        $this->plugin = $core;

        parent::__construct($core);
    }

    /**
     * @param PlayerInteractEvent $ev
     */
    public function onItemInteract(PlayerInteractEvent $ev)
    {
        $player = $ev->getPlayer();

        $block = $ev->getBlock();

        $item = $ev->getItem();
        $itemName = $item->getCustomName();

        if ($ev->isCancelled()) return;

        if (isset($this->cooldown[$player->getName()]) and $this->cooldown[$player->getName()] + 1 > microtime(true)) return;
        $this->cooldown[$player->getName()] = microtime(true);

        switch ($itemName) {
            case ItemNameUtils::SPAWN_ITEM_DUELS_BOT:
                $this->sendBotDuelForm($player);
                $ev->setCancelled(true);
                break;

            case ItemNameUtils::SPAWN_ITEM_FFA:
                $this->sendFFAForm($player);
                $ev->setCancelled(true);
                break;

            case ItemNameUtils::SPAWN_ITEM_DUELS_UNRANKED:
                $this->sendDuelForm($player, false);
                $ev->setCancelled(true);
                break;

            case ItemNameUtils::SPAWN_ITEM_DUELS_RANKED:
                $this->sendDuelForm($player, true);
                $ev->setCancelled(true);
                break;

            case ItemNameUtils::SPAWN_ITEM_DUELS_SPECTATE:
                //$this->sendDuelSpectateForm($player);
                $player->sendMessage(Utils::getPrefix() . TextFormat::RED . "Coming soon.");
                $ev->setCancelled(true);
                break;

            case ItemNameUtils::SPAWN_ITEM_SETTINGS:
                //$this->sendSettingsForm($player);
                $player->sendMessage(Utils::getPrefix() . TextFormat::RED . "Coming soon.");
                $ev->setCancelled(true);
                break;

            case ItemNameUtils::SPAWN_ITEM_LEAVE_QUEUE:
                if (PracticeCore::getDuelManager()->isInQueue($player)) {
                    PracticeCore::getDuelManager()->removeFromQueue($player, true);
                    $ev->setCancelled(true);
                }
                break;

            case ItemNameUtils::SPAWN_ITEM_PARTY:
                //if (PracticeCore::getDuelManager()->isInQueue($player)) PracticeCore::getDuelManager()->removeFromQueue($player, false);
                //$this->sendDefaultPartyForm($player);
                $player->sendMessage(Utils::getPrefix() . TextFormat::RED . "Coming soon.");
                $ev->setCancelled(true);
            break;

            case ItemNameUtils::SPAWN_ITEM_EVENTS:
                //$this->sendEventsForm($player);
                $player->sendMessage(Utils::getPrefix() . TextFormat::RED . "Coming soon.");
                $ev->setCancelled(true);
                break;

            case ItemNameUtils::SPAWN_ITEM_PARTY_VS_PARTY_DUEL:
                $this->sendPartyvsPartyForm($player);
                $ev->setCancelled(true);
                break;

            case ItemNameUtils::SPAWN_ITEM_LEAVE_PARTY_QUEUE:
                $playerParty = PracticeCore::getPartyManager()->getPartyFromPlayer($player);

                if (PracticeCore::getPartyManager()->getEventManager()->isInQueue($playerParty)) {
                    PracticeCore::getPartyManager()->getEventManager()->removeFromQueue($playerParty, true);
                    $ev->setCancelled(true);
                }
                break;

            case ItemNameUtils::SPAWN_ITEM_LEAVE_PARTY:
                $this->sendPartyLeaveForm($player, PracticeCore::getPartyManager()->getPartyFromPlayer($player)->isOwner($player));
                $ev->setCancelled(true);
                break;

            case ItemNameUtils::SPAWN_ITEM_PARTY_SETTINGS:
                if (PlayerHandler::getSession($player)->hasParty())
                    $this->sendPartySettingsForm($player);
                $ev->setCancelled(true);
                break;

            default:
                break;
        }
    }

    /**
     * @param Player $player
     */
    public function sendBotDuelForm(Player $player)
    {

    }

    /**
     * @param Player $player
     */
    public function sendFFAForm(Player $player)
    {
        $form = new SimpleForm(function (Player $event, $data = null) {

            switch($data)
            {
                case null:
                    break;

                default:
                    if(is_int($data)) return;

                    $arenaName = $data;
                    $p = Utils::getPlayer($event);

                    $arenaManager = PracticeCore::getArenaManager();
                    $arena = PracticeCore::getArenaManager()->getArena($arenaName);

                    if ($arena !== null && $arena instanceof FFAArena && !PlayerHandler::getSession($event)->isInDuel()) {
                        if (PlayerHandler::getSession($event)->isInDuelQueue()) {
                            PracticeCore::getDuelManager()->removeFromQueue($event, false);
                        }

                        PlayerHandler::getSession($event)->teleportToFFAArena($arena);
                        if($arenaName === "Resistance-FFA") $event->addEffect(new EffectInstance(Effect::getEffect(Effect::RESISTANCE), 999, 100));
                    }
                break;
            }
        });

        $form->setTitle("§r§8FFA");
        $form->setContent("§fSelect a FFA kit type:");

        $arenaManager = PracticeCore::getArenaManager();

        $arenas = $arenaManager->getFFAArenas();

        $size = count($arenas);

        if ($size <= 0) {
            $form->addButton(Utils::getUncoloredString("None"));
            $player->sendForm($form);
            return;
        }

        foreach ($arenas as $arena) {
            $name = $arena->getName();
            $name = str_replace("-FFA", "", $name);

            $players = $arenaManager->getPlayersInArena($arena);

            $name .= "\n" . 'Players: ' . $players;

            $texture = $arena->getTexture();
            $form->addButtonLegacy($name, 0, $texture, $arena->getName());
        }

        $form->addButton("§c« Back");

        $player->sendForm($form);
    }

    /**
     * @param Player $player
     * @param bool $ranked
     */
    public function sendDuelForm(Player $player, bool $ranked = false)
    {
        $form = new SimpleForm(function (Player $event, $data = null) {
            if ($event instanceof Human) {
                $formData = $event->removeFormData();
                $session = PlayerHandler::getSession($event);

                if ($data !== null && $formData !== null) {
                    if($data === "exit") return;

                    if (isset($formData[$data]['text'])) {
                        $queue = TextFormat::clean(explode("\n", $formData[$data]['text'])[0]);
                    } else {
                        return;
                    }

                    if ($session->isInSpawn()) {
                        if (isset($formData['ranked'])) {
                            PracticeCore::getDuelManager()->placeInQueue($event, $queue, (bool)$formData['ranked']);
                        } else {
                            return;
                        }
                    }
                }
            }
        });

        if ($ranked === true) $isRanked = "Ranked";
        else $isRanked = "Unranked";

        $form->setTitle("§r§8" . $isRanked);
        $form->setContent("§fSelect a " . strtolower($isRanked) . " kit type:");

        $list = PracticeCore::getKits()->getKits();

        foreach ($list as $kit) {
            if(!$kit->ffaOnly())
            {
                $name = Utils::getUncoloredString($kit->getName());

                $numInQueue = PracticeCore::getDuelManager()->getPlayersInQueue($ranked, $name);
                $numInFights = PracticeCore::getDuelManager()->getPlayersInDuel($ranked, $name);

                $inQueues = "\n" . "§fQueued: " . $numInQueue;
                $inFights = "§fFighting: " . $numInFights;

                $name .= $inQueues . " §8| " . $inFights;

                $form->addButton($name, 0, $kit->getTexture());
            }
        }

        $form->addButton("§c« Back", "exit");

        $player->sendFormWindow($form, ['ranked' => $ranked]);
    }

    /**
     * @param Player $player
     */
    public function sendDuelSpectateForm(Player $player)
    {
        if (!$player->isOp()) {
            $player->sendMessage(Utils::getPrefix() . TextFormat::RED . "Coming soon.");
            return;
        }
    }

    /**
     * @param Player $player
     */
    public function sendSettingsForm(Player $player)
    {
        $form = new SimpleForm(function (Player $player, $data = null): void {
            switch ($data) {
                case "display":
                    $this->sendDisplaySettingsForm($player);
                break;

                /*case "settings":
                    $this->settingsForm($player);
                    break;
                case "capeCMD":
                    $this->plugin->getServer()->dispatchCommand($player, "cape");
                    break;
                case "cosmetics":
                    $this->cosmeticsForm($player);
                    break;*/
                default:
                return;
            }
        });

        $form->setTitle("§r§8Settings");
        $form->setContent("§fSelect an option:");

        /* Scoreboard, CPS-Counter */
        $form->addButton(Utils::getUncoloredString("Display"), "display");
        /* Auto Rekit, Auto Requeue, BloodFX, TODO: Lighting */
        $form->addButton(Utils::getUncoloredString("Gameplay"), "gameplay");
        /** TODO: PE Only, Ping Range */
        $form->addButton(Utils::getUncoloredString("Matchmaking"), "gameplay");
        /** TODO: Cape, Pot Splash Color */
        $form->addButton(Utils::getUncoloredString("Cosmetics"), "cosmetics");

        $form->addButton("§c« Back", "exit");

        $player->sendForm($form);
    }

    /**
     * @param Player $player
     */
    public function sendDisplaySettingsForm(Player $player)
    {
        $form = new CustomForm(function (Player $player, $data = null): void
        {
            if($data !== null)
            {
                $session = PlayerHandler::getSession($player);

                $session->setScoreboard((bool)$data[0]);
                $player->sendMessage("You have " . ($data[0]) ? "enabled" : "disabled" . " the scoreboard.");
            }
        });

        $session = PlayerHandler::getSession($player);

        $form->setTitle("§r§8Display Settings");

        $isScoreboard = ($session->isScoreboard() ? 1 : 0);

        $form->addDropdown(
            "Scoreboard",
            [
                TextFormat::GREEN . "Enabled",
                TextFormat::RED . "Disabled"
            ],
            $session->isScoreboard()
        );

        $player->sendForm($form);
    }

    /**
     * @param Player $player
     */
    public function sendStatsForm(Player $player)
    {
        $form = new SimpleForm(function (Player $player, $data = null): void {
            switch ($data) {
                case "exit":
                    $this->sendSettingsForm($player);
                    break;
            }
        });

        $session = PlayerHandler::getSession($player);

        $controls = $session->getInput(true);
        $os = $session->getDeviceOS(true);

        //TODO:
        $elo = 0;

        $kills = $session->getKills();
        $deaths = $session->getDeaths();
        $kdr = $session->getKdr();
        $killstreak = $session->getKillstreak();
        $bestkillstreak = $session->getBestKillstreak();
        $division = Utils::formatDivision($player);

        $form->setTitle("§r§8Stats");

        $form->setContent(Utils::getThemeColor() . "Your Competitive Stats:§r" . "\n§fElo: " . $elo . "\n\n" . Utils::getThemeColor() . "Your Casual Stats:§r" . "\n§fDivision: " . $division . "\n§fKills: " . $kills . "\n§fDeaths: " . $deaths . "\n§fKillstreak " . $killstreak . "\n§fBest Killstreak: " . $bestkillstreak . "\n§fKDR: " . $kdr . "\n\n" . Utils::getThemeColor() . "Misc Stats:" . "\n§fInput: " . $controls . "\n§fOS: " . $os);

        $form->addButton("§c« Back", "exit");

        $player->sendForm($form);
    }

    /**
     * @param Player $player
     */
    public function sendEventsForm(Player $player)
    {
        if (!$player->isOp()) {
            $player->sendMessage(Utils::getPrefix() . TextFormat::RED . "Coming soon.");
            return;
        }
    }

    /**
     * @param Player $player
     */
    public function sendDefaultPartyForm(Player $player)
    {
        $form = new SimpleForm(function (Player $player, $data = null): void
        {
            $session = PlayerHandler::getSession($player);

            switch ($data)
            {
                case "create":

                    if(!$session->hasParty())
                    {
                        $this->sendPartyCreateForm($player);
                    }else{
                        $player->sendMessage(PracticeParty::getPrefix() . "You are already in a party.");
                        return;
                    }

                break;

                case "join":

                    if(!$session->hasParty())
                    {
                        $this->sendPartyJoinForm($player);
                        return;
                    }else{
                        $player->sendMessage(PracticeParty::getPrefix() . "You are already in a party.");
                        return;
                    }

                break;

                default:
                    break;
            }
        });

        $form->setTitle("§r§8Party");
        $form->setContent("§fSelect an option:");

        $form->addButtonLegacy("§7Create Party", 0, "", "create");
        $form->addButtonLegacy("§7Join Public Party", 0, "", "join");

        $form->addButton("§c« Back", "exit");

        $player->sendForm($form);
    }

    /**
     * @param Player $player
     */
    public function sendPartyCreateForm(Player $player)
    {
        $form = new CustomForm(function (Player $player, $data = null): void
        {
            $session = PlayerHandler::getSession($player);

            if($data !== null and $session->isInSpawn())
            {
                $partyManager = PracticeCore::getPartyManager();

                $partyName = (string) $data[0];
                $inviteOnly = (bool) $data[1];

                $partyManager->createParty($player, $partyName, 4, !$inviteOnly);
            }

        });

        $form->setTitle("§r§8Create Party");

        $default = $player->getName() . "'s Party";

        $form->addInput(TextFormat::WHITE . "Name:", $default, $default);
        $form->addToggle(TextFormat::WHITE . "Invite only", false);

        $player->sendForm($form);
    }

    /**
     * @param Player $player
     */
    public function sendPartyJoinForm(Player $player)
    {
        $form = new SimpleForm(function (Player $event, $data = null)
        {
            if($event instanceof Human)
            {
                $session = PlayerHandler::getSession($event);
                $formData = $event->removeFormData();

                if($data !== null and $session->isInSpawn())
                {
                    $index = $data;

                    $text = $formData[$index]['text'];

                    if($text !== "None")
                    {
                        $parties = array_values($formData['parties']);

                        if (!isset($parties[$index])) return;

                        $partyManager = PracticeCore::getPartyManager();

                        $party = $parties[$index];
                        $name = $party->getName();

                        $party = $partyManager->getPartyFromName($name);

                        if ($party === null)
                        {
                            $event->sendMessage(Utils::getPrefix() . TextFormat::RED . "The party does not exist.");
                            return;
                        }

                        $maxPlayers = $party->getMaxPlayers();

                        $currentPlayers = (int)$party->getPlayers(true);

                        $blacklisted = $party->isBlackListed($event);

                        if ($party->isOpen() and $currentPlayers < $maxPlayers and !$blacklisted)
                            $party->addPlayer($event);
                        else
                        {
                            if ($currentPlayers >= $maxPlayers)
                            {
                                $msg = Utils::getPrefix() . TextFormat::RED . "That party is currently full.";
                                $event->sendMessage($msg);
                            } elseif (!$party->isOpen())
                            {
                                $msg = Utils::getPrefix() . TextFormat::RED . "That party is invite only.";
                                $event->sendMessage($msg);
                            } elseif ($blacklisted)
                            {
                                $msg = Utils::getPrefix() . TextFormat::RED . "You are blacklisted from that party.";
                                $event->sendMessage($msg);
                            }
                        }
                    }
                }
            }
        });

        $form->setTitle(TextFormat::DARK_GRAY . "Join Party");

        $form->setContent(TextFormat::WHITE . "List of public parties:");

        $partyManager = PracticeCore::getPartyManager();

        $parties = $partyManager->getParties();

        $size = count($parties);

        if($size <= 0)
        {
            $player->sendForm($form);
            return;
        }

        $openStr = "Open";
        $closedStr = "Closed";
        $blacklistedStr = "Blacklisted";

        foreach ($parties as $party)
        {
            $name = TextFormat::BOLD . TextFormat::GOLD . $party->getName();
            $numPlayers = $party->getPlayers(true);
            $maxPlayers = $party->getMaxPlayers();
            $isBlacklisted = $party->isBlackListed($player);
            $blacklisted = ($isBlacklisted) ? TextFormat::DARK_GRAY . '[' . TextFormat::RED . $blacklistedStr . TextFormat::DARK_GRAY . '] ' : '';
            $open = $party->isOpen() ? TextFormat::GREEN . $openStr : TextFormat::RED . $closedStr;
            $text = $blacklisted . $name . "\n" . TextFormat::RESET . TextFormat::YELLOW . $numPlayers . '/' . $maxPlayers . TextFormat::DARK_GRAY . ' | ' . $open;
            $form->addButton($text);
        }

        $player->sendForm($form);
    }

    /**
     * @param Player $player
     */
    public function sendPartyvsPartyForm(Player $player)
    {
        $form = new SimpleForm(function (Player $player, $data = null): void {
            if ($data !== null) {
                if (Utils::areLevelsEqual($player->getLevel(), Server::getInstance()->getLevelByName(PracticeCore::LOBBY))) {
                    if (PlayerHandler::getSession($player)->hasParty()) {
                        // 2vs2
                        if ($data === 0) {
                            $this->sendPartyDuelForm($player, 2);
                        }

                        // 3vs3
                        if ($data === 1) {
                            $this->sendPartyDuelForm($player, 3);
                        }

                        // 4vs4
                        if ($data === 2) {
                            $this->sendPartyDuelForm($player, 4);
                        }

                        // 5vs5
                        if ($data === 3) {
                            $this->sendPartyDuelForm($player, 5);
                        }
                    }
                }
            }
        });

        $form->setTitle("§r§8Party vs Party");
        $form->setContent("§fSelect a queue type:");

        $format = TextFormat::WHITE . "Queued: " . '%iq%';

        $partyManager = PracticeCore::getPartyManager();

        $eventManager = $partyManager->getEventManager();

        $form->addButton(Utils::getUncoloredString("2vs2") . "\n" . str_replace("%iq%", $eventManager->getPartysInQueue(2), $format), 0);
        $form->addButton(Utils::getUncoloredString("3vs3") . "\n" . str_replace("%iq%", $eventManager->getPartysInQueue(3), $format), 0);
        $form->addButton(Utils::getUncoloredString("4vs4") . "\n" . str_replace("%iq%", $eventManager->getPartysInQueue(4), $format), 0);
        // TODO: Make only for ranks.
        $form->addButton(Utils::getUncoloredString("5vs5") . "\n" . str_replace("%iq%", $eventManager->getPartysInQueue(5), $format), 0);

        $form->addButton("§c« Back");

        $player->sendForm($form);
    }

    /**
     * @param Player $player
     * @param int $size
     */
    public function sendPartyDuelForm(Player $player, int $size)
    {
        $form = new SimpleForm(function (Player $player, $data = null) use ($size): void {
            if ($data !== null) {
                if ($data === "exit") {
                    $this->sendPartyvsPartyForm($player);
                    return;
                }

                if (Utils::areLevelsEqual($player->getLevel(), Server::getInstance()->getLevelByName(PracticeCore::LOBBY))) {
                    if (PlayerHandler::getSession($player)->hasParty()) {
                        $queue = $data;

                        $partyManager = PracticeCore::getPartyManager();
                        $party = $partyManager->getPartyFromPlayer($player);

                        $partyManager->getEventManager()->placeInQueue($party, $queue, $size);
                    }
                }
            }
        });

        $form->setTitle("§r§8" . $size . "vs" . $size);
        $form->setContent("§fSelect a kit type:");

        $partyManager = PracticeCore::getPartyManager();

        $eventManager = $partyManager->getEventManager();

        $itemHandler = PracticeCore::getItemHandler();

        $items = $itemHandler->getDuelItems();

        foreach ($items as $item) {
            if ($item instanceof PracticeItem) {
                $name = $item->getName();

                $uncolored = Utils::getUncoloredString($name);

                $numInQueue = $numInQueue = $eventManager->getPartysInQueue($size, $uncolored);

                $inQueues = "\n" . "§fQueued: " . $numInQueue;

                $name .= $inQueues;

                $texture = $item->getTexture();
                $form->addButtonLegacy("" . $name, 0, $texture, $item->getName());
            }
        }

        $form->addButtonLegacy("§c« Back", 0, "", "exit");

        $player->sendForm($form);
    }

    /**
     * @param Player $player
     */
    public function sendPartySettingsForm(Player $player)
    {
        $form = new SimpleForm(function (Player $player, $data = null) {
            switch ($data) {
                case "config":
                    $this->sendPartyConfigForm($player);
                    break;

                case "invite":
                    $this->sendPartyInviteForm($player);
                    break;

                case "ownership":
                    $partyManager = PracticeCore::getPartyManager();
                    $playerParty = $partyManager->getPartyFromPlayer($player);

                    $players = $playerParty->getPlayers();

                    $possiblePromotions = [];

                    $ownerName = $playerParty->getOwner()->getName();

                    foreach ($players as $p) {
                        $name = $p->getName();

                        if ($ownerName !== $name)
                            $possiblePromotions[] = $name;
                    }

                    $this->sendPartyOwnershipForm($player, $possiblePromotions);
                    break;

                default:
                    //
                    break;
            }
        });

        $partyManager = PracticeCore::getPartyManager();
        $party = $partyManager->getPartyFromPlayer($player);
        $partyOwner = $party->getOwner();

        $form->setTitle("§r§8Party Settings");

        if (strtolower($partyOwner->getName()) === strtolower($player->getName())) {
            $form->addButtonLegacy(Utils::getUncoloredString("Party Config"), 0, "", "config");
            $form->addButtonLegacy(Utils::getUncoloredString("Invite Player"), 0, "", "invite");
            $form->addButtonLegacy(Utils::getUncoloredString("Transfer Ownership"), 0, "", "ownership");
        } else {
            $form->setContent("§cYou are not the owner of this party.");
        }

        $form->addButton("§c« Back");

        $player->sendForm($form);
    }

    /**
     * @param Player $player
     */
    public function sendPartyConfigForm(Player $player)
    {
        $form = new CustomForm(function (Player $player, $data = null) {
            if ($player instanceof Player) {
                if ($data !== null) {
                    $inviteOnly = (bool)$data[1];

                    $maxPlayers = (int)$data[2];

                    $party = PracticeCore::getPartyManager()->getPartyFromPlayer($player);

                    $party->setOpen(!$inviteOnly);

                    $party->setMaxPlayers($maxPlayers);

                    $player->sendMessage(PracticeParty::getPrefix() . "§aYour party config have been updated.");
                }
            }
        });

        $party = PracticeCore::getPartyManager()->getPartyFromPlayer($player);
        $owner = $party->getOwner();

        $inviteOnly = !$party->isOpen();

        $inviteOnlyStr = "Invite Only";

        $maxPlayersStr = "Max Members";

        $max = 4;

        $rank = PlayerHandler::getSession($owner)->getRank();
        $rank = strtolower($rank);

        // TODO: Change this for ranks.
        if ($rank === "owner" || $rank === "admin" || $rank === "dev" || $rank === "mod" || $rank === "helper" || $rank === "famous" || $rank === "donatorplus" || $rank === "donator") {
            if ($max < 8) $max = 8;
        } elseif ($rank === "media" || $rank === "booster" || $rank === "voter" || $rank === "designer" || $rank === "builder") {
            if ($max < 6) $max = 6;
        }

        $form->setTitle("§r§8Party Config");
        $form->addLabel("§l§e" . $party->getName() . "§r§e's Config:");

        $form->addToggle($inviteOnlyStr, $inviteOnly);
        $form->addSlider($maxPlayersStr, 2, $max, 1, $party->getMaxPlayers());

        $player->sendForm($form);
    }

    /**
     * @param Player $player
     */
    public function sendPartyInviteForm(Player $player)
    {
        $form = new CustomForm(function (Player $player, $data = null) {
            if ($player instanceof Player) {
                if ($data !== null) {
                    if (!isset($data[0])) return;

                    $partyManager = PracticeCore::getPartyManager();

                    $party = $partyManager->getPartyFromPlayer($player);

                    $requestHandler = $partyManager->getRequestHandler();

                    if (($to = Server::getInstance()->getPlayerExact($data[0])) !== null && $to instanceof Player) {
                        if (PlayerHandler::getSession($to)->hasParty()) {
                            $player->sendMessage(PracticeParty::getPrefix() . "§c" . $to->getName() . " is already in a party.");
                        } else {
                            $requestHandler->sendRequest($player, $to, $party);
                        }

                    } else {
                        $player->sendMessage(PracticeParty::getPrefix() . "§c" . $data[0] . " isn't online on your current region.");
                    }
                }
            }
        });

        $onlinePlayers = $player->getServer()->getOnlinePlayers();

        $form->setTitle("§r§8Invite Player");

        $dropdownArr = [];

        $name = $player->getDisplayName();

        $size = count($onlinePlayers);

        foreach ($onlinePlayers as $p) {
            $pName = $p->getDisplayName();

            if ($pName !== $name)
                $dropdownArr[] = $pName;
        }

        if (($size - 1) > 0)
            $form->addDropdown("§7Sent to:", $dropdownArr);
        else
            $form->addLabel("§cNobody except you is online on your current region.");

        $player->sendForm($form);
    }

    /**
     * @param Player $player
     * @param array $possiblePromotions
     */
    public function sendPartyOwnershipForm(Player $player, array $possiblePromotions = [])
    {
        $form = new CustomForm(function (Player $player, $data = null) {
            if ($player instanceof Player) {
                if ($data !== null && isset($data[0])) {
                    $partyManager = PracticeCore::getPartyManager();
                    $playerParty = $partyManager->getPartyFromPlayer($player);

                    $name = $data[0];

                    if ($playerParty->isPlayer($name)) {
                        $promotedPlayer = $playerParty->getPlayer($name);

                        $playerParty->promoteToOwner($promotedPlayer);
                    } else {
                        $player->sendMessage(Utils::getPrefix() . "§c" . $data[0] . " isn't in your party.");
                    }
                }
            }
        });

        $form->setTitle("§8Transfer Ownership");

        $playerLabel = "§fPlayers:";

        $size = count($possiblePromotions);

        if ($size <= 0) {
            $form->addLabel("§cYou don't have any members to promote.");
            $player->sendForm($form);
            return;
        }

        $form->addDropdown($playerLabel, $possiblePromotions);

        $player->sendForm($form);
    }

    /**
     * @param Player $player
     * @param bool $isOwner
     */
    public function sendPartyLeaveForm(Player $player, bool $isOwner)
    {
        $form = new ModalForm(function (Player $player, $data = null): void
        {
            if ($player instanceof Human)
            {
                $player->removeFormData();

                if($data !== null)
                {
                    $partyManager = PracticeCore::getPartyManager();

                    $party = $partyManager->getPartyFromPlayer($player);

                    $index = (bool)$data;

                    if($index === true) {
                        $party->removePlayer($player);
                        if(PlayerHandler::getSession($player)->hasParty()) PlayerHandler::getSession($player)->setParty(false);
                    }
                }
            }

        });

        $content = ($isOwner ? TextFormat::WHITE . "Are you sure that you want to disband the party?" : TextFormat::WHITE . "Are you sure that you want to leave the party?");

        $form->setTitle(TextFormat::DARK_GRAY . "Leave Party");
        $form->setContent($content);

        $form->setButton1(TextFormat::GRAY . "Yes");
        $form->setButton2(TextFormat::GRAY . "No");

        $player->sendForm($form);
    }
}