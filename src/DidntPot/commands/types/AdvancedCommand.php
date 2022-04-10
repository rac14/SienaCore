<?php

namespace DidntPot\commands\types;

use DidntPot\commands\parameters\BaseParameter;
use DidntPot\commands\parameters\Parameter;
use DidntPot\commands\parameters\SimpleParameter;
use DidntPot\utils\Utils;
use JetBrains\PhpStorm\Pure;
use pocketmine\command\Command;
use pocketmine\command\CommandSender;

class AdvancedCommand extends Command
{
    protected array $parameters;

    /**
     * BaseCommand constructor.
     * @param $name
     * @param string $description
     * @param null $usageMessage
     */
    public function __construct($name, string $description = "", $usageMessage = null)
    {
        parent::__construct($name, $description, $usageMessage);
        //parent::setPermission("practice.permission.$name");
        $this->parameters = array();
    }

    /**
     * @param array $params
     */
    public function setParameters(array $params)
    {
        $this->parameters = $params;
    }

    /**
     * @param CommandSender $sender
     * @param string[] $args
     *
     * @return mixed
     */
    public function canExecute(CommandSender $sender, array $args): bool
    {
        $execute = false;
        $result = false;
        $msg = null;
        if ($this->areParametersCorrect()) {
            $len = count($args);
            if ($len > 0 and $this->hasParamGroup($args[0])) {
                $execute = true;
            }
        }

        if ($execute) {
            if ($this->checkPermissions($sender, $args[0])) {
                $paramGroup = $this->getParamGroupFrom($args[0]);
                if ($this->hasProperParamLen($args, $paramGroup) and $this->hasProperParamTypes($args, $paramGroup)) {
                    $result = true;
                } else $msg = $this->getUsageOf($paramGroup, false);

            } else $msg = "";

        } else $msg = $this->getFullUsage();

        if (!is_null($msg)) $sender->sendMessage($msg);

        return $result;
    }

    /**
     * @return bool
     */
    private function areParametersCorrect(): bool
    {
        $result = true;

        if (is_array($this->parameters)) {
            $size = count($this->parameters);
            for ($v = 0; $v < $size; $v++) {
                $group = $this->parameters[$v];
                if (!is_array($group)) {
                    $result = false;
                    break;
                }
            }
        }

        return $result;
    }

    /**
     * @param string $name
     * @return bool
     */
    #[Pure] private function hasParamGroup(string $name): bool
    {
        return $this->getParamGroupFrom($name) != null;
    }

    /**
     * @param string $name
     * @return mixed
     */
    #[Pure] protected function getParamGroupFrom(string $name): mixed
    {
        $paramGroup = null;

        $keys = array_keys($this->parameters);

        foreach ($keys as $key) {
            if (is_int($key) and is_array($this->parameters[$key])) {
                $arr = $this->parameters[$key];
                if (is_array($arr) and count($arr) > 0) {
                    $parameter = $arr[0];
                    if ($parameter instanceof BaseParameter) {
                        $theName = $parameter->getName();
                        if ($theName === $name) {
                            $paramGroup = $arr;
                            break;
                        }
                    } elseif ($parameter instanceof SimpleParameter) {
                        if ($parameter->hasExactValues()) {
                            if ($parameter->isExactValue($name)) {
                                $paramGroup = $arr;
                                break;
                            }
                        } else {
                            $paramGroup = $arr;
                        }
                    }
                }
            } else if (is_string($key)) {
                $paramGroup = $this->parameters[$key];
            }
        }
        return $paramGroup;
    }

    /**
     * @param CommandSender $sender
     * @param string $paramGroup
     * @return bool
     */
    protected function checkPermissions(CommandSender $sender, string $paramGroup): bool
    {
        return true;
    }

    /**
     * @param array $args
     * @param array $paramGroup
     * @return bool
     */
    #[Pure] protected function hasProperParamLen(array $args, array $paramGroup): bool
    {
        $argsLen = count($args);
        $minLen = 0;
        $maxLen = 0;

        $result = false;

        foreach ($paramGroup as $parameter) {
            $addToLen = true;
            if ($parameter instanceof SimpleParameter) {
                if ($parameter->isOptional()) {
                    $addToLen = false;
                }
            }
            if ($addToLen) $minLen += 1;
            $maxLen += 1;
        }

        if ($minLen === $maxLen) {
            $result = $argsLen === $maxLen;
        } else {
            $result = $argsLen >= $minLen and $argsLen <= $maxLen;
        }
        return $result;
    }

    /**
     * @param array $args
     * @param array $paramGroup
     * @return bool
     */
    #[Pure] protected function hasProperParamTypes(array $args, array $paramGroup): bool
    {
        $count = 0;
        $result = true;

        foreach ($args as $paramArg) {
            $parameter = $paramGroup[$count];
            if ($parameter instanceof BaseParameter) {
                if (is_string(($paramArg))) {
                    if ($paramArg !== $parameter->getName()) {
                        $result = false;
                        break;
                    }
                } else {
                    $result = false;
                    break;
                }
            } else if ($parameter instanceof SimpleParameter) {
                if (is_string($paramArg)) {
                    if (!$this->hasProperParamType($paramArg, $parameter)) {
                        $result = false;
                        break;
                    }
                } else {
                    $result = false;
                    break;
                }
            }
            $count++;
        }

        return $result;
    }

    /**
     * @param string $s
     * @param SimpleParameter $param
     * @return bool
     */
    #[Pure] public function hasProperParamType(string $s, SimpleParameter $param): bool
    {
        $result = false;

        switch ($param->getParameterType()) {
            case Parameter::PARAMTYPE_INTEGER:
                $result = Utils::canParse($s, true);
                break;

            case Parameter::PARAMTYPE_FLOAT:
                $result = Utils::canParse($s, false);
                break;
            case Parameter::PARAMTYPE_BOOLEAN:
                $result = $this->isBoolean($s);
                break;
            case Parameter::PARAMTYPE_STRING:
            case Parameter::PARAMTYPE_ANY:
            case Parameter::PARAMTYPE_TARGET:
                $result = true;
                break;
            default:
        }

        if ($result) {
            if ($param->hasExactValues()) {
                if (!$param->isExactValue($s)) {
                    $result = false;
                }
            }
        }
        return $result;
    }

    /**
     * @param string $boolean
     * @return bool
     */
    #[Pure] protected function isBoolean(string $boolean): bool
    {
        return !is_null($this->getBoolean($boolean));
    }

    /**
     * @param string $s
     * @return bool|null
     */
    protected function getBoolean(string $s): ?bool
    {
        $result = null;
        if ($s === "enable" or $s === "on" or $s == "true") {
            $result = true;
        } else if ($s === "disable" or $s === "off" or $s === "false") {
            $result = false;
        }
        return $result;
    }

    /**
     * @param array $paramGrp
     * @param bool $fullMsg
     * @return String
     */
    protected function getUsageOf(array $paramGrp, bool $fullMsg): string
    {
        $theCommandName = parent::getName();
        $str = ($fullMsg ? " - /$theCommandName " : "Usage: /$theCommandName ");
        $count = 0;
        $desc = null;
        $len = count($paramGrp) - 1;

        foreach ($paramGrp as $parameter) {
            if ($parameter instanceof Parameter) {

                if ($count === 0) {
                    $name = $parameter->getName();
                    $s = ($len === 0 ? "" : " ");

                    if ($parameter instanceof BaseParameter) {
                        $str = $str . $name . $s;
                        $desc = $parameter->getDescription();
                    } else if ($parameter instanceof SimpleParameter) {

                        $str = $str . $this->getParameterType($parameter) . $s;

                        if ($parameter->hasDescription()) $desc = $parameter->getDescription();
                    }
                } else {
                    $space = ($count === $len ? "" : " ");
                    if ($parameter instanceof SimpleParameter) {
                        $str = $str . $this->getParameterType($parameter) . $space;
                    }
                }
                $count++;
            }
        }

        if (!is_null($desc)) {
            $str = $str . " - " . $desc;
        }
        return $str;
    }

    /**
     * @param SimpleParameter $param
     * @return string
     */
    #[Pure] protected function getParameterType(SimpleParameter $param): string
    {
        $string = $param->getName();
        $result = $string;
        switch ($param->getParameterType()) {
            case Parameter::PARAMTYPE_INTEGER:
                $result = "[int : $string]";
                break;
            case Parameter::PARAMTYPE_FLOAT:
                $result = "[float : $string]";
                break;
            case Parameter::PARAMTYPE_BOOLEAN:
                $result = "[boolean : $string]";
                break;
            case Parameter::PARAMTYPE_TARGET:
                $result = "[target : $string]";
                break;
            case Parameter::PARAMTYPE_STRING:
                $result = "[string : $string]";
                break;
            case Parameter::PARAMTYPE_ANY:
                $result = "[any : $string]";
                break;
            default:
        }
        return $result;
    }

    /**
     * @return string
     */
    protected function getFullUsage(): string
    {
        $array = array();

        $size = count($this->parameters);

        for ($i = 0; $i < $size; $i++) {
            $arr = $this->parameters[$i];
            if (is_array($arr) and count($arr) > 0) {
                $first = $arr[0];
                if ($first instanceof Parameter) {
                    $name = $first->getName();
                    array_push($array, $name);
                }
            }
        }

        $result = "All the " . parent::getName() . " commands:\n";
        $count = 0;
        $len = count($array) - 1;

        foreach ($array as $string) {
            if (is_string($string)) {
                $newLine = "\n";

                if ($count == $len) {
                    $newLine = "";
                }
                $result = $result . $this->getUsageOf($this->getParamGroupFrom($string), true) . $newLine;
                $count++;
            }
        }

        return $result;
    }

    /**
     * @param CommandSender $sender
     * @param string $commandLabel
     * @param string[] $args
     *
     * @return bool
     */
    public function execute(CommandSender $sender, string $commandLabel, array $args): bool
    {
        return false;
    }
}