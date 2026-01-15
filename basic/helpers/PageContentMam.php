<?php

namespace app\helpers;

use app\models\Page;
use app\models\PageContent;
use yii\helpers\Html;
use yii\helpers\HtmlPurifier;
use yii\helpers\Markdown;
use Yii;

class PageContentMam
{
    private static $cssRegistered = false;

    public static function render(string $code, array $options = [], ?string $language = null): string
    {
        if (!self::$cssRegistered) {
            $css = <<<CSS
            .page-content-mam-container {
                position: relative;
            }
            .page-content-mam-edit {
                position: absolute;
                top: 4px;
                right: 4px;
                display: none;
                cursor: pointer;
                background: rgba(0,0,0,0.6);
                color: #fff;
                padding: 4px 8px;
                border-radius: 3px;
                font-size: 0.9rem;
                text-decoration: none;
                z-index: 10;
            }
            .page-content-mam-container:hover .page-content-mam-edit {
                display: block;
            }
            CSS;
            Yii::$app->view->registerCss($css);
            self::$cssRegistered = true;
        }

        $language = $language ?? substr(Yii::$app->language, 0, 2);

        $fallbackText = $options['fallbackText'] ?? null;

        $page = Page::find()->where(['code' => $code])->one();
        $content = null;

        if($page) {
            $content = $page->getPageContents()
                ->where(['language' => $language])
                ->one()
                ?? $page->getPageContents()->where(['language' => 'en'])->one();
        }

        $out = Html::beginTag('div', ['class' => 'page-content-mam-container']);

        if (!Yii::$app->user->isGuest) {
            $label = $content ? Yii::t('app', 'Edit') : Yii::t('app', 'Create content');
            $out .= Html::a(
                Yii::t('app', $label),
                ['page/edit', 'code' => $code, 'language' => $language],
                ['class' => 'page-content-mam-edit']
            );
        }

        if ($content) {
            $html = Markdown::process($content->content_md, 'gfm');
            $out .= HtmlPurifier::process($html);
        } elseif ($fallbackText) {
            $out .= Html::tag('div', Html::encode($fallbackText));
        }

        $out .= Html::endTag('div');

        return $out;
    }
}
