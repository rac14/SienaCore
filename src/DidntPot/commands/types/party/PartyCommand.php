<?php

namespace DidntPot\commands\types\party;

use DidntPot\commands\parameters\BaseParameter;
use DidntPot\commands\parameters\Parameter;
use DidntPot\commands\parameters\SimpleParameter;
use DidntPot\commands\types\AdvancedCommand;
use DidntPot\forms\types\SimpleForm;
use DidntPot\parties\PracticeParty;
use DidntPot\parties\requests\PartyRequest;
use DidntPot\player\sessions\PlayerHandler;
use DidntPot\PracticeCore;
use DidntPot\utils\Utils;
use pocketmine\command\CommandSender;
use pocketmine\Player;
use pocketmine\Server;
use pocketmine\utils\TextFormat;

class PartyCommand extends AdvancedCommand
{
    public function __construct()
    {
        parent::__construct("party", "", "/party help");

        $parameters = [
            0 => [
                new BaseParameter("help", Parameter::NO_PERMISSION, "Lists all the party commands.")
            ],

            1 => [
                new BaseParameter("accept", Parameter::NO_PERMISSION, "Accept the party invitation.")
            ],

            2 => [
                new BaseParameter("decline", Parameter::NO_PERMISSION, "Decline the party invitation.")
            ],

            3 => [
                new BaseParameter("invite", Parameter::NO_PERMISSION, "Invite a player to your party."),
                new SimpleParameter("playerName", Parameter::PARAMTYPE_STRING)
            ]
        ];

        $this->setAliases(["p"]);
        $this->setParameters($parameters);
    }

    /**
     * @param CommandSender $sender
     * @param $commandLabel
     * @param array $args
     * @return bool
     */
    public function execute(CommandSender $sender, $commandLabel, array $args): bool
    {
        $msg = null;
        $player = $sender;

        if (!$player instanceof Player) {
            $sender->sendMessage("[CONSOLE] You can only use this command in-game.");
            return false;
        }

        if (self::canExecute($sender, $args)) {
            $name = strval($args[0]);
            $session = PlayerHandler::getSession($player);

            switch ($name) {
                case "help":
                    $msg = $this->getFullUsage();
                    break;

                case "accept":
                    $this->acceptParty($player);
                    break;

                case "decline":
                    $this->declineParty($player);
                    break;

                case "invite":
                    if ($session->hasParty()) $this->invitePlayer($player, $args);
                    else $player->sendMessage(Utils::getPrefix() . TextFormat::RED . "You are not in a party.");
                    break;

            }
        }

        if (!is_null($msg)) $sender->sendMessage($msg);
        return true;
    }

    private function acceptParty(Player $player)
    {
        $session = PlayerHandler::getSession($player);
        $partyManager = PracticeCore::getPartyManager();
        $party = $partyManager->getPartyFromPlayer($player);

        $requestHandler = $partyManager->getRequestHandler();
        $requests = $requestHandler->getRequestsOf($player);

        $count = count($requests);

        if ($count <= 0) {
            $player->sendMessage(PracticeParty::getPrefix() . TextFormat::RED . "You have no pending party requests.");
            return;
        }

        $request = $requests;

        if ($session->isInSpawn()) {
            $this->getPartyInbox($player, $requestHandler->getRequestsOf($player));
        } else {
            $player->sendMessage(Utils::getPrefix() . "§cYou can only use this command in spawn.");
        }
    }

    public function getPartyInbox(Player $ogPlayer, $requestInbox = [])
    {
        $form = new SimpleForm(function (Player $ogPlayer, $data = null) {
            if ($ogPlayer instanceof Player) {
                $session = PlayerHandler::getSession($ogPlayer);
                $partyManager = PracticeCore::getPartyManager();
                $party = $partyManager->getPartyFromPlayer($ogPlayer);

                $requestHandler = $partyManager->getRequestHandler();
                $requests = $requestHandler->getRequestsOf($ogPlayer);

                if ($data !== null) {
                    $index = (int)$data;

                    if ($index !== "None") {
                        $keys = array_keys($requests);
                        if (!isset($keys[$index])) return;
                        $name = $keys[$index];
                        $request = $requests[$name];

                        if ($request instanceof PartyRequest) {
                            $pName = $ogPlayer->getName();

                            $opponentName = ($pName === $request->getToName()) ? $request->getFromName() : $request->getToName();

                            if (($opponent = Server::getInstance()->getPlayer($opponentName)) instanceof Player && $opponent->isOnline()) {
                                if ($session->isInSpawn()) {
                                    $party = $request->getParty();

                                    if ($party === null) {
                                        $ogPlayer->sendMessage(PracticeParty::getPrefix() . "The party doesn't exist..");
                                        return;
                                    }

                                    $maxPlayers = $party->getMaxPlayers();

                                    $currentPlayers = (int)$party->getPlayers(true);

                                    $blacklisted = $party->isBlackListed($ogPlayer);

                                    if ($currentPlayers < $maxPlayers && !$blacklisted) {
                                        if ($partyManager->getEventManager()->isInQueue($party)) {
                                            $ogPlayer->sendMessage(PracticeParty::getPrefix() . TextFormat::RED . "The party you are trying to join is in queue.");
                                            return;
                                        } elseif ($partyManager->getEventManager()->getPartyEvent($party) !== null) {
                                            $ogPlayer->sendMessage(PracticeParty::getPrefix() . TextFormat::RED . "The party you are trying to join is in a match.");
                                            return;
                                        }

                                        $partyManager->getRequestHandler()->acceptRequest($request);
                                        $party->addPlayer($ogPlayer);
                                    } else {
                                        if ($currentPlayers >= $maxPlayers) {
                                            $ogPlayer->sendMessage(PracticeParty::getPrefix() . TextFormat::RED . "The party you are trying to join is full.");
                                        } elseif ($blacklisted) {
                                            $ogPlayer->sendMessage(PracticeParty::getPrefix() . TextFormat::RED . "The party you are trying to join has blacklisted you.");
                                        }
                                    }
                                } else $ogPlayer->sendMessage(PracticeParty::getPrefix() . TextFormat::RED . "You can only execute this command in spawn.");
                            } else {
                                $message = null;
                                if ($opponent === null || !$opponent->isOnline())
                                    $message = "The player isn't online on your current region.";

                                if ($message !== null) $ogPlayer->sendMessage(PracticeParty::getPrefix() . TextFormat::RED . $message);
                            }
                        }
                    }
                }
            }
        });

        $count = count($requestInbox);

        $form->setTitle(TextFormat::DARK_GRAY . "Party Request");

        if ($count <= 0) {
            $form->setContent(TextFormat::RED . "You have no pending party requests.");
            $ogPlayer->sendForm($form);
            return;
        }

        $keys = array_keys($requestInbox);

        foreach ($keys as $name) {
            $name = (string)$name;

            $request = $requestInbox[$name];

            $party = $request->getParty()->getName();

            if (($player = Server::getInstance()->getPlayerExact($name)) instanceof Player) {
                $name = $player->getName();
            }

            $sentBy = "Party Invite:";

            $text = $sentBy . "\n" . TextFormat::GREEN . $party;

            $form->addButton($text, 0, $request->getTexture());
        }

        $ogPlayer->sendForm($form);
    }

    private function declineParty(Player $player)
    {
        $session = PlayerHandler::getSession($player);
        $partyManager = PracticeCore::getPartyManager();
        $party = $partyManager->getPartyFromPlayer($player);

        $requestHandler = $partyManager->getRequestHandler();

        if ($session->isInSpawn()) {
            $this->getPartyInbox($player, $requestHandler->getRequestsOf($player));
        } else {
            $player->sendMessage(Utils::getPrefix() . TextFormat::RED . "You can only use this command in spawn.");
        }

    }

    public function invitePlayer(Player|CommandSender $player, array $data)
    {
        $session = PlayerHandler::getSession($player);
        $partyManager = PracticeCore::getPartyManager();
        $party = $partyManager->getPartyFromPlayer($player);

        $requestHandler = $partyManager->getRequestHandler();

        if (!$session->isInSpawn()) {
            $player->sendMessage(Utils::getPrefix() . TextFormat::RED . "You can only use this command in spawn.");
            return;
        }

        if (($to = Server::getInstance()->getPlayer($data[1])) !== null && $to instanceof Player) {
            if (PlayerHandler::getSession($to)->hasParty()) {
                $player->sendMessage(PracticeParty::getPrefix() . "§c" . $to->getName() . " is already in a party.");
            } else {
                $requestHandler->sendRequest($player, $to, $party);
            }

        } else {
            $player->sendMessage(PracticeParty::getPrefix() . "§c" . $data[1] . " isn't online on your current region.");
        }
    }
}