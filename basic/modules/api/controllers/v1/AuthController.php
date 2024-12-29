<?php
namespace app\modules\api\controllers\v1;

use Yii;
use yii\rest\Controller;
use yii\web\Response;
use yii\web\BadRequestHttpException;
use app\models\Pilot;

class AuthController extends Controller
{
    public function behaviors()
    {
        $behaviors = parent::behaviors();

        $behaviors['contentNegotiator'] = [
            'class' => \yii\filters\ContentNegotiator::class,
            'formats' => [
                'application/json' => Response::FORMAT_JSON,
            ],
        ];

        $behaviors['verbFilter'] = [
            'class' => \yii\filters\VerbFilter::class,
            'actions' =>  [
                'login' => ['POST',]
            ]
        ];

        return $behaviors;
    }

    public function actionLogin()
    {
        $request = Yii::$app->request->post();
        $license = $request['license'] ?? null;
        $password = $request['password'] ?? null;

        if (!$license || !$password) {
            throw new BadRequestHttpException('License and password are required.');
        }

        $pilot = Pilot::findOne(['license' => $license]);

        if (!$license || !Yii::$app->security->validatePassword($password, $pilot->password)) {
            throw new BadRequestHttpException('Invalid username or password.');
        }

        // Generate unique token
        $pilot->access_token = Yii::$app->security->generateRandomString(32);
        $pilot->save(false);

        return [
            'status' => 'success',
            'access_token' => $pilot->access_token,
        ];
    }
}