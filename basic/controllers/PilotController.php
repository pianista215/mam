<?php

namespace app\controllers;

use app\helpers\LoggerTrait;
use app\config\Config;
use app\models\Country;
use app\models\Pilot;
use app\models\PilotSearch;
use app\models\SubmittedFlightPlan;
use DateTime;
use yii\web\Controller;
use yii\web\ForbiddenHttpException;
use yii\web\NotFoundHttpException;
use yii\filters\VerbFilter;
use Yii;


/**
 * PilotController implements the CRUD actions for Pilot model.
 */
class PilotController extends Controller
{
    use LoggerTrait;

    // TODO: WE NEED A RESET PASSWORD ACTION ALLOWING ADMIN OR PILOT TO RESET ITS PASSWORD

    /**
     * @inheritDoc
     */
    public function behaviors()
    {
        return array_merge(
            parent::behaviors(),
            [
                'verbs' => [
                    'class' => VerbFilter::className(),
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
        // TODO: SORT BY
        $searchModel = new PilotSearch();
        $dataProvider = $searchModel->search($this->request->queryParams);

        return $this->render('index', [
            'searchModel' => $searchModel,
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
        return $this->render('view', [
            'model' => $this->findModel($id),
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

        $registrationStart = DateTime::createFromFormat('Y-m-d', Config::get('registration_start'));
        $registrationEnd = DateTime::createFromFormat('Y-m-d', Config::get('registration_end'));
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
            $model->location = Config::get('registration_start_location');

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
        if(Yii::$app->user->can('userCrud')){
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

            return $this->render('create', [
                'model' => $model,
                'countries' => $countries,
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
        if(Yii::$app->user->can('userCrud')){
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

            return $this->render('update', [
                'model' => $model,
                'countries' => $countries,
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
        if(Yii::$app->user->can('userCrud')){
            $this->findModel($id)->delete();
            $this->logInfo('Deleted pilot', ['id' => $id, 'user' => Yii::$app->user->identity->license]);
            return $this->redirect(['index']);
        } else {
            throw new ForbiddenHttpException();
        }
    }

    public function actionActivatePilots(){
        if(Yii::$app->user->can('userCrud')){
            $searchModel = new PilotSearch();
            $dataProvider = $searchModel->search([]);
            $dataProvider->query->andWhere(['license' => null]);
            return $this->render('activate-pilots', [
                'dataProvider' => $dataProvider,
            ]);
        } else {
            throw new ForbiddenHttpException();
        }
    }

    public function actionActivate($id){
        if(Yii::$app->user->can('userCrud')){
            $model = $this->findModel($id);

            if(isset($model->license)){
                $msg = "The pilot is already activated.";
                $this->logError('Pilot already activated', ['id' => $id, 'user' => Yii::$app->user->identity->license]);
                throw new ForbiddenHttpException($msg);
            }

            $model->setScenario(Pilot::SCENARIO_ACTIVATE);

            if ($this->request->isPost && $model->load($this->request->post()) && $model->save()) {
                $auth = Yii::$app->authManager;
                $pilotRole = $auth->getRole('pilot');
                $auth->assign($pilotRole, $model->id);
                // TODO: SEND MAIL TO THE PILOT
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
