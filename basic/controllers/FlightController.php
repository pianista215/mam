<?php

namespace app\controllers;

use app\models\Flight;
use app\models\FlightSearch;
use yii\web\Controller;
use yii\web\ForbiddenHttpException;
use yii\web\NotFoundHttpException;
use yii\filters\VerbFilter;
use Yii;

/**
 * FlightController implements the CRUD actions for Flight model.
 */
class FlightController extends Controller
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
     * Lists all Flight models.
     *
     * @return string
     */
    public function actionIndex()
    {
        $searchModel = new FlightSearch();
        $dataProvider = $searchModel->search($this->request->queryParams);

        return $this->render('index', [
            'searchModel' => $searchModel,
            'dataProvider' => $dataProvider,
        ]);
    }

    /**
     * Displays a single Flight model.
     * @param string $id ID
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
     * Validate or reject a Flight
     * @param string $id ID
     * @return string
     * @throws NotFoundHttpException if the model cannot be found
     */
    public function actionValidate($id)
    {
        $model = $this->findModel($id);

        $model->scenario = Flight::SCENARIO_VALIDATE;;
        if ($model->isPendingValidation() && $model->load(Yii::$app->request->post())) {
            $model->validator_id = Yii::$app->user->id;
            $model->validation_date = date('Y-m-d H:i:s');

            $action = Yii::$app->request->post('action');
            if ($action === 'approve') {
                $model->status = 'F';
            } elseif ($action === 'reject') {
                $model->status = 'R';
            }

            if ($model->save()) {
                Yii::$app->session->setFlash('success', 'Flight validation finished.');
                return $this->redirect(['view', 'id' => $model->id]);
            } else {
                Yii::error("Error with flight validation {$model->id}: " . json_encode($model->errors));
                Yii::$app->session->setFlash('error', 'Error validating flight.');
                return $this->redirect(['view', 'id' => $model->id]);
            }
        } else {
            throw new ForbiddenHttpException('You\'re not allowed to validate this flight');
        }
    }


    /**
     * Finds the Flight model based on its primary key value.
     * If the model is not found, a 404 HTTP exception will be thrown.
     * @param string $id ID
     * @return Flight the loaded model
     * @throws NotFoundHttpException if the model cannot be found
     */
    protected function findModel($id)
    {
        if (($model = Flight::findOne(['id' => $id])) !== null) {
            return $model;
        }

        throw new NotFoundHttpException('The requested page does not exist.');
    }
}
