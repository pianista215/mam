<?php

namespace app\controllers;

use app\helpers\LoggerTrait;
use app\models\Page;
use app\models\PageContent;
use Yii;
use yii\data\ActiveDataProvider;
use yii\helpers\HtmlPurifier;
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

        if (Yii::$app->user->isGuest && $page->type !== Page::TYPE_SITE) {
            $this->logInfo('Forbidden page access for guest', ['page' => $page]);
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

        return $this->render('view', [
            'page' => $page,
            'title' => $content->title
        ]);
    }

    public function actionEdit($code, $language)
    {
        if (Yii::$app->user->isGuest) { // TODO: UNAI PERMISOS
            throw new ForbiddenHttpException();
        }

        if (!in_array($language, ['en', 'es'])) { // TODO PageContent model
            throw new NotFoundHttpException();
        }

        $page = Page::find()->where(['code' => $code])->one(); // Upsert tours/others?
        if (!$page) {
            throw new NotFoundHttpException();
        }

        $model = $page->getPageContents()->where(['language' => $language])->one();

        if (!$model) {
            $model = new PageContent([
                'page_id' => $page->id,
                'language' => $language,
            ]);
        }

        if ($model->load(Yii::$app->request->post()) && $model->save()) {
            $this->logInfo('Page content saved', ['model' => $model, 'user' => Yii::$app->user->identity->license]);
            if($code === 'home') {
                return $this->redirect(['/']);
            } else {
                return $this->redirect(['view', 'code' => $code]);
            }

        }

        return $this->render('edit', [
            'page' => $page,
            'model' => $model,
            'language' => $language,
        ]);
    }
}

