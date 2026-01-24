<?php

namespace app\config;
use DateTime;

class ConfigHelper
{
    // Registration
    public const REGISTRATION_START = 'registration_start';
    public const REGISTRATION_END = 'registration_end';
    public const REGISTRATION_START_LOCATION = 'registration_start_location';

    // Settings
    public const CHUNKS_STORAGE_PATH = 'chunks_storage_path';
    public const IMAGES_STORAGE_PATH = 'images_storage_path';
    public const ACARS_RELEASES_PATH = 'acars_releases_path';
    public const ACARS_INSTALLER_NAME = 'acars_installer_name';
    public const TOKEN_LIFE_H = 'token_life_h';
    public const CHARTER_RATIO = 'charter_ratio';

    // Global
    public const AIRLINE_NAME = 'airline_name';
    public const NO_REPLY_MAIL = 'no_reply_mail';
    public const SUPPORT_MAIL = 'support_mail';

    // Footer
    public const X_URL = 'x_url';
    public const INSTAGRAM_URL = 'instagram_url';
    public const FACEBOOK_URL = 'facebook_url';

    public static function getRegistrationStart(): ?DateTime
    {
        $value = Config::get(self::REGISTRATION_START);
        if ($value === null) {
            return null;
        }
        return DateTime::createFromFormat('Y-m-d', $value);
    }

    public static function getRegistrationEnd(): ?DateTime
    {
        $value = Config::get(self::REGISTRATION_END);
        if ($value === null) {
            return null;
        }
        return DateTime::createFromFormat('Y-m-d', $value);
    }

    public static function getRegistrationStartLocation(): string
    {
        return Config::get(self::REGISTRATION_START_LOCATION);
    }

    public static function getChunksStoragePath(): string
    {
        return Config::get(self::CHUNKS_STORAGE_PATH) ?? '/opt/mam/chunks';
    }

    public static function getImagesStoragePath(): string
    {
        return Config::get(self::IMAGES_STORAGE_PATH) ?? '/opt/mam/images';
    }

    public static function getAcarsReleasesPath(): string
    {
        return Config::get(self::ACARS_RELEASES_PATH) ?? '/opt/mam/acars-releases';
    }

    public static function getAcarsInstallerName(): string
    {
        return Config::get(self::ACARS_INSTALLER_NAME) ?? 'Setup.exe';
    }

    public static function getTokenLifeH(): int
    {
        return (int) Config::get(self::TOKEN_LIFE_H) ?? 24;
    }

    public static function getCharterRatio(): float
    {
        return (float) Config::get(self::CHARTER_RATIO) ?? 0.1;
    }

    public static function getAirlineName(): string
    {
        return Config::get(self::AIRLINE_NAME) ?? 'AirlineName';
    }

    public static function getNoReplyMail(): string
    {
        return Config::get(self::NO_REPLY_MAIL) ?? 'no-reply@airlinename.com';
    }

    public static function getSupportMail(): string
    {
        return Config::get(self::SUPPORT_MAIL) ?? 'support@airlinename.com';
    }

    public static function getXUrl(): ?string
    {
        return Config::get(self::X_URL);
    }

    public static function getInstagramUrl(): ?string
    {
        return Config::get(self::INSTAGRAM_URL);
    }

    public static function getFacebookUrl(): ?string
    {
        return Config::get(self::FACEBOOK_URL);
    }

}
