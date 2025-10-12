<?php

namespace app\controllers;

use app\models\Page;
use yii\data\ActiveDataProvider;
use yii\helpers\Markdown;
use yii\web\Controller;
use yii\web\NotFoundHttpException;
use yii\filters\VerbFilter;

/**
 * PageController implements the CRUD actions for Page model.
 */
class PageController extends Controller
{

    public function actionView($code, $lang = 'en')
    {
        $page = Page::find()->where(['code' => $code])->one();
        if (!$page) {
            throw new NotFoundHttpException("Page not found: $code");
        }

        $content = $page->getPageContents()->where(['language' => $lang])->one();
        if (!$content) {
            throw new NotFoundHttpException("Content not found for language: $lang");
        }

        $htmlContent = Markdown::process($content->content_md, 'gfm');

        return $this->render('view', [
            'page' => $page,
            'title' => $content->title,
            'content' => $htmlContent,
        ]);
    }
}
