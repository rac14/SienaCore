<?php

namespace DidntPot\forms;

use pocketmine\form\Form as IForm;
use pocketmine\Player;

abstract class Form implements IForm
{
    /** @var array */
    protected array $data = [];
    /** @var array */
    protected array $extraData = [];
    /** @var callable */
    private $callable;

    /**
     * @param callable|null $callable $callable
     */
    public function __construct(?callable $callable)
    {
        $this->callable = $callable;
    }

    /**
     * @param array $data
     *
     * Sets the extra data of the form.
     */
    public function setExtraData(array $data): void
    {
        $this->extraData = $data;
    }

    /**
     * @param string $key
     * @param $value
     *
     * Adds an extra data to the form.
     */
    public function addExtraData(string $key, $value): void
    {
        $this->extraData[$key] = $value;
    }

    /**
     * @param Player $player
     * @param mixed $data
     *
     * Handles when the response is given to the player.
     */
    public function handleResponse(Player $player, mixed $data): void
    {
        $this->processData($data);
        $callable = $this->getCallable();

        if ($callable !== null) {
            $callable($player, $data, $this->extraData);
        }
    }

    /**
     * @param $data
     *
     * Processes the data.
     */
    public function processData(&$data): void
    {
    }

    /**
     * @return callable|null
     *
     * Gets the callable function used for forms.
     */
    public function getCallable(): ?callable
    {
        return $this->callable;
    }

    /**
     * @param callable|null $callable
     *
     * Sets the callable function used for forms.
     */
    public function setCallable(?callable $callable)
    {
        $this->callable = $callable;
    }

    /**
     * @return array
     *
     * Serializes the form into a json format.
     */
    public function jsonSerialize(): array
    {
        return $this->data;
    }
}