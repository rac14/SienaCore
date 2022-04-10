<?php

namespace DidntPot\player;

use DidntPot\utils\Utils;
use pocketmine\form\Form;
use pocketmine\math\Vector3;
use pocketmine\network\mcpe\protocol\DataPacket;
use pocketmine\network\mcpe\protocol\LevelSoundEventPacket;
use pocketmine\Player;

class Human extends Player
{
    /** @var array */
    private $formData = [];
    /** @var bool */
    private $lookingAtForm = false;

    # ------------------------------------------------------------------------------------------------------------ #
    #                                              PM-EDITED PART                                                  #
    # ------------------------------------------------------------------------------------------------------------ #

    /**
     * @param Vector3 $pos
     * @param float|null $yaw
     * @param float|null $pitch
     * @return bool
     */
    public function teleport(Vector3 $pos, ?float $yaw = null, ?float $pitch = null): bool
    {
        $t = parent::teleport($pos, $yaw, $pitch);
        $this->broadcastMotion();
        return $t;
    }

    /**
     * @return int
     */
    public function getPing(): int
    {
        $ping = (parent::getPing() - 20) ?: mt_rand(1, 5);
        if($ping < 0) $ping = mt_rand(0, 5);

        return $ping;
    }

    /**
     * @param LevelSoundEventPacket $packet
     * @return bool
     */
    public function handleLevelSoundEvent(LevelSoundEventPacket $packet): bool
    {
        if($packet->sound === LevelSoundEventPacket::SOUND_ATTACK_STRONG or $packet->sound === LevelSoundEventPacket::SOUND_ATTACK_NODAMAGE)
        {
            return false;
        }

        Utils::broadcastPacketToViewers($this, $packet, function(Player $player, DataPacket $packet)
        {
            if($player instanceof Human and $packet instanceof LevelSoundEventPacket)
            {
                if(!isset(Utils::SWISH_SOUNDS[$packet->sound]))
                {
                    return true;
                }

                return false;
            }
            return true;
        });
        return true;
    }

    /**
     * @param Form $form
     * @param array $addedContent
     *
     * Sends the form to a player.
     */
    public function sendFormWindow(Form $form, array $addedContent = []): void
    {
        if (!$this->lookingAtForm) {
            $formToJSON = $form->jsonSerialize();
            $content = [];

            if (isset($formToJSON['content']) && is_array($formToJSON['content'])) {
                $content = $formToJSON['content'];
            } elseif (isset($formToJSON['buttons']) && is_array($formToJSON['buttons'])) {
                $content = $formToJSON['buttons'];
            }

            if (!empty($addedContent)) {
                $content = array_replace($content, $addedContent);
            }

            $this->formData = $content;
            $this->lookingAtForm = true;
            $this->sendForm($form);
        }
    }

    /**
     * @return array
     *
     * Officially removes the form data from the player.
     */
    public function removeFormData(): array
    {
        $data = $this->formData;
        $this->formData = [];
        return $data;
    }

    /**
     * @param int $formId
     * @param mixed $responseData
     *
     * @return bool
     */
    public function onFormSubmit(int $formId, $responseData): bool
    {
        $this->lookingAtForm = false;
        $result = parent::onFormSubmit($formId, $responseData);

        if (isset($this->forms[$formId])) {
            unset($this->forms[$formId]);
        }

        return $result;
    }
}