<?php

namespace DidntPot\parties\events\types;

use DidntPot\arenas\DuelArena;
use DidntPot\arenas\GamesArena;
use DidntPot\kits\AbstractKit;
use DidntPot\kits\DefaultKits;
use DidntPot\kits\Kit;
use DidntPot\parties\events\PartyEvent;
use DidntPot\parties\events\types\match\data\PracticeTeam;
use DidntPot\parties\PracticeParty;
use DidntPot\player\Human;
use DidntPot\player\PlayerExtensions;
use DidntPot\player\sessions\PlayerHandler;
use DidntPot\PracticeCore;
use DidntPot\utils\Utils;
use JetBrains\PhpStorm\Pure;
use pocketmine\block\Block;
use pocketmine\block\BlockIds;
use pocketmine\level\Level;
use pocketmine\level\Position;
use pocketmine\math\Vector3;
use pocketmine\Player;
use pocketmine\utils\TextFormat;

class PartyDuel extends PartyEvent
{
    private const MAX_DURATION_SECONDS = 60 * 10;

    /* @var int */
    private $currentTick;

    /* @var int */
    private $playersPerTeam;

    /* @var PracticeParty */
    private $party;

    /* @var Human[]|PracticeTeam[] */
    private $participants;

    /* @var string */
    private $queue;

    /* @var AbstractKit */
    private $kit;

    /* @var int */
    private $countdownSeconds;

    /* @var int */
    private $durationSeconds;

    /* @var bool */
    private $ended;

    /* @var bool */
    private $started;

    /* @var Level */
    private $level;

    public function __construct(PracticeParty $party, string $queue, int $playersPerTeam, Level $level)
    {
        parent::__construct(self::EVENT_DUEL);
        $this->currentTick = 0;
        $this->playersPerTeam = $playersPerTeam;
        $this->party = $party;
        $this->participants = [];

        $this->countdownSeconds = 10;

        $this->level = $level;

        $this->durationSeconds = 0;

        $this->started = false;
        $this->ended = false;

        $this->queue = $queue;

        $this->kit = PracticeCore::getKits()->getKit($queue);

        $players = $party->getPlayers();

        $count = 0;

        $size = count($players) - 1;

        if ($playersPerTeam > 1)
        {
            $team = new PracticeTeam();

            foreach($players as $p)
            {
                if($count % $this->playersPerTeam === 0 or $count === $size)
                {
                    $this->participants[] = $team;

                    $colors = [];

                    foreach ($this->participants as $participant)
                    {
                        if ($participant instanceof PracticeTeam)
                            $colors[] = $participant->getTeamColor();
                    }

                    if($count < $size) $team = new PracticeTeam($colors);
                }

                $team->addToTeam($p);

                $count++;
            }

        } else $this->participants = $players;
    }

    /**
     * Updates the party event each tick.
     */
    public function update(): void
    {
        $this->currentTick++;

        if (!$this->team1->getPlayers() instanceof Player) return;
        if (!$this->team2->getPlayers() instanceof Player) return;

        $checkSeconds = $this->currentTick % 20 === 0;

        if ($this->currentTick > 5 && !$this->hasEnded() && (count($this->team1->getPlayers()) === 0 || count($this->team2->getPlayers()) === 0)) {
            if (count($this->team1->getPlayers()) === 0) {
                $this->setEnded($this->party2);
            } elseif (count($this->team2->getPlayers()) === 0) {
                $this->setEnded($this->party1);
            }
        }

        if ($this->isCountingDown()) {
            if ($this->currentTick === 5) $this->setInDuel();

            if ($checkSeconds) {
                $members1 = $this->team1->getPlayers();
                $members2 = $this->team2->getPlayers();

                foreach ($members1 as $player) {
                    if ($player->isOnline()) {
                        // TODO:
                        /*$Sb = $player->getScoreboardInfo();
                        if($Sb->getScoreboardType() !== Scoreboard::SCOREBOARD_NONE && $Sb->getScoreboardType() !== Scoreboard::SCOREBOARD_DUEL){
                            $Sb->setScoreboard(Scoreboard::SCOREBOARD_DUEL);
                        }*/
                    }
                }

                foreach ($members2 as $player) {
                    if ($player->isOnline()) {
                        // TODO:
                        /*$Sb = $player->getScoreboardInfo();
                        if($Sb->getScoreboardType() !== Scoreboard::SCOREBOARD_NONE && $Sb->getScoreboardType() !== Scoreboard::SCOREBOARD_DUEL){
                            $Sb->setScoreboard(Scoreboard::SCOREBOARD_DUEL);
                        }*/
                    }
                }

                if ($this->countdownSeconds === 5) {
                    foreach ($members1 as $player) {
                        if ($player->isOnline()) {
                            $msg = $this->getCountdownMessage(true, $this->countdownSeconds);
                            $player->sendMessage($msg);
                        }
                    }

                    foreach ($members2 as $player) {
                        if ($player->isOnline()) {
                            $msg = $this->getCountdownMessage(true, $this->countdownSeconds);
                            $player->sendMessage($msg);
                        }
                    }

                } elseif ($this->countdownSeconds !== 0) {
                    foreach ($members1 as $player) {
                        if ($player->isOnline()) {
                            $msg = $this->getJustCountdown($this->countdownSeconds);
                            $player->sendMessage($msg);
                        }
                    }

                    foreach ($members2 as $player) {
                        if ($player->isOnline()) {
                            $msg = $this->getJustCountdown($this->countdownSeconds);
                            $player->sendMessage($msg);
                        }
                    }
                } else {
                    foreach ($members1 as $player) {
                        if ($player->isOnline()) {
                            $msg = PracticeParty::getPrefix() . "§aThe match has started, good luck.";
                            $player->sendMessage($msg);
                        }
                    }

                    foreach ($members2 as $player) {
                        if ($player->isOnline()) {
                            $msg = PracticeParty::getPrefix() . "§aThe match has started, good luck.";
                            $player->sendMessage($msg);
                        }
                    }
                }

                if ($this->countdownSeconds <= 0) {
                    $this->started = true;

                    foreach ($members1 as $player) {
                        if ($player->isOnline()) {
                            $player->setImmobile(false);
                        }
                    }

                    foreach ($members2 as $player) {
                        if ($player->isOnline()) {
                            $player->setImmobile(false);
                        }
                    }
                }

                $this->countdownSeconds--;
            }
        } elseif ($this->isRunning()) {
            $queue = strtolower($this->queue);

            if ($queue === "Sumo") {
                $spawnPos = $this->arena->getPlayerPos();
                $minY = $spawnPos->getY() - 5;

                $members1 = $this->team1->getPlayers();
                $members2 = $this->team2->getPlayers();

                foreach ($members1 as $member) {
                    if ($member->isOnline()) {
                        $pos = $member->getPosition();
                        $y = $pos->y;

                        if ($y < $minY) {
                            // TODO:
                            //$this->addSpectator($member);
                        }
                    }
                }

                foreach ($members2 as $member) {
                    if ($member->isOnline()) {
                        $pos = $member->getPosition();
                        $y = $pos->y;
                        if ($y < $minY) {
                            // TODO:
                            //$this->addSpectator($member);
                        }
                    }
                }
            }

            if ($checkSeconds) {
                $members1 = $this->team1->getPlayers();
                $members2 = $this->team2->getPlayers();

                foreach ($members1 as $player) {
                    if ($player->isOnline()) {
                        // TODO:
                        /*$Duration = $player->getLanguageInfo()->getLanguage()->scoreboard(Language::DUELS_SCOREBOARD_DURATION);
                        $DurationStr = TextFormat::WHITE . ' ' . $Duration . ': ' . $this->getDuration();
                        $player->getScoreboardInfo()->updateLineOfScoreboard(1, $DurationStr);*/
                    }
                }

                foreach ($members2 as $player) {
                    if ($player->isOnline()) {
                        // TODO:
                        /*$Duration = $player->getLanguageInfo()->getLanguage()->scoreboard(Language::DUELS_SCOREBOARD_DURATION);
                        $DurationStr = TextFormat::WHITE . ' ' . $Duration . ': ' . $this->getDuration();
                        $player->getScoreboardInfo()->updateLineOfScoreboard(1, $DurationStr);*/
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
            $diff = $this->currentTick - $this->endTick;
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
        $this->winner = $winner;
        $loser = $this->getOpponent($winner->getName());
        $this->loser = $loser;

        $this->ended = true;
        $this->endTick = $this->currentTick;
    }

    /**
     * @param string|PracticeParty $party
     *
     * @return PracticeParty|null
     */
    #[Pure] public function getOpponent(PracticeParty|string $party): ?PracticeParty
    {
        $result = null;
        $name = $party instanceof PracticeParty ? $party->getName() : $party;
        if ($this->isParty($party)) {
            if ($name === $this->party1->getName())
                $result = $this->party2;
            else $result = $this->party1;
        }
        return $result;
    }

    /**
     * @param string|PracticeParty $party
     *
     * @return bool
     */
    #[Pure] public function isParty(PracticeParty|string $party): bool
    {
        $name = $party instanceof PracticeParty ? $party->getName() : $party;
        return $this->party1->getName() === $name || $this->party2->getName() === $name;
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
        $members1 = $this->party1->getPlayers();
        $members2 = $this->party2->getPlayers();

        $spawnPos = $this->arena->getPlayerPos();
        $x = $spawnPos->getX();
        $y = $spawnPos->getY();
        $z = $spawnPos->getZ();
        $level = $this->level;

        $p1Pos = new Position($x, $y, $z, $level);

        foreach ($members1 as $player) {
            if ($player->isOnline()) {
                $player->setGamemode(0);
                PlayerExtensions::enableFlying($player, false);
                $player->setImmobile(true);

                PlayerExtensions::clearAll($player);

                Utils::onChunkGenerated($level, $x >> 4, $z >> 4, function () use ($p1Pos, $player) {
                    $player->teleport($p1Pos);
                });

                $this->team1->addToTeam($player);
                $player->getKitHolder()->setKit($this->kit);
            }
        }

        $spawnPos = $this->arena->getOpponentPos();
        $x = $spawnPos->getX();
        $y = $spawnPos->getY();
        $z = $spawnPos->getZ();

        $p2Pos = new Position($x, $y, $z, $level);

        foreach ($members2 as $player) {
            if ($player->isOnline()) {
                $player->setGamemode(0);
                $player->getExtensions()->enableFlying(false);
                $player->setImmobile(true);

                PlayerExtensions::clearAll($player);

                Utils::onChunkGenerated($level, $x >> 4, $z >> 4, function () use ($p2Pos, $player) {
                    $player->teleport($p2Pos);
                });

                $this->team2->addToTeam($player);
                $player->getKitHolder()->setKit($this->kit);
            }
        }

        foreach ($members1 as $player) {
            if ($player->isOnline()) {
                $plevel = $player->getLevel();
                if ($plevel->getName() !== $level->getName())
                    $player->teleport($p1Pos);
            }
        }

        foreach ($members2 as $player) {
            if ($player->isOnline()) {
                $plevel = $player->getLevel();
                if ($plevel->getName() !== $level->getName())
                    $player->teleport($p2Pos);
            }
        }

        $p2x = $p2Pos->x;
        $p2z = $p2Pos->z;

        $p1x = $p1Pos->x;
        $p1z = $p1Pos->z;

        $this->centerPosition = new Position(intval((($p2x + $p1x) / 2)), intval($p1Pos->y), intval((($p2z + $p1z) / 2)), $this->level);
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
        else
            $message = $countdown . "...";
        return $message;
    }

    /**
     * @param int $countdown
     *
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

    private function endDuel(): void
    {
        $this->ended = true;

        if ($this->endTick === null) $this->endTick = $this->currentTicks;

        $members1 = $this->party1->getPlayers();
        $members2 = $this->party2->getPlayers();

        foreach ($members1 as $player) {
            if ($player->isOnline()) {
                $this->sendFinalMessage($player);

                // TODO:
                /*$pSb = $player->getScoreboardInfo();
                if($pSb->getScoreboardType() !== Scoreboard::SCOREBOARD_NONE){
                    $pSb->setScoreboard(Scoreboard::SCOREBOARD_SPAWN);
                }*/

                Utils::resetPlayer($player, true, $player->isAlive());

                PlayerHandler::getSession($player)->hasParty() ? DefaultKits::sendPartyKit($player) : DefaultKits::sendSpawnKit($player);
            }
        }

        foreach ($members2 as $player) {
            if ($player->isOnline()) {
                $this->sendFinalMessage($player);

                // TODO:
                /*$pSb = $player->getScoreboardInfo();
                if($pSb->getScoreboardType() !== Scoreboard::SCOREBOARD_NONE){
                    $pSb->setScoreboard(Scoreboard::SCOREBOARD_SPAWN);
                }*/

                Utils::resetPlayer($player, true, $player->isAlive());

                PlayerHandler::getSession($player)->hasParty() ? DefaultKits::sendPartyKit($player) : DefaultKits::sendSpawnKit($player);
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
            $loser = $this->loser ?? "None";

            $resultMsg = Utils::str_replace("§l§eMatch Results§r\n§aWinner§7: §e%winner% §8| §cLoser§7: §e%loser%", ["%winner%" => $winner, "%loser%" => $loser]);
            $result = ['*', $resultMsg, '*'];

            $separator = ' ';

            $result = ['%', $resultMsg, $resultMsg, '%'];

            $keys = array_keys($result);

            foreach ($keys as $key) {
                $str = $result[$key];
                if ($str === '%') $result[$key] = $separator;
            }

            foreach ($result as $res)
                $playerToSendMessage->sendMessage($res);
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
     *
     * Tracks when a block is set.
     */
    public function clearBlock(): void
    {
        foreach ($this->mlgblock as $deleteblock) {
            $pos = explode(':', $deleteblock);
            $this->level->setBlock(new Vector3($pos[0], $pos[1], $pos[2]), Block::get(BlockIds::AIR));
        }

        $this->mlgblock = [];
    }

    /**
     * @return bool
     */
    public function cantDamagePlayers(): bool
    {
        return true;
    }

    /**
     * @return string
     */
    public function getQueue(): string
    {
        return $this->queue;
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
     * @return string
     */
    public function getTexture(): string
    {
        return $this->kit !== null ? PracticeCore::getItemHandler()->getTextureOf($this->kit->getRepItem()) : '';
    }

    /**
     * @return int
     */
    public function getWorldId(): int
    {
        return $this->worldId;
    }

    /**
     * @param Player $player
     */
    public function removeFromEvent(Player $player): void
    {
        $team = $this->getTeam($player);

        if ($team instanceof PracticeTeam && $team->isInTeam($player)) {
            $color = $team->getTeamColor();

            $members1 = $this->party1->getPlayers();
            $members2 = $this->party2->getPlayers();

            foreach ($members1 as $member) {
                if ($member->isOnline()) $member->sendMessage(PracticeParty::getPrefix() . $color . $player->getDisplayName() . TextFormat::RED . " has been eliminated.");
            }

            foreach ($members2 as $member) {
                if ($member->isOnline()) $member->sendMessage(PracticeParty::getPrefix() . $color . $player->getDisplayName() . TextFormat::RED . " has been eliminated.");
            }

            $team->removeFromTeam($player);
        }
    }

    /**
     * @param string|Player $player
     *
     * @return PracticeTeam|null
     */
    #[Pure] public function getTeam(Player|string $player): ?PracticeTeam
    {
        $result = null;

        if ($this->team1->isInTeam($player)) {
            $result = $this->team1;
        } elseif ($this->team2->isInTeam($player)) {
            $result = $this->team2;
        }
        return $result;
    }
}