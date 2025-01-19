<?php
namespace app\modules\api\controllers\v1;


use app\models\Pilot;
use app\modules\api\dto\v1\response\TokenInfoDTO;
use yii\rest\Controller;
use yii\web\Response;
use yii\web\UnauthorizedHttpException;
use Yii;

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
            throw new UnauthorizedHttpException('License and password are required.');
        }

        $pilot = Pilot::findOne(['license' => $license]);

        if (!$pilot || !Yii::$app->security->validatePassword($password, $pilot->password)) {
            throw new UnauthorizedHttpException('Invalid username or password.');
        }

        // Generate unique token
        $pilot->access_token = Yii::$app->security->generateRandomString(32);
        $pilot->save(false);

        $dto = TokenInfoDTO::fromModel($pilot);

        return $dto;
    }
}