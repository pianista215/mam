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
     * Displays a single pilot credential with pre-computed action availability.
     */
    public function actionView($id)
    {
        $model = $this->findModel($id);
        return $this->render('view', [
            'model'        => $model,
            'canRenew'     => $model->canRenew(),
            'canRevoke'    => $model->canRevoke(),
            'cascadeNames' => $model->getCascadeCredentialNames(),
        ]);
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

        $model            = new PilotCredential();
        $model->pilot_id  = $pilot->id;
        $model->status    = PilotCredential::STATUS_ACTIVE;
        $model->issued_by = Yii::$app->user->id;

        if ($this->request->isPost && $model->load($this->request->post())) {
            // Server-side: validate prerequisites are met (guards against injected credential_type_id)
            if ($model->credential_type_id) {
                $parentIds = array_map('intval', CredentialTypePrerequisite::find()
                    ->select('parent_id')
                    ->where(['child_id' => $model->credential_type_id])
                    ->column());
                if (!empty($parentIds)) {
                    $hasMet = PilotCredential::find()
                        ->where(['pilot_id' => $model->pilot_id, 'credential_type_id' => $parentIds])
                        ->exists();
                    if (!$hasMet) {
                        $model->addError('credential_type_id', Yii::t('app', 'Prerequisites for this credential type are not met.'));
                        $this->logWarn('Attempted to issue credential without meeting prerequisites', [
                            'pilot_id'           => $pilot->id,
                            'credential_type_id' => $model->credential_type_id,
                            'user'               => Yii::$app->user->identity->license,
                        ]);
                    }
                }
            }

            // If prereqs are met only via STUDENT credentials, new credential must also be STUDENT
            if ($model->credential_type_id && !$model->hasErrors() && (int)$model->status === PilotCredential::STATUS_ACTIVE) {
                $parentIds = array_map('intval', CredentialTypePrerequisite::find()
                    ->select('parent_id')
                    ->where(['child_id' => $model->credential_type_id])
                    ->column());
                if (!empty($parentIds)) {
                    $hasActiveMet = PilotCredential::find()
                        ->where(['pilot_id' => $model->pilot_id, 'credential_type_id' => $parentIds, 'status' => PilotCredential::STATUS_ACTIVE])
                        ->exists();
                    if (!$hasActiveMet) {
                        $model->addError('status', Yii::t('app', 'This credential can only be issued as Student because all prerequisites are held as Student.'));
                        $this->logWarn('Attempted to issue active credential with only student prerequisites', [
                            'pilot_id'           => $pilot->id,
                            'credential_type_id' => $model->credential_type_id,
                            'user'               => Yii::$app->user->identity->license,
                        ]);
                    }
                }
            }

            if (!$model->hasErrors()) {
                $transaction = Yii::$app->db->beginTransaction();
                try {
                    if ($model->save()) {
                        // When issuing an active LICENSE, clear expiry_date on ancestor licenses
                        // so only the highest license in the chain retains an active expiry
                        if ($model->isActive() && $model->credentialType->isLicense()) {
                            $this->clearAncestorLicenseExpiries($model);
                        }
                        $transaction->commit();
                        $this->logInfo('Issued credential', [
                            'pilot_id'           => $pilot->id,
                            'credential_type_id' => $model->credential_type_id,
                            'user'               => Yii::$app->user->identity->license,
                        ]);
                        return $this->redirect(['/pilot/view', 'id' => $pilot->id]);
                    }
                    $transaction->rollBack();
                } catch (\Throwable $e) {
                    $transaction->rollBack();
                    throw $e;
                }
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

        // For each candidate type, determine if it can only be issued as Student
        // (i.e. all met prerequisites are held as Student, none as Active)
        $studentOnlyTypeIds = [];
        foreach (array_keys($credentialTypes) as $typeId) {
            $parentIds = array_map('intval', CredentialTypePrerequisite::find()
                ->select('parent_id')
                ->where(['child_id' => $typeId])
                ->column());
            if (!empty($parentIds)) {
                $hasActiveMet = PilotCredential::find()
                    ->where(['pilot_id' => $pilot->id, 'credential_type_id' => $parentIds, 'status' => PilotCredential::STATUS_ACTIVE])
                    ->exists();
                if (!$hasActiveMet) {
                    $studentOnlyTypeIds[] = (int)$typeId;
                }
            }
        }

        return $this->render('issue', [
            'model'              => $model,
            'pilot'              => $pilot,
            'credentialTypes'    => $credentialTypes,
            'studentOnlyTypeIds' => $studentOnlyTypeIds,
        ]);
    }

    /**
     * Promotes a student credential to active (first real issuance).
     * issued_date is editable here — this is the date the credential was truly earned.
     * When the new active credential is a LICENSE, clears expiry_date on ancestor licenses.
     */
    public function actionActivate($id)
    {
        if (!Yii::$app->user->can(Permissions::ISSUE_CREDENTIAL)) {
            throw new ForbiddenHttpException();
        }

        $model = $this->findModel($id);

        if (!$model->isStudent()) {
            $this->logWarn('Attempted to activate non-student credential', [
                'id'   => $id,
                'user' => Yii::$app->user->identity->license,
            ]);
            throw new ForbiddenHttpException();
        }

        $model->issued_by = Yii::$app->user->id;
        $model->status    = PilotCredential::STATUS_ACTIVE;

        if ($this->request->isPost && $model->load($this->request->post())) {
            $transaction = Yii::$app->db->beginTransaction();
            try {
                if ($model->save()) {
                    if ($model->isActive() && $model->credentialType->isLicense()) {
                        $this->clearAncestorLicenseExpiries($model);
                    }
                    $transaction->commit();
                    $this->logInfo('Activated credential', [
                        'id'   => $model->id,
                        'user' => Yii::$app->user->identity->license,
                    ]);
                    return $this->redirect(['view', 'id' => $model->id]);
                }
                $transaction->rollBack();
            } catch (\Throwable $e) {
                $transaction->rollBack();
                throw $e;
            }
        }

        return $this->render('activate', ['model' => $model]);
    }

    /**
     * Renews an active credential: updates expiry_date in place.
     * issued_date is immutable. When renewing an active LICENSE, also auto-renews
     * active descendant RATING credentials (including ratings from ancestor licenses).
     */
    public function actionRenew($id)
    {
        if (!Yii::$app->user->can(Permissions::ISSUE_CREDENTIAL)) {
            throw new ForbiddenHttpException();
        }

        $model = $this->findModel($id);

        if (!$model->isActive()) {
            $this->logWarn('Attempted to renew non-active credential', [
                'id'   => $id,
                'user' => Yii::$app->user->identity->license,
            ]);
            throw new ForbiddenHttpException();
        }

        if (!$model->canRenew()) {
            $this->logWarn('Attempted to renew credential that cannot be renewed', [
                'id'   => $id,
                'user' => Yii::$app->user->identity->license,
            ]);
            throw new ForbiddenHttpException(Yii::t('app', 'This credential cannot be renewed.'));
        }

        $model->issued_by   = Yii::$app->user->id;
        $originalIssuedDate = $model->issued_date;

        if ($this->request->isPost && $model->load($this->request->post())) {
            $model->issued_date = $originalIssuedDate;

            if ($model->expiry_date !== null && $model->expiry_date <= date('Y-m-d')) {
                $model->addError('expiry_date', Yii::t('app', 'Expiry date must be after today.'));
            }

            if (!$model->hasErrors()) {
                $transaction = Yii::$app->db->beginTransaction();
                try {
                    if ($model->save()) {
                        if ($model->credentialType->isLicense() && $model->expiry_date !== null) {
                            $this->autoRenewDescendantRatings($model);
                        }
                        $transaction->commit();
                        $this->logInfo('Renewed credential', [
                            'id'   => $model->id,
                            'user' => Yii::$app->user->identity->license,
                        ]);
                        return $this->redirect(['view', 'id' => $model->id]);
                    }
                    $transaction->rollBack();
                } catch (\Throwable $e) {
                    $transaction->rollBack();
                    throw $e;
                }
            }
        }

        return $this->render('renew', ['model' => $model]);
    }

    /**
     * Revokes a credential by deleting it and cascade-deleting all descendant credentials
     * the pilot holds. Cannot revoke a LICENSE if the pilot holds a higher LICENSE that depends on it.
     */
    public function actionRevoke($id)
    {
        if (!Yii::$app->user->can(Permissions::ISSUE_CREDENTIAL)) {
            throw new ForbiddenHttpException();
        }

        $model = $this->findModel($id);

        // Guard: cannot revoke a LICENSE that is a prerequisite of another LICENSE the pilot holds
        if (!$model->canRevoke()) {
            $this->logWarn('Attempted to revoke blocked license', [
                'id'                 => $id,
                'pilot_id'           => $model->pilot_id,
                'credential_type_id' => $model->credential_type_id,
                'user'               => Yii::$app->user->identity->license,
            ]);
            throw new ForbiddenHttpException(Yii::t('app', 'This credential cannot be revoked because the pilot holds a higher license that depends on it.'));
        }

        $pilotId           = $model->pilot_id;
        $descendantTypeIds = $model->credentialType->getDescendantTypeIds();

        $transaction = Yii::$app->db->beginTransaction();
        try {
            if ($model->credentialType->isLicense()) {
                $this->restoreAncestorLicenseExpiries($model);
            }
            if (!empty($descendantTypeIds)) {
                $cascadeDeleted = PilotCredential::deleteAll([
                    'pilot_id'           => $pilotId,
                    'credential_type_id' => $descendantTypeIds,
                ]);
                if ($cascadeDeleted > 0) {
                    $this->logInfo('Cascade revoked credentials', [
                        'parent_id'        => $id,
                        'pilot_id'         => $pilotId,
                        'cascade_type_ids' => $descendantTypeIds,
                        'deleted'          => $cascadeDeleted,
                        'user'             => Yii::$app->user->identity->license,
                    ]);
                }
            }
            $model->delete();
            $transaction->commit();
            $this->logInfo('Revoked credential', [
                'id'       => $id,
                'pilot_id' => $pilotId,
                'user'     => Yii::$app->user->identity->license,
            ]);
        } catch (\Throwable $e) {
            $transaction->rollBack();
            throw $e;
        }

        return $this->redirect(['/pilot/view', 'id' => $pilotId]);
    }

    /**
     * Auto-renews active descendant RATING credentials when a LICENSE is renewed.
     * Includes ratings that are descendants of any ancestor license in the chain
     * (e.g. renewing ATPL also renews ratings hanging off CPL or PPL).
     * Student-status ratings are intentionally excluded.
     * Must be called inside an open transaction.
     */
    private function autoRenewDescendantRatings(PilotCredential $license): void
    {
        // Collect descendant type IDs from the renewed license itself
        $allDescendantIds = $license->credentialType->getDescendantTypeIds();

        // Also collect descendants from every ancestor license in the chain
        $ancestorIds = $this->getAncestorTypeIds($license->credential_type_id);
        if (!empty($ancestorIds)) {
            $ancestorModels = CredentialType::findAll($ancestorIds);
            foreach ($ancestorModels as $ancestor) {
                foreach ($ancestor->getDescendantTypeIds() as $did) {
                    if (!in_array($did, $allDescendantIds, true)) {
                        $allDescendantIds[] = $did;
                    }
                }
            }
        }

        if (empty($allDescendantIds)) {
            return;
        }

        $ratingTypeIds = CredentialType::find()
            ->select('id')
            ->where(['id' => $allDescendantIds, 'type' => CredentialType::TYPE_RATING])
            ->column();
        if (empty($ratingTypeIds)) {
            return;
        }
        $updated = PilotCredential::updateAll(
            ['expiry_date' => $license->expiry_date],
            [
                'pilot_id'           => $license->pilot_id,
                'credential_type_id' => $ratingTypeIds,
                'status'             => PilotCredential::STATUS_ACTIVE,
            ]
        );
        if ($updated > 0) {
            $this->logInfo('Auto-renewed descendant ratings', [
                'license_id'      => $license->id,
                'rating_type_ids' => $ratingTypeIds,
                'updated'         => $updated,
                'user'            => Yii::$app->user->identity->license,
            ]);
        }
    }

    /**
     * Clears expiry_date on all ancestor LICENSE credentials the pilot holds
     * when a new higher active LICENSE is issued.
     * Must be called inside an open transaction.
     */
    private function clearAncestorLicenseExpiries(PilotCredential $newLicense): void
    {
        $ancestorIds = $this->getAncestorTypeIds($newLicense->credential_type_id);
        if (empty($ancestorIds)) {
            return;
        }
        $ancestorLicenseIds = CredentialType::find()
            ->select('id')
            ->where(['id' => $ancestorIds, 'type' => CredentialType::TYPE_LICENSE])
            ->column();
        if (empty($ancestorLicenseIds)) {
            return;
        }
        $updated = PilotCredential::updateAll(
            ['expiry_date' => null],
            [
                'pilot_id'           => $newLicense->pilot_id,
                'credential_type_id' => $ancestorLicenseIds,
                'status'             => PilotCredential::STATUS_ACTIVE,
            ]
        );
        if ($updated > 0) {
            $this->logInfo('Cleared expiry on ancestor licenses after issuing higher license', [
                'new_license_id'      => $newLicense->id,
                'ancestor_type_ids'   => $ancestorLicenseIds,
                'updated'             => $updated,
                'user'                => Yii::$app->user->identity->license,
            ]);
        }
    }

    /**
     * Restores expiry_date on ancestor LICENSE credentials after a LICENSE is revoked.
     * The revoked license's expiry_date propagates down to ancestor licenses so they
     * regain a meaningful expiry and can be renewed again.
     * Must be called inside an open transaction.
     */
    private function restoreAncestorLicenseExpiries(PilotCredential $revokedLicense): void
    {
        $ancestorIds = $this->getAncestorTypeIds($revokedLicense->credential_type_id);
        if (empty($ancestorIds)) {
            return;
        }
        $ancestorLicenseIds = CredentialType::find()
            ->select('id')
            ->where(['id' => $ancestorIds, 'type' => CredentialType::TYPE_LICENSE])
            ->column();
        if (empty($ancestorLicenseIds)) {
            return;
        }
        $updated = PilotCredential::updateAll(
            ['expiry_date' => $revokedLicense->expiry_date],
            [
                'pilot_id'           => $revokedLicense->pilot_id,
                'credential_type_id' => $ancestorLicenseIds,
                'status'             => PilotCredential::STATUS_ACTIVE,
            ]
        );
        if ($updated > 0) {
            $this->logInfo('Restored expiry on ancestor licenses after revoking higher license', [
                'revoked_id'        => $revokedLicense->id,
                'ancestor_type_ids' => $ancestorLicenseIds,
                'restored_expiry'   => $revokedLicense->expiry_date,
                'updated'           => $updated,
                'user'              => Yii::$app->user->identity->license,
            ]);
        }
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
