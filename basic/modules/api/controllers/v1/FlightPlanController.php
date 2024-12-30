<?php

namespace app\modules\api\controllers\v1;

use app\models\SubmittedFlightPlan;
use app\modules\api\dto\v1\FlightPlanDTO;
use yii\filters\auth\HttpBearerAuth;
use yii\rest\Controller;
use yii\web\NotFoundHttpException;
use yii\web\Response;
use Yii;

/**
 * FlightPlan controller for the `api` module
 */
class FlightPlanController extends Controller
{
    public function behaviors()
    {
        $behaviors = parent::behaviors();

        $behaviors['authenticator'] = [
            'class' => HttpBearerAuth::class,
        ];

        return $behaviors;
    }

    public function actionCurrentFpl()
    {
        $submittedFlightPlan = SubmittedFlightPlan::findOne(['pilot_id' => Yii::$app->user->identity->id]);
        if(!$submittedFlightPlan){
            throw new NotFoundHttpException("Flight plan not found.");
        }

        $dto = FlightPlanDTO::fromModel($submittedFlightPlan);

        return $dto;
    }
}
