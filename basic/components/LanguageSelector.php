<?php
namespace app\components;

use yii\base\BootstrapInterface;

class LanguageSelector implements BootstrapInterface
{
    public $supportedLanguages = ['en', 'es'];

    public function bootstrap($app)
    {
        $preferred = $app->request->getPreferredLanguage($this->supportedLanguages);
        $app->language = $preferred ?: 'en';
    }
}
