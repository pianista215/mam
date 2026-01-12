<?php

namespace app\controllers;

use app\helpers\LoggerTrait;
use app\models\Flight;
use app\models\FlightSearch;
use app\models\PilotTourCompletion;
use app\rbac\constants\Permissions;
use yii\web\Controller;
use yii\web\BadRequestHttpException;
use yii\web\ForbiddenHttpException;
use yii\web\NotFoundHttpException;
use yii\filters\AccessControl;
use yii\filters\VerbFilter;
use Yii;

/**
 * FlightController implements the CRUD actions for Flight model.
 */
class FlightController extends Controller
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
                    'only' => ['index', 'index-pending', 'view', 'validate'],
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
     * Lists all Flight models.
     *
     * @return string
     */
    public function actionIndex()
    {
        $searchModel = new FlightSearch();
        $dataProvider = $searchModel->search($this->request->queryParams);

        $dataProvider->sort->defaultOrder = [
            'creation_date' => SORT_DESC,
        ];

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
     * Lists all Flight pending validation
     *
     * @return string
     */
    public function actionIndexPending()
    {
        if (!Yii::$app->user->can(Permissions::VALIDATE_VFR_FLIGHT) && !Yii::$app->user->can(Permissions::VALIDATE_IFR_FLIGHT)) {
            throw new ForbiddenHttpException(Yii::t('app', 'You\'re not allowed to validate flights.'));
        }

        $searchModel = new FlightSearch();
        $searchModel->onlyPending = true;
        $dataProvider = $searchModel->search($this->request->queryParams);

        $dataProvider->sort->defaultOrder = [
            'creation_date' => SORT_DESC,
        ];

        return $this->render('index_pending', [
            'searchModel' => $searchModel,
            'dataProvider' => $dataProvider,
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

        if (!Yii::$app->user->can(Permissions::VALIDATE_FLIGHT, ['flight' => $model])) {
            throw new ForbiddenHttpException(Yii::t('app', 'You\'re not allowed to validate this flight.'));
        }

        $model->scenario = Flight::SCENARIO_VALIDATE;

        if (!$model->isPendingValidation() || !$model->load(Yii::$app->request->post())) {
            throw new ForbiddenHttpException(Yii::t('app', 'You\'re not allowed to validate this flight.'));
        }

        $transaction = Yii::$app->db->beginTransaction();

        try {
            $model->validator_id = Yii::$app->user->id;
            $model->validation_date = date('Y-m-d H:i:s');

            $action = Yii::$app->request->post('action');
            if ($action === 'approve') {
                $model->status = 'F';
            } elseif ($action === 'reject') {
                $model->status = 'R';
            } else {
                throw new BadRequestHttpException("Illegal validation action: $action");
            }

            if (!$model->save()) {
                $this->logError('Error saving flight', ['model' => $model, 'user' => Yii::$app->user->identity->license]);
                throw new \Exception(Yii::t('app', 'Error saving flight validation. Contact the administrator.'));
            }

            if ($model->tour_stage_id && $model->status === 'F') {
                $tourStage = $model->tourStage;
                if ($tourStage && $tourStage->tour) {
                    $tour = $tourStage->tour;
                    $pilotId = $model->pilot_id;

                    $alreadyCompleted = PilotTourCompletion::find()
                        ->where(['tour_id' => $tour->id, 'pilot_id' => $pilotId])
                        ->exists();

                    if (!$alreadyCompleted) {
                        $totalStages = $tour->getTourStages()->count();

                        $completedStages = \app\models\Flight::find()
                            ->where([
                                'pilot_id' => $pilotId,
                                'status' => 'F',
                            ])
                            ->andWhere(['in', 'tour_stage_id', $tour->getTourStages()->select('id')])
                            ->count('DISTINCT tour_stage_id');

                        if ($completedStages === $totalStages) {
                            $completion = new PilotTourCompletion([
                                'pilot_id' => $pilotId,
                                'tour_id' => $tour->id,
                                'completed_at' => date('Y-m-d'),
                            ]);

                            if (!$completion->save()) {
                                $this->logError('Error saving tour completion', ['completion' => $completion, 'user' => Yii::$app->user->identity->license]);
                                throw new \Exception(Yii::t('app', 'Error saving tour completion. Contact the administrator.'));
                            }
                        }
                    }
                }
            }

            $transaction->commit();
            Yii::$app->session->setFlash('success', Yii::t('app', 'Flight validation finished.'));
        } catch (\Throwable $e) {
            $transaction->rollBack();
            $this->logError('Error validating flight', ['model' => $model, 'user' => Yii::$app->user->identity->license, 'ex' => $e]);
            Yii::$app->session->setFlash('error', Yii::t('app', 'Error validating flight.'));
        }

        return $this->redirect(['view', 'id' => $model->id]);
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
