<?php

namespace app\controllers;

use app\models\forms\LoginForm;
use app\models\Aircraft;
use app\models\ContactForm;
use app\models\EntryForm;
use app\models\Flight;
use app\models\Page;
use app\models\Pilot;
use app\models\Route;
use Yii;
use yii\filters\AccessControl;
use yii\helpers\Markdown;
use yii\web\Controller;
use yii\web\Cookie;
use yii\web\Response;
use yii\filters\VerbFilter;

class SiteController extends Controller
{
    /**
     * {@inheritdoc}
     */
    public function behaviors()
    {
        return [
            'access' => [
                'class' => AccessControl::class,
                'only' => ['logout'],
                'rules' => [
                    [
                        'actions' => ['logout'],
                        'allow' => true,
                        'roles' => ['@'],
                    ],
                ],
            ],
            'verbs' => [
                'class' => VerbFilter::class,
                'actions' => [
                    'logout' => ['post'],
                ],
            ],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function actions()
    {
        return [
            'error' => [
                'class' => 'yii\web\ErrorAction',
            ],
            'captcha' => [
                'class' => 'yii\captcha\CaptchaAction',
                'fixedVerifyCode' => YII_ENV_TEST ? 'testme' : null,
            ],
        ];
    }

    /**
     * Displays homepage.
     *
     * @return string
     */
    public function actionIndex()
    {
        $homePage = Page::findOne(['code' => 'home']);
        $content = $homePage->getPageContents()
                ->andWhere(['language' => Yii::$app->language])
                ->one();

        $bodyHtmlContent = Markdown::process($content->content_md, 'gfm');

        $lastFlights = Flight::find()
                ->with(['pilot', 'aircraft.aircraftConfiguration.aircraftType'])
                ->orderBy(['creation_date' => SORT_DESC])
                ->limit(5)
                ->all();

        $lastPilots = Pilot::find()
                ->where(['not', ['license' => null]])
                ->orderBy(['registration_date' => SORT_DESC])
                ->limit(5)
                ->all();

        $totalPilots = Pilot::find()->count();
        $totalAircraft = Aircraft::find()->count();
        $totalRoutes = Route::find()->count();
        $totalFlights = Flight::find()->count();


        return $this->render('index', [
            'homeContent' => $bodyHtmlContent,
            'lastFlights' => $lastFlights,
            'lastPilots' => $lastPilots,
            'flightModel' => new Flight(),
            'pilotModel' => new Pilot(),
            'totalPilots' => $totalPilots,
            'totalAircraft' => $totalAircraft,
            'totalRoutes' => $totalRoutes,
            'totalFlights' => $totalFlights
        ]);
    }

    /**
     * Login action.
     *
     * @return Response|string
     */
    public function actionLogin()
    {
        if (!Yii::$app->user->isGuest) {
            return $this->goHome();
        }

        $model = new LoginForm();
        if ($model->load(Yii::$app->request->post()) && $model->login()) {
            return $this->goBack();
        }

        $model->password = '';
        return $this->render('login', [
            'model' => $model,
        ]);
    }

    /**
     * Logout action.
     *
     * @return Response
     */
    public function actionLogout()
    {
        Yii::$app->user->logout();

        return $this->goHome();
    }

    public function actionLanguage()
    {
        $language = Yii::$app->request->post('language');

        if (in_array($language, ['en', 'es'])) {
            Yii::$app->language = $language;

            $cookie = new \yii\web\Cookie([
                'name' => 'language',
                'value' => $language,
                'expire' => time() + 10 * 365 * 24 * 60 * 60, // 10 years
            ]);
            Yii::$app->response->cookies->add($cookie);
        }

        return $this->redirect(Yii::$app->request->referrer ?: Yii::$app->homeUrl);
    }


}
