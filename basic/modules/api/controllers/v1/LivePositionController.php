<?php

namespace app\modules\api\controllers\v1;

use app\helpers\LoggerTrait;
use app\models\LiveFlightPosition;
use app\models\SubmittedFlightPlan;
use app\modules\api\dto\v1\request\LivePositionDTO;
use yii\filters\auth\HttpBearerAuth;
use yii\rest\Controller;
use yii\web\BadRequestHttpException;
use yii\web\NotFoundHttpException;
use yii\web\ServerErrorHttpException;
use Yii;

/**
 * LivePosition controller for updating flight position in real-time
 */
class LivePositionController extends Controller
{
    use LoggerTrait;

    public function behaviors()
    {
        $behaviors = parent::behaviors();

        $behaviors['authenticator'] = [
            'class' => HttpBearerAuth::class,
        ];

        return $behaviors;
    }

    /**
     * Update the live position for an active flight plan
     *
     * @param int $flight_plan_id The submitted flight plan ID
     * @return array Success response
     * @throws NotFoundHttpException If flight plan not found or doesn't belong to user
     * @throws BadRequestHttpException If validation fails
     * @throws ServerErrorHttpException If save fails
     */
    public function actionUpdate($flight_plan_id)
    {
        $dto = new LivePositionDTO();

        if (!$dto->load(Yii::$app->request->post(), '') || !$dto->validate()) {
            $this->logError('Invalid position data', [
                'errors' => $dto->getErrors(),
                'flight_plan_id' => $flight_plan_id,
                'user' => Yii::$app->user->identity->license
            ]);
            throw new BadRequestHttpException('Invalid position data.');
        }

        $submittedFlightPlan = SubmittedFlightPlan::findOne(['pilot_id' => Yii::$app->user->identity->id]);

        if (!$submittedFlightPlan) {
            $this->logError('User without submitted flight plan', [
                'flight_plan_id' => $flight_plan_id,
                'user' => Yii::$app->user->identity->license
            ]);
            throw new NotFoundHttpException('Flight plan not found.');
        }

        if ($submittedFlightPlan->id != $flight_plan_id) {
            $this->logError('User flight plan and sent mismatch', [
                'submitted' => $submittedFlightPlan->id,
                'sent' => $flight_plan_id,
                'user' => Yii::$app->user->identity->license
            ]);
            throw new NotFoundHttpException('Flight plan not found.');
        }

        $position = LiveFlightPosition::findOne(['submitted_flight_plan_id' => $submittedFlightPlan->id]);

        if (!$position) {
            $position = new LiveFlightPosition();
            $position->submitted_flight_plan_id = $submittedFlightPlan->id;
        }

        $position->latitude = $dto->latitude;
        $position->longitude = $dto->longitude;
        $position->altitude = $dto->altitude;
        $position->heading = $dto->heading;
        $position->ground_speed = $dto->ground_speed;

        if (!$position->save()) {
            $this->logError('Failed to save live position', [
                'errors' => $position->getErrors(),
                'flight_plan_id' => $flight_plan_id,
                'user' => Yii::$app->user->identity->license
            ]);
            throw new ServerErrorHttpException('Failed to save live position.');
        }

        $this->logInfo('Live position updated', [
            'position' => $position,
            'user' => Yii::$app->user->identity->license
        ]);

        return ['status' => 'success'];
    }
}
