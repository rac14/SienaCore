<?php

namespace DidntPot\commands\types\basic;

use DidntPot\commands\types\PracticeCommand;
use DidntPot\parties\PracticeParty;
use DidntPot\player\sessions\PlayerHandler;
use DidntPot\utils\Utils;
use pocketmine\command\CommandSender;
use pocketmine\Player;

class SpawnCommand extends PracticeCommand
{
    public function __construct()
    {
        parent::__construct("spawn", "", "/spawn", ["lobby"]);
    }

    public function execute(CommandSender $sender, string $commandLabel, array $args)
    {
        if (!$sender instanceof Player) {
            $sender->sendMessage("[CONSOLE] You can only use this command in-game.");
        } else {
            $player = $sender;
            $session = PlayerHandler::getSession($player);

            if ($session->isInDuelQueue()) {
                $player->sendMessage(Utils::getPrefix() . "§cYou can't use this command while in queue.");
                return;
            }

            if ($session->isInDuel()) {
                $player->sendMessage(Utils::getPrefix() . "§cYou can't use this command while in a duel.");
                return;
            }

            if ($session->hasParty()) {
                $player->sendMessage(PracticeParty::getPrefix() . "§cYou can't use this command while in a party.");
                return;
            }

            if ($session->isCombat() === false) {
                $session->teleportPlayer($player, "lobby", true, true);

                $player->sendMessage(Utils::getPrefix() . "§aYou have successfully warped to spawn.");
                return;
            } else {
                $player->sendMessage(Utils::getPrefix() . "§cYou can't use this command while in combat.");
                return;
            }
        }
    }
}