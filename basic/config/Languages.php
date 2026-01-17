<?php

namespace app\config;

final class Languages
{
    public const EN = 'en';
    public const ES = 'es';

    public const ALL = [
        self::EN,
        self::ES,
    ];

    public const DEFAULT_LANG = self::EN;
}
