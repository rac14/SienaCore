<?php

namespace DidntPot\commands\types\moderation;

use DidntPot\commands\types\PracticeCommand;
use DidntPot\player\sessions\PlayerHandler;
use DidntPot\PracticeCore;
use DidntPot\utils\StaffUtils;
use DidntPot\utils\Utils;
use pocketmine\command\CommandSender;
use pocketmine\Player;
use pocketmine\utils\TextFormat;

class GameModeCommand extends PracticeCommand
{
    /** @var PracticeCore */
    private PracticeCore $plugin;

    public function __construct()
    {
        parent::__construct("gm", "", "/gm", ["gamemode"]);

        $this->plugin = PracticeCore::getInstance();
    }

    /**
     * @param CommandSender $sender
     * @param string $commandLabel
     * @param array $args
     * @return bool
     */
    public function execute(CommandSender $sender, string $commandLabel, array $args): bool
    {
        if (!$sender instanceof Player) {
            $sender->sendMessage("[CONSOLE] You can only use this command in-game.");
        } else {
            if ((!$sender->hasPermission("practice.command.gamemode"))) {
                $sender->sendMessage(TextFormat::RED . "Unknown command. Try /help for a list of commands");
                return false;
            }

            $player = $sender;
            $session = PlayerHandler::getSession($player);

            if (!$player->isOp()) {
                if ($session->isCombat()) {
                    $player->sendMessage(Utils::getPrefix() . "§cYou can't use this command while in combat.");
                    return false;
                }
            }

            if (!isset($args[0])) {
                $player->sendMessage(Utils::getPrefix() . "§cProvide an argument: 0:1:2:3");
                return false;
            }

            if (!isset($args[1])) {
                switch ($args[0]) {
                    case "0":
                    case "s":
                    case "survival":

                        $newgamemode = "survival";

                        if ($player->getGamemode() === 0) {
                            $player->sendMessage(Utils::getPrefix() . "§cYour gamemode is already set to " . $newgamemode . ".");
                            return false;
                        }

                        $player->setGamemode(0);
                        $player->setAllowFlight(false);
                        $player->setFlying(false);

                        $player->sendMessage(Utils::getPrefix() . "§aYou updated your gamemode to " . $newgamemode . ".");

                        $message = StaffUtils::sendStaffNotification("gamemode_change");

                        $message = str_replace("{name}", $player->getName(), $message);
                        $message = str_replace("{newgamemode}", $newgamemode, $message);

                        foreach ($this->plugin->getServer()->getOnlinePlayers() as $online) {
                            if ($online->hasPermission("practice.staff.notification")) {
                                $online->sendMessage($message);
                            }
                        }

                        break;

                    case "1":
                    case "c":
                    case "creative";

                        $newgamemode = "creative";

                        if ($player->getGamemode() === 1) {
                            $player->sendMessage(Utils::getPrefix() . "§cYour gamemode is already set to " . $newgamemode . ".");
                            return false;
                        }

                        $player->setGamemode(1);
                        $player->setAllowFlight(true);
                        $player->sendMessage(Utils::getPrefix() . "§aYou updated your gamemode to " . $newgamemode . ".");
                        $message = StaffUtils::sendStaffNotification("gamemode_change");
                        $message = str_replace("{name}", $player->getName(), $message);
                        $message = str_replace("{newgamemode}", $newgamemode, $message);
                        foreach ($this->plugin->getServer()->getOnlinePlayers() as $online) {
                            if ($online->hasPermission("practice.staff.notification")) {
                                $online->sendMessage($message);
                            }
                        }
                        break;
                    case "2":
                    case "a":
                    case "adventure";
                        $newgamemode = "adventure";
                        if ($player->getGamemode() == 2) {
                            $player->sendMessage(Utils::getPrefix() . "§cYour gamemode is already set to " . $newgamemode . ".");
                            return false;
                        }
                        $player->setGamemode(2);
                        $player->setAllowFlight(false);
                        $player->setFlying(false);
                        $player->sendMessage(Utils::getPrefix() . "§aYou updated your gamemode to " . $newgamemode . ".");
                        $message = StaffUtils::sendStaffNotification("gamemode_change");
                        $message = str_replace("{name}", $player->getName(), $message);
                        $message = str_replace("{newgamemode}", $newgamemode, $message);
                        foreach ($this->plugin->getServer()->getOnlinePlayers() as $online) {
                            if ($online->hasPermission("practice.staff.notification")) {
                                $online->sendMessage($message);
                            }
                        }
                        break;
                    case "3":
                    case "sp":
                    case "spectator";
                        $newgamemode = "spectator";
                        if ($player->getGamemode() == 3) {
                            $player->sendMessage(Utils::getPrefix() . "§cYour gamemode is already set to " . $newgamemode . ".");
                            return false;
                        }
                        $player->setGamemode(3);
                        $player->sendMessage(Utils::getPrefix() . "§aYou updated your gamemode to " . $newgamemode . ".");
                        $message = StaffUtils::sendStaffNotification("gamemode_change");
                        $message = str_replace("{name}", $player->getName(), $message);
                        $message = str_replace("{newgamemode}", $newgamemode, $message);
                        foreach ($this->plugin->getServer()->getOnlinePlayers() as $online) {
                            if ($online->hasPermission("practice.staff.notification")) {
                                $online->sendMessage($message);
                            }
                        }
                        break;
                    default:
                        $player->sendMessage(Utils::getPrefix() . "§cProvide a valid argument: 0:1:2:3");
                }
            } else {
                if (!$player->hasPermission("practice.command.gamemode_other")) {
                    $player->sendMessage(Utils::getPrefix() . "§cYou cannot update another players gamemode.");
                    return false;
                }
                if ($this->plugin->getServer()->getPlayer($args[1]) === null) {
                    $player->sendMessage(Utils::getPrefix() . "§cPlayer not found.");
                    return false;
                }
                switch ($args[0]) {
                    case "0":
                    case "s":
                    case "survival";
                        $newgamemode = "survival";
                        $target = $this->plugin->getServer()->getPlayer($args[1]);
                        if ($target->getGamemode() == 0) {
                            $player->sendMessage(Utils::getPrefix() . "§c" . $target->getName() . "'s gamemode is already set to " . $newgamemode . ".");
                            return false;
                        }
                        $target->setGamemode(0);
                        $target->setAllowFlight(false);
                        $target->setFlying(false);
                        $player->sendMessage(Utils::getPrefix() . "§aYou updated " . $target->getName() . "'s gamemode to " . $newgamemode . ".");
                        $target->sendMessage(Utils::getPrefix() . "§aYour gamemode was updated to " . $newgamemode . ".");
                        $message = StaffUtils::sendStaffNotification("gamemode_change_other");
                        $message = str_replace("{name}", $player->getName(), $message);
                        $message = str_replace("{target}", $target->getName(), $message);
                        $message = str_replace("{newgamemode}", $newgamemode, $message);
                        foreach ($this->plugin->getServer()->getOnlinePlayers() as $online) {
                            if ($online->hasPermission("practice.staff.notification")) {
                                $online->sendMessage($message);
                            }
                        }
                        break;
                    case "1":
                    case "c":
                    case "creative";
                        $newgamemode = "creative";
                        $target = $this->plugin->getServer()->getPlayer($args[1]);
                        if ($target->getGamemode() == 1) {
                            $player->sendMessage(Utils::getPrefix() . "§c" . $target->getName() . "'s gamemode is already set to " . $newgamemode . ".");
                            return false;
                        }
                        $target->setGamemode(1);
                        $target->setAllowFlight(true);
                        $player->sendMessage(Utils::getPrefix() . "§aYou updated " . $target->getName() . "'s gamemode to " . $newgamemode . ".");
                        $target->sendMessage(Utils::getPrefix() . "§aYour gamemode was updated to " . $newgamemode . ".");
                        $message = StaffUtils::sendStaffNotification("gamemode_change_other");
                        $message = str_replace("{name}", $player->getName(), $message);
                        $message = str_replace("{target}", $target->getName(), $message);
                        $message = str_replace("{newgamemode}", $newgamemode, $message);
                        foreach ($this->plugin->getServer()->getOnlinePlayers() as $online) {
                            if ($online->hasPermission("practice.staff.notification")) {
                                $online->sendMessage($message);
                            }
                        }
                        break;
                    case "2":
                    case "a":
                    case "adventure";
                        $newgamemode = "adventure";
                        $target = $this->plugin->getServer()->getPlayer($args[1]);
                        if ($target->getGamemode() == 2) {
                            $player->sendMessage(Utils::getPrefix() . "§c" . $target->getName() . "'s gamemode is already set to " . $newgamemode . ".");
                            return false;
                        }
                        $target->setGamemode(2);
                        $target->setAllowFlight(false);
                        $target->setFlying(false);
                        $player->sendMessage(Utils::getPrefix() . "§aYou updated " . $target->getName() . "'s gamemode to " . $newgamemode . ".");
                        $target->sendMessage(Utils::getPrefix() . "§aYour gamemode was updated to " . $newgamemode . ".");
                        $message = StaffUtils::sendStaffNotification("gamemode_change_other");
                        $message = str_replace("{name}", $player->getName(), $message);
                        $message = str_replace("{target}", $target->getName(), $message);
                        $message = str_replace("{newgamemode}", $newgamemode, $message);
                        foreach ($this->plugin->getServer()->getOnlinePlayers() as $online) {
                            if ($online->hasPermission("practice.staff.notification")) {
                                $online->sendMessage($message);
                            }
                        }
                        break;
                    case "3":
                    case "sp":
                    case "spectator";
                        $newgamemode = "spectator";
                        $target = $this->plugin->getServer()->getPlayer($args[1]);
                        if ($target->getGamemode() == 3) {
                            $player->sendMessage(Utils::getPrefix() . "§c" . $target->getName() . "'s gamemode is already set to " . $newgamemode . ".");
                            return false;
                        }
                        $target->setGamemode(3);
                        $player->sendMessage(Utils::getPrefix() . "§aYou updated " . $target->getName() . "'s gamemode to " . $newgamemode . ".");
                        $target->sendMessage(Utils::getPrefix() . "§aYour gamemode was updated to " . $newgamemode . ".");
                        $message = StaffUtils::sendStaffNotification("gamemode_change_other");
                        $message = str_replace("{name}", $player->getName(), $message);
                        $message = str_replace("{target}", $target->getName(), $message);
                        $message = str_replace("{newgamemode}", $newgamemode, $message);
                        foreach ($this->plugin->getServer()->getOnlinePlayers() as $online) {
                            if ($online->hasPermission("practice.staff.notification")) {
                                $online->sendMessage($message);
                            }
                        }
                        break;
                    default:
                        $player->sendMessage(Utils::getPrefix() . "§cProvide a valid argument: 0:1:2:3");
                        break;
                }
            }

            return false;
        }

        return false;
    }
}