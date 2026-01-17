<?php

namespace app\controllers;

use app\helpers\LoggerTrait;
use app\config\ConfigHelper as CK;
use app\models\forms\ChangePasswordForm;
use app\models\forms\ForgotPasswordForm;
use app\models\Country;
use app\models\FlightSearch;
use app\models\Pilot;
use app\models\PilotSearch;
use app\models\Rank;
use app\models\SubmittedFlightPlan;
use app\rbac\constants\Permissions;
use app\rbac\constants\Roles;
use DateTime;

use yii\db\Expression;
use yii\data\ActiveDataProvider;
use yii\filters\AccessControl;
use yii\filters\VerbFilter;
use yii\web\BadRequestHttpException;
use yii\web\Controller;
use yii\web\ForbiddenHttpException;
use yii\web\NotFoundHttpException;
use Yii;


/**
 * PilotController implements the CRUD actions for Pilot model.
 */
class PilotController extends Controller
{
    use LoggerTrait;

    /**
     * @inheritDoc
     */
    public function behaviors()
    {
        return array_merge(
            parent::behaviors(),
            [
                'access' => [
                    'class' => AccessControl::class,
                    'only' => ['view', 'create', 'update', 'delete', 'activate', 'activate-pilots', 'move'],
                    'rules' => [
                        [
                            'allow' => true,
                            'roles' => ['@'],
                        ],
                    ],
                ],
                'verbs' => [
                    'class' => VerbFilter::class,
                    'actions' => [
                        'delete' => ['POST'],
                    ],
                ],
            ]
        );
    }

    /**
     * Lists all Pilot models.
     *
     * @return string
     */
    public function actionIndex()
    {
        $query = Pilot::find()->joinWith('rank')
            ->select(['pilot.*'])
            ->andWhere(['not', ['pilot.license' => null]]);

        $dataProvider = new ActiveDataProvider([
            'query' => $query,
            'pagination' => false,
            'sort' => [
                'attributes' => [
                    'license',
                    'fullname' => [
                        'asc'  => new Expression("CONCAT(pilot.name, ' ', pilot.surname) ASC"),
                        'desc' => new Expression("CONCAT(pilot.name, ' ', pilot.surname) DESC"),
                    ],
                    'rank_name' => [
                        'asc'  => ['rank.name' => SORT_ASC],
                        'desc' => ['rank.name' => SORT_DESC],
                    ],
                    'hours_flown',
                    'location'
                ],
                'defaultOrder' => ['license' => SORT_ASC],
            ],
        ]);

        return $this->render('index', [
            'dataProvider' => $dataProvider,
        ]);
    }

    /**
     * Displays a single Pilot model.
     * @param int $id ID
     * @return string
     * @throws NotFoundHttpException if the model cannot be found
     */
    public function actionView($id)
    {
        $model = $this->findModel($id);
        $flightSearch = new FlightSearch();
        $flightsProvider = $flightSearch->searchForPilot($model->id, Yii::$app->request->queryParams);

        $stats = $model->getFlightStats();
        $stats['hours_flown'] = $model->hours_flown;

        return $this->render('view', [
            'model' => $model,
            'flightSearch' => $flightSearch,
            'flightsProvider' => $flightsProvider,
            'stats' => $stats
        ]);
    }

    /**
     * Register a new Pilot in the system
     * This pilot won't have permissions until an administrator activates it
     * If the creation is successful, the broser will be redirected to the 'welcome' page.
     * @return string|\yii\web\Response
     */
    public function actionRegister()
    {

        $registrationStart = CK::getRegistrationStart();
        $registrationEnd = CK::getRegistrationEnd();
        $now = new DateTime();

        if ($registrationStart === false || $registrationEnd === false) {
            $this->logError('Invalid registration dates', ['start' => $registrationStart, 'end' => $registrationEnd]);
            throw new Exception("Invalid registration dates. Contact an admin.");
        }

        if($now < $registrationStart || $now > $registrationEnd) {
            return $this->render('registration_closed');
        } else {
            $model = new Pilot();
            $model->scenario = Pilot::SCENARIO_REGISTER;
            $model->location = CK::getRegistrationStartLocation();

            if($this->request->isPost){
                if ($model->load($this->request->post()) && $model->save()) {
                    $this->logInfo('Pilot registered', $model->email);
                    return $this->redirect(['register-thanks']);
                }
            } else {
                $model->loadDefaultValues();
            }

            $countries = Country::find()
                ->select(['name'])
                ->indexBy('id')
                ->orderBy(['name' => SORT_ASC])
                ->column();

            return $this->render('register', [
                'model' => $model,
                'countries' => $countries,
            ]);
        }
    }

    /**
     * Displays a thank you for registration message
     * @return string
     */
    public function actionRegisterThanks()
    {
        return $this->render('register_thanks');
    }

    /**
     * Creates a new Pilot model.
     * Creation is only supported for admin users
     * If creation is successful, the browser will be redirected to the 'view' page.
     * @return string|\yii\web\Response
     */
    public function actionCreate()
    {
        if(Yii::$app->user->can(Permissions::USER_CRUD)){
            $model = new Pilot();

            if ($this->request->isPost) {
                if ($model->load($this->request->post()) && $model->save()) {
                    $this->logInfo('Pilot created by admin', ['model' => $model, 'user' => Yii::$app->user->identity->license]);
                    return $this->redirect(['view', 'id' => $model->id]);
                }
            } else {
                $model->loadDefaultValues();
            }

            $countries = Country::find()
                ->select(['name'])
                ->indexBy('id')
                ->orderBy(['name' => SORT_ASC])
                ->column();

            $ranks = Rank::find()
                ->select(['name'])
                ->indexBy('id')
                ->orderBy(['position' => SORT_ASC])
                ->column();

            return $this->render('create', [
                'model' => $model,
                'countries' => $countries,
                'ranks' => $ranks,
            ]);
        } else {
            throw new ForbiddenHttpException();
        }
    }

    /**
     * Updates an existing Pilot model.
     * If update is successful, the browser will be redirected to the 'view' page.
     * @param int $id ID
     * @return string|\yii\web\Response
     * @throws NotFoundHttpException if the model cannot be found
     */
    public function actionUpdate($id)
    {
        if(Yii::$app->user->can(Permissions::USER_CRUD)){
            $model = $this->findModel($id);

            if(!isset($model->license)){
                $msg = "You can't update a pilot that hasn't been activated. Please active the pilot first.";
                $this->logError('Fail trying to update non activated pilot', ['id' => $id, 'user' => Yii::$app->user->identity->license]);
                throw new ForbiddenHttpException($msg);
            }

            $model->setScenario(Pilot::SCENARIO_UPDATE);

            if ($this->request->isPost && $model->load($this->request->post()) && $model->save()) {
                $this->logInfo('Updated pilot', ['model' => $model, 'user' => Yii::$app->user->identity->license]);
                return $this->redirect(['view', 'id' => $model->id]);
            }

            $countries = Country::find()
                ->select(['name'])
                ->indexBy('id')
                ->orderBy(['name' => SORT_ASC])
                ->column();

            $ranks = Rank::find()
                ->select(['name'])
                ->indexBy('id')
                ->orderBy(['position' => SORT_ASC])
                ->column();

            return $this->render('update', [
                'model' => $model,
                'countries' => $countries,
                'ranks' => $ranks,
            ]);
        } else {
            throw new ForbiddenHttpException();
        }
    }

    /**
     * Deletes an existing Pilot model.
     * If deletion is successful, the browser will be redirected to the 'index' page.
     * @param int $id ID
     * @return \yii\web\Response
     * @throws NotFoundHttpException if the model cannot be found
     */
    public function actionDelete($id)
    {
        if(Yii::$app->user->can(Permissions::USER_CRUD)){
            $this->findModel($id)->delete();
            $this->logInfo('Deleted pilot', ['id' => $id, 'user' => Yii::$app->user->identity->license]);
            return $this->redirect(['index']);
        } else {
            throw new ForbiddenHttpException();
        }
    }

    public function actionActivatePilots(){
        if(Yii::$app->user->can(Permissions::USER_CRUD)){
            $searchModel = new PilotSearch();
            $dataProvider = $searchModel->search([]);
            $dataProvider->query->andWhere(['license' => null]);

            $dataProvider->sort->defaultOrder = [
                'registration_date' => SORT_ASC,
            ];

            return $this->render('activate-pilots', [
                'dataProvider' => $dataProvider,
            ]);
        } else {
            throw new ForbiddenHttpException();
        }
    }

    public function actionActivate($id){
        if(Yii::$app->user->can(Permissions::USER_CRUD)){
            $model = $this->findModel($id);

            if(isset($model->license)){
                $msg = "The pilot is already activated.";
                $this->logError('Pilot already activated', ['id' => $id, 'user' => Yii::$app->user->identity->license]);
                throw new ForbiddenHttpException($msg);
            }

            $model->setScenario(Pilot::SCENARIO_ACTIVATE);

            if ($this->request->isPost && $model->load($this->request->post()) && $model->save()) {
                $auth = Yii::$app->authManager;
                $pilotRole = $auth->getRole(Roles::PILOT);
                $auth->assign($pilotRole, $model->id);
                $noReplyMail = CK::getNoReplyMail();
                $supportMail = CK::getSupportMail();
                $airline = CK::getAirlineName();
                Yii::$app->mailer
                    ->compose('activatedAccount', [
                        'license' => $model->license,
                        'name' => $model->fullname
                    ])
                    ->setFrom([$noReplyMail => $airline])
                    ->setReplyTo([$supportMail => 'Support '.$airline])
                    ->setTo($model->email)
                    ->setSubject($airline . ': Account activated')
                    ->send();
                $this->logInfo('Pilot activated', ['id' => $id, 'user' => Yii::$app->user->identity->license]);
                return $this->redirect(['view', 'id' => $model->id]);
            }
            return $this->render('activate', ['model' => $model]);
        } else {
            throw new ForbiddenHttpException();
        }
    }

    public function actionMove()
    {
        if (Yii::$app->user->isGuest) {
            throw new ForbiddenHttpException('You must be logged in to move your pilot.');
        }

        $userId = Yii::$app->user->id;
        $model = $this->findModel($userId);

        // Check if there is an active submitted flight plan for this pilot
        $activePlan = SubmittedFlightPlan::findOne(['pilot_id' => $userId]);
        if ($activePlan !== null) {
            throw new ForbiddenHttpException('You cannot change your location with an active submitted flight plan.');
        }

        $model->setScenario(Pilot::SCENARIO_MOVE);

        if ($this->request->isPost && $model->load($this->request->post()) && $model->save()) {
            Yii::$app->session->setFlash('success', 'Now you are at ' . $model->location. ' airport.');
            return $this->redirect(['view', 'id' => $model->id]);
        }

        return $this->render('move', [
            'model' => $model,
        ]);
    }

    public function actionForgotPassword()
    {
        $model = new ForgotPasswordForm();

        if($model->load(Yii::$app->request->post()) && $model->validate()) {
            $email = $model->email;
            $pilot = Pilot::findOne(['email' => $email]);

            if($pilot) {
                $now = new \DateTime();

                // Only generate tokens if 15 mins has passed
                if (!empty($pilot->pwd_reset_token_created_at)) {
                    $tokenCreated = new \DateTimeImmutable($pilot->pwd_reset_token_created_at);
                    $expiry = $tokenCreated->modify('+15 minutes');
                    $now = new \DateTimeImmutable();

                    if ($now < $expiry) {
                        Yii::warning("Password reset requested too soon for pilot_id={$pilot->id}", __METHOD__);
                        return $this->render('forgot-password-sent', ['email' => $email]);
                    }
                }

                $pilot->pwd_reset_token = Yii::$app->security->generateRandomString(255);
                $pilot->pwd_reset_token_created_at = date('Y-m-d H:i:s');
                if (!$pilot->save()) {
                    Yii::error("Error with request of change password {$email}: " . json_encode($pilot->errors));
                } else {
                    $noReplyMail = CK::getNoReplyMail();
                    $supportMail = CK::getSupportMail();
                    $airline = CK::getAirlineName();
                    Yii::$app->mailer
                        ->compose('passwordResetToken', [
                            'id' => $pilot->id,
                            'name' => $pilot->fullname,
                            'token' => $pilot->pwd_reset_token,
                        ])
                        ->setFrom([$noReplyMail => $airline])
                        ->setReplyTo([$supportMail => 'Support '.$airline])
                        ->setTo($pilot->email)
                        ->setSubject($airline . ': Password Reset Request')
                        ->send();
                }
            }

            return $this->render('forgot-password-sent', ['email' => $email]);
        }

        return $this->render('forgot-password', [
            'model' => $model,
        ]);
    }

    public function actionChangePassword($id, $token)
    {
        $pilot = Pilot::findOne($id);

        if (!$pilot || $pilot->pwd_reset_token !== $token || $pilot->isPasswordResetTokenExpired()) {
            Yii::warning(
                    'Password reset attempt failed for pilot_id=' . $id .
                    ', token_prefix=' . substr($token, 0, 8) . '...',
                );
            throw new BadRequestHttpException('The password reset link is invalid or has expired.');
        }

        $model = new ChangePasswordForm();

        if ($model->load(Yii::$app->request->post()) && $model->validate()) {
            $pilot->password = Yii::$app->security->generatePasswordHash($model->password);
            $pilot->pwd_reset_token = null;
            $pilot->pwd_reset_token_created_at = null;
            $pilot->auth_key = Yii::$app->security->generateRandomString(32);
            $pilot->access_token = Yii::$app->security->generateRandomString(32);

            if ($pilot->save()) {
                Yii::$app->session->setFlash('success', 'Password successfully updated.');
                return $this->redirect(['site/login']);
            } else {
                Yii::$app->session->setFlash('error', 'Could not change password. Please try again later.');
                Yii::error('Failed to change new password for pilot'. $pilot->id . json_encode($pilot->errors));
            }

            Yii::$app->session->setFlash('error', 'Could not change new password. Try again later.');
        }

        return $this->render('change-password', [
            'model' => $model,
        ]);
    }

    /**
     * Finds the Pilot model based on its primary key value.
     * If the model is not found, a 404 HTTP exception will be thrown.
     * @param int $id ID
     * @return Pilot the loaded model
     * @throws NotFoundHttpException if the model cannot be found
     */
    protected function findModel($id)
    {
        if (($model = Pilot::findOne(['id' => $id])) !== null) {
            return $model;
        }

        throw new NotFoundHttpException('The requested page does not exist.');
    }
}
