<?php

namespace DidntPot\events;

use DidntPot\arenas\EventArena;
use DidntPot\events\duels\EventDuel;
use DidntPot\player\PlayerExtensions;
use DidntPot\player\sessions\PlayerHandler;
use DidntPot\PracticeCore;
use DidntPot\utils\Utils;
use JetBrains\PhpStorm\Pure;
use pocketmine\Player;
use pocketmine\Server;
use pocketmine\utils\TextFormat;

class PracticeEvent
{
    const TYPE_SUMO = 0;
    const TYPE_GAPPLE = 1;
    const TYPE_FIST = 2;
    const TYPE_NODEBUFF = 3;

    const STATUS_AWAITING_PLAYERS = 0;
    const STATUS_IN_PROGESS = 1;
    const STATUS_ENDING = 2;

    const MINUTES_AWAITING_PLAYERS = 5;

    const MIN_PLAYERS_NEEDED_ON_SERVER = 10;

    const MAX_PLAYERS = 30;
    const MIN_PLAYERS = 2;

    const MAX_DELAY_SECONDS = 3;

    /** @var int */
    protected $type;

    /** @var EventArena */
    protected $arena;

    /** @var int */
    protected $status;

    /** @var array */
    protected $players;

    /** @var int */
    protected $currentTick;

    /** @var int */
    protected $currentEventTick;

    /** @var int */
    protected $awaitingPlayersTick;

    /** @var int */
    protected $currentDelay;

    /** @var int */
    protected $endingDelay;

    /** @var array */
    protected $eliminated;

    /** @var EventDuel|null */
    protected $current1vs1;

    /** @var string|null */
    protected $winner;

    /** @var int */
    protected $startingDelay;

    /** @var int */
    private $maxNumberOfPlayers;

    /** @var string|null */
    private $lastWinnerOfDuel;

    public function __construct(int $type, EventArena $arena)
    {
        $this->type = $type;
        $this->arena = $arena;
        $this->players = [];
        $this->status = self::STATUS_AWAITING_PLAYERS;
        $this->currentTick = 0;
        $this->currentEventTick = 0;
        $this->awaitingPlayersTick = 0;
        $this->currentDelay = 0;
        $this->endingDelay = 5;
        $this->eliminated = [];
        $this->current1vs1 = null;
        $this->startingDelay = 4;
        $this->maxNumberOfPlayers = 0;
        $this->lastWinnerOfDuel = null;
    }

    /**
     * @param Player $player
     *
     * Adds a player to the list of players.
     */
    public function addPlayer(Player $player): void
    {
        $playerCount = $this->getPlayers(true);

        if(!$this->canJoin())
        {
            $stop = true;

            $msg = null;

            if(!$this->isAwaitingPlayers())
            {
                $msg = $lang->getMessage(Language::EVENTS_MESSAGE_JOIN_FAIL_STARTED);
            } elseif (!$this->hasEnoughPlayers())
            {
                if($playerCount > 0)
                {
                    $stop = false;
                } else
                {
                    $msg = $lang->getMessage(Language::EVENTS_MESSAGE_JOIN_FAIL_PLAYERS);
                }
            }

            if($msg !== null)
            {
                $player->sendMessage(Utils::getPrefix() . TextFormat::RESET . $msg);
            }

            if($stop)
            {
                return;
            }
        }

        $duelHandler = PracticeCore::getDuelManager();
        $session = PlayerHandler::getSession($player);

        if($session->isInDuelQueue())
        {
            $duelHandler->removeFromQueue($player, false);
        }

        PlayerExtensions::clearAll($player);

        PlayerExtensions::enableFlying($player, false);
        $player->setGamemode(0);

        $arena = $this->getArena();
        $arena->teleportPlayer($player);

        $name = $player->getName();

        if(!isset($this->players[$name]))
        {
            $this->players[$name] = $player;
        }

        // TODO:
        /*if($player->getScoreboardType() !== Scoreboard::SCOREBOARD_NONE) {
            $player->setScoreboard(Scoreboard::SCOREBOARD_EVENT_SPEC);
        }*/

        $numPlayers = strval(count($this->players));

        $playersLine = $this->isAwaitingPlayers() ? 3 : 1;

        foreach($this->players as $pName => $player)
        {
            // TODO:
            /*if($player->isOnline())
            {
                if($pName !== $name)
                {
                    $playersLineValue = $lang->getMessage(Language::PLAYERS_LABEL);
                    $players = " " . $playersLineValue . ": " . TextFormat::GOLD . strval($numPlayers) . " ";

                    $player->updateLineOfScoreboard($playersLine, $players);
                }

                $message = $lang->getMessage(Language::EVENTS_MESSAGE_JOIN_SUCCESS, ["name" => $name]);
                $player->sendMessage(MineceitUtil::getPrefix() . ' ' . TextFormat::RESET . $message);
            }*/
        }
    }

    /**
     * @param Player $player
     * @param bool $message
     * @param bool $eliminate
     * @param bool $broadcast
     */
    public function removePlayer(Player $player, bool $message = true, bool $eliminate = true, bool $broadcast = true): void
    {
        $name = $player->getName();
        $p = $player;

        $alreadyEliminated = isset($this->eliminated[$name]);

        if(isset($this->players[$name]))
        {
            unset($this->players[$name]);

            if($this->status === self::STATUS_IN_PROGESS and $eliminate)
            {
                $previousCount = count($this->eliminated);

                if(!isset($this->eliminated[$name]))
                {
                    $this->eliminated[$name] = count($this->eliminated) + 1;
                }

                $count = count($this->eliminated);

                if($previousCount !== $count)
                {
                    $count = strval($count);

                    foreach($this->players as $player)
                    {
                        // TODO:
                        /*if($player->isOnline()) {
                            $lang = $player->getLanguage();
                            $pMessage = " " . $lang->getMessage(Language::EVENT_SCOREBOARD_ELIMINATED, ['num' => $count]);
                            $player->updateLineOfScoreboard(4, $pMessage);
                        }*/
                    }
                }
            }

            if($p->isOnline())
            {
                if($message)
                {
                    $message = $lang->getMessage(Language::EVENTS_MESSAGE_LEAVE_EVENT_SENDER);
                    $p->sendMessage(Utils::getPrefix() . TextFormat::RESET . $message);
                }

                // TODO:
                /*$p->clearKit();
                $p->reset(true, true);
                $p->setSpawnNameTag();

                $itemHandler = MineceitCore::getItemHandler();
                $itemHandler->spawnHubItems($p);

                if($p->getScoreboardType() !== Scoreboard::SCOREBOARD_NONE) {
                    $p->setScoreboard(Scoreboard::SCOREBOARD_SPAWN);
                }*/
            }
        }

        if($broadcast) {

            $playersLine = $this->isAwaitingPlayers() ? 3 : 1;

            $numPlayers = strval(count($this->players));

            $playersLeft = strval($this->numPlayersLeft());

            foreach ($this->players as $player) {

                if ($player->isOnline()) {

                    $lang = $player->getLanguage();

                    if (!$player->isInEventDuel()) {
                        $playersLabel = $lang->getMessage(Language::PLAYERS_LABEL);
                        $msg = " " . $playersLabel . ": " . TextFormat::GOLD . "$numPlayers ";
                        if ($lang->getLocale() === Language::ARABIC) {
                            $msg = TextFormat::GOLD . " $numPlayers" . TextFormat::WHITE . " :" . "$playersLabel ";
                        }

                        $player->updateLineOfScoreboard($playersLine, $msg);
                    }

                    $leaveEvent = $lang->getMessage(Language::EVENTS_MESSAGE_LEAVE_EVENT_RECEIVER, ["name" => $name]);
                    $message = $leaveEvent;

                    if ($this->status === self::STATUS_IN_PROGESS and $eliminate and !$alreadyEliminated) {
                        $left = TextFormat::RESET . TextFormat::DARK_GRAY . '(' . TextFormat::RED . $lang->getMessage(Language::EVENT_MESSAGE_PLAYERS_LEFT, ['num' => $playersLeft]) . TextFormat::DARK_GRAY . ")";
                        $message = $leaveEvent . " $left";
                        if ($lang->getLocale() === Language::ARABIC) {
                            $message = $left . " $leaveEvent";
                        }
                    }

                    $player->sendMessage(MineceitUtil::getPrefix() . ' ' . TextFormat::RESET . $message);
                }
            }
        }
    }

    /**
     * Updates the event.
     */
    public function update() : void {

        if($this->status === self::STATUS_AWAITING_PLAYERS) {

            $playersCount = count($this->players);

            if($playersCount <= 0) {
                /* if($this->awaitingPlayersTick > 0) {
                    $this->awaitingPlayersTick = 0;
                } */
                //$this->awaitingPlayersTick++;
                $this->currentTick++;
                return;
            }

            $minutes = MineceitUtil::ticksToMinutes($this->awaitingPlayersTick);
            $seconds = MineceitUtil::ticksToSeconds($this->awaitingPlayersTick);

            if($this->awaitingPlayersTick % 20 === 0) {

                // Updates the time until event starts.
                foreach($this->players as $player) {

                    if($player->isOnline()) {

                        $lang = $player->getLanguage();
                        $startingTime = $this->getTimeUntilStart();
                        $startingLine = " " . $lang->getMessage(Language::EVENT_SCOREBOARD_STARTING_IN, ["time" => $startingTime]) . " ";
                        $player->updateLineOfScoreboard(1, $startingLine);
                    }
                }

                $maxSeconds = MineceitUtil::ticksToSeconds(MineceitUtil::minutesToTicks(self::MINUTES_AWAITING_PLAYERS));
                $seconds = $maxSeconds - $seconds;
                if($seconds <= 5 and $seconds >= 0) {

                    if($seconds === 5) {

                        foreach($this->players as $player) {
                            if($player->isOnline()) {
                                $msg = $this->getCountdownMessage($player->getLanguage(), $seconds);
                                $player->sendTitle($msg, '', 5, 20, 5);
                            }
                        }

                    } elseif ($seconds !== 0) {

                        foreach($this->players as $player) {
                            if($player->isOnline()) {
                                $msg = $this->getJustCountdown($player->getLanguage(), $seconds);
                                $player->sendTitle($msg, '', 5, 20, 5);
                            }
                        }
                    }
                }
            }

            if($minutes >= self::MINUTES_AWAITING_PLAYERS) {

                $this->awaitingPlayersTick = 0;

                if($playersCount < self::MIN_PLAYERS) {

                    foreach($this->players as $player) {
                        if($player->isOnline()) {
                            $lang = $player->getLanguage();
                            $msg = $lang->getMessage(Language::EVENTS_MESSAGE_CANCELED);
                            $this->removePlayer($player, false, false, false);
                            $player->sendMessage(MineceitUtil::getPrefix() . ' ' . TextFormat::RESET . $msg);
                        }
                    }

                    $this->resetEverything();
                    $this->currentTick++;
                    return;
                }

                $this->status = self::STATUS_IN_PROGESS;
                $this->maxNumberOfPlayers = $playersCount;

                // Removes the scoreboard lines when the event starts.
                foreach($this->players as $player) {
                    if($player->isOnline()) {
                        $player->reloadScoreboard();
                        $lang = $player->getLanguage();
                        $title = $lang->getMessage(Language::EVENTS_MESSAGE_STARTING_NOW);
                        $player->sendTitle($title, "", 5, 20, 5);
                    }
                }

                $this->awaitingPlayersTick++;
                $this->currentTick++;
                return;
            }

            $this->awaitingPlayersTick++;

        } elseif ($this->status === self::STATUS_IN_PROGESS) {

            if($this->startingDelay > 0) {
                if($this->currentTick % 20 === 0) {
                    $this->startingDelay--;
                    if($this->startingDelay < 0) {
                        $this->startingDelay = 0;
                    }
                }
                $this->currentTick++;
                return;
            }


            if($this->currentDelay > 0) {
                if($this->currentTick % 20 === 0) {
                    $this->currentDelay--;
                    if($this->currentDelay < 0) {
                        $this->currentDelay = 0;
                    }
                }
                $this->currentTick++;
                return;
            }

            if($this->current1vs1 === null) {

                $playersLeft = $this->getPlayersLeft();
                $playersLeftKeys = array_keys($playersLeft);
                $count = count($playersLeftKeys);

                if($this->checkWinner()) {
                    $this->currentTick++;
                    return;
                }

                $p1Key = $playersLeftKeys[mt_rand(0, $count - 1)];
                $p2Key = $playersLeftKeys[mt_rand(0, $count - 1)];

                // TODO TEST
                // Ensures that p2 and p1 are not the same.
                while($p2Key === $p1Key or ($count >= 3 and $this->lastWinnerOfDuel !== null and $this->lastWinnerOfDuel === $p2Key)) {
                    $p2Key = $playersLeftKeys[mt_rand(0, $count - 1)];
                }

                /** @var MineceitPlayer $player1 */
                $player1 = $playersLeft[$p1Key];
                /** @var MineceitPlayer $player2 */
                $player2 = $playersLeft[$p2Key];

                $this->createNewDuel($player1, $player2);

            } else {

                $this->current1vs1->update();

                if($this->current1vs1->getStatus() === EventDuel::STATUS_ENDED) {

                    $results = $this->current1vs1->getResults();
                    $winner = $results['winner'];
                    $loser = $results['loser'];

                    $this->lastWinnerOfDuel = $winner;

                    if($winner !== null and $loser !== null) {

                        $alreadyEliminated = isset($this->eliminated[$loser]);

                        if(!isset($this->eliminated[$loser])) {
                            $this->eliminated[$loser] = count($this->eliminated) + 1;
                        }

                        $eliminated = strval(count($this->eliminated));

                        $playersLeft = strval($this->numPlayersLeft());

                        if(!$alreadyEliminated) {

                            foreach ($this->players as $player) {

                                if ($player->isOnline()) {

                                    $lang = $player->getLanguage();
                                    $eliminatedLine = " " . $lang->getMessage(Language::EVENT_SCOREBOARD_ELIMINATED, ["num" => $eliminated]) . " ";
                                    $player->updateLineOfScoreboard(4, $eliminatedLine);

                                    $eliminatedMsg = $lang->getMessage(Language::EVENTS_MESSAGE_ELIMINATED, ["name" => $loser]);
                                    $left = TextFormat::RESET . TextFormat::DARK_GRAY . '(' . TextFormat::RED . $lang->getMessage(Language::EVENT_MESSAGE_PLAYERS_LEFT, ['num' => $playersLeft]) . TextFormat::DARK_GRAY . ")";
                                    $msg = $eliminatedMsg . " $left";
                                    if ($lang->getLocale() === Language::ARABIC) {
                                        $msg = $left . " $eliminatedMsg";
                                    }

                                    $player->sendMessage(MineceitUtil::getPrefix() . ' ' . TextFormat::RESET . $msg);
                                }
                            }
                        }
                    }

                    $this->current1vs1 = null;

                    if($this->checkWinner()) {
                        $this->currentTick++;
                        return;
                    }

                    $this->currentDelay = self::MAX_DELAY_SECONDS;
                }
            }
        } elseif ($this->status === self::STATUS_ENDING) {

            if($this->endingDelay > 0) {
                if($this->currentTick % 20 === 0) {
                    $this->endingDelay--;
                    if($this->endingDelay < 0) {
                        $this->endingDelay = 0;
                    }
                }
            } elseif ($this->endingDelay === 0) {

                $this->end();

                $this->status = self::STATUS_AWAITING_PLAYERS;
            }
        }


        $this->currentTick++;
    }

    /**
     * @return bool
     *
     * Checks for a winner of the event.
     */
    private function checkWinner() : bool {

        $playersLeft = $this->getPlayersLeft();
        $playersLeftKeys = array_keys($playersLeft);
        $count = count($playersLeftKeys);

        if($count === 1) {
            $this->status = self::STATUS_ENDING;
            $this->winner = (string)$playersLeftKeys[0];
            return true;
        } elseif ($count === 0) {
            $minimum = null;
            $winner = null;
            foreach($this->eliminated as $name => $place) {
                if($minimum === null or $place < $minimum) {
                    $minimum = $place;
                    $winner = $name;
                }
            }
            if($winner !== null) {
                $this->status = self::STATUS_ENDING;
                $this->winner = (string)$winner;
            }
            return true;
        }

        return false;
    }

    /**
     *
     * Ends the event.
     */
    public function end() : void {

        $itemHandler = MineceitCore::getItemHandler();

        $eliminated = count($this->eliminated);

        /** @var MineceitPlayer[] $onlinePlayers */
        $onlinePlayers = Server::getInstance()->getOnlinePlayers();

        foreach($onlinePlayers as $player) {

            $name = $player->getName();
            $lang = $player->getLanguage();
            $winner = $this->winner ?? $lang->getMessage(Language::NONE);

            if(isset($this->players[$name])) {

                $place = 1;

                if(isset($this->eliminated[$name])) {
                    $number = (int)$this->eliminated[$name];
                    $place = ($eliminated - $number) + 2;
                }

                $winnerMessage = $lang->getMessage(Language::DUELS_MESSAGE_WINNER, ["name" => $winner]);

                $separator = '--------------------------';

                $postfix = MineceitUtil::getOrdinalPostfix($place, $lang);
                $num = $postfix;
                if($lang->doesShortenOrdinals()) {
                    $num = strval($place) . $postfix;
                }

                $resultMessage = $lang->getMessage(Language::EVENTS_MESSAGE_RESULT, ["place" => $num]);

                $placeMessage = $resultMessage;

                $array = [$separator, $winnerMessage, $placeMessage, $separator];

                foreach($array as $message) {
                    $player->sendMessage($message);
                }

                $player->clearKit();
                $player->reset(true, true);
                $player->setSpawnNameTag();
                $itemHandler->spawnHubItems($player, false);

                if ($player->getScoreboardType() !== Scoreboard::SCOREBOARD_NONE) {
                    $player->setScoreboard(Scoreboard::SCOREBOARD_SPAWN);
                }

            } else {

                if($this->winner === null) {
                    continue;
                }

                $eventName = strtolower($this->getName());
                $message = $lang->getMessage(Language::EVENT_WINNER_ANNOUNCEMENT, ['winner' => $winner, 'event' => $eventName]);
                $player->sendMessage(MineceitUtil::getPrefix() . ' ' . TextFormat::RESET . $message);
            }
        }

        $this->resetEverything();
    }

    /**
     * Resets everything back to their original state.
     */
    private function resetEverything(): void
    {
        $this->eliminated = [];
        $this->players = [];
        $this->current1vs1 = null;
        $this->currentDelay = 0;
        $this->endingDelay = 5;
        $this->winner = null;
        $this->currentEventTick = 0;
        $this->awaitingPlayersTick = 0;
        $this->startingDelay = 5;
        $this->maxNumberOfPlayers = 0;
        $this->lastWinnerOfDuel = null;
    }

    /**
     * @return array
     *
     * Gets the players left.
     */
    protected function getPlayersLeft(): array
    {
        return array_diff_key($this->players, $this->eliminated);
    }

    /**
     * @param bool $intval
     * @return array|int
     */
    public function getPlayers(bool $intval = false): array|int
    {
        return $intval ? count($this->players) : $this->players;
    }

    /**
     * @param Player $player
     * @return bool
     *
     * Determines if the player is in the event.
     */
    #[Pure] public function isPlayer(Player $player): bool
    {
        return isset($this->players[$player->getName()]);
    }

    /**
     * @param Player $player
     * @return bool
     */
    #[Pure] public function isEliminated(Player $player): bool
    {
        return isset($this->eliminated[$player->getName()]);
    }

    /**
     * @return EventDuel|null
     */
    public function getCurrentDuel(): EventDuel|null
    {
        return $this->current1vs1;
    }

    /**
     * @param Player $p1
     * @param Player $p2
     *
     * Creates a new duel.
     */
    public function createNewDuel(Player $p1, Player $p2): void
    {
        $this->current1vs1 = new EventDuel($p1, $p2, $this);
    }

    /**
     * @return int
     *
     * Gets the type of event.
     */
    public function getType(): int
    {
        return $this->type;
    }

    /**
     * @return int
     */
    #[Pure] public function numPlayersLeft(): int
    {
        return count($this->getPlayersLeft());
    }

    /**
     * @param bool $count
     * @return array|int
     *
     * Gets the eliminated players.
     */
    public function getEliminated(bool $count = false): int|array
    {
        return $count ? count($this->eliminated) : array_keys($this->eliminated);
    }

    /**
     * @return bool
     *
     * Determines if the player can join the event.
     */
    public function canJoin(): bool
    {
        return $this->hasEnoughPlayers() and $this->isAwaitingPlayers();
    }

    /**
     * @return bool
     *
     * Determines if the server has enough players for the event.
     */
    protected function hasEnoughPlayers(): bool
    {
        $server = Server::getInstance();
        $playerCount = count($server->getOnlinePlayers());
        return $playerCount >= self::MIN_PLAYERS_NEEDED_ON_SERVER;
    }

    /**
     * @return bool
     *
     * Determines if the event is awaiting players.
     */
    public function isAwaitingPlayers(): bool
    {
        return $this->status === self::STATUS_AWAITING_PLAYERS;
    }

    /**
     * @return bool
     *
     * Determines if the event has started.
     */
    public function hasStarted(): bool
    {
        return $this->status === self::STATUS_IN_PROGESS;
    }

    /**
     * @return bool
     *
     * Determines if the event has ended.
     */
    public function hasEnded(): bool
    {
        return $this->status === self::STATUS_ENDING;
    }

    /**
     * @return EventArena
     *
     * Gets the arena.
     */
    public function getArena(): EventArena
    {
        return $this->arena;
    }

    /**
     * @return string
     */
    public function getName(): string
    {
        switch($this->type)
        {
            case self::TYPE_SUMO:
                return "Sumo";
            case self::TYPE_GAPPLE:
                return "Gapple";
            case self::TYPE_NODEBUFF:
                return "NoDebuff";
            case self::TYPE_FIST:
                return "Fist";
        }

        return "";
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
     * @param int $countdown
     * @return string
     */
    private function getCountdownMessage(int $countdown): string
    {
        return "$countdown...";
    }

    /**
     * @return string
     */
    public function getTimeUntilStart(): string
    {
        $minutes = Utils::minutesToTicks(self::MINUTES_AWAITING_PLAYERS);
        $minutes = Utils::ticksToSeconds($minutes);
        $time = $minutes - Utils::ticksToSeconds($this->awaitingPlayersTick);

        $seconds = $time % 60;
        $minutes = intval($time / 60);

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
     * @return int
     *
     * Gets the number of players at the start of the game.
     */
    public function getNumberPlayersAtStart(): int
    {
        return $this->maxNumberOfPlayers;
    }
}