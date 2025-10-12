<?php

namespace app\controllers;

use app\models\ContactForm;
use app\models\EntryForm;
use app\models\Flight;
use app\models\LoginForm;
use app\models\Page;
use app\models\Pilot;
use Yii;
use yii\filters\AccessControl;
use yii\helpers\Markdown;
use yii\web\Controller;
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
            ->andWhere(['language' => 'en']) // TODO: Use language
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


        return $this->render('index', [
            'homeContent' => $bodyHtmlContent,
            'lastFlights' => $lastFlights,
            'lastPilots' => $lastPilots,
            'flightModel' => new Flight(),
            'pilotModel' => new Pilot(),
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

    /**
     * Displays about page.
     *
     * @return string
     */
    public function actionAbout()
    {
        return $this->render('about');
    }


    public function actionEntry()
    {
        $model = new EntryForm();

        if ($model->load(Yii::$app->request->post()) && $model->validate()) {
            // valid data received in $model
            // do something meaningful here about $model ...
            return $this->render('entry-confirm', ['model' => $model]);
        } else {
            // either the page is initially displayed or there is some validation error
            return $this->render('entry', ['model' => $model]);
        }
    }
}
