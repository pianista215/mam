<?php

namespace app\controllers;

use app\config\Config;
use app\models\Country;
use app\models\Pilot;
use app\models\PilotSearch;
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
                $msg = "You can't update a user that hasn't been activated. Please active the user first.";
                throw new ForbiddenHttpException($msg);
            }

            $model->setScenario(Pilot::SCENARIO_UPDATE);

            if ($this->request->isPost && $model->load($this->request->post()) && $model->save()) {
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
                $msg = "The user is already activated.";
                throw new ForbiddenHttpException($msg);
            }

            $model->setScenario(Pilot::SCENARIO_ACTIVATE);

            if ($this->request->isPost && $model->load($this->request->post()) && $model->save()) {
                $auth = Yii::$app->authManager;
                $pilotRole = $auth->getRole('pilot');
                $auth->assign($pilotRole, $model->id);
                // TODO: SEND MAIL TO THE PILOT
                return $this->redirect(['view', 'id' => $model->id]);
            }
            return $this->render('activate', ['model' => $model]);
        } else {
            throw new ForbiddenHttpException();
        }
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
