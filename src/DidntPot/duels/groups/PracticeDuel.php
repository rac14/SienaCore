<?php

namespace DidntPot\duels\groups;

use DidntPot\duels\level\classic\ClassicDuelGen;
use DidntPot\kits\AbstractKit;
use DidntPot\kits\DefaultKits;
use DidntPot\kits\Kits;
use DidntPot\player\info\DuelInfo;
use DidntPot\player\PlayerExtensions;
use DidntPot\player\sessions\PlayerHandler;
use DidntPot\PracticeCore;
use DidntPot\scoreboard\ScoreboardUtils;
use DidntPot\utils\Utils;
use JetBrains\PhpStorm\Pure;
use pocketmine\block\Block;
use pocketmine\block\BlockIds;
use pocketmine\level\Level;
use pocketmine\level\Position;
use pocketmine\Player;
use pocketmine\Server;
use pocketmine\utils\TextFormat;

class PracticeDuel
{
    private const MAX_DURATION_SECONDS = 60 * 15;

    /* @var Player */
    private $player1;

    /* @var Player */
    private $player2;

    /* @var string */
    private $worldId;

    /* @var AbstractKit */
    private $kit;

    /* @var bool */
    private $ranked;

    /* @var Level */
    private $level;

    /* @var int */
    private $durationSeconds;

    /* @var int */
    private $countdownSeconds;

    /* @var bool */
    private $started;

    /* @var bool */
    private $ended, $sentReplays;

    /* @var string|null */
    private $winner;

    /* @var string|null */
    private $loser;

    /* @var Player[]|array */
    private $spectators;

    /* @var Server */
    private $server;

    /* @var int */
    private $currentTicks;

    /* @var int|null */
    private $endTick;

    /** @var string */
    private $queue;

    /* @var Block[]|array */
    private $blocks;

    /* @var int[]|array */
    private $numHits;

    /* @var Position|null */
    private $centerPosition;

    /** @var int */
    private $player1Elo, $player2Elo, $player1DevOS, $player2DevOS;

    /** @var string */
    private $player1Name, $player2Name, $player1DisplayName, $player2DisplayName;

    /**
     * @param string $worldId
     * @param Player $p1
     * @param Player $p2
     * @param string $queue
     * @param bool $ranked
     * @param string $generatorClass
     */
    public function __construct(string $worldId, Player $p1, Player $p2, string $queue, bool $ranked = false, string $generatorClass = ClassicDuelGen::class)
    {
        $this->player1 = $p1;
        $this->player2 = $p2;
        $this->player1Name = $p1->getName();
        $this->player2Name = $p2->getName();
        $this->player1DisplayName = $p1->getDisplayName();
        $this->player2DisplayName = $p2->getDisplayName();
        $this->worldId = $worldId;
        $this->kit = PracticeCore::getKits()->getKit($queue);
        $this->queue = $queue;
        $this->ranked = $ranked;
        $this->server = Server::getInstance();
        $this->blocks = [];
        $this->level = $this->server->getLevelByName("$worldId");
        $this->centerPosition = null;
        $this->player1Elo = PlayerHandler::getSession($this->player1)->getElo($this->queue);
        $this->player2Elo = PlayerHandler::getSession($this->player2)->getElo($this->queue);

        $this->player1DevOS = PracticeCore::getPlayerDeviceInfo()->getPlayerOs($this->player1);
        $this->player2DevOS = PracticeCore::getPlayerDeviceInfo()->getPlayerOs($this->player2);

        $this->started = false;
        $this->ended = false;
        $this->countdownSeconds = 5;
        $this->durationSeconds = 0;
        $this->currentTicks = 0;

        $this->sentReplays = false;

        $this->endTick = null;

        $this->winner = null;
        $this->loser = null;

        $this->spectators = [];

        $this->numHits = [$this->player1Name => 0, $this->player2Name => 0];
    }

    /**
     * Updates the duel.
     */
    public function update()
    {
        $this->currentTicks++;

        $checkSeconds = $this->currentTicks % 20 === 0;

        if (!$this->player1->isOnline() or !$this->player2->isOnline()) {
            if ($this->ended) $this->endDuel();
            return;
        }

        if ($this->isCountingDown()) {
            if ($this->currentTicks === 5) $this->setInDuel();

            if ($checkSeconds) {
                /*$p1Sb = $this->player1->getScoreboardType();
                $p2Sb = $this->player2->getScoreboardType();*/

                if ($this->countdownSeconds === 5) {
                    $p1Msg = $this->getCountdownMessage(false, $this->countdownSeconds);
                    $p2Msg = $this->getCountdownMessage(false, $this->countdownSeconds);
                    $this->player1->sendMessage($p1Msg);
                    $this->player2->sendMessage($p2Msg);

                } elseif ($this->countdownSeconds !== 0) {
                    $p1Msg = $this->getJustCountdown($this->countdownSeconds);
                    $p2Msg = $this->getJustCountdown($this->countdownSeconds);
                    $this->player1->sendMessage($p1Msg);
                    $this->player2->sendMessage($p2Msg);
                } else {
                    $p1Msg = Utils::getPrefix() . "§aThe match has started, good luck.";
                    $p2Msg = Utils::getPrefix() . "§aThe match has started, good luck.";
                    $this->player1->sendMessage($p1Msg);
                    $this->player2->sendMessage($p2Msg);
                }

                if (ScoreboardUtils::isPlayerSetDuelScoreboard($this->player1)) {
                    PracticeCore::getScoreboardManager()->sendDuelScoreboard($this->player1, $this->player2);
                }

                if (ScoreboardUtils::isPlayerSetDuelScoreboard($this->player2)) {
                    PracticeCore::getScoreboardManager()->sendDuelScoreboard($this->player2, $this->player1);
                }

                if ($this->countdownSeconds <= 0) {
                    $this->started = true;
                    $this->player1->setImmobile(false);
                    $this->player2->setImmobile(false);
                }

                $this->countdownSeconds--;
            }

        } elseif ($this->isRunning()) {
            $queue = strtolower($this->queue);

            if ($queue === Kits::BOXING) {
                $this->player1->sendTip(TextFormat::GREEN . $this->player1->getName() . ": " . TextFormat::WHITE . $this->numHits[$this->player1->getName()] . TextFormat::DARK_GRAY . " | " . TextFormat::RED . $this->player2->getName() . ": " . TextFormat::WHITE . $this->numHits[$this->player2->getName()]);
                $this->player2->sendTip(TextFormat::GREEN . $this->player2->getName() . ": " . TextFormat::WHITE . $this->numHits[$this->player2->getName()] . TextFormat::DARK_GRAY . " | " . TextFormat::RED . $this->player1->getName() . ": " . TextFormat::WHITE . $this->numHits[$this->player1->getName()]);
            }

            if ($queue === Kits::SUMO or $queue === Kits::SPLEEF) {
                $minY = $queue === Kits::SUMO ? 97 : 95;

                $p1Pos = $this->player1->getPosition();
                $p2Pos = $this->player2->getPosition();

                $p1Y = $p1Pos->y;
                $p2Y = $p2Pos->y;

                if ($p1Y < $minY) {
                    $this->setEnded($this->player2);
                    return;
                }

                if ($p2Y < $minY) {
                    $this->setEnded($this->player1);
                    return;
                }
            }

            if ($checkSeconds) {
                if (ScoreboardUtils::isPlayerSetDuelScoreboard($this->player1)) {
                    PracticeCore::getScoreboardManager()->sendDuelScoreboard($this->player1, $this->player2);
                }

                if (ScoreboardUtils::isPlayerSetDuelScoreboard($this->player2)) {
                    PracticeCore::getScoreboardManager()->sendDuelScoreboard($this->player2, $this->player1);
                }

                foreach ($this->spectators as $spec) {
                    if ($spec->isOnline()) {
                        PracticeCore::getScoreboardManager()->sendDuelScoreboard($this->player1, $this->player2);
                    }
                }

                if ($this->durationSeconds >= self::MAX_DURATION_SECONDS) {
                    $this->setEnded();
                    return;
                }

                $this->durationSeconds++;
            }

        } elseif ($this->hasEnded()) {
            $diff = $this->currentTicks - $this->endTick;

            if ($diff >= 10) {
                $this->endDuel();
                return;
            }
        }
    }

    private function endDuel(): void
    {
        $this->ended = true;

        if (!$this->sentReplays) {
            $this->sentReplays = true;

            if ($this->endTick === null) $this->endTick = $this->currentTicks;

            $info = null;

            $size = count($this->spectators);

            if ($this->player1 !== null and $this->player1->isOnline()) {
                $this->sendFinalMessage($this->player1, ($this->player2 === null or !$this->player2->isOnline()) and $size <= 0);

                PlayerHandler::getSession($this->player1)->teleportPlayer($this->player1, "lobby", true, true);
            }

            if ($this->player2 !== null and $this->player2->isOnline()) {
                $this->sendFinalMessage($this->player2, $size <= 0);

                PlayerHandler::getSession($this->player2)->teleportPlayer($this->player2, "lobby", true, true);
            }

            $this->sendFinalMessageToSpecs();

            $count = 0;

            foreach ($this->spectators as $spec) {
                if ($spec !== null and $spec->isOnline()) {
                    PracticeCore::getScoreboardManager()->sendSpawnScoreboard($spec);
                    DefaultKits::sendSpawnKit($spec);
                }

                $count++;
            }

            $this->spectators = [];

            Utils::deleteLevel($this->level);

            PracticeCore::getDuelManager()->removeDuel($this->worldId);
        }
    }

    /**
     * @param Player|null $playerToSendMessage
     * @param bool $setElo
     */
    public function sendFinalMessage(?Player $playerToSendMessage, bool $setElo = false): void
    {
        if ($playerToSendMessage !== null and $playerToSendMessage->isOnline()) {
            $none = "None";

            $winner = $this->winner ?? $none;
            $loser = $this->loser ?? $none;

            $winnerMessage = "§aWinner§7: §e" . $winner;
            $loserMessage = "§cLoser§7: §e" . $loser;

            $separator = TextFormat::DARK_GRAY . '--------------------------';

            $eloChangesStr = null;

            $result = ['%', $winnerMessage, $loserMessage, '%'];

            if($this->ranked and $this->winner !== null and $this->loser !== null)
            {
                $winnerStartElo = $this->winner === $this->player1Name ? $this->player1Elo : $this->player2Elo;
                $loserStartElo = $this->loser === $this->player1Name ? $this->player1Elo : $this->player2Elo;

                $elo = Utils::updateElo($this->winner, $this->loser, $winnerStartElo, $loserStartElo, strtolower($this->queue), $setElo);

                $winnerEloChange = $elo['winner-change'];
                $loserEloChange = $elo['loser-change'];

                $wStr = str_replace('%elo-change%', $winnerEloChange, TextFormat::GREEN . "$this->winner" . TextFormat::GRAY . ' (' . TextFormat::GREEN . '+%elo-change%' . TextFormat::GRAY . ')');
                $lStr = str_replace('%elo-change%', $loserEloChange, TextFormat::RED . "$this->loser" . TextFormat::GRAY . ' (' . TextFormat::RED . '-%elo-change%' . TextFormat::GRAY . ')');

                $ec = $wStr . TextFormat::RESET . TextFormat::DARK_GRAY . ' | ' . $lStr;

                $eloChange = TextFormat::GOLD . "Elo Changes" . TextFormat::GRAY . ": " . $ec;

                $result[] = $eloChange;
                $result[] = '%';
            }

            $size = count($this->spectators);

            $spectators = TextFormat::GOLD . "Spectators" . TextFormat::GRAY . ": " . TextFormat::RESET . "$none";

            if($size > 0)
            {
                $spectators = str_replace("{$none}", '', $spectators);

                $specs = '';

                $theSize = ($size > 3) ? 3 : $size;

                $keys = array_keys($this->spectators);

                $size -= $theSize;

                for ($i = 0; $i < $theSize; $i++)
                {
                    $key = $keys[$i];
                    $spec = $this->spectators[$key];
                    $comma = $i === $theSize - 1 ? '' : TextFormat::DARK_GRAY . ', ';
                    $specs .= TextFormat::YELLOW . $spec->getName() . $comma;
                }

                $specs .= TextFormat::GRAY . ' (' . TextFormat::YELLOW . "+$size" . TextFormat::GRAY . ')';

                $spectators .= $specs;
            }

            $result[] = $spectators;
            $result[] = '%';

            $keys = array_keys($result);

            foreach ($keys as $key)
            {
                $str = $result[$key];
                if ($str === '%') $result[$key] = $separator;
            }

            foreach($result as $res)
                $playerToSendMessage->sendMessage($res);
        }
    }

    private function sendFinalMessageToSpecs(): void
    {
        foreach ($this->spectators as $spectator)
            if ($spectator->isOnline()) $this->sendFinalMessage($spectator);
    }

    /**
     * @return bool
     */
    public function isCountingDown(): bool
    {
        return !$this->started and !$this->ended;
    }

    /**
     * Sets the players in the duel.
     */
    private function setInDuel(): void
    {
        $this->player1->setGamemode(0);
        $this->player2->setGamemode(0);

        PlayerExtensions::enableFlying($this->player1, false);
        PlayerExtensions::enableFlying($this->player2, false);

        $this->player1->setImmobile(true);
        $this->player2->setImmobile(true);

        PlayerHandler::getSession($this->player1)->setCombatNameTag();
        PlayerHandler::getSession($this->player2)->setCombatNameTag();

        PlayerExtensions::clearInventory($this->player1);
        PlayerExtensions::clearInventory($this->player2);

        $level = $this->level;

        $queue = strtolower($this->queue);

        $x = ($queue === Kits::SUMO) ? 9 : 24;
        $z = ($queue === Kits::SUMO) ? 5 : 40;

        $y = 100;

        $p1Pos = new Position($x, $y, $z, $level);

        Utils::onChunkGenerated($level, $x >> 4, $z >> 4, function () use ($p1Pos) {
            $this->player1->teleport($p1Pos);
        });

        if ($queue !== Kits::SUMO) {
            $z = 10;
        } else {
            $x = 1;
        }

        $p2Pos = new Position($x, $y, $z, $level);

        $p2x = $p2Pos->x;
        $p2z = $p2Pos->z;

        $p1x = $p1Pos->x;
        $p1z = $p1Pos->z;

        $this->centerPosition = new Position(intval((($p2x + $p1x) / 2)), intval($p1Pos->y), intval((($p2z + $p1z) / 2)), $this->level);

        Utils::onChunkGenerated($level, $x >> 4, $z >> 4, function () use ($p2Pos) {
            $this->player2->teleport($p2Pos);
        });

        $this->kit->giveTo($this->player1, false);
        $this->kit->giveTo($this->player2, false);

        $p1Level = $this->player1->getLevel();
        $p2Level = $this->player2->getLevel();

        if ($p1Level->getName() !== $level->getName())
            $this->player1->teleport($p1Pos);

        if ($p2Level->getName() !== $level->getName())
            $this->player2->teleport($p2Pos);
    }

    /**
     * @param bool $title
     * @param int $countdown
     * @return string
     */
    private function getCountdownMessage(bool $title, int $countdown): string
    {
        if (!$title)
            $message = TextFormat::YELLOW . $countdown . '...';
        else {
            $message = TextFormat::YELLOW . "$countdown...";
        }

        return $message;
    }

    /**
     * @param int $countdown
     * @return string
     */
    private function getJustCountdown(int $countdown): string
    {
        return TextFormat::YELLOW . "$countdown...";
    }

    /**
     * @return bool
     */
    public function isRunning(): bool
    {
        return $this->started and !$this->ended;
    }

    /**
     * @param Player|null $winner
     * @param bool $logDuelHistory
     *
     * Sets the duel as ended.
     */
    public function setEnded(Player $winner = null, bool $logDuelHistory = true): void
    {
        $online = $this->player1 !== null and $this->player1->isOnline() and $this->player2 !== null and $this->player2->isOnline();

        if ($winner !== null and $this->isPlayer($winner) and $logDuelHistory) {
            $this->winner = $winner->getName();
            $loser = $this->getOpponent($this->winner);
            $this->loser = $loser->getName();

            $winnerSession = PlayerHandler::getSession($winner);
            $loserSession = PlayerHandler::getSession($loser);

            $winnerInfo = new DuelInfo($winner, $this->queue, $this->ranked, $this->numHits[$winner->getName()]);

            $loserInfo = new DuelInfo($loser, $this->queue, $this->ranked, $this->numHits[$loser->getName()]);

            $winnerSession->addToDuelHistory($winnerInfo, $loserInfo);
            $loserSession->addToDuelHistory($winnerInfo, $loserInfo);

        } elseif ($winner === null and $online and $logDuelHistory) {
            $p1Info = new DuelInfo($this->player1, $this->queue, $this->ranked, $this->numHits[$this->player1->getName()]);
            $p2Info = new DuelInfo($this->player2, $this->queue, $this->ranked, $this->numHits[$this->player2->getName()]);

            $player1Session = PlayerHandler::getSession($this->player1);
            $player2Session = PlayerHandler::getSession($this->player2);

            $player1Session->addToDuelHistory($p1Info, $p2Info, true);
            $player2Session->addToDuelHistory($p2Info, $p1Info, true);
        }

        $player1Session = PlayerHandler::getSession($this->player1);
        $player2Session = PlayerHandler::getSession($this->player2);

        $player1Session->setNormalNameTag();
        $player2Session->setNormalNameTag();

        $this->ended = true;
        $this->endTick = $this->currentTicks;
    }

    /**
     * @param string|Player $player
     * @return bool
     */
    #[Pure] public function isPlayer(Player|string $player): bool
    {
        $name = $player instanceof Player ? $player->getName() : $player;
        return $this->player1Name === $name or $this->player2Name === $name;
    }

    /**
     * @param string|Player $player
     * @return Player|null
     */
    #[Pure] public function getOpponent(Player|string $player): ?Player
    {
        $result = null;
        $name = $player instanceof Player ? $player->getName() : $player;

        if ($this->isPlayer($player)) {
            if ($name === $this->player1Name)
                $result = $this->player2;
            else $result = $this->player1;
        }

        return $result;
    }

    /**
     * @return bool
     */
    public function hasEnded(): bool
    {
        return $this->ended;
    }

    /**
     * @return string
     */
    public function getDuration(): string
    {
        $seconds = $this->durationSeconds % 60;
        $minutes = intval($this->durationSeconds / 60);

        $result = '%min%:%sec%';

        $secStr = "$seconds";
        $minStr = "$minutes";

        if ($seconds < 10)
            $secStr = '0' . $seconds;

        if ($minutes < 10)
            $minStr = '0' . $minutes;

        return str_replace('%min%', $minStr, str_replace('%sec%', $secStr, $result));
    }

    /**
     * @param Player $player
     */
    public function addSpectator(Player $player): void
    {
        $name = $player->getName();

        $local = strtolower($name);

        $this->spectators[$local] = $player;

        DefaultKits::sendSpecKit($player);

        $player->teleport($this->centerPosition);

        // TODO: SPEC
        /*$this->broadcastMessage(Language::DUELS_SPECTATOR_ADD, ["name" => $name]);
        $player->setScoreboard(Scoreboard::SCOREBOARD_SPECTATOR);*/
    }

    /**
     * @param string|Player $player
     * @param bool $teleportToSpawn
     * @param bool $sendMessage
     */
    public function removeSpectator(Player|string $player, bool $teleportToSpawn = true, bool $sendMessage = true): void
    {
        $name = $player instanceof Player ? $player->getName() : $player;

        $local = strtolower($name);

        if (isset($this->spectators[$local])) {
            $player = $this->spectators[$local];

            if ($player->isOnline()) {
                Utils::resetPlayer($player, false, $teleportToSpawn);
                if ($teleportToSpawn) DefaultKits::sendSpawnKit($player);
            }

            unset($this->spectators[$local]);

            // TODO: SPEC
            if ($sendMessage) $this->broadcastMessage("DUELS_SPECTATOR_LEAVE");
        }
    }

    /**
     * @param string $message
     * @param bool $sendToSpectators
     */
    private function broadcastMessage(string $message, bool $sendToSpectators = true): void
    {
        if ($this->player1 !== null and $this->player1->isOnline())
            $this->player1->sendMessage($message);

        if ($this->player2 !== null and $this->player2->isOnline())
            $this->player2->sendMessage($message);

        if ($sendToSpectators) {
            foreach ($this->spectators as $spectator) {
                if ($spectator->isOnline()) $spectator->sendMessage($message);
            }
        }
    }

    /**
     * @param Player|null $killer
     * @param Player|null $killed
     */
    public function broadcastDeathMessage(?Player $killer, ?Player $killed): void
    {
        $message = '';

        if ($killed !== null and $killed->isOnline()) {
            $message = TextFormat::RED . $killed->getName() . TextFormat::YELLOW . ' died.';
            if ($killer !== null and $killed->isOnline())
                $message = TextFormat::RED . $killed->getName() . TextFormat::YELLOW . ' was killed by ' . TextFormat::RED . $killer->getName() . TextFormat::YELLOW . ".";
        }

        if ($message !== '')
            $this->broadcastMessage($message);
    }

    /**
     * @param Block $block
     * @param bool $break
     * @return bool
     */
    #[Pure] public function canPlaceBlock(Block $block, bool $break = false): bool
    {
        $queue = strtolower($this->queue);

        if (!$this->isRunning() or $queue === Kits::SUMO)
            return false;
        elseif ($queue === Kits::SPLEEF) {
            return $this->isRunning() and $break and $block->getId() === BlockIds::SNOW_BLOCK;
        }

        $blocks = [
            BlockIds::COBBLESTONE => true,
            BlockIds::WOODEN_PLANKS => true,
        ];

        return isset($blocks[$block->getId()]);
    }

    /**
     * @return bool
     */
    #[Pure] public function cantDamagePlayers(): bool
    {
        return !$this->kit->canDamageOthers();
    }

    /**
     * @param string|Player $player
     * @param float $damage
     */
    public function addHitTo(Player|string $player, float $damage): void
    {
        $name = $player instanceof Player ? $player->getName() : $player;

        if (isset($this->numHits[$name])) {
            $hits = $this->numHits[$name];
            $this->numHits[$name] = $hits + 1;

            if (strtolower($this->queue) === Kits::BOXING)
            {
                $this->player1->sendTip(TextFormat::GREEN . $this->player1->getName() . ": " . TextFormat::WHITE . $this->numHits[$this->player1->getName()] . TextFormat::DARK_GRAY . " | " . TextFormat::RED . $this->player2->getName() . ": " . TextFormat::WHITE . $this->numHits[$this->player2->getName()]);
                $this->player2->sendTip(TextFormat::GREEN . $this->player2->getName() . ": " . TextFormat::WHITE . $this->numHits[$this->player2->getName()] . TextFormat::DARK_GRAY . " | " . TextFormat::RED . $this->player1->getName() . ": " . TextFormat::WHITE . $this->numHits[$this->player1->getName()]);

                if ($this->numHits[$name] >= 100)
                {
                    $this->setEnded(Server::getInstance()->getPlayer($name));
                }
            }
        }
    }

    /**
     * @param string|Player $player
     * @return bool
     */
    #[Pure] public function isSpectator(Player|string $player): bool
    {
        $name = $player instanceof Player ? $player->getName() : $player;
        $local = strtolower($name);
        return isset($this->spectators[$local]);
    }

    /**
     * @return string
     */
    #[Pure] public function getTexture(): string
    {
        if ($this->kit !== null)
            return $this->kit->getTexture();
        return '';
    }

    /**
     * @param PracticeDuel $duel
     * @return bool
     */
    #[Pure] public function equals(PracticeDuel $duel): bool
    {
        return $duel->isRanked() === $this->ranked and $duel->getP1Name() === $this->player1Name
            and $duel->getP2Name() === $this->player2Name and $duel->getQueue() === $this->queue
            and $duel->getWorldId() === $this->worldId;
    }

    /**
     * @return bool
     */
    public function isRanked(): bool
    {
        return $this->ranked;
    }

    /**
     * @return string
     */
    public function getP1Name(): string
    {
        return $this->player1Name;
    }

    /**
     * @return string
     */
    public function getP2Name(): string
    {
        return $this->player2Name;
    }

    /**
     * @return string
     */
    public function getQueue(): string
    {
        return $this->queue;
    }

    /**
     * @return string
     */
    public function getWorldId(): string
    {
        return $this->worldId;
    }

    /**
     * @return bool
     *
     * Determines whether the duel is a spleef duel.
     */
    public function isSpleef(): bool
    {
        return strtolower($this->queue) === Kits::SPLEEF;
    }
}