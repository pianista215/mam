<?php

namespace app\filters;

use yii\filters\auth\HttpBearerAuth;
use yii\web\NotFoundHttpException;

/**
 * SilentHttpBearerAuth extends HttpBearerAuth but throws 404 instead of 401.
 *
 * Useful for endpoints that should appear non-existent to unauthorized users,
 * hiding the fact that authentication is required.
 */
class SilentHttpBearerAuth extends HttpBearerAuth
{
    /**
     * @inheritdoc
     * @throws NotFoundHttpException
     */
    public function handleFailure($response)
    {
        throw new NotFoundHttpException();
    }
}
