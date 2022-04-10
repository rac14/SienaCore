<?php

namespace DidntPot\commands\types\basic;

use DidntPot\commands\types\PracticeCommand;
use DidntPot\parties\PracticeParty;
use DidntPot\player\sessions\PlayerHandler;
use DidntPot\PracticeCore;
use DidntPot\utils\Utils;
use pocketmine\command\CommandSender;
use pocketmine\Player;
use pocketmine\Server;

class RekitCommand extends PracticeCommand
{
    public function __construct()
    {
        parent::__construct("rekit", "", "/rekit", []);
    }

    public function execute(CommandSender $sender, string $commandLabel, array $args)
    {
        if (!$sender instanceof Player) {
            $sender->sendMessage("[CONSOLE] You can only use this command in-game.");
        } else {
            $player = $sender;
            $session = PlayerHandler::getSession($sender);
            $level = $player->getLevel();

            if ($session->isInSpawn())
            {
                $player->sendMessage(Utils::getPrefix() . "§cYou can't use this command in spawn.");
                return;
            }

            if ($session->isInDuelQueue())
            {
                $player->sendMessage(Utils::getPrefix() . "§cYou can't use this command while in queue.");
                return;
            }

            if ($session->isInDuel())
            {
                $player->sendMessage(Utils::getPrefix() . "§cYou can't use this command while in a duel.");
                return;
            }

            if ($session->hasParty())
            {
                $player->sendMessage(PracticeParty::getPrefix() . "§cYou can't use this command while in a party.");
                return;
            }

            if ($session->isCombat() === false)
            {
                if(Utils::areLevelsEqual($level, Server::getInstance()->getLevelByName("nodebuff-ffa")))
                {
                    $kit = PracticeCore::getKits()->getKit("nodebuff");
                    $kit->giveTo($player, true);
                    return;
                }

                if(Utils::areLevelsEqual($level, Server::getInstance()->getLevelByName("resistance-ffa")))
                {
                    $kit = PracticeCore::getKits()->getKit("resistance");
                    $kit->giveTo($player, true);
                    return;
                }

                if(Utils::areLevelsEqual($level, Server::getInstance()->getLevelByName("sumo-ffa")))
                {
                    $kit = PracticeCore::getKits()->getKit("sumo");
                    $kit->giveTo($player, true);
                    return;
                }
            } else {
                $player->sendMessage(Utils::getPrefix() . "§cYou can't use this command while in combat.");
            }
        }
    }
}