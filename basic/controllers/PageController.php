<?php

namespace app\controllers;

use app\helpers\LoggerTrait;
use app\models\Page;
use Yii;
use yii\data\ActiveDataProvider;
use yii\helpers\Markdown;
use yii\web\Controller;
use yii\web\ForbiddenHttpException;
use yii\web\NotFoundHttpException;
use yii\filters\VerbFilter;

/**
 * PageController implements the CRUD actions for Page model.
 */
class PageController extends Controller
{
    use LoggerTrait;

    public function actionView($code)
    {
        $page = Page::find()->where(['code' => $code])->one();
        if (!$page) {
            $this->logInfo('Page not found', ['code' => $code]);
            throw new NotFoundHttpException();
        }

        if (!$page->public && Yii::$app->user->isGuest) {
            $this->logInfo('Forbidden page access for guest', ['code' => $code]);
            throw new ForbiddenHttpException();
        }

        $userLang = Yii::$app->language;
        $content = $page->getPageContents()->where(['language' => $userLang])->one();

        if (!$content) {
            $this->logInfo('Content not found for user language', [
                'code' => $code,
                'user_language' => $userLang
            ]);

            $content = $page->getPageContents()->where(['language' => 'en'])->one();
        }

        if (!$content) {
            $this->logInfo('Content not found in any language (including fallback en)', [
                'code' => $code,
            ]);
            throw new NotFoundHttpException();
        }

        $htmlContent = Markdown::process($content->content_md, 'gfm');

        return $this->render('view', [
            'page' => $page,
            'title' => $content->title,
            'content' => $htmlContent,
        ]);
    }
}

