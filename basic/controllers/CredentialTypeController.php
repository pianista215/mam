<?php

namespace app\controllers;

use app\helpers\LoggerTrait;
use app\models\AircraftType;
use app\models\CredentialType;
use app\models\CredentialTypePrerequisite;
use app\models\CredentialTypeSearch;
use app\rbac\constants\Permissions;
use yii\helpers\ArrayHelper;
use yii\web\Controller;
use yii\web\ForbiddenHttpException;
use yii\web\NotFoundHttpException;
use yii\filters\AccessControl;
use yii\filters\VerbFilter;
use Yii;

/**
 * CredentialTypeController implements the CRUD actions for CredentialType model.
 */
class CredentialTypeController extends Controller
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
                    'only' => ['index', 'view', 'create', 'update', 'delete'],
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
     * Lists all CredentialType models.
     *
     * @return string
     */
    public function actionIndex()
    {
        $searchModel = new CredentialTypeSearch();
        $dataProvider = $searchModel->search($this->request->queryParams);

        $dataProvider->sort->defaultOrder = [
            'type' => SORT_ASC,
            'name' => SORT_ASC,
        ];

        return $this->render('index', [
            'searchModel'  => $searchModel,
            'dataProvider' => $dataProvider,
        ]);
    }

    /**
     * Displays a single CredentialType model.
     *
     * @param int $id
     * @return string
     * @throws NotFoundHttpException
     */
    public function actionView($id)
    {
        return $this->render('view', [
            'model' => $this->findModel($id),
        ]);
    }

    /**
     * Creates a new CredentialType model.
     *
     * @return string|\yii\web\Response
     */
    public function actionCreate()
    {
        if (!Yii::$app->user->can(Permissions::CREDENTIAL_CRUD)) {
            throw new ForbiddenHttpException();
        }

        $model = new CredentialType();

        if ($this->request->isPost && $model->load($this->request->post())) {
            $transaction = Yii::$app->db->beginTransaction();
            try {
                if ($model->save()) {
                    $this->syncRelations($model);
                    $transaction->commit();
                    $this->logInfo('Created credential type', ['model' => $model, 'user' => Yii::$app->user->identity->license]);
                    return $this->redirect(['view', 'id' => $model->id]);
                }
                $transaction->rollBack();
            } catch (\Throwable $e) {
                $transaction->rollBack();
                throw $e;
            }
        }

        return $this->render('create', [
            'model'            => $model,
            'credentialTypes'  => $this->getCredentialTypeOptions(),
            'aircraftTypes'    => $this->getAircraftTypeOptions(),
        ]);
    }

    /**
     * Updates an existing CredentialType model.
     *
     * @param int $id
     * @return string|\yii\web\Response
     * @throws NotFoundHttpException
     */
    public function actionUpdate($id)
    {
        if (!Yii::$app->user->can(Permissions::CREDENTIAL_CRUD)) {
            throw new ForbiddenHttpException();
        }

        $model = $this->findModel($id);

        if ($this->request->isPost && $model->load($this->request->post())) {
            $transaction = Yii::$app->db->beginTransaction();
            try {
                if ($model->save()) {
                    $this->syncRelations($model);
                    $transaction->commit();
                    $this->logInfo('Updated credential type', ['model' => $model, 'user' => Yii::$app->user->identity->license]);
                    return $this->redirect(['view', 'id' => $model->id]);
                }
                $transaction->rollBack();
            } catch (\Throwable $e) {
                $transaction->rollBack();
                throw $e;
            }
        } else {
            // Pre-populate virtual properties from existing relations
            $model->prerequisiteIds = ArrayHelper::getColumn($model->prerequisites, 'parent_id');
            $model->aircraftTypeIds = ArrayHelper::getColumn($model->aircraftTypes, 'id');
        }

        return $this->render('update', [
            'model'           => $model,
            'credentialTypes' => $this->getCredentialTypeOptions($model->id),
            'aircraftTypes'   => $this->getAircraftTypeOptions(),
        ]);
    }

    /**
     * Deletes an existing CredentialType model.
     *
     * @param int $id
     * @return \yii\web\Response
     * @throws NotFoundHttpException
     */
    public function actionDelete($id)
    {
        if (!Yii::$app->user->can(Permissions::CREDENTIAL_CRUD)) {
            throw new ForbiddenHttpException();
        }

        $model = $this->findModel($id);
        $model->delete();
        $this->logInfo('Deleted credential type', ['id' => $id, 'user' => Yii::$app->user->identity->license]);

        return $this->redirect(['index']);
    }

    /**
     * Syncs the prerequisite and aircraft type junction tables for a credential type.
     */
    protected function syncRelations(CredentialType $model): void
    {
        // Sync prerequisites
        CredentialTypePrerequisite::deleteAll(['child_id' => $model->id]);
        foreach (array_filter((array) $model->prerequisiteIds) as $parentId) {
            $prereq = new CredentialTypePrerequisite();
            $prereq->parent_id = (int) $parentId;
            $prereq->child_id  = $model->id;
            $prereq->save();
        }

        // Sync aircraft types
        Yii::$app->db->createCommand()
            ->delete('credential_type_aircraft_type', ['credential_type_id' => $model->id])
            ->execute();
        foreach (array_filter((array) $model->aircraftTypeIds) as $aircraftTypeId) {
            Yii::$app->db->createCommand()
                ->insert('credential_type_aircraft_type', [
                    'credential_type_id' => $model->id,
                    'aircraft_type_id'   => (int) $aircraftTypeId,
                ])
                ->execute();
        }
    }

    /**
     * Returns credential types as id => label map, optionally excluding one (self).
     */
    protected function getCredentialTypeOptions(?int $excludeId = null): array
    {
        $query = CredentialType::find()->orderBy(['type' => SORT_ASC, 'name' => SORT_ASC]);
        if ($excludeId !== null) {
            $query->andWhere(['<>', 'id', $excludeId]);
        }
        return ArrayHelper::map(
            $query->all(),
            'id',
            fn(CredentialType $ct) => '[' . $ct->getTypeLabel() . '] ' . $ct->name
        );
    }

    /**
     * Returns aircraft types as id => name map.
     */
    protected function getAircraftTypeOptions(): array
    {
        return ArrayHelper::map(
            AircraftType::find()->orderBy(['name' => SORT_ASC])->all(),
            'id',
            'name'
        );
    }

    /**
     * Finds the CredentialType model based on its primary key value.
     *
     * @param int $id
     * @return CredentialType
     * @throws NotFoundHttpException
     */
    protected function findModel($id)
    {
        if (($model = CredentialType::findOne(['id' => $id])) !== null) {
            return $model;
        }

        throw new NotFoundHttpException(Yii::t('app', 'The requested page does not exist.'));
    }
}
