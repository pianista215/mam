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
                    'only' => ['add-stage', 'update', 'delete'],
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

            if ($tour->getFlights()->exists()) {
                Yii::$app->session->setFlash('error', 'Cannot add stages to a tour that already has flown stages.');
                return $this->redirect(['tour/view', 'id' => $tour_id]);
            } else {

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
            }
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
            $tour = $model->tour;

            $model->setScenario(TourStage::SCENARIO_UPDATE);

            if ($this->request->isPost && $model->load($this->request->post()) && $model->save()) {
                $this->logInfo('Updated tour stage', ['model' => $model, 'user' => Yii::$app->user->identity->license]);
                return $this->redirect(['tour/view', 'id' => $tour->id]);
            }

            return $this->render('update', [
                'model' => $model,
                'tour' => $tour,
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
            $tour_id = $model->tour->id;

            if($model->flights) {
                Yii::$app->session->setFlash('error', 'Can\'t delete stage with flights associated.');
            } else {
                $transaction = Yii::$app->db->beginTransaction();
                try {
                    // Delete the stage and reorder the later stages sequences
                    $model->delete();

                    $stages = TourStage::find()
                        ->where(['tour_id' => $tour_id])
                        ->andWhere(['>', 'sequence', $model->sequence])
                        ->orderBy(['sequence' => SORT_ASC])
                        ->all();

                    foreach ($stages as $stage) {
                        $stage->sequence -= 1;
                        if (!$stage->save(false, ['sequence'])) { // Only update sequence
                             $errors = $stage->getErrors();
                             throw new \Exception('Failed to re-sequence stage #' . $stage->id . '. Errors: ' . json_encode($errors));
                        }
                    }

                    $transaction->commit();
                    $this->logInfo('Deleted tour stage', ['id' => $id, 'user' => Yii::$app->user->identity->license]);
                } catch (\Exception $e) {
                    $transaction->rollBack();
                    $this->logError('Error deleting tour stage', ['id' => $id, 'user' => Yii::$app->user->identity->license, 'ex' => $e]);
                    Yii::$app->session->setFlash('danger', 'Error deleting stage. Contact administrator');
                }
            }
            return $this->redirect(['tour/view', 'id' => $tour_id]);
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
