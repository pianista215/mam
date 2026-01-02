<?php
namespace app\components;

use yii\base\BootstrapInterface;

class LanguageSelector implements BootstrapInterface
{
    public $supportedLanguages = ['en', 'es'];

    public function bootstrap($app)
    {
        $preferredLanguage = $app->request->cookies->getValue('language', null);
        if (empty($preferredLanguage)) {
            $preferredLanguage = $app->request->getPreferredLanguage($this->supportedLanguages);
        }

        // Get only the code, in order to match all spanish people in 'es'
        $preferredLanguage = substr($preferredLanguage, 0, 2);
        if (!in_array($preferredLanguage, $this->supportedLanguages)) {
            $preferredLanguage = 'en';
        }
        $app->language = $preferredLanguage;

        if ($preferredLanguage === 'es') {
            $app->formatter->locale = 'es_ES';
        } else {
            $app->formatter->locale = 'en_US';
        }
    }
}
