<?php

namespace DidntPot\parties\requests;

use DidntPot\parties\PracticeParty;
use JetBrains\PhpStorm\Pure;
use pocketmine\Player;

class PartyRequest
{
    /* @var Player */
    private Player $from;

    /* @var Player */
    private Player $to;

    /* @var PracticeParty */
    private PracticeParty $party;

    /* @var string */
    private string $fromName;

    /* @var string */
    private string $toName;

    /* @var string */
    private string $texture;

    /* @var string */
    private string $toDisplayName;

    /* @var string */
    private string $fromDisplayName;


    #[Pure] public function __construct(Player $from, Player $to, PracticeParty $party)
    {
        $this->from = $from;
        $this->to = $to;
        $this->party = $party;
        $this->toName = $to->getName();
        $this->fromName = $from->getName();
        $this->toDisplayName = $to->getDisplayName();
        $this->fromDisplayName = $from->getDisplayName();
        $this->texture = '';
    }

    /**
     * @return string
     */
    public function getTexture(): string
    {
        return $this->texture;
    }

    /**
     * @return Player
     */
    public function getFrom(): Player
    {
        return $this->from;
    }

    /**
     * @return Player
     */
    public function getTo(): Player
    {
        return $this->to;
    }

    /**
     * @return PracticeParty
     */
    public function getParty(): PracticeParty
    {
        return $this->party;
    }

    /**
     * @return string
     */
    public function getFromName(): string
    {
        return $this->fromName;
    }

    /**
     * @return string
     */
    public function getToName(): string
    {
        return $this->toName;
    }


    /**
     * @return string
     */
    public function getFromDisplayName(): string
    {
        return $this->fromDisplayName;
    }

    /**
     * @return string
     */
    public function getToDisplayName(): string
    {
        return $this->toDisplayName;
    }
}