<?php

namespace app\controllers;

use app\helpers\LoggerTrait;
use app\models\Image;
use app\models\ImageSearch;
use yii\helpers\FileHelper;
use yii\web\Controller;
use yii\web\ForbiddenHttpException;
use yii\web\NotFoundHttpException;
use yii\web\UploadedFile;
use yii\filters\VerbFilter;
use Yii;

/**
 * ImageController implements the CRUD actions for Image model.
 */
class ImageController extends Controller
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
        $searchModel = new ImageSearch();
        $dataProvider = $searchModel->search($this->request->queryParams);

        return $this->render('index', [
            'searchModel' => $searchModel,
            'dataProvider' => $dataProvider,
        ]);
    }

    public function actionView(string $type, int $related_id, int $element = 0)
    {
        // TODO: UNAI METER RBAC Y AÑADE LOGS
        /*if (!Yii::$app->user->can('viewImage', ['type' => $type, 'related_id' => $related_id])) {
            throw new ForbiddenHttpException();
        }*/

        $image = Image::findOne([
            'type' => $type,
            'related_id' => $related_id,
            'element' => $element,
        ]);

        // TODO: Simplificar porque hay mucho is_file que sobra
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

    public function actionUpload(string $type, int $related_id, int $element = 0)
    {
        /*if (!Yii::$app->user->can('uploadImage', ['type' => $type, 'related_id' => $related_id])) {
            throw new ForbiddenHttpException();
        }*/

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

        $relatedModel = $image->getRelatedModel();

        if ($relatedModel === null || !$image->isValidElement()) {
            throw new NotFoundHttpException('Element does not exist.');
        }

        if (Yii::$app->request->isPost) {
            $uploadedFile = UploadedFile::getInstanceByName('croppedImage');
            if ($uploadedFile) {
                $newFilename = Yii::$app->security->generateRandomString() . '.' . $uploadedFile->extension;

                $image->filename = $newFilename;

                if ($uploadedFile->saveAs($image->path)){
                    if($image->save()) {
                        if($oldFile !== null){
                            // Delete the old version
                            unlink($oldFile);
                        }
                        Yii::$app->session->setFlash('success', 'Image correctly uploaded.');
                        $this->logInfo('Image uploaded', ['image' => $image, 'user' => Yii::$app->user->identity->license]);
                        return $this->redirect($image->getCallbackUrl());
                    } else {
                        // Delete the new image uploaded
                        unlink($image->path);
                        Yii::$app->session->setFlash('error', 'Error saving image information.');
                        $this->logError(
                            'Error saving uploaded file',
                            [
                                'errors' => $image->getErrors(),
                                'user' => Yii::$app->user->identity->license
                            ]
                        );
                    }
                } else {
                    $this->logError(
                        'Error saving uploaded file',
                        [
                            'image' => $image,
                            'user' => Yii::$app->user->identity->license
                        ]
                    );
                    Yii::$app->session->setFlash('error', 'Error saving uploaded file.');
                }

            } else {
                $this->logError(
                    'Uploaded file not found',
                    [
                        'request' => Yii::$app->request,
                        'user' => Yii::$app->user->identity->license
                    ]);
                Yii::$app->session->setFlash('error', "Can't find image");
            }
        }

        return $this->render('upload', [
            'image' => $image,
            'description' => $relatedModel->getImageDescription(),
        ]);
    }


    /**
     * Creates a new Image model.
     * If creation is successful, the browser will be redirected to the 'view' page.
     * @return string|\yii\web\Response
     */
    public function actionCreate()
    {
        $model = new Image();

        if ($this->request->isPost) {
            if ($model->load($this->request->post()) && $model->save()) {
                return $this->redirect(['view', 'id' => $model->id]);
            }
        } else {
            $model->loadDefaultValues();
        }

        return $this->render('create', [
            'model' => $model,
        ]);
    }

    /**
     * Updates an existing Image model.
     * If update is successful, the browser will be redirected to the 'view' page.
     * @param string $id ID
     * @return string|\yii\web\Response
     * @throws NotFoundHttpException if the model cannot be found
     */
    public function actionUpdate($id)
    {
        $model = $this->findModel($id);

        if ($this->request->isPost && $model->load($this->request->post()) && $model->save()) {
            return $this->redirect(['view', 'id' => $model->id]);
        }

        return $this->render('update', [
            'model' => $model,
        ]);
    }

    /**
     * Deletes an existing Image model.
     * If deletion is successful, the browser will be redirected to the 'index' page.
     * @param string $id ID
     * @return \yii\web\Response
     * @throws NotFoundHttpException if the model cannot be found
     */
    public function actionDelete($id)
    {
        $this->findModel($id)->delete();

        return $this->redirect(['index']);
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
