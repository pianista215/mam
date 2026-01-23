<?php

namespace app\controllers;

use app\config\ConfigHelper;
use app\models\Pilot;
use Yii;
use yii\filters\AccessControl;
use yii\web\Controller;
use yii\web\NotFoundHttpException;

/**
 * AcarsUpdaterController serves ACARS application updates and installer downloads.
 *
 * Compatible with Velopack update system (https://velopack.io/).
 * Files should be placed in the configured ACARS releases path (Site Settings).
 *
 * Expected files in releases directory:
 * - releases.{channel}.json (primary feed)
 * - RELEASES (legacy compatibility)
 * - {AppId}-{version}-full.nupkg
 * - {AppId}-{version}-delta.nupkg
 * - {AppId}-Setup.exe (installer)
 *
 * Endpoints:
 * - `installer`: For logged-in web users to download the setup executable
 * - `update`: For Velopack client with Bearer token (returns 404 if unauthorized)
 */
class AcarsUpdaterController extends Controller
{
    public function behaviors()
    {
        return array_merge(
            parent::behaviors(),
            [
                'access' => [
                    'class' => AccessControl::class,
                    'only' => ['installer'],
                    'rules' => [
                        [
                            'actions' => ['installer'],
                            'allow' => true,
                            'roles' => ['@'],
                        ],
                    ],
                ],
            ]
        );
    }

    /**
     * Downloads the installer executable.
     * Requires web session login.
     *
     * GET /acars-updater/installer
     */
    public function actionInstaller()
    {
        $releasesPath = ConfigHelper::getAcarsReleasesPath();
        $installerName = ConfigHelper::getAcarsInstallerName();
        $filePath = $releasesPath . '/' . $installerName;

        if (!file_exists($filePath) || !is_file($filePath)) {
            throw new NotFoundHttpException();
        }

        return Yii::$app->response->sendFile($filePath, $installerName, [
            'mimeType' => 'application/octet-stream',
            'inline' => false,
        ]);
    }

    /**
     * Downloads update files for Velopack client.
     * Requires Bearer token. Returns 404 if unauthorized.
     *
     * GET /acars-updater/update/releases.stable.json
     * GET /acars-updater/update/RELEASES
     * GET /acars-updater/update/MyApp-1.0.0-full.nupkg
     */
    public function actionUpdate($file)
    {
        if (!$this->authenticateBearer()) {
            throw new NotFoundHttpException();
        }

        if (empty($file)) {
            throw new NotFoundHttpException();
        }

        $filename = basename($file);
        $releasesPath = ConfigHelper::getAcarsReleasesPath();
        $filePath = $releasesPath . '/' . $filename;

        if (!file_exists($filePath) || !is_file($filePath)) {
            throw new NotFoundHttpException();
        }

        $allowedExtensions = ['nupkg', 'json'];
        $extension = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
        $isReleasesFile = strtoupper($filename) === 'RELEASES';
        $hasAllowedExtension = in_array($extension, $allowedExtensions);

        if (!$isReleasesFile && !$hasAllowedExtension) {
            throw new NotFoundHttpException();
        }

        return Yii::$app->response->sendFile($filePath, $filename, [
            'mimeType' => 'application/octet-stream',
            'inline' => false,
        ]);
    }

    private function authenticateBearer(): bool
    {
        $authHeader = Yii::$app->request->headers->get('Authorization');

        if ($authHeader === null || !preg_match('/^Bearer\s+(.+)$/i', $authHeader, $matches)) {
            return false;
        }

        $token = $matches[1];
        $pilot = Pilot::findIdentityByAccessToken($token);

        return $pilot !== null;
    }
}
