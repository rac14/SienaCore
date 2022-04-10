<?php

namespace DidntPot\parties\events\types;

use DidntPot\duels\level\duel\RiverDuelGen;
use DidntPot\kits\AbstractKit;
use DidntPot\kits\DefaultKits;
use DidntPot\kits\Kits;
use DidntPot\parties\events\PartyEvent;
use DidntPot\parties\events\types\match\data\PracticeTeam;
use DidntPot\parties\PracticeParty;
use DidntPot\player\PlayerExtensions;
use DidntPot\player\sessions\PlayerHandler;
use DidntPot\PracticeCore;
use DidntPot\utils\Utils;
use JetBrains\PhpStorm\Pure;
use pocketmine\level\Level;
use pocketmine\level\Position;
use pocketmine\network\mcpe\protocol\types\GameMode;
use pocketmine\Player;
use pocketmine\Server;

class PartyVsParty extends PartyEvent
{
    /** @var float|int */
    private const MAX_DURATION_SECONDS = 60 * 10;

    /* @var int */
    private int $currentTick;

    /* @var int */
    private int $playersPerTeam;

    /* @var PracticeParty */
    private PracticeParty $party;

    /* @var Player[]|PracticeTeam[] */
    private array $participants;

    /* @var Player[] */
    private array $players;

    /* @var int */
    private int $worldId;

    /* @var AbstractKit */
    private AbstractKit $kit;

    /* @var Level */
    private Level $level;

    /* @var int */
    private int $durationSeconds;

    /* @var int */
    private int $countdownSeconds;

    /* @var bool */
    private bool $started;

    /* @var bool */
    private bool $ended;

    private $winner;

    /* @var Player[]|array */
    private array $spectators;

    /* @var Server */
    private Server $server;

    /* @var int */
    private int $currentTicks;

    /* @var int|null */
    private ?int $endTick;

    /* @var Position|null */
    private ?Position $centerPosition;

    /** @var string */
    private string $queue;

    /**
     * @param int $worldId
     * @param PracticeParty $party
     * @param int $playersPerTeam
     * @param string $queue
     * @param string $generatorClass
     */
    public function __construct(int $worldId, PracticeParty $party, int $playersPerTeam, string $queue, string $generatorClass = RiverDuelGen::class)
    {
        parent::__construct(self::EVENT_PARTY_VS_PARTY);
        $this->playersPerTeam = $playersPerTeam;
        $this->queue = $queue;
        $this->party = $party;
        $this->worldId = $worldId;
        $this->participants = [];
        $this->spectators = [];

        $this->countdownSeconds = 5;
        $this->durationSeconds = 0;
        $this->currentTicks = 0;

        $this->server = Server::getInstance();
        $this->level = $this->server->getLevelByName("$worldId");

        $this->started = false;
        $this->ended = false;

        $this->kit = PracticeCore::getKits()->getKit($queue);

        $this->players = $party->getPlayers();

        shuffle($this->players);

        $count = 0;

        $size = count($this->players);

        $colors = [];

        if ($playersPerTeam > 1)
        {
            $team = new PracticeTeam();

            foreach ($this->players as $player)
            {
                $count++;
                $team->addToTeam($player);

                if ($count % $this->playersPerTeam === 0 || $count === $size) {
                    $this->participants[] = $team;
                    $colors[] = $team->getTeamColor();
                    $team = new PracticeTeam($colors);
                }
            }

        } else $this->participants = $this->players;
    }

    /**
     * Updates the party event each tick.
     */
    public function update(): void
    {
        $this->currentTicks++;

        $checkSeconds = $this->currentTicks % 20 === 0;

        if ($this->currentTicks > 5 && !$this->hasEnded() && count($this->participants) <= 1) {
            if (count($this->participants) === 0) {
                $this->setEnded();
                return;
            }

            foreach ($this->participants as $key => $winner) {
                $this->setEnded($winner);
                unset($this->participants[$key]);
                return;
            }
        }

        if ($this->isCountingDown()) {
            if ($this->currentTicks === 5) $this->setInDuel();

            if ($checkSeconds) {
                $participants = $this->participants;

                foreach ($participants as $p) {
                    if ($p instanceof PracticeTeam) {
                        $players = $p->getPlayers();

                        foreach ($players as $player) {
                            // TODO:
                            /*if(
                                $Sb->getScoreboardType() !== Scoreboard::SCOREBOARD_NONE &&
                                $Sb->getScoreboardType() !== Scoreboard::SCOREBOARD_EVENT_DUEL
                            )
                                $Sb->setScoreboard(Scoreboard::SCOREBOARD_EVENT_DUEL);*/
                        }
                    } elseif ($p instanceof Player) {
                        // TODO:
                        /*if(
                            $Sb->getScoreboardType() !== Scoreboard::SCOREBOARD_NONE &&
                            $Sb->getScoreboardType() !== Scoreboard::SCOREBOARD_EVENT_DUEL
                        )
                            $Sb->setScoreboard(Scoreboard::SCOREBOARD_EVENT_DUEL);*/
                    }
                }

                if ($this->countdownSeconds === 5) {
                    foreach ($participants as $p) {
                        if ($p instanceof PracticeTeam) {
                            $players = $p->getPlayers();

                            foreach ($players as $player) {
                                $msg = $this->getCountdownMessage(false, $this->countdownSeconds);
                                $player->sendMessage($msg);
                            }

                        } elseif ($p instanceof Player) {
                            $msg = $this->getCountdownMessage(false, $this->countdownSeconds);
                            $p->sendMessage($msg);
                        }
                    }

                } elseif ($this->countdownSeconds !== 0) {
                    foreach ($participants as $p) {
                        if ($p instanceof PracticeTeam) {
                            $players = $p->getPlayers();
                            foreach ($players as $player) {
                                $msg = $this->getJustCountdown($this->countdownSeconds);
                                $player->sendMessage($msg);
                            }
                        } elseif ($p instanceof Player) {
                            $msg = $this->getJustCountdown($this->countdownSeconds);
                            $p->sendMessage($msg);
                        }
                    }
                } else {
                    foreach ($participants as $p) {
                        if ($p instanceof PracticeTeam) {
                            $players = $p->getPlayers();

                            foreach ($players as $player) {
                                $msg = PracticeParty::getPrefix() . "§aThe match has started, good luck.";
                                $player->sendMessage($msg);
                            }
                        } elseif ($p instanceof Player
                        ) {
                            $msg = PracticeParty::getPrefix() . "§aThe match has started, good luck.";
                            $p->sendMessage($msg);
                        }
                    }
                }

                if ($this->countdownSeconds <= 0) {
                    $this->started = true;

                    foreach ($participants as $p) {
                        if ($p instanceof PracticeTeam) {
                            $players = $p->getPlayers();
                            foreach ($players as $player) {
                                $player->setImmobile(false);
                            }
                        } elseif ($p instanceof Player) {
                            $p->setImmobile(false);
                        }
                    }
                }

                $this->countdownSeconds--;
            }
        } elseif ($this->isRunning()) {
            if ($this->getKit() === 'Knock') {
                foreach ($this->participants as $p) {
                    if ($p instanceof PracticeTeam) {
                        $players = $p->getPlayers();
                        foreach ($players as $player) {
                            if ($player->getFloorY() <= 0) {
                                $this->addSpectator($player);
                            }
                        }
                    } elseif ($p instanceof Player && $p->getFloorY() <= 0) {
                        $this->addSpectator($p);
                    }
                }
            } elseif ($this->getKit() === 'Build') {
                foreach ($this->participants as $p) {
                    if ($p instanceof PracticeTeam) {
                        $players = $p->getPlayers();

                        foreach ($players as $player) {
                            if ($player->getFloorY() <= 57 || $player->getFloorY() >= 87) {
                                $this->addSpectator($player);
                            }
                        }
                    } elseif ($p instanceof Player && ($p->getFloorY() <= 57 || $p->getFloorY() >= 87)) {
                        $this->addSpectator($p);
                    }
                }
            } elseif ($this->getKit() === 'Sumo') {
                foreach ($this->participants as $p) {
                    if ($p instanceof PracticeTeam) {
                        $players = $p->getPlayers();
                        foreach ($players as $player) {
                            if ($player->getFloorY() <= 50) {
                                $this->addSpectator($player);
                            }
                        }
                    } elseif ($p instanceof Player && $p->getFloorY() <= 50) {
                        $this->addSpectator($p);
                    }
                }
            }

            if ($checkSeconds) {
                foreach ($this->participants as $p) {
                    if ($p instanceof PracticeTeam) {
                        $players = $p->getPlayers();

                        foreach ($players as $player) {
                            // TODO:
                            /*$Duration = Language::DUELS_SCOREBOARD_DURATION;
                            $DurationStr = TextFormat::WHITE . ' ' . $Duration . ': ' . $this->getDuration();
                            //$player->getScoreboardInfo()->updateLineOfScoreboard(1, $DurationStr);*/
                        }
                    } elseif ($p instanceof Player) {
                        // TODO:
                        /*$Duration = Language::DUELS_SCOREBOARD_DURATION;
                        $DurationStr = TextFormat::WHITE . ' ' . $Duration . ': ' . $this->getDuration();
                        //$p->getScoreboardInfo()->updateLineOfScoreboard(1, $DurationStr);*/
                    }
                }

                foreach ($this->spectators as $spec) {
                    if ($spec->isOnline()) {
                        // TODO:
                        /*$specLang = $spec->getLanguageInfo()->getLanguage();
                        $specDuration = TextFormat::WHITE . $specLang->scoreboard(Language::DUELS_SCOREBOARD_DURATION);
                        $specDurationStr = TextFormat::WHITE . ' ' . $specDuration . ': ' . $this->getDuration();
                        $spec->getScoreboardInfo()->updateLineOfScoreboard(1, $specDurationStr);*/
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
            if ($diff >= 30) {
                $this->endDuel();
                return;
            }
        }
    }

    /**
     * @return bool
     */
    public function hasEnded(): bool
    {
        return $this->ended;
    }

    /**
     * @param PracticeParty|null $winner
     *
     * Sets the duel as ended.
     */
    public function setEnded(PracticeParty $winner = null): void
    {
        $this->winner = null;

        if ($winner instanceof PracticeTeam) {
            $this->winner = $winner;
        } elseif ($winner instanceof Player) {
            $this->winner = $winner;
        }

        $this->ended = true;
        $this->endTick = $this->currentTicks;
    }

    /**
     * @return bool
     */
    public function isCountingDown(): bool
    {
        return !$this->started && !$this->ended;
    }

    /**
     * Sets the players in the duel.
     */
    private function setInDuel(): void
    {
        $exSpawns = [];

        $level = $this->level;

        $queue = strtolower($this->queue);

        $x = ($queue === Kits::SUMO) ? 9 : 24;
        $z = ($queue === Kits::SUMO) ? 5 : 40;

        $y = 100;

        $pPos = new Position($x, $y, $z, $level);

        $participants = $this->participants;

        foreach ($participants as $p) {
            if ($p instanceof PracticeTeam) {
                $players = $p->getPlayers();

                foreach ($players as $player) {
                    $this->setPlayerInDuel($spawn, $player);
                }

                $exSpawns[] = $spawn;
                $spawn = $this->arena->randomSpawnExclude($exSpawns);

            } elseif ($p instanceof Player) {
                $this->setPlayerInDuel($spawn, $p);
                $exSpawns[] = $spawn;
                $spawn = $this->arena->randomSpawnExclude($exSpawns);
            }
        }
    }

    private function setPlayerInDuel(int $spawn, Player $player)
    {
        if ($player->isOnline()) {
            $player->setGamemode(0);
            PlayerExtensions::enableFlying($player, false);
            PlayerExtensions::clearAll($player);
            $player->setImmobile(true);
            $this->arena->teleportPlayerByKey($player, $spawn, $this->level);
        }
    }

    /**
     * @param bool $title
     * @param int $countdown
     *
     * @return string
     */
    private function getCountdownMessage(bool $title, int $countdown): string
    {
        if (!$title)
            $message = $countdown . '...';
        else {
            $message = "$countdown...";
        }

        return $message;
    }

    /**
     * @param int $countdown
     * @return string
     */
    private function getJustCountdown(int $countdown): string
    {
        return "$countdown...";
    }

    /**
     * @return bool
     */
    public function isRunning(): bool
    {
        return $this->started && !$this->ended;
    }

    /**
     * @return string
     */
    #[Pure] public function getKit(): string
    {
        return $this->kit->getName();
    }

    /**
     * @param Player $player
     */
    public function addSpectator(Player $player): void
    {
        $name = $player->getDisplayName();
        $local = strtolower($name);

        if (!isset($this->spectators[$local])) {
            $team = $this->getTeam($player);
            if ($team instanceof PracticeTeam && $team->isInTeam($player)) {
                $color = $team->getTeamColor();

                foreach ($this->players as $p) {
                    if ($p->isOnline()) {
                        $p->sendMessage(PracticeParty::getPrefix() . "Language::PARTIES_DUEL_ELIMINATED");
                    }
                }

                $team->removeFromTeam($player);

                if (count($team->getPlayers()) === 0) {
                    foreach ($this->participants as $key => $temp) {
                        if ($temp === $team) unset($this->participants[$key]);
                    }
                }

            } elseif ($team === null) {
                foreach ($this->players as $p) {
                    if ($p->isOnline()) {
                        $p->sendMessage(PracticeParty::getPrefix() . "Language::PARTIES_DUEL_ELIMINATED");
                    }
                }

                foreach ($this->participants as $key => $member) {
                    if ($member->getName() === $player->getName()) unset($this->participants[$key]);
                }
            }

            PlayerExtensions::clearAll($player);
            $this->spectators[$local] = $player;
            $player->setGamemode(GameMode::SURVIVAL_VIEWER);
            /*$player->getExtensions()->setFakeSpectator();
            $player->getScoreboardInfo()->setScoreboard(Scoreboard::SCOREBOARD_SPECTATOR);*/
        }
    }

    /**
     * @param string|Player $player
     *
     * @return PracticeTeam|null
     */
    #[Pure] public function getTeam(Player|string $player): ?PracticeTeam
    {
        if ($this->playersPerTeam === 1) return null;

        foreach ($this->participants as $team) {
            if ($team->isInTeam($player)) return $team;
        }

        return null;
    }

    private function endDuel(): void
    {
        $this->ended = true;

        if ($this->endTick === null) $this->endTick = $this->currentTicks;

        if ($this->party !== null) {
            $members = $this->party->getPlayers();

            foreach ($members as $player) {
                if ($player->isOnline()) {
                    $this->sendFinalMessage($player);

                    PracticeCore::getScoreboardManager()->sendSpawnScoreboard($player);

                    Utils::resetPlayer($player, true, $player->isAlive());
                    PlayerHandler::getSession($player)->hasParty() ? DefaultKits::sendPartyKit($player) : DefaultKits::sendSpawnKit($player);
                }
            }
        }

        $this->spectators = [];

        Utils::deleteLevel($this->level);
        PracticeCore::getPartyManager()->getEventManager()->removeDuel($this->worldId);
    }

    /**
     * @param Player|null $playerToSendMessage
     */
    public function sendFinalMessage(?Player $playerToSendMessage): void
    {
        if ($playerToSendMessage !== null && $playerToSendMessage->isOnline()) {
            $winner = $this->winner ?? "None";

            if ($winner instanceof PracticeTeam) {
                $winnerMessage = PracticeParty::getPrefix() . "Language::DUELS_MESSAGE_WINNER";
            } elseif ($winner instanceof Player) {
                $winnerMessage = PracticeParty::getPrefix() . "Language::DUELS_MESSAGE_WINNER";
            } else {
                $winnerMessage = PracticeParty::getPrefix() . "Language::DUELS_MESSAGE_WINNER";
            }

            $separator = '  ';
            $result = ['%', $winnerMessage, '%'];
            $keys = array_keys($result);

            foreach ($keys as $key) {
                $str = $result[$key];
                if ($str === '%') $result[$key] = $separator;
            }

            foreach ($result as $res) {
                $playerToSendMessage->sendMessage($res);
            }
        }
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
     * @param string|Player $player
     *
     * @return bool
     */
    #[Pure] public function isSpectator(Player|string $player): bool
    {
        $name = $player instanceof Player ? $player->getDisplayName() : $player;
        $local = strtolower($name);
        return isset($this->spectators[$local]);
    }

    /**
     * @return int
     */
    public function getWorldId(): int
    {
        return $this->worldId;
    }

    /**
     * @param string|PracticeParty $party
     *
     * @return bool
     */
    #[Pure] public function isParty(PracticeParty|string $party): bool
    {
        $name = $party instanceof PracticeParty ? $party->getName() : $party;
        return $this->party->getName() === $name;
    }

    /**
     * @param Player $player
     */
    public function removeFromEvent(Player $player): void
    {
        $team = $this->getTeam($player);

        if ($team instanceof PracticeTeam && $team->isInTeam($player)) {
            $color = $team->getTeamColor();

            foreach ($this->players as $p) {
                if ($p->isOnline()) $p->sendMessage(PracticeParty::getPrefix() . "Language::PARTIES_DUEL_ELIMINATED");
            }

            $team->removeFromTeam($player);

            if (count($team->getPlayers()) === 0) {
                foreach ($this->participants as $key => $temp) {
                    if ($temp === $team) unset($this->participants[$key]);
                }
            }

        } elseif ($team === null) {
            foreach ($this->players as $p) {
                if ($p->isOnline()) {
                    $p->sendMessage(PracticeParty::getPrefix() . "Language::PARTIES_DUEL_ELIMINATED");
                }
            }

            foreach ($this->participants as $key => $member) {
                if ($member instanceof Player && $member->getName() === $player->getName()) unset($this->participants[$key]);
            }
        }
    }
}