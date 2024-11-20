<?php

namespace app\controllers;

use app\models\Aircraft;
use app\models\AircraftSearch;
use app\models\AircraftType;
use yii\web\Controller;
use yii\web\ForbiddenHttpException;
use yii\web\NotFoundHttpException;
use yii\filters\VerbFilter;
use Yii;

/**
 * AircraftController implements the CRUD actions for Aircraft model.
 */
class AircraftController extends Controller
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
     * Lists all Aircraft models.
     *
     * @return string
     */
    public function actionIndex()
    {
        $searchModel = new AircraftSearch();
        $dataProvider = $searchModel->search($this->request->queryParams);

        return $this->render('index', [
            'searchModel' => $searchModel,
            'dataProvider' => $dataProvider,
        ]);
    }

    /**
     * Displays a single Aircraft model.
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
     * Creates a new Aircraft model.
     * If creation is successful, the browser will be redirected to the 'view' page.
     * @return string|\yii\web\Response
     */
    public function actionCreate()
    {
        if(Yii::$app->user->can('aircraftCrud')){
            $model = new Aircraft();

            if ($this->request->isPost) {
                if ($model->load($this->request->post()) && $model->save()) {
                    return $this->redirect(['view', 'id' => $model->id]);
                }
            } else {
                $model->loadDefaultValues();
            }

            $aircraftTypes = AircraftType::find()->select(['name'])->indexBy('id')->column();

            return $this->render('create', [
                'model' => $model,
                'aircraftTypes' => $aircraftTypes,
            ]);
        } else {
            throw new ForbiddenHttpException();
        }
    }

    /**
     * Updates an existing Aircraft model.
     * If update is successful, the browser will be redirected to the 'view' page.
     * @param int $id ID
     * @return string|\yii\web\Response
     * @throws NotFoundHttpException if the model cannot be found
     */
    public function actionUpdate($id)
    {
        if(Yii::$app->user->can('aircraftCrud')){
            $model = $this->findModel($id);

            if ($this->request->isPost && $model->load($this->request->post()) && $model->save()) {
                return $this->redirect(['view', 'id' => $model->id]);
            }

            return $this->render('update', [
                'model' => $model,
            ]);
        } else {
            throw new ForbiddenHttpException();
        }
    }

    /**
     * Deletes an existing Aircraft model.
     * If deletion is successful, the browser will be redirected to the 'index' page.
     * @param int $id ID
     * @return \yii\web\Response
     * @throws NotFoundHttpException if the model cannot be found
     */
    public function actionDelete($id)
    {
        if(Yii::$app->user->can('aircraftCrud')){
            $this->findModel($id)->delete();

            return $this->redirect(['index']);
        } else {
            throw new ForbiddenHttpException();
        }
    }

    /**
     * Finds the Aircraft model based on its primary key value.
     * If the model is not found, a 404 HTTP exception will be thrown.
     * @param int $id ID
     * @return Aircraft the loaded model
     * @throws NotFoundHttpException if the model cannot be found
     */
    protected function findModel($id)
    {
        if (($model = Aircraft::findOne(['id' => $id])) !== null) {
            return $model;
        }

        throw new NotFoundHttpException('The requested page does not exist.');
    }
}
