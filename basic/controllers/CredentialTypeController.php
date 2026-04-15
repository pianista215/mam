<?php

namespace app\controllers;

use app\helpers\LoggerTrait;
use app\models\AircraftType;
use app\models\CredentialType;
use app\models\CredentialTypeAirportAircraft;
use app\models\CredentialTypePrerequisite;
use app\models\CredentialTypeSearch;
use app\models\PilotCredential;
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

        $allCredentials = CredentialType::find()
            ->with('aircraftTypes')
            ->orderBy(['type' => SORT_ASC, 'name' => SORT_ASC])
            ->all();
        $edges = CredentialTypePrerequisite::find()->all();

        return $this->render('index', [
            'searchModel'   => $searchModel,
            'dataProvider'  => $dataProvider,
            'mermaidGraph'  => $this->generateMermaidGraph($allCredentials, $edges),
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
        $model = $this->findModel($id);

        $currentCredentials = PilotCredential::find()
            ->with('pilot')
            ->where(['credential_type_id' => $model->id])
            ->orderBy(['status' => SORT_ASC, 'expiry_date' => SORT_ASC])
            ->all();

        return $this->render('view', [
            'model'              => $model,
            'currentCredentials' => $currentCredentials,
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
                    $this->syncRelations(
                        $model,
                        $this->request->post('airportIcaos', []),
                        $this->request->post('restrictionAircraftTypeIds', [])
                    );
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
            'model'                      => $model,
            'credentialTypes'            => $this->getCredentialTypeOptions(),
            'aircraftTypes'              => $this->getAircraftTypeOptions(),
            'restrictionAirports'        => [],
            'restrictionAircraftTypeIds' => [],
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
                    $this->syncRelations(
                        $model,
                        $this->request->post('airportIcaos', []),
                        $this->request->post('restrictionAircraftTypeIds', [])
                    );
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

        $existingRestrictions       = $model->airportAircraftRestrictions;
        $restrictionAirports        = array_values(array_unique(ArrayHelper::getColumn($existingRestrictions, 'airport_icao')));
        $restrictionAircraftTypeIds = array_values(array_unique(ArrayHelper::getColumn($existingRestrictions, 'aircraft_type_id')));

        return $this->render('update', [
            'model'                      => $model,
            'credentialTypes'            => $this->getCredentialTypeOptions($model->id),
            'aircraftTypes'              => $this->getAircraftTypeOptions(),
            'restrictionAirports'        => $restrictionAirports,
            'restrictionAircraftTypeIds' => $restrictionAircraftTypeIds,
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
     * Syncs the prerequisite, aircraft type, and airport-aircraft restriction tables for a credential type.
     * Airport restrictions are stored as the cross-product of $airportIcaos × $restrictionAircraftTypeIds.
     *
     * @param string[] $airportIcaos              ICAO codes from POST (airportIcaos[])
     * @param int[]    $restrictionAircraftTypeIds aircraft type IDs from POST (restrictionAircraftTypeIds[])
     */
    protected function syncRelations(CredentialType $model, array $airportIcaos = [], array $restrictionAircraftTypeIds = []): void
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

        // Sync airport-aircraft restrictions: cross-product of airports × aircraft types
        CredentialTypeAirportAircraft::deleteAll(['credential_type_id' => $model->id]);
        $icaos = array_unique(array_filter(
            array_map(fn($s) => strtoupper(trim($s)), $airportIcaos),
            fn($s) => strlen($s) === 4
        ));
        $acIds = array_unique(array_filter(array_map('intval', $restrictionAircraftTypeIds)));
        foreach ($icaos as $icao) {
            foreach ($acIds as $acId) {
                $r = new CredentialTypeAirportAircraft();
                $r->credential_type_id = $model->id;
                $r->aircraft_type_id   = $acId;
                $r->airport_icao       = $icao;
                $r->save();
            }
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
     * Builds a Mermaid graph definition (TD) for the credential DAG.
     * Nodes that share the same parent are placed side by side automatically
     * by dagre's layout engine.
     *
     * @param CredentialType[] $credentials
     * @param CredentialTypePrerequisite[] $edges
     */
    protected function generateMermaidGraph(array $credentials, array $edges): string
    {
        if (empty($credentials)) {
            return '';
        }

        $cssClasses = [
            CredentialType::TYPE_LICENSE       => 'license',
            CredentialType::TYPE_RATING        => 'rating',
            CredentialType::TYPE_CERTIFICATION => 'certification',
        ];

        $lines   = ['---'];
        $lines[] = 'config:';
        $lines[] = '  look: neo';
        $lines[] = '  flowchart:';
        $lines[] = '    nodeSpacing: 200';
        $lines[] = '    rankSpacing: 150';
        $lines[] = '    curve: natural';
        $lines[] = '---';
        $lines[] = 'graph TD';
        $lines[] = '    classDef license fill:#4e73df,color:#fff,stroke:#2e59d9';
        $lines[] = '    classDef rating fill:#1cc88a,color:#fff,stroke:#17a673';
        $lines[] = '    classDef certification fill:#f6c23e,color:#333,stroke:#dda20a';
        $lines[] = '    linkStyle default stroke:#555,stroke-width:5px';

        foreach ($credentials as $ct) {
            $cssClass      = $cssClasses[$ct->type] ?? 'license';
            $label         = addslashes($ct->name);
            $aircraftNames = array_map(fn($at) => $at->icao_type_code, $ct->aircraftTypes);
            if (!empty($aircraftNames)) {
                $label .= '&lt;br/&gt;──────────&lt;br/&gt;' . implode('&lt;br/&gt;', array_map('addslashes', $aircraftNames));
            }
            $lines[] = '    n' . $ct->id . '["' . $label . '"]:::' . $cssClass;
        }

        foreach ($edges as $edge) {
            $lines[] = '    n' . $edge->parent_id . ' --> n' . $edge->child_id;
        }

        return implode("\n", $lines);
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
