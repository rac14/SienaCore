<?php

namespace DidntPot\events\duels;

use DidntPot\arenas\EventArena;
use DidntPot\events\PracticeEvent;
use DidntPot\player\PlayerExtensions;
use DidntPot\utils\Utils;
use JetBrains\PhpStorm\Pure;
use pocketmine\Player;

class EventDuel
{
    const STATUS_STARTING = 0;
    const STATUS_IN_PROGRESS = 1;
    const STATUS_ENDING = 2;
    const STATUS_ENDED = 3;

    /** @var Player */
    private $p1;
    /** @var string */
    private $p1Name;

    /** @var Player */
    private $p2;
    /** @var string */
    private $p2Name;

    /** @var int */
    private $currentTick;

    /** @var int */
    private $countdownSeconds;

    /** @var int */
    private $durationSeconds;

    /** @var PracticeEvent */
    private $event;

    /** @var int */
    private $status;

    /** @var string|null */
    private $winner;

    /** @var string|null */
    private $loser;

    /** @var int */
    private $endingSeconds;

    #[Pure] public function __construct(Player $p1, Player $p2, PracticeEvent $event)
    {
        $this->p1 = $p1;
        $this->p2 = $p2;
        $this->currentTick = 0;
        $this->durationSeconds = 0;
        $this->countdownSeconds = 5;
        $this->event = $event;
        $this->status = self::STATUS_STARTING;
        $this->p1Name = $p1->getName();
        $this->p2Name = $p2->getName();
        $this->endingSeconds = 1;
    }

    /**
     * Sets the players in a duel/
     */
    protected function setPlayersInDuel(): void
    {
        $arena = $this->event->getArena();

        $this->p1->setGamemode(0);
        $this->p2->setGamemode(0);

        PlayerExtensions::enableFlying($this->p1, false);
        PlayerExtensions::enableFlying($this->p2, false);

        $this->p1->setImmobile();
        $this->p2->setImmobile();

        PlayerExtensions::clearInventory($this->p1);
        PlayerExtensions::clearInventory($this->p2);

        $arena->teleportPlayer($this->p1, EventArena::P1);
        $arena->teleportPlayer($this->p2, EventArena::P2);

        // TODO:
        /*$p1Message = $p1Lang->getMessage(Language::EVENTS_MESSAGE_DUELS_MATCHED, ["name" => $this->p2Name]);
        $p2Message = $p2Lang->getMessage(Language::EVENTS_MESSAGE_DUELS_MATCHED, ["name" => $this->p1Name]);

        $this->p1->sendMessage(MineceitUtil::getPrefix() . ' ' . TextFormat::RESET . $p1Message);
        $this->p2->sendMessage(MineceitUtil::getPrefix() . ' ' . TextFormat::RESET . $p2Message);*/
    }

    /**
     * Updates the duel.
     */
    public function update(): void
    {
        $this->currentTick++;

        $checkSeconds = $this->currentTick % 20 === 0;

        if (!$this->p1->isOnline() or !$this->p2->isOnline())
        {
            if ($this->p1->isOnline())
            {
                $this->winner = $this->p1Name;
                $this->loser = $this->p2Name;

                if ($this->status !== self::STATUS_ENDED)
                {
                    $this->p1->clearKit();
                    $this->p1->reset(true, false);
                    $arena = $this->event->getArena();
                    $arena->teleportPlayer($this->p1);
                    $this->p1->setScoreboard(Scoreboard::SCOREBOARD_EVENT_SPEC);
                    $this->p1->setSpawnNameTag();
                }

            } elseif ($this->p2->isOnline())
            {
                $this->winner = $this->p1Name;
                $this->loser = $this->p2Name;

                if ($this->status !== self::STATUS_ENDED)
                {
                    $this->p2->clearKit();
                    $this->p2->reset(true, false);
                    $arena = $this->event->getArena();
                    $arena->teleportPlayer($this->p2);
                    $this->p2->setScoreboard(Scoreboard::SCOREBOARD_EVENT_SPEC);
                    $this->p2->setSpawnNameTag();
                }
            }

            $this->status = self::STATUS_ENDED;
            return;
        }

        if ($this->status === self::STATUS_STARTING)
        {
            if ($this->currentTick === 4)
            {
                $this->setPlayersInDuel();
            }

            if ($checkSeconds)
            {
                // TODO:
                /*$p1Sb = $this->p1->getScoreboardType();
                $p2Sb = $this->p2->getScoreboardType();

                if ($p1Sb !== Scoreboard::SCOREBOARD_NONE and $p1Sb !== Scoreboard::SCOREBOARD_EVENT_DUEL) {
                    $this->p1->setScoreboard(Scoreboard::SCOREBOARD_EVENT_DUEL);
                }

                if ($p2Sb !== Scoreboard::SCOREBOARD_NONE and $p2Sb !== Scoreboard::SCOREBOARD_EVENT_DUEL) {
                    $this->p2->setScoreboard(Scoreboard::SCOREBOARD_EVENT_DUEL);
                }*/

                // Countdown messages.
                if ($this->countdownSeconds === 5)
                {
                    $p1Msg = $this->getCountdownMessage(true, $this->countdownSeconds);
                    $p2Msg = $this->getCountdownMessage(true, $this->countdownSeconds);
                    $this->p1->sendMessage($p1Msg);
                    $this->p2->sendMessage($p2Msg);
                } elseif ($this->countdownSeconds !== 0)
                {
                    $p1Msg = $this->getJustCountdown($this->countdownSeconds);
                    $p2Msg = $this->getJustCountdown($this->countdownSeconds);
                    $this->p1->sendMessage($p1Msg);
                    $this->p2->sendMessage($p2Msg);
                } else
                {
                    $p1Msg = Utils::getPrefix() . "§aThe match has started, good luck.";
                    $p2Msg = Utils::getPrefix() . "§aThe match has started, good luck.";
                    $this->p1->sendMessage($p1Msg);
                    $this->p2->sendMessage($p2Msg);
                }

                if ($this->countdownSeconds === 0)
                {
                    $this->status = self::STATUS_IN_PROGRESS;
                    $this->p1->setImmobile(false);
                    $this->p2->setImmobile(false);
                    return;
                }

                $this->countdownSeconds--;
            }

        } elseif ($this->status === self::STATUS_IN_PROGRESS)
        {
            $arena = $this->event->getArena();

            $centerMinY = $arena->getCenter()->getY() - 4;

            $p1Pos = $this->p1->getPosition();
            $p2Pos = $this->p2->getPosition();

            $p1Y = $p1Pos->getY();
            $p2Y = $p2Pos->getY();

            if ($this->event->getType() === PracticeEvent::TYPE_SUMO)
            {
                if ($p1Y < $centerMinY)
                {
                    $this->winner = $this->p2Name;
                    $this->loser = $this->p1Name;
                    $this->status = self::STATUS_ENDING;
                    return;
                }

                if ($p2Y < $centerMinY)
                {
                    $this->winner = $this->p1Name;
                    $this->loser = $this->p2Name;
                    $this->status = self::STATUS_ENDING;
                    return;
                }
            }

            // Used for updating scoreboards.
            if ($checkSeconds)
            {
                // TODO:
                /*$p1Duration = TextFormat::WHITE . $p1Lang->scoreboard(Language::DUELS_SCOREBOARD_DURATION);
                $p2Duration = TextFormat::WHITE . $p2Lang->scoreboard(Language::DUELS_SCOREBOARD_DURATION);

                $p1DurationStr = TextFormat::WHITE . ' ' . $p1Duration . ': ' . $this->getDuration();
                $p2DurationStr = TextFormat::WHITE . ' ' . $p2Duration . ': ' . $this->getDuration();

                if ($p1Lang->getLocale() === Language::ARABIC)
                    $p1DurationStr = ' ' . $this->getDuration() . TextFormat::WHITE . ' :' . $p1Duration;

                if ($p2Lang->getLocale() === Language::ARABIC)
                    $p2DurationStr = ' ' . $this->getDuration() . TextFormat::WHITE . ' :' . $p2Duration;

                $this->p1->updateLineOfScoreboard(1, $p1DurationStr);
                $this->p2->updateLineOfScoreboard(1, $p2DurationStr);*/

                $this->durationSeconds++;
            }

        } elseif ($this->status === self::STATUS_ENDING)
        {
            if ($checkSeconds and $this->endingSeconds > 0)
            {
                $this->endingSeconds--;

                if ($this->endingSeconds === 0)
                {
                    if ($this->p1->isOnline())
                    {
                        $this->p1->clearKit();
                        $this->p1->reset(true, false);
                        $arena = $this->event->getArena();
                        $arena->teleportPlayer($this->p1);
                        $this->p1->setScoreboard(Scoreboard::SCOREBOARD_EVENT_SPEC);
                        $this->p1->setSpawnNameTag();
                    }

                    if ($this->p2->isOnline())
                    {
                        $this->p2->clearKit();
                        $this->p2->reset(true, false);
                        $arena = $this->event->getArena();
                        $arena->teleportPlayer($this->p2);
                        $this->p2->setScoreboard(Scoreboard::SCOREBOARD_EVENT_SPEC);
                        $this->p2->setSpawnNameTag();
                    }

                    $this->status = self::STATUS_ENDED;
                }
            }
        }
    }

    /**
     * @return string
     *
     * Gets the duration of the duel for scoreboard;
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
     * @param Player $player
     *
     * @return Player|null
     */
    public function getOpponent(Player $player): ?Player
    {
        if ($this->p1->equalsPlayer($player))
        {
            return $this->p2;
        } elseif ($this->p2->equalsPlayer($player))
        {
            return $this->p1;
        }

        return null;
    }

    /**
     * @param Player $player
     * @return bool
     */
    public function isPlayer(Player $player): bool
    {
        return $this->p1->equalsPlayer($player) or $this->p2->equalsPlayer($player);
    }

    /**
     * @return int
     *
     * Gets the status of the duel.
     */
    public function getStatus(): int
    {
        return $this->status;
    }

    /**
     * @return array
     *
     * Gets the results of the duel.
     */
    public function getResults(): array
    {
        return ['winner' => $this->winner, 'loser' => $this->loser];
    }

    /**
     * @param Player|null $winner
     */
    public function setResults(Player $winner = null): void
    {

        if ($winner !== null)
        {
            $name = $winner->getName();

            if ($this->isPlayer($winner))
            {
                $loser = $name === $this->p1Name ? $this->p2Name : $this->p1Name;
                $this->winner = $name;
                $this->loser = $loser;
            }
        }

        $this->status = self::STATUS_ENDING;
    }

    /**
     * @param bool $title
     * @param int $countdown
     * @return string
     */
    private function getCountdownMessage(bool $title, int $countdown): string
    {
        $message = $countdown . '...';
        return $message;
    }

    /**
     * @param int $countdown
     * @return string
     */
    private function getJustCountdown(int $countdown): string
    {
        $message = "$countdown...";
        return $message;
    }
}
