<?php

namespace app\helpers;

class CustomRules
{
    public static function removeSpaces($value)
    {
        return preg_replace('/\s+/', '', $value);
    }
}