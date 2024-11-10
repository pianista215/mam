<?php

namespace app\controllers;

use app\models\Country;
use app\models\Pilot;
use app\models\PilotSearch;
use yii\web\Controller;
use yii\web\NotFoundHttpException;
use yii\filters\VerbFilter;

/**
 * PilotController implements the CRUD actions for Pilot model.
 */
class PilotController extends Controller
{

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

        // TODO: MOVE TO CONFIGURATION STORED IN DB
        $registration_is_closed = false;

        if($registration_is_closed) {
            return $this->render('registration_closed');
        } else {
            $model = new Pilot();

            if($this->request->isPost){
                if ($model->load($this->request->post()) && $model->save()) {
                    return $this->redirect(['register-thanks']);
                }
            } else {
                $model->loadDefaultValues();
            }

            $countries = Country::find()->select(['name'])->indexBy('id')->column();

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
        $model = new Pilot();

        if ($this->request->isPost) {
            if ($model->load($this->request->post()) && $model->save()) {
                return $this->redirect(['view', 'id' => $model->id]);
            }
        } else {
            $model->loadDefaultValues();
        }

        $countries = Country::find()->select(['name'])->indexBy('id')->column();

        return $this->render('create', [
            'model' => $model,
            'countries' => $countries,
        ]);
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
        $model = $this->findModel($id);

        if ($this->request->isPost && $model->load($this->request->post()) && $model->save()) {
            return $this->redirect(['view', 'id' => $model->id]);
        }

        return $this->render('update', [
            'model' => $model,
        ]);
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
        $this->findModel($id)->delete();

        return $this->redirect(['index']);
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
