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
 * issuing, viewing, renewing (update in place), and revoking (delete).
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
     * Displays a single pilot credential.
     */
    public function actionView($id)
    {
        return $this->render('view', ['model' => $this->findModel($id)]);
    }

    /**
     * Issues a new credential to a pilot.
     */
    public function actionIssue($pilotId)
    {
        if (!Yii::$app->user->can(Permissions::ISSUE_CREDENTIAL)) {
            throw new ForbiddenHttpException();
        }

        $pilot = $this->findPilot($pilotId);

        $model                     = new PilotCredential();
        $model->pilot_id           = $pilot->id;
        $model->status             = PilotCredential::STATUS_ACTIVE;
        $model->issued_by          = Yii::$app->user->id;

        if ($this->request->isPost && $model->load($this->request->post())) {
            if ($model->save()) {
                $this->logInfo('Issued credential', ['pilot_id' => $pilot->id, 'credential_type_id' => $model->credential_type_id]);
                return $this->redirect(['/pilot/view', 'id' => $pilot->id]);
            }
        }

        // Credential types the pilot does not already hold
        $existingTypeIds = array_map('intval', PilotCredential::find()
            ->select('credential_type_id')
            ->where(['pilot_id' => $pilot->id])
            ->column());

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
     * Renews (or issues from student to active) a credential: updates the existing record in place.
     */
    public function actionRenew($id)
    {
        if (!Yii::$app->user->can(Permissions::ISSUE_CREDENTIAL)) {
            throw new ForbiddenHttpException();
        }

        $model = $this->findModel($id);
        $model->issued_by = Yii::$app->user->id;

        if ($this->request->isPost && $model->load($this->request->post())) {
            if ($model->save()) {
                $this->logInfo('Renewed credential', ['id' => $model->id]);
                return $this->redirect(['view', 'id' => $model->id]);
            }
        }

        return $this->render('renew', ['model' => $model]);
    }

    /**
     * Revokes a credential by deleting it.
     */
    public function actionRevoke($id)
    {
        if (!Yii::$app->user->can(Permissions::ISSUE_CREDENTIAL)) {
            throw new ForbiddenHttpException();
        }

        $model   = $this->findModel($id);
        $pilotId = $model->pilot_id;
        $model->delete();

        $this->logInfo('Revoked credential', ['id' => $id, 'pilot_id' => $pilotId]);

        return $this->redirect(['/pilot/view', 'id' => $pilotId]);
    }

    /**
     * Traverses the prerequisite graph upward (BFS) and returns all ancestor
     * credential type IDs for the given type.
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

    protected function findModel($id)
    {
        $model = PilotCredential::findOne(['id' => $id]);
        if ($model === null) {
            throw new NotFoundHttpException(Yii::t('app', 'The requested page does not exist.'));
        }
        return $model;
    }

    protected function findPilot($id)
    {
        $pilot = Pilot::findOne(['id' => $id]);
        if ($pilot === null) {
            throw new NotFoundHttpException(Yii::t('app', 'The requested page does not exist.'));
        }
        return $pilot;
    }
}
