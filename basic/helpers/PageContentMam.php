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

    public static function render(string $code, ?string $language = null): string
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

        $page = Page::find()->where(['code' => $code])->one();
        if (!$page) {
            return '';
        }

        $content = $page->getPageContents()
            ->where(['language' => $language])
            ->one()
            ?? $page->getPageContents()->where(['language' => 'en'])->one();

        if (!$content) {
            return '';
        }

        $html = Markdown::process($content->content_md, 'gfm');
        $html = HtmlPurifier::process($html);

        $out = Html::beginTag('div', ['class' => 'page-content-mam-container']);

        // Overlay editar
        if (!Yii::$app->user->isGuest) {
            $out .= Html::a(
                Yii::t('app', 'Edit'),
                ['page/edit', 'code' => $code, 'language' => $content->language],
                ['class' => 'page-content-mam-edit']
            );
        }

        $out .= $html;
        $out .= Html::endTag('div');

        return $out;
    }
}
