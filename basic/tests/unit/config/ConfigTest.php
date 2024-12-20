<?php

namespace tests\unit\config;

use app\config\Config;
use tests\unit\BaseUnitTest;
use Yii;
use DateTime;

class ConfigTest extends BaseUnitTest
{
    protected function _before()
    {
        parent::_before();
        Yii::$app->cache->flush();
    }

    public function testSetAndGetDate()
    {
        Config::set('registration_start', '2024-12-01');
        Config::set('registration_end', '2024-12-31');

        $registrationStart = DateTime::createFromFormat('Y-m-d', Config::get('registration_start'));
        $registrationEnd = DateTime::createFromFormat('Y-m-d', Config::get('registration_end'));

        $this->assertInstanceOf(DateTime::class, $registrationStart);
        $this->assertInstanceOf(DateTime::class, $registrationEnd);
        $this->assertEquals('2024-12-01', $registrationStart->format('Y-m-d'));
        $this->assertEquals('2024-12-31', $registrationEnd->format('Y-m-d'));
    }

    public function testSetAndGetUrl()
    {
        Config::set('logo_url', 'https://example.com/logo.png');
        $logoUrl = Config::get('logo_url');
        $this->assertEquals('https://example.com/logo.png', $logoUrl);
    }

    public function testSetAndGetText()
    {
        Config::set('welcome_message', 'Welcome to our site!');
        $welcomeMessage = Config::get('welcome_message');
        $this->assertEquals('Welcome to our site!', $welcomeMessage);
    }

    public function testSetAndGetHtml()
    {
        $htmlContent = '<div><h1>Welcome</h1><p>This is a test</p></div>';
        Config::set('embedded_html', $htmlContent);
        $retrievedHtml = Config::get('embedded_html');
        $this->assertEquals($htmlContent, $retrievedHtml);
    }

    public function testDefaultValue()
    {
        $defaultValue = Config::get('non_existing_key', 'default_value');
        $this->assertEquals('default_value', $defaultValue);
    }

    public function testDeleteConfig()
    {
        Config::set('temporary_key', 'temporary_value');
        $this->assertEquals('temporary_value', Config::get('temporary_key'));

        Config::delete('temporary_key');
        $this->assertNull(Config::get('temporary_key'));
    }
}