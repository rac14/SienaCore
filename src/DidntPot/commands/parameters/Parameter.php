<?php

namespace DidntPot\commands\parameters;

interface Parameter
{
    const PARAMTYPE_STRING = 0;
    const PARAMTYPE_INTEGER = 1;
    const PARAMTYPE_TARGET = 2;
    const PARAMTYPE_BOOLEAN = 3;
    const PARAMTYPE_FLOAT = 4;
    const PARAMTYPE_ANY = 5;

    const NO_PERMISSION = "none";

    /**
     * @return string
     */
    function getName(): string;

    /**
     * @return bool
     */
    function hasPermission(): bool;

    /**
     * @return string
     */
    function getPermission(): string;
}