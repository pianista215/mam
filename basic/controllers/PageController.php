<?php

namespace app\controllers;

use app\helpers\LoggerTrait;
use app\models\Page;
use app\models\PageContent;
use app\models\Tour;
use Yii;
use yii\web\Controller;
use yii\web\ForbiddenHttpException;
use yii\web\NotFoundHttpException;
use app\rbac\constants\Permissions;

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

    public function actionEdit($code, $language, $type)
    {
        $page = Page::find()->where(['code' => $code])->one();

        if (!$page) {
            if ($type === Page::TYPE_TOUR) {
                $page = Tour::findOrCreateTourPage($code);
            }
        }

        if (!$page) {
            throw new NotFoundHttpException();
        }

        if (!Yii::$app->user->can(Permissions::EDIT_PAGE_CONTENT, ['page' => $page])) {
            $this->logInfo('User without permissions trying to edit page', ['page' => $page, 'user' => Yii::$app->user->identity->license]);
            throw new ForbiddenHttpException();
        }

        $model = $page->getPageContents()->where(['language' => $language])->one();

        if (!$model) {
            $model = new PageContent([
                'page_id' => $page->id,
                'language' => $language,
            ]);
        }

        if ($model->load(Yii::$app->request->post())) {
            if ($page->type !== Page::TYPE_SITE) {
                $model->title = '';
            }

            if ($model->save()) {
                $this->logInfo('Page content saved', ['model' => $model, 'user' => Yii::$app->user->identity->license]);

                if ($code === Page::HOME_PAGE) {
                    return $this->redirect(['/']);
                } elseif ($page->type === Page::TYPE_TOUR) {
                    $tourId = Tour::extractIdFromPageCode($code);
                    return $this->redirect(['tour/view', 'id' => $tourId]);
                } else {
                    return $this->redirect(['view', 'code' => $code]);
                }
            }
        }

        $title = $this->buildEditTitle($page, $language);

        return $this->render('edit', [
            'page' => $page,
            'model' => $model,
            'language' => $language,
            'title' => $title,
        ]);
    }

    private function buildEditTitle(Page $page, string $language): string
    {
        switch ($page->type) {
            case Page::TYPE_TOUR:
                $tourId = Tour::extractIdFromPageCode($page->code);
                $tour = Tour::findOne($tourId);
                $description = Yii::t('app', 'Tour page') . ': ' . ($tour ? $tour->name : $page->code);
                break;
            case Page::TYPE_COMPONENT:
                $description = Yii::t('app', 'Component') . ': ' . $page->code;
                break;
            default:
                $description = Yii::t('app', 'Page') . ': ' . $page->code;
        }

        return "({$language}) " . Yii::t('app', 'Editing') . ' ' . $description;
    }
}

