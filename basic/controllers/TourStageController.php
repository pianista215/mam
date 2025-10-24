<?php

namespace app\controllers;

use app\helpers\LoggerTrait;
use app\models\Tour;
use app\models\TourStage;
use app\models\TourStageSearch;
use yii\web\Controller;
use yii\web\ForbiddenHttpException;
use yii\web\NotFoundHttpException;
use yii\filters\AccessControl;
use yii\filters\VerbFilter;
use Yii;

/**
 * TourStageController implements the CRUD actions for TourStage model.
 */
class TourStageController extends Controller
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
                    'only' => ['create', 'update', 'delete'],
                    'rules' => [
                        [
                            'allow' => true,
                            'roles' => ['@'],
                        ],
                    ],
                ],
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
     * Add a new TourStage model.
     * If creation is successful, the browser will be redirected to the 'view' page.
     * @return string|\yii\web\Response
     */
    public function actionAddStage($tour_id)
    {
        if(Yii::$app->user->can('tourCrud')){
            $tour = Tour::findOne($tour_id);
            if (!$tour) {
                throw new NotFoundHttpException("Tour not found.");
            }

            $model = new TourStage();
            $model->tour_id = $tour->id;

            $nextSequence = TourStage::find()
                ->where(['tour_id' => $tour_id])
                ->max('sequence');
            $model->sequence = $nextSequence ? $nextSequence + 1 : 1;

            if ($this->request->isPost) {
                if ($model->load($this->request->post()) && $model->save()) {
                    Yii::$app->session->setFlash('success', 'Stage added successfully.');
                    $this->logInfo('Created tour stage', ['model' => $model, 'user' => Yii::$app->user->identity->license]);
                    return $this->redirect(['tour/view', 'id' => $tour_id]);
                }
            } else {
                $model->loadDefaultValues();
            }

            return $this->render('add', [
                'model' => $model,
                'tour' => $tour,
            ]);
        } else {
            throw new ForbiddenHttpException();
        }
    }

    /**
     * Updates an existing TourStage model.
     * If update is successful, the browser will be redirected to the 'view' page.
     * @param int $id ID
     * @return string|\yii\web\Response
     * @throws NotFoundHttpException if the model cannot be found
     */
    public function actionUpdate($id)
    {
        if(Yii::$app->user->can('tourCrud')){
            $model = $this->findModel($id);

            if ($this->request->isPost && $model->load($this->request->post()) && $model->save()) {
                $this->logInfo('Updated tour stage', ['model' => $model, 'user' => Yii::$app->user->identity->license]);
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
     * Deletes an existing TourStage model.
     * If deletion is successful, the browser will be redirected to the 'index' page.
     * @param int $id ID
     * @return \yii\web\Response
     * @throws NotFoundHttpException if the model cannot be found
     */
    public function actionDelete($id)
    {
        if(Yii::$app->user->can('tourCrud')){
            $model = $this->findModel($id);
            if($model->flights) {
                Yii::$app->session->setFlash('error', 'Can\'t delete stage with flights associated.');
            } else {
                $this->findModel($id)->delete();
                $this->logInfo('Deleted tour stage', ['id' => $id, 'user' => Yii::$app->user->identity->license]);
            }
            return $this->redirect(['tour/view', 'id' => $model->tour->id]);
        } else {
            throw new ForbiddenHttpException();
        }
    }

    /**
     * Finds the TourStage model based on its primary key value.
     * If the model is not found, a 404 HTTP exception will be thrown.
     * @param int $id ID
     * @return TourStage the loaded model
     * @throws NotFoundHttpException if the model cannot be found
     */
    protected function findModel($id)
    {
        if (($model = TourStage::findOne(['id' => $id])) !== null) {
            return $model;
        }

        throw new NotFoundHttpException('The requested page does not exist.');
    }
}
