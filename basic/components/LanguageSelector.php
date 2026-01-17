<?php
namespace app\components;

use app\config\Languages;
use yii\base\BootstrapInterface;

class LanguageSelector implements BootstrapInterface
{
    public function bootstrap($app)
    {
        $preferredLanguage = $app->request->cookies->getValue('language', null);
        if (empty($preferredLanguage)) {
            $preferredLanguage = $app->request->getPreferredLanguage(Languages::ALL);
        }

        // Get only the code, in order to match all spanish people in 'es'
        $preferredLanguage = substr($preferredLanguage, 0, 2);
        if (!in_array($preferredLanguage, Languages::ALL)) {
            $preferredLanguage = Languages::DEFAULT_LANG;
        }
        $app->language = $preferredLanguage;

        if ($preferredLanguage === Languages::ES) {
            $app->formatter->locale = 'es_ES';
        } else {
            $app->formatter->locale = 'en_US';
        }
    }
}
