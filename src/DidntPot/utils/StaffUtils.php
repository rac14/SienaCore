<?php

namespace DidntPot\utils;

class StaffUtils
{
    /**
     * @param string $type
     * @return string
     */
    public static function sendStaffNotification(string $type): string
    {
        switch($type)
        {
            case "rank_change":
                $message = "§7[§d{name}: §7updated {target}'s rank to {rank}]";
            return $message;

            case "gamemode_change":
                $message = "§7[§d{name}: §7updated their gamemode to {newgamemode}]";
            return $message;

            case "gamemode_change_other":
                $message = "§7[§d{name}: §7updated {target}'s gamemode to {newgamemode}]";
            return $message;

            default:
            return "";
        }
    }
}