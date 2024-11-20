<?php

namespace app\controllers;

use Yii;
use yii\filters\AccessControl;
use yii\web\Controller;
use yii\web\Response;
use yii\filters\VerbFilter;
use app\models\LoginForm;
use app\models\ContactForm;
use app\models\EntryForm;

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
        return $this->render('index');
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
     * Displays contact page.
     *
     * @return Response|string
     */
    public function actionContact()
    {
        $model = new ContactForm();
        if ($model->load(Yii::$app->request->post()) && $model->contact(Yii::$app->params['adminEmail'])) {
            Yii::$app->session->setFlash('contactFormSubmitted');

            return $this->refresh();
        }
        return $this->render('contact', [
            'model' => $model,
        ]);
    }

    // TODO: REMOVE WHEN NO LONGER IS NEEDED TO SET ADMIN BY THAT
    // index.php?r=site%2Ftoggle-admin-role
    public function actionToggleAdminRole() {
        if (!Yii::$app->user->isGuest) {
            $auth = Yii::$app->authManager;
            $actualRoles = $auth->getRolesByUser(Yii::$app->user->id);
            if(isset($actualRoles['admin'])) {
                $adminRole = $actualRoles['admin'];
                $auth->revoke($adminRole, Yii::$app->user->id);
            } else {
                $adminRole = $auth->getRole('admin');
                $auth->assign($adminRole, Yii::$app->user->id);
            }
        }
        return $this->redirect(['index']);
    }

    // TODO: REMOVE WHEN ROLES ARE FINISHED AND ALL IS SET IN SQL FOR DB
    // index.php?r=site%2Fgenerate-roles
    public function actionGenerateRoles()
    {
        Yii::$app->db->createCommand()->delete('`auth_assignment`')->execute();
        Yii::$app->db->createCommand()->delete('`auth_item_child`')->execute();
        Yii::$app->db->createCommand()->delete('`auth_item`')->execute();
        Yii::$app->db->createCommand()->delete('`auth_rule`')->execute();

        $auth = Yii::$app->authManager;

        // Pilot
        $reportFlight = $auth->createPermission('reportFlight');
        $reportFlight->description = 'Report a flight from Acars';
        $auth->add($reportFlight);

        $submitFpl = $auth->createPermission('submitFpl');
        $submitFpl->description = 'Submit a flight plan';
        $auth->add($submitFpl);

        $pilot = $auth->createRole('pilot');
        $auth->add($pilot);
        $auth->addChild($pilot, $reportFlight);
        $auth->addChild($pilot, $submitFpl);

        // VFR validator
        $validateVfrFlight = $auth->createPermission('validateVfrFlight');
        $validateVfrFlight->description = 'Validate a VFR flight';
        $auth->add($validateVfrFlight);

        $vfrValidator = $auth->createRole('vfrValidator');
        $auth->add($vfrValidator);
        $auth->addChild($vfrValidator, $validateVfrFlight);

        // IFR validator
        $validateIfrFlight = $auth->createPermission('validateIfrFlight');
        $validateIfrFlight->description = 'Validate a IFR flight';
        $auth->add($validateIfrFlight);

        $ifrValidator = $auth->createRole('ifrValidator');
        $auth->add($ifrValidator);
        $auth->addChild($ifrValidator, $validateIfrFlight);

        // Fleet Manager
        $moveAircraft = $auth->createPermission('moveAircraft');
        $moveAircraft->description = 'Move the aircraft to a new location';
        $auth->add($moveAircraft);

        $cancelAircraftReservation = $auth->createPermission('cancelAircraftReservation');
        $cancelAircraftReservation->description = 'Cancel the reservation of other user for the aircraft';
        $auth->add($cancelAircraftReservation);

        $fleetManager = $auth->createRole('fleetManager');
        $auth->add($fleetManager);
        $auth->addChild($fleetManager, $moveAircraft);
        $auth->addChild($fleetManager, $cancelAircraftReservation);

        // Certifier
        $issueLicense = $auth->createPermission('issueLicense');
        $issueLicense->description = 'Issues or renew a license to a pilot';
        $auth->add($issueLicense);

        // Needed???
        $validateLicenseFlight = $auth->createPermission('validateLicenseFlight');
        $validateLicenseFlight->description = 'Validate a flight required to obtain a license';
        $auth->add($validateLicenseFlight);

        $certifier = $auth->createRole('certifier');
        $auth->add($certifier);
        $auth->addChild($certifier, $issueLicense);
        $auth->addChild($certifier, $validateLicenseFlight);

        // Route Manager
        $routeCrud = $auth->createPermission('routeCrud');
        $routeCrud->description = 'Can create, delete or modify routes';
        $auth->add($routeCrud);

        $routeManager = $auth->createRole('routeManager');
        $auth->add($routeManager);
        $auth->addChild($routeManager, $routeCrud);

        // Tour Manager
        $tourCrud = $auth->createPermission('tourCrud');
        $tourCrud->description = 'Can create, delete or modify tours';
        $auth->add($tourCrud);

        $tourManager = $auth->createRole('tourManager');
        $auth->add($tourManager);
        $auth->addChild($tourManager, $tourCrud);

        // Admin
        $userCrud = $auth->createPermission('userCrud');
        $userCrud->description = 'Can create, delete, modify, activate and reset users';
        $auth->add($userCrud);

        // TODO: Think if the aircrafts cruds and airport cruds should be on other role
        $aircraftTypeCrud = $auth->createPermission('aircraftTypeCrud');
        $aircraftTypeCrud->description = 'Can create, delete, and modify aircraft types';
        $auth->add($aircraftTypeCrud);

        $aircraftCrud = $auth->createPermission('aircraftCrud');
        $aircraftCrud->description = 'Can create, delete, and modify aircrafts';
        $auth->add($aircraftCrud);

        $airportCrud = $auth->createPermission('airportCrud');
        $airportCrud->description = 'Can create, delete, and modify airports';
        $auth->add($airportCrud);

        // TODO: Think if we need to manage countries, or we can consider them static
        $countryCrud = $auth->createPermission('countryCrud');
        $countryCrud->description = 'Can create, delete, and modify countries';
        $auth->add($countryCrud);

        $roleAssignment = $auth->createPermission('roleAssignment');
        $roleAssignment->description = 'Can assign or remove roles to other users';
        $auth->add($roleAssignment);

        $admin = $auth->createRole('admin');
        $auth->add($admin);
        $auth->addChild($admin, $userCrud);
        $auth->addChild($admin, $aircraftTypeCrud);
        $auth->addChild($admin, $aircraftCrud);
        $auth->addChild($admin, $airportCrud);
        $auth->addChild($admin, $countryCrud);
        $auth->addChild($admin, $roleAssignment);
        $auth->addChild($admin, $pilot);
        $auth->addChild($admin, $vfrValidator);
        $auth->addChild($admin, $ifrValidator);
        $auth->addChild($admin, $fleetManager);
        $auth->addChild($admin, $certifier);
        $auth->addChild($admin, $routeManager);
        $auth->addChild($admin, $tourManager);

        return $this->render('say', ['message' => 'Roles created']);
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

    public function actionSay($message = 'Hello')
    {
        return $this->render('say', ['message' => $message]);
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
