<?php

namespace app\controllers;

use app\helpers\LoggerTrait;
use app\models\Image;
use app\models\ImageSearch;
use app\models\Page;
use app\rbac\constants\Permissions;
use yii\helpers\FileHelper;
use yii\web\Controller;
use yii\web\ForbiddenHttpException;
use yii\web\NotFoundHttpException;
use yii\web\UploadedFile;
use yii\filters\AccessControl;
use yii\filters\VerbFilter;
use Yii;

/**
 * ImageController implements the CRUD actions for Image model.
 */
class ImageController extends Controller
{
    use LoggerTrait;

    const REDIRECT_IMAGE_MANAGER = 'image_manager';
    const REDIRECT_PAGE_EDITOR = 'page_editor';

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
                    'only' => ['index', 'upload', 'delete'],
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
     * Lists all Image models.
     *
     * @return string
     */
    public function actionIndex()
    {
        if(!Yii::$app->user->can(Permissions::IMAGE_CRUD)){
            throw new ForbiddenHttpException();
        }
        $searchModel = new ImageSearch();
        $dataProvider = $searchModel->search(Yii::$app->request->queryParams);

        // Build dropdown
        $types = [];
        foreach (Image::getAllowedTypes() as $key => $info) {
            $types[$key] = $info['label'] ?? $key;
        }

        return $this->render('index', [
            'searchModel' => $searchModel,
            'dataProvider' => $dataProvider,
            'types' => $types,
        ]);
    }



    public function actionView(string $type, int $related_id, int $element = 0)
    {

        $image = Image::findOne([
            'type' => $type,
            'related_id' => $related_id,
            'element' => $element,
        ]);

        if (Yii::$app->user->isGuest) {
            if ($type === Image::TYPE_PILOT_PROFILE || $type === Image::TYPE_TOUR_IMAGE) {
                throw new ForbiddenHttpException();
            }

            if ($type === Image::TYPE_PAGE_IMAGE) {
                if ($image === null) {
                    throw new ForbiddenHttpException();
                }

                $page = $image->getRelatedModel();

                if (!$page || $page->type === Page::TYPE_TOUR) {
                    throw new ForbiddenHttpException();
                }
            }
        }

        $filePath = null;
        $mimeType = 'image/png'; // Placeholders default

        if ($image) {
            $candidate = $image->path;
            if (is_file($candidate)) {
                $filePath = $candidate;
                $mimeType = FileHelper::getMimeType($candidate);
            }
        }

        if (!$filePath) {
            $placeholder = Image::getPlaceholder($type);

            if ($placeholder === null) {
                throw new NotFoundHttpException();
            }

            $candidate = Yii::getAlias($placeholder);
            if (is_file($candidate)) {
                $filePath = $candidate;
                $mimeType = FileHelper::getMimeType($candidate);
            } else {
                throw new NotFoundHttpException();
            }
        }

        return Yii::$app->response->sendFile($filePath, null, [
            'inline' => true,
            'mimeType' => $mimeType,
            'cacheControlHeader' => 'public, max-age=86400',
        ]);
    }

    public function actionUpload(string $type, int $related_id, int $element = 0, ?string $redirect = null)
    {
        $image = Image::findOne([
            'type' => $type,
            'related_id' => $related_id,
            'element' => $element,
        ]);

        $oldFile = $image ? $image->path : null;

        $image = $image ?? new Image([
            'type' => $type,
            'related_id' => $related_id,
            'element' => $element,
        ]);

        if (!Yii::$app->user->can(Permissions::UPLOAD_IMAGE, ['image' => $image])) {
            throw new ForbiddenHttpException();
        }

        $relatedModel = $image->getRelatedModel();

        if ($relatedModel === null || !$image->isValidElement()) {
            throw new NotFoundHttpException(Yii::t('app', 'Element does not exist.'));
        }

        if (Yii::$app->request->isPost) {
            $uploadedFile = UploadedFile::getInstanceByName('croppedImage');
            if ($uploadedFile && $uploadedFile->error === UPLOAD_ERR_OK) {
                $newFilename = Yii::$app->security->generateRandomString() . '.' . $uploadedFile->extension;
                $image->filename = $newFilename;

                $directory = dirname($image->path);
                if (!is_dir($directory)) {
                    FileHelper::createDirectory($directory, 0755, true);
                }

                if ($uploadedFile->saveAs($image->path)){
                    if($image->save()) {
                        if($oldFile !== null){
                            // Delete the old version
                            unlink($oldFile);
                        }
                        Yii::$app->session->setFlash('success', Yii::t('app', 'Image correctly uploaded.'));
                        $this->logInfo('Image uploaded', ['image' => $image, 'user' => Yii::$app->user->identity->license]);

                        return $this->redirect($this->getRedirectUrl($redirect, $type, $relatedModel, $image));
                    } else {
                        // Delete the new image uploaded
                        unlink($image->path);
                        Yii::$app->session->setFlash('error', Yii::t('app', 'Error saving image information.'));
                        $this->logError(
                            'Error saving uploaded file due to model problems',
                            [
                                'errors' => $image->getErrors(),
                                'user' => Yii::$app->user->identity->license
                            ]
                        );
                    }
                } else {
                    $this->logError(
                        'Error saving uploaded file into the location',
                        [
                            'image' => $image,
                            'path' => $image->path,
                            'user' => Yii::$app->user->identity->license
                        ]
                    );
                    Yii::$app->session->setFlash('error', Yii::t('app', 'Error saving uploaded file.'));
                }

            } elseif ($uploadedFile && $uploadedFile->error === UPLOAD_ERR_INI_SIZE) {
                $maxSize = ini_get('upload_max_filesize');
                $this->logError(
                    'Uploaded file exceeds size limit',
                    [
                        'error' => $uploadedFile->error,
                        'max_size' => $maxSize,
                        'user' => Yii::$app->user->identity->license
                    ]);
                Yii::$app->session->setFlash('error', Yii::t('app', 'The image is too large. Maximum size allowed: {size}.', ['size' => $maxSize]));
            } else {
                $this->logError(
                    'Uploaded file not found or has errors',
                    [
                        'error' => $uploadedFile ? $uploadedFile->error : 'no file',
                        'user' => Yii::$app->user->identity->license
                    ]);
                Yii::$app->session->setFlash('error', Yii::t('app', 'Can\'t find image'));
            }
        }

        return $this->render('upload', [
            'image' => $image,
            'description' => $relatedModel->getImageDescription(),
            'redirect' => $redirect,
        ]);
    }

    /**
     * Deletes an existing Image model.
     * If deletion is successful, the browser will be redirected based on the redirect parameter.
     * @param int $id ID
     * @param string|null $redirect Where to redirect after deletion
     * @return \yii\web\Response
     * @throws NotFoundHttpException if the model cannot be found
     * @throws ForbiddenHttpException if user lacks permission
     */
    public function actionDelete(int $id, ?string $redirect = null)
    {
        $image = $this->findModel($id);

        if (!Yii::$app->user->can(Permissions::UPLOAD_IMAGE, ['image' => $image])) {
            throw new ForbiddenHttpException();
        }

        $type = $image->type;
        $relatedModel = $image->getRelatedModel();
        $redirectUrl = $this->getRedirectUrl($redirect, $type, $relatedModel, $image);

        $image->delete();

        return $this->redirect($redirectUrl);
    }

    /**
     * Determines the redirect URL based on the redirect parameter.
     * @param string|null $redirect The redirect type
     * @param string $type The image type
     * @param mixed $relatedModel The related model
     * @param Image $image The image model
     * @return array|string The redirect URL
     */
    protected function getRedirectUrl(?string $redirect, string $type, $relatedModel, Image $image)
    {
        if ($redirect === self::REDIRECT_IMAGE_MANAGER) {
            return ['index', 'ImageSearch[type]' => $type];
        } elseif ($redirect === self::REDIRECT_PAGE_EDITOR && $type === Image::TYPE_PAGE_IMAGE && $relatedModel) {
            return ['page/edit', 'code' => $relatedModel->code, 'language' => Yii::$app->language, 'type' => $relatedModel->type];
        } else {
            return $image->getCallbackUrl();
        }
    }

    /**
     * Finds the Image model based on its primary key value.
     * If the model is not found, a 404 HTTP exception will be thrown.
     * @param string $id ID
     * @return Image the loaded model
     * @throws NotFoundHttpException if the model cannot be found
     */
    protected function findModel($id)
    {
        if (($model = Image::findOne(['id' => $id])) !== null) {
            return $model;
        }

        throw new NotFoundHttpException('The requested page does not exist.');
    }
}
