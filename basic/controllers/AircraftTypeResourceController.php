<?php

namespace app\controllers;

use app\config\ConfigHelper as CK;
use app\helpers\LoggerTrait;
use app\models\AircraftType;
use app\models\AircraftTypeResource;
use app\rbac\constants\Permissions;
use yii\filters\AccessControl;
use yii\filters\VerbFilter;
use yii\helpers\FileHelper;
use yii\web\Controller;
use yii\web\ForbiddenHttpException;
use yii\web\NotFoundHttpException;
use yii\web\UploadedFile;
use Yii;

class AircraftTypeResourceController extends Controller
{
    use LoggerTrait;

    public function behaviors(): array
    {
        return array_merge(parent::behaviors(), [
            'access' => [
                'class' => AccessControl::class,
                'only' => ['upload', 'delete', 'download'],
                'rules' => [['allow' => true, 'roles' => ['@']]],
            ],
            'verbs' => [
                'class' => VerbFilter::class,
                'actions' => [
                    'upload' => ['POST'],
                    'delete' => ['POST'],
                ],
            ],
        ]);
    }

    public function actionUpload(int $aircraftTypeId): \yii\web\Response
    {
        if (!Yii::$app->user->can(Permissions::AIRCRAFT_TYPE_RESOURCE_CRUD)) {
            throw new ForbiddenHttpException();
        }

        $aircraftType = AircraftType::findOne(['id' => $aircraftTypeId]);
        if ($aircraftType === null) {
            throw new NotFoundHttpException();
        }

        $uploadedFile = UploadedFile::getInstanceByName('file');
        if ($uploadedFile === null) {
            Yii::$app->session->setFlash('error', Yii::t('app', 'No file was uploaded.'));
            return $this->redirect(['aircraft-type/view', 'id' => $aircraftTypeId]);
        }

        if ($uploadedFile->error !== UPLOAD_ERR_OK) {
            if (in_array($uploadedFile->error, [UPLOAD_ERR_INI_SIZE, UPLOAD_ERR_FORM_SIZE], true)) {
                Yii::$app->session->setFlash('error', Yii::t('app', 'The file exceeds the maximum upload size allowed by the server.'));
            } else {
                $this->logError('PHP upload error for aircraft type resource', [
                    'code'          => $uploadedFile->error,
                    'aircraftTypeId' => $aircraftTypeId,
                ]);
                Yii::$app->session->setFlash('error', Yii::t('app', 'Error saving uploaded file.'));
            }
            return $this->redirect(['aircraft-type/view', 'id' => $aircraftTypeId]);
        }

        $ext = strtolower($uploadedFile->extension);
        if (!in_array($ext, AircraftTypeResource::ALLOWED_EXTENSIONS, true)) {
            $allowed = implode(', ', AircraftTypeResource::ALLOWED_EXTENSIONS);
            Yii::$app->session->setFlash('error', Yii::t('app', 'Invalid file type. Allowed: {types}.', ['types' => $allowed]));
            return $this->redirect(['aircraft-type/view', 'id' => $aircraftTypeId]);
        }

        $uploadedMb = $uploadedFile->size / 1024 / 1024;
        if (AircraftTypeResource::getTotalSizeMb() + $uploadedMb > CK::getAircraftTypeResourcesLimitMb()) {
            $this->logWarn('Aircraft type resource upload rejected: global size limit exceeded', [
                'aircraftTypeId' => $aircraftTypeId,
                'uploadedMb'     => $uploadedMb,
                'limitMb'        => CK::getAircraftTypeResourcesLimitMb(),
                'user'           => Yii::$app->user->identity->license,
            ]);
            Yii::$app->session->setFlash('error', Yii::t('app', 'Global file storage limit exceeded. Upload rejected.'));
            return $this->redirect(['aircraft-type/view', 'id' => $aircraftTypeId]);
        }

        $filename  = Yii::$app->security->generateRandomString() . '.' . $ext;
        $directory = CK::getAircraftTypeResourcesStoragePath() . '/aircraft_type/' . $aircraftTypeId;
        $fullPath  = $directory . '/' . $filename;

        FileHelper::createDirectory($directory, 0755, true);

        if (!$uploadedFile->saveAs($fullPath)) {
            $this->logError('Failed to save uploaded aircraft type resource to disk', [
                'aircraftTypeId' => $aircraftTypeId,
                'path'           => $fullPath,
            ]);
            Yii::$app->session->setFlash('error', Yii::t('app', 'Error saving uploaded file.'));
            return $this->redirect(['aircraft-type/view', 'id' => $aircraftTypeId]);
        }

        $resource = new AircraftTypeResource();
        $resource->aircraft_type_id = $aircraftTypeId;
        $resource->filename         = $filename;
        $resource->original_name    = $uploadedFile->name;
        $resource->size_bytes       = $uploadedFile->size;

        if (!$resource->save()) {
            unlink($fullPath);
            $this->logError('Failed to save aircraft type resource record to DB', [
                'aircraftTypeId' => $aircraftTypeId,
                'errors'         => $resource->errors,
            ]);
            Yii::$app->session->setFlash('error', Yii::t('app', 'Error saving file information.'));
            return $this->redirect(['aircraft-type/view', 'id' => $aircraftTypeId]);
        }

        $this->logInfo('Uploaded aircraft type resource', [
            'id'             => $resource->id,
            'aircraftTypeId' => $aircraftTypeId,
            'originalName'   => $uploadedFile->name,
            'user'           => Yii::$app->user->identity->license,
        ]);

        Yii::$app->session->setFlash('success', Yii::t('app', 'Resource uploaded successfully.'));
        return $this->redirect(['aircraft-type/view', 'id' => $aircraftTypeId]);
    }

    public function actionDelete(int $id): \yii\web\Response
    {
        $resource = AircraftTypeResource::findOne(['id' => $id]);
        if ($resource === null) {
            throw new NotFoundHttpException();
        }

        if (!Yii::$app->user->can(Permissions::AIRCRAFT_TYPE_RESOURCE_CRUD)) {
            throw new ForbiddenHttpException();
        }

        $aircraftTypeId = $resource->aircraft_type_id;
        $resource->delete();

        $this->logInfo('Deleted aircraft type resource', [
            'id'             => $id,
            'aircraftTypeId' => $aircraftTypeId,
            'user'           => Yii::$app->user->identity->license,
        ]);

        Yii::$app->session->setFlash('success', Yii::t('app', 'Resource deleted successfully.'));
        return $this->redirect(['aircraft-type/view', 'id' => $aircraftTypeId]);
    }

    public function actionDownload(int $id)
    {
        $resource = AircraftTypeResource::findOne(['id' => $id]);
        if ($resource === null) {
            throw new NotFoundHttpException();
        }

        if (!Yii::$app->user->can(Permissions::ACCESS_AIRCRAFT_TYPE_RESOURCES, ['aircraft_type_id' => $resource->aircraft_type_id])) {
            throw new ForbiddenHttpException();
        }

        $path = $resource->getPath();
        if (!file_exists($path)) {
            throw new NotFoundHttpException();
        }

        $ext    = strtolower(pathinfo($resource->filename, PATHINFO_EXTENSION));
        $inline = in_array($ext, ['png', 'jpg', 'jpeg', 'pdf'], true);

        return Yii::$app->response->sendFile($path, $resource->original_name, ['inline' => $inline]);
    }
}
