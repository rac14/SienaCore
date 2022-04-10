<?php

namespace DidntPot\duels;

use DidntPot\duels\groups\PracticeDuel;
use DidntPot\duels\players\QueuedPlayer;
use DidntPot\duels\requests\RequestHandler;
use DidntPot\kits\DefaultKits;
use DidntPot\PracticeCore;
use DidntPot\scoreboard\ScoreboardUtils;
use DidntPot\utils\Utils;
use JetBrains\PhpStorm\Pure;
use pocketmine\level\generator\GeneratorManager;
use pocketmine\Player;
use pocketmine\Server;

class DuelManager
{
    /* @var QueuedPlayer[]|array */
    private $queuedPlayers;

    /* @var PracticeDuel[]|array */
    private $duels;

    /* @var Server */
    private $server;

    /* @var PracticeCore */
    private $core;

    /** @var RequestHandler */
    private $requestHandler;

    #[Pure] public function __construct(PracticeCore $core)
    {
        $this->requestHandler = new RequestHandler($core);
        $this->queuedPlayers = [];
        $this->duels = [];
        $this->server = $core->getServer();
        $this->core = $core;
    }

    public function update(): void
    {
        $duels = $this->getDuels();
        foreach ($duels as $duel) $duel->update();
    }

    /**
     * @return RequestHandler
     */
    public function getRequestHandler(): RequestHandler
    {
        return $this->requestHandler;
    }

    /**
     * @param Player $player
     * @param string $queue
     * @param bool $ranked
     */
    public function placeInQueue(Player $player, string $queue, bool $ranked = false): void
    {
        $local = strtolower($player->getName());

        if (isset($this->queuedPlayers[$local])) {
            unset($this->queuedPlayers[$local]);
        }

        $theQueue = new QueuedPlayer($player, $queue, $ranked);
        $this->queuedPlayers[$local] = $theQueue;

        if ($ranked === true) $isRanked = "Ranked";
        else $isRanked = "Unranked";

        DefaultKits::sendQueueKit($player);

        PracticeCore::getScoreboardManager()->sendSpawnScoreboard($player, true,
            [
                "isRanked" => $isRanked,
                "queue" => $queue
            ]
        );

        $player->sendMessage("\n§e§l" . $isRanked . " " . $queue . "\n§r§e * Ping Range: §6[Unrestricted]\n§r  §7§oSearching for a match ...\n\n");

        if (($matched = $this->findMatch($theQueue)) !== null && $matched instanceof QueuedPlayer) {
            $matchedLocal = strtolower($matched->getPlayer()->getName());
            unset($this->queuedPlayers[$local], $this->queuedPlayers[$matchedLocal]);
            $this->placeInDuel($player, $matched->getPlayer(), $queue, $ranked);
        }
    }

    /**
     * @param QueuedPlayer $player
     * @return QueuedPlayer|null
     */
    #[Pure] public function findMatch(QueuedPlayer $player): ?QueuedPlayer
    {
        $p = $player->getPlayer();

        // TODO: Add PE Only support.
        $peOnly = false;
        $isPe = false;

        foreach ($this->queuedPlayers as $queue) {
            $queuedPlayer = $queue->getPlayer();

            $isMatch = false;

            if ($p->getDisplayName() === $queue->getPlayer()->getDisplayName()) {
                continue;
            }

            if ($queue->isRanked() === $player->isRanked() and $player->getQueue() === $queue->getQueue()) {
                $isMatch = true;

                if ($peOnly and $isPe) {
                    //$isMatch = $queuedPlayer->isOnline() and $queuedPlayer->isPe();
                }
            }

            if ($isMatch) {
                return $queue;
            }
        }

        return null;
    }

    /**
     * @param Player $p1
     * @param Player $p2
     * @param string $queue
     * @param bool $ranked
     * @param bool $foundDuel
     * @param string|null $generator
     */
    public function placeInDuel(Player $p1, Player $p2, string $queue, bool $ranked = false, bool $foundDuel = true, string $generator = null): void
    {
        $matchId = 0;

        $dataPath = $this->server->getDataPath() . '/worlds';

        while (isset($this->duels[$matchId]) or is_dir($dataPath . '/' . $matchId)) {
            $matchId++;
        }

        if ($generator == null) {
            $kit = PracticeCore::getKits()->getKit($queue);

            switch ($kit->getWorldType()) {
                case "type_sumo":
                    $generator = Utils::randomizeSumoArenas();
                    break;

                case "type_spleef":
                    $generator = Utils::CLASSIC_SPLEEF_GEN;
                    break;

                default:
                    $generator = Utils::randomizeDuelArenas();
                    break;
            }
        }

        $generatorClass = PracticeCore::getGeneratorManager()->getGeneratorClass($generator);

        $generator = GeneratorManager::getGenerator(GeneratorManager::getGeneratorName($generatorClass));

        $this->server->generateLevel("duel_$matchId", null, $generator, []);
        $this->server->loadLevel("duel_$matchId");

        $this->duels[$matchId] = new PracticeDuel("duel_$matchId", $p1, $p2, $queue, $ranked, $generatorClass);

        if ($foundDuel) {
            foreach(Server::getInstance()->getOnlinePlayers() as $players)
            {
                if(ScoreboardUtils::isPlayerSetSpawnScoreboard($players))
                {
                    PracticeCore::getScoreboardManager()->sendSpawnScoreboard($players);
                }
            }

            if($ranked === true) $isRanked = "Ranked";
            else $isRanked = "Unranked";

            $msg = "\n§l§e%ranked% %queue%§r\n§e * Map: §d%map%\n§e * Opponent: §c%player%\n§e * Their Ping: §c%player_ping%ms\n\n";
            $msg = Utils::str_replace($msg, ["%ranked%" => $isRanked, "%queue%" => $queue]);
            $msg1 = "§eGiving you §6Default Kit§e.";

            $oppMsg = Utils::str_replace($msg, ["%player%" => $p1->getName(), "%player_ping%" => $p1->getPing(), "%map%" => GeneratorManager::getGeneratorName($generatorClass)]);
            $pMsg = Utils::str_replace($msg, ["%player%" => $p2->getName(), "%player_ping%" => $p2->getPing(), "%map%" => GeneratorManager::getGeneratorName($generatorClass)]);

            $p1->sendMessage($pMsg);
            $p1->sendMessage($msg1);

            $p2->sendMessage($oppMsg);
            $p2->sendMessage($msg1);

            PracticeCore::getInstance()->getScoreboardManager()->sendDuelScoreboard($p1, $p2);
            PracticeCore::getInstance()->getScoreboardManager()->sendDuelScoreboard($p2, $p1);
        }
    }

    /**
     * @param string|Player $player
     * @return bool
     */
    #[Pure] public function isInQueue(Player|string $player): bool
    {
        $name = $player instanceof Player ? $player->getName() : $player;
        return isset($this->queuedPlayers[strtolower($name)]);
    }

    /**
     * @param Player $player
     * @param bool $sendMessage
     */
    public function removeFromQueue(Player $player, bool $sendMessage = true): void
    {
        $local = strtolower($player->getName());

        if (!isset($this->queuedPlayers[$local])) {
            return;
        }

        /** @var QueuedPlayer $queue */
        $queue = $this->queuedPlayers[$local];
        unset($this->queuedPlayers[$local]);

        DefaultKits::sendSpawnKit($player);

        PracticeCore::getScoreboardManager()->sendSpawnScoreboard($player);

        $ranked = ($queue->isRanked() ? "Ranked" : "Unranked");
        $arr = ["%ranked%" => $ranked, "%queue%" => $queue->getQueue()];

        $msg = Utils::getPrefix() . "§cYou have left the queue for %ranked% %queue%.";
        $msg = Utils::str_replace($msg, $arr);

        if ($sendMessage) {
            $player->sendMessage($msg);
        }
    }

    /**
     * @param bool $ranked
     * @param string $queue
     * @return int
     */
    #[Pure] public function getPlayersInQueue(bool $ranked, string $queue): int
    {
        $count = 0;
        foreach ($this->queuedPlayers as $pQueue) {
            if ($queue === $pQueue->getQueue() and $pQueue->isRanked() === $ranked) {
                $count++;
            }
        }

        return $count;
    }

    /**
     * @param string|Player $player
     * @return null|QueuedPlayer
     */
    #[Pure] public function getQueueOf(Player|string $player): ?QueuedPlayer
    {
        $name = $player instanceof Player ? $player->getName() : $player;

        if (isset($this->queuedPlayers[strtolower($name)])) {
            return $this->queuedPlayers[strtolower($name)];
        }

        return null;
    }

    /**
     * @return int
     */
    public function getEveryoneInQueues(): int
    {
        return count($this->queuedPlayers);
    }

    /**
     * @param bool $count
     * @return array|int
     */
    public function getDuels(bool $count = false): array|int
    {
        return $count ? count($this->duels) : $this->duels;
    }

    /**
     * @param bool $ranked
     * @param string $queue
     * @return int
     */
    #[Pure] public function getPlayersInDuel(bool $ranked, string $queue): int
    {
        $count = 0;
        foreach ($this->duels as $pQueue) {
            if ($queue === $pQueue->getQueue() and $pQueue->isRanked() === $ranked) {
                $count++;
            }
        }

        return $count;
    }

    /**
     * @param string|Player $player
     * @return PracticeDuel|null
     */
    #[Pure] public function getDuel(Player|string $player): ?PracticeDuel
    {
        foreach ($this->duels as $duel) {
            if ($duel->isPlayer($player)) {
                return $duel;
            }
        }

        return null;
    }

    /**
     * @param string $key
     *
     * Removes a duel with the given key.
     */
    public function removeDuel(string $key): void
    {
        $key = str_replace("duel_", "", $key);

        if (isset($this->duels[$key])) {
            unset($this->duels[$key]);
        }
    }

    /**
     * @param int|string $level
     * @return bool
     *
     * Determines if the level is a duel level.
     */
    public function isDuelLevel(int|string $level): bool
    {
        $name = is_int($level) ? $level : $level;
        return is_numeric($name) and isset($this->duels[intval($name)]);
    }

    /**
     * @param int|string $level
     * @return PracticeDuel|null
     *
     * Gets the duel based on the level name.
     */
    public function getDuelFromLevel(int|string $level): ?PracticeDuel
    {
        $name = is_numeric($level) ? intval($level) : $level;
        return (is_numeric($name) and isset($this->duels[$name])) ? $this->duels[$name] : null;
    }

    /**
     * @param string|Player $player
     * @return PracticeDuel|null
     *
     * Gets the duel from the spectator.
     */
    #[Pure] public function getDuelFromSpec(Player|string $player): ?PracticeDuel
    {
        foreach ($this->duels as $duel) {
            if ($duel->isSpectator($player)) {
                return $duel;
            }
        }
        return null;
    }

    /**
     * @param Player $player
     * @param PracticeDuel $duel
     *
     * Adds a spectator to a duel.
     */
    public function addSpectatorTo(Player $player, PracticeDuel $duel): void
    {
        $local = strtolower($player->getName());

        if (isset($this->queuedPlayers[$local])) {
            unset($this->queuedPlayers[$local]);
        }

        $duel->addSpectator($player);
    }
}