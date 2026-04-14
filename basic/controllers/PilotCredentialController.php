<?php

namespace app\controllers;

use app\helpers\LoggerTrait;
use app\models\CredentialType;
use app\models\CredentialTypePrerequisite;
use app\models\Pilot;
use app\models\PilotCredential;
use app\rbac\constants\Permissions;
use yii\web\Controller;
use yii\web\ForbiddenHttpException;
use yii\web\NotFoundHttpException;
use yii\filters\AccessControl;
use yii\filters\VerbFilter;
use Yii;

/**
 * PilotCredentialController manages the lifecycle of pilot credentials:
 * issuing, viewing, renewing, and revoking.
 */
class PilotCredentialController extends Controller
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
                        'revoke' => ['POST'],
                    ],
                ],
            ]
        );
    }

    /**
     * Displays a single pilot credential with its full history for the same
     * pilot + credential type pair.
     *
     * @param int $id
     * @return string
     * @throws NotFoundHttpException
     */
    public function actionView($id)
    {
        $model   = $this->findModel($id);
        $history = $model->getHistory()->all();

        return $this->render('view', [
            'model'   => $model,
            'history' => $history,
        ]);
    }

    /**
     * Issues a new credential to a pilot.
     *
     * @param int $pilotId
     * @return string|\yii\web\Response
     * @throws ForbiddenHttpException
     * @throws NotFoundHttpException
     */
    public function actionIssue($pilotId)
    {
        if (!Yii::$app->user->can(Permissions::ISSUE_CREDENTIAL)) {
            throw new ForbiddenHttpException();
        }

        $pilot = $this->findPilot($pilotId);

        $model            = new PilotCredential();
        $model->pilot_id  = $pilot->id;
        $model->status    = PilotCredential::STATUS_ACTIVE;
        $model->issued_by = Yii::$app->user->id;

        if ($this->request->isPost && $model->load($this->request->post())) {
            if ($model->save()) {
                $this->logInfo('Issued credential', ['pilot_id' => $pilot->id, 'credential_type_id' => $model->credential_type_id]);
                return $this->redirect(['/pilot/view', 'id' => $pilot->id]);
            }
        }

        // Credential types the pilot does not already hold (current records only)
        $existingTypeIds = array_map('intval', PilotCredential::find()
            ->select('credential_type_id')
            ->where(['pilot_id' => $pilot->id, 'superseded_at' => null])
            ->column());

        // Load candidates not yet held
        $query = CredentialType::find()->orderBy(['type' => SORT_ASC, 'name' => SORT_ASC]);
        if (!empty($existingTypeIds)) {
            $query->andWhere(['not in', 'id', $existingTypeIds]);
        }
        $candidates = $query->all();

        // Keep only types whose prerequisites are met (OR: pilot holds at least 1 parent)
        $credentialTypes = [];
        foreach ($candidates as $ct) {
            $parentIds = array_map('intval', CredentialTypePrerequisite::find()
                ->select('parent_id')
                ->where(['child_id' => $ct->id])
                ->column());
            if (empty($parentIds)) {
                $credentialTypes[$ct->id] = '[' . $ct->getTypeLabel() . '] ' . $ct->name;
            } else {
                foreach ($parentIds as $pid) {
                    if (in_array($pid, $existingTypeIds, true)) {
                        $credentialTypes[$ct->id] = '[' . $ct->getTypeLabel() . '] ' . $ct->name;
                        break;
                    }
                }
            }
        }

        return $this->render('issue', [
            'model'           => $model,
            'pilot'           => $pilot,
            'credentialTypes' => $credentialTypes,
        ]);
    }

    /**
     * Renews an existing credential: closes the current record and creates a new one.
     *
     * @param int $id
     * @return string|\yii\web\Response
     * @throws ForbiddenHttpException
     * @throws NotFoundHttpException
     */
    public function actionRenew($id)
    {
        if (!Yii::$app->user->can(Permissions::ISSUE_CREDENTIAL)) {
            throw new ForbiddenHttpException();
        }

        $current = $this->findModel($id);

        $model                      = new PilotCredential();
        $model->pilot_id            = $current->pilot_id;
        $model->credential_type_id  = $current->credential_type_id;
        $model->status              = $current->status;
        $model->issued_date         = date('Y-m-d');
        $model->expiry_date         = $current->expiry_date;
        $model->notes               = $current->notes;
        $model->issued_by           = Yii::$app->user->id;

        if ($this->request->isPost && $model->load($this->request->post())) {
            $transaction = Yii::$app->db->beginTransaction();
            try {
                $now = date('Y-m-d H:i:s');
                Yii::$app->db->createCommand()->update(
                    'pilot_credential',
                    ['superseded_at' => $now],
                    ['id' => $current->id]
                )->execute();

                if ($model->save()) {
                    $transaction->commit();
                    $this->logInfo('Renewed credential', ['old_id' => $current->id, 'new_id' => $model->id]);
                    return $this->redirect(['view', 'id' => $model->id]);
                }
                $transaction->rollBack();
            } catch (\Throwable $e) {
                $transaction->rollBack();
                throw $e;
            }
        }

        return $this->render('renew', [
            'model'   => $model,
            'current' => $current,
        ]);
    }

    /**
     * Revokes a credential by setting superseded_at to NOW().
     *
     * @param int $id
     * @return \yii\web\Response
     * @throws ForbiddenHttpException
     * @throws NotFoundHttpException
     */
    public function actionRevoke($id)
    {
        if (!Yii::$app->user->can(Permissions::ISSUE_CREDENTIAL)) {
            throw new ForbiddenHttpException();
        }

        $model = $this->findModel($id);
        $pilotId = $model->pilot_id;

        Yii::$app->db->createCommand()->update(
            'pilot_credential',
            ['superseded_at' => date('Y-m-d H:i:s')],
            ['id' => $model->id]
        )->execute();

        $this->logInfo('Revoked credential', ['id' => $model->id, 'pilot_id' => $pilotId]);

        return $this->redirect(['/pilot/view', 'id' => $pilotId]);
    }

    /**
     * Traverses the prerequisite graph upward (BFS) and returns all ancestor
     * credential type IDs for the given type.
     *
     * @param int $credentialTypeId
     * @return int[]
     */
    private function getAncestorTypeIds(int $credentialTypeId): array
    {
        $ancestors = [];
        $queue     = [$credentialTypeId];
        while (!empty($queue)) {
            $current   = array_shift($queue);
            $parentIds = array_map('intval', CredentialTypePrerequisite::find()
                ->select('parent_id')
                ->where(['child_id' => $current])
                ->column());
            foreach ($parentIds as $pid) {
                if (!in_array($pid, $ancestors, true)) {
                    $ancestors[] = $pid;
                    $queue[]     = $pid;
                }
            }
        }
        return $ancestors;
    }

    /**
     * Finds a PilotCredential by primary key.
     *
     * @param int $id
     * @return PilotCredential
     * @throws NotFoundHttpException
     */
    protected function findModel($id)
    {
        $model = PilotCredential::findOne(['id' => $id]);
        if ($model === null) {
            throw new NotFoundHttpException(Yii::t('app', 'The requested page does not exist.'));
        }
        return $model;
    }

    /**
     * Finds a Pilot by primary key.
     *
     * @param int $id
     * @return Pilot
     * @throws NotFoundHttpException
     */
    protected function findPilot($id)
    {
        $pilot = Pilot::findOne(['id' => $id]);
        if ($pilot === null) {
            throw new NotFoundHttpException(Yii::t('app', 'The requested page does not exist.'));
        }
        return $pilot;
    }
}
