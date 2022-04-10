<?php

namespace DidntPot\player\info;

use DidntPot\PracticeCore;

class PermissionInfo
{
    public static function setPermission($player, $rank)
    {
        $rank = strtolower($rank);
        $plugin = PracticeCore::getInstance();

        if($rank === "knight" or $rank === "duke" or $rank === "siena" or $rank === "media" or $rank === "famous")
        {
            $player->addAttachment($plugin, "practice.command.flight", true);
            return;
        }

        if($rank === "helper" or $rank === "moderator")
        {
            $player->addAttachment($plugin, "practice.bypass.chatcooldown", true);
            $player->addAttachment($plugin, "practice.staff.chat", true);
            $player->addAttachment($plugin, "practice.staff.notification", true);
            $player->addAttachment($plugin, "practice.command.gamemode", true);
            return;
        }

        if($rank === "admin" or $rank === "manager" or $rank === "owner")
        {
            $player->addAttachment($plugin, "practice.bypass.chatcooldown", true);
            $player->addAttachment($plugin, "practice.staff.chat", true);
            $player->addAttachment($plugin, "practice.staff.notification", true);
            $player->addAttachment($plugin, "practice.command.gamemode", true);
            $player->addAttachment($plugin, "practice.command.gamemode_other", true);
            return;
        }
    }
}