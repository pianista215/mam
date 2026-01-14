<?php

namespace tests\unit\config;

use app\config\Config;
use app\config\ConfigHelper as CK;
use tests\unit\BaseUnitTest;
use DateTime;
use Yii;

class ConfigHelperTest extends BaseUnitTest
{
    protected function _before()
    {
        parent::_before();
        Yii::$app->cache->flush();
    }

    public function testRegistrationDates()
    {
        Config::set(CK::REGISTRATION_START, '2024-12-01');
        Config::set(CK::REGISTRATION_END, '2024-12-31');

        $start = CK::getRegistrationStart();
        $end = CK::getRegistrationEnd();

        $this->assertInstanceOf(DateTime::class, $start);
        $this->assertInstanceOf(DateTime::class, $end);

        $this->assertEquals('2024-12-01', $start->format('Y-m-d'));
        $this->assertEquals('2024-12-31', $end->format('Y-m-d'));
    }

    public function testRegistrationStartLocation()
    {
        Config::set(CK::REGISTRATION_START_LOCATION, 'LEMD');
        $icao = CK::getRegistrationStartLocation();

        $this->assertIsString($icao);
        $this->assertEquals('LEMD', $icao);
    }

    public function testStoragePaths()
    {
        Config::set(CK::CHUNKS_STORAGE_PATH, '/tmp/chunks');
        Config::set(CK::IMAGES_STORAGE_PATH, '/tmp/images');

        $this->assertEquals('/tmp/chunks', CK::getChunksStoragePath());
        $this->assertEquals('/tmp/images', CK::getImagesStoragePath());
    }

    public function testTokenAndCharter()
    {
        Config::set(CK::TOKEN_LIFE_H, '24');
        Config::set(CK::CHARTER_RATIO, '0.15');

        $this->assertEquals(24, CK::getTokenLifeH());
        $this->assertEquals(0.15, CK::getCharterRatio());
    }

    public function testGlobalSettings()
    {
        Config::set(CK::AIRLINE_NAME, 'MamAirlines');
        Config::set(CK::NO_REPLY_MAIL, 'no-reply@example.com');
        Config::set(CK::SUPPORT_MAIL, 'support@example.com');

        $this->assertEquals('MamAirlines', CK::getAirlineName());
        $this->assertEquals('no-reply@example.com', CK::getNoReplyMail());
        $this->assertEquals('support@example.com', CK::getSupportMail());
    }

    public function testFooterUrls()
    {
        Config::set(CK::X_URL, 'https://x.com/');
        Config::set(CK::INSTAGRAM_URL, 'https://instagram.com/');
        Config::set(CK::FACEBOOK_URL, 'https://facebook.com/');

        $this->assertEquals('https://x.com/', CK::getXUrl());
        $this->assertEquals('https://instagram.com/', CK::getInstagramUrl());
        $this->assertEquals('https://facebook.com/', CK::getFacebookUrl());
    }

    public function testUnsetValuesReturnNullOrDefaults()
    {
        Config::delete('non_existing_key');

        $this->assertNull(Config::get('non_existing_key')); // para fechas y strings opcionales
    }
}
