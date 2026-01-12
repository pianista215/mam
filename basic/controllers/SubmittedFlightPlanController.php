<?php

namespace app\controllers;

use app\config\Config;
use app\helpers\GeoUtils;
use app\helpers\LoggerTrait;
use app\models\Aircraft;
use app\models\AircraftSearch;
use app\models\Airport;
use app\models\CharterRoute;
use app\models\Route;
use app\models\RouteSearch;
use app\models\SubmittedFlightPlan;
use app\models\SubmittedFlightPlanSearch;
use app\models\TourStage;
use app\models\TourStageSearch;
use app\rbac\constants\Permissions;
use yii\web\Controller;
use yii\web\BadRequestHttpException;
use yii\web\ForbiddenHttpException;
use yii\web\NotFoundHttpException;
use yii\web\ServerErrorHttpException;
use yii\filters\AccessControl;
use yii\filters\VerbFilter;
use Yii;

/**
 * SubmittedFlightPlanController implements the CRUD actions for SubmittedFlightPlan model.
 */
class SubmittedFlightPlanController extends Controller
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
                    'only' => [
                        'select-flight',
                        'select-aircraft-route',
                        'select-aircraft-tour',
                        'select-aircraft-charter',
                        'prepare-fpl-route',
                        'prepare-fpl-tour',
                        'prepare-fpl-charter',
                        'my-fpl',
                        'index',
                        'view',
                        'update',
                        'delete'
                    ],
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

    protected function checkEntityIsUserLocation($entity)
    {
        return isset($entity)
            && isset(Yii::$app->user->identity->location)
            && $entity->departure == Yii::$app->user->identity->location;
    }

    protected function checkAircraftIsOnLocation($aircraft, $location){
        return  isset($aircraft) &&
                isset($location) &&
                isset($aircraft->location) &&
                $aircraft->location == $location;
    }

    protected function checkAircraftHaveValidRange($aircraft, $distance_nm){
        return  isset($aircraft) &&
                isset($distance_nm) &&
                isset($aircraft->aircraftConfiguration->aircraftType->max_nm_range) &&
                $aircraft->aircraftConfiguration->aircraftType->max_nm_range >= $distance_nm;
    }

    protected function checkAircraftIsAvailable($aircraft){
        return  isset($aircraft) &&
                !SubmittedFlightPlan::find()->where(['aircraft_id' => $aircraft->id])->exists();
    }

    protected function checkUserCanCreateCharter() {
        $ratio = (float) Config::get('charter_ratio');
        return Yii::$app->user->identity->getCharterRatio() > $ratio;
    }

    protected function getCurrentFpl() {
        return SubmittedFlightPlan::findOne(['pilot_id' => Yii::$app->user->identity->id]);
    }

    public function actionSelectFlight()
    {
        if(Yii::$app->user->can(Permissions::SUBMIT_FPL) && isset(Yii::$app->user->identity->location)){
            $model = $this->getCurrentFpl();
            if($model !== null){
                $this->logInfo('Returning user current fpl (sel route)', ['model' => $model, 'user' => Yii::$app->user->identity->license]);
                return $this->redirect(['view', 'id' => $model->id]);
            } else {
                $location = Yii::$app->user->identity->location;

                $stageSearch = new TourStageSearch();
                $tourStages = $stageSearch->searchWithFixedDeparture($location);

                $routeSearch = new RouteSearch();
                $routeDataProvider = $routeSearch->searchWithFixedDeparture($location, $this->request->queryParams);

                $this->logInfo('User selecting flight', ['location' => Yii::$app->user->identity->location, 'user' => Yii::$app->user->identity->license]);
                return $this->render('select_flight', [
                    'routeDataProvider' => $routeDataProvider,
                    'tourStages' => $tourStages,
                ]);
            }
        } else {
            throw new ForbiddenHttpException();
        }
    }

    public function actionSelectAircraftRoute($route_id)
    {
        $route = Route::findOne(['id' => $route_id]);
        return $this->selectAircraft('route', $route);
    }

    public function actionSelectAircraftTour($tour_stage_id)
    {
        $stage = TourStage::findOne(['id' => $tour_stage_id]);
        return $this->selectAircraft('stage', $stage);
    }

    public function actionSelectAircraftCharter($arrival)
    {
        if($this->checkUserCanCreateCharter()){
            Yii::$app->session->setFlash('error', Yii::t('app', 'Your charter flights ratio is too high. Please complete more regular or tour flights before booking another charter.'));
            return $this->redirect(['select-flight']);
        }

        $charter = new CharterRoute();
        $charter->pilot_id = Yii::$app->user->identity->id;
        $charter->departure = Yii::$app->user->identity->location;
        $charter->arrival = $arrival;

        if ($charter->validate()) {
            return $this->selectAircraft('charter', $charter);
        } else {
            $errors = $charter->getFirstErrors();
            $message = implode('; ', $errors);
            $this->logInfo('Select aircraft charter error', [
                'charter' => $charter,
            ]);
            throw new BadRequestHttpException($message ?: 'Invalid charter destination.');
        }
    }

    protected function selectAircraft(string $type, $entity)
    {
        if (!Yii::$app->user->can(Permissions::SUBMIT_FPL)) {
            throw new ForbiddenHttpException();
        }

        $model = $this->getCurrentFpl();
        if ($model !== null) {
            $this->logInfo('Returning user current fpl (select aircraft)', [
                'model' => $model,
                'user' => Yii::$app->user->identity->license,
            ]);
            return $this->redirect(['view', 'id' => $model->id]);
        }

        if ($type === 'route') {
            $departure = $entity->departure;
            $arrival = $entity->arrival;
            $distance = $entity->distance_nm;
            $label = "{$departure}-{$arrival}";
        } elseif ($type === 'stage') {
            if(!$entity->tour->isActive()){
                throw new ForbiddenHttpException('Tour is not active.');
            }

            $departure = $entity->departure;
            $arrival = $entity->arrival;
            $distance = $entity->distance_nm;
            $label = "Stage: {$departure}-{$arrival}";
        } elseif ($type === 'charter'){
            $departure = $entity->departure;
            $arrival = $entity->arrival;
            $distance = $entity->distance_nm;
            $label = "{$departure}-{$arrival}";
        } else {
            $this->logInfo('Invalid entity type', [
                        'type' => $type,
                        'user' => Yii::$app->user->identity->license,
                    ]);
            throw new BadRequestHttpException();
        }

        $checkLocation = $this->checkEntityIsUserLocation($entity);

        if (!$checkLocation) {
            throw new ForbiddenHttpException('Pilot location is not at '.$departure);
        }

        $searchModel = new AircraftSearch();
        $dataProvider = $searchModel->searchAvailableAircraftsInLocationWithRange($departure, $distance);

        $this->logInfo('User selecting aircraft', [
            'entity' => $type,
            'id' => $entity->id,
            'user' => Yii::$app->user->identity->license,
        ]);

        return $this->render('select_aircraft', [
            'dataProvider' => $dataProvider,
            'entity' => $entity,
            'type' => $type,
        ]);
    }

    public function actionPrepareFplRoute($route_id, $aircraft_id)
    {
        if (!Yii::$app->user->can(Permissions::SUBMIT_FPL)) {
            throw new ForbiddenHttpException();
        }

        $model = $this->getCurrentFpl();
        if ($model !== null) {
            $this->logInfo('Returning user current fpl (prepare route)', [
                'model' => $model,
                'user' => Yii::$app->user->identity->license
            ]);
            return $this->redirect(['view', 'id' => $model->id]);
        }

        $route = Route::findOne($route_id);
        $aircraft = Aircraft::findOne($aircraft_id);

        return $this->prepareFplGeneric($route, $aircraft, 'route');
    }

    public function actionPrepareFplTour($tour_stage_id, $aircraft_id)
    {
        if (!Yii::$app->user->can(Permissions::SUBMIT_FPL)) {
            throw new ForbiddenHttpException();
        }

        $model = $this->getCurrentFpl();
        if ($model !== null) {
            $this->logInfo('Returning user current fpl (prepare stage)', [
                'model' => $model,
                'user' => Yii::$app->user->identity->license
            ]);
            return $this->redirect(['view', 'id' => $model->id]);
        }

        $stage = TourStage::findOne($tour_stage_id);
        $aircraft = Aircraft::findOne($aircraft_id);

        return $this->prepareFplGeneric($stage, $aircraft, 'stage');
    }

    public function actionPrepareFplCharter($arrival, $aircraft_id)
    {
        if (!Yii::$app->user->can(Permissions::SUBMIT_FPL)) {
            throw new ForbiddenHttpException();
        }

        $model = $this->getCurrentFpl();
        if ($model !== null) {
            $this->logInfo('Returning user current fpl (prepare charter)', [
                'model' => $model,
                'user' => Yii::$app->user->identity->license
            ]);
            return $this->redirect(['view', 'id' => $model->id]);
        }

        if($this->checkUserCanCreateCharter()){
            Yii::$app->session->setFlash('error', Yii::t('app', 'Your charter flights ratio is too high. Please complete more regular or tour flights before booking another charter.'));
            return $this->redirect(['select-flight']);
        }

        $charter = new CharterRoute();
        $charter->pilot_id = Yii::$app->user->identity->id;
        $charter->departure = Yii::$app->user->identity->location;
        $charter->arrival = $arrival;

        if ($charter->validate()) {
            $aircraft = Aircraft::findOne($aircraft_id);
            return $this->prepareFplGeneric($charter, $aircraft, 'charter');
        } else {
            $errors = $charter->getFirstErrors();
            $message = implode('; ', $errors);
            $this->logInfo('Select aircraft charter error', [
                        'charter' => $charter,
                    ]);
            throw new BadRequestHttpException($message ?: 'Invalid charter destination.');
        }
    }

    protected function prepareFplGeneric($entity, $aircraft, $entityType)
    {
        if (!$this->checkEntityIsUserLocation($entity)
            || !$this->checkAircraftIsOnLocation($aircraft, $entity->departure)
            || !$this->checkAircraftHaveValidRange($aircraft, $entity->distance_nm)
            || !$this->checkAircraftIsAvailable($aircraft)) {
            throw new ForbiddenHttpException();
        }

        $model = new SubmittedFlightPlan();
        $pilotName = Yii::$app->user->identity->fullName;

        $model->aircraft_id = $aircraft->id;
        $model->pilot_id = Yii::$app->user->identity->id;

        if ($entityType === 'route') {
            $model->route_id = $entity->id;
        } elseif ($entityType === 'stage') {
            if(!$entity->tour->isActive()){
                throw new ForbiddenHttpException('Tour is not active.');
            }
            $model->tour_stage_id = $entity->id;
        }

        if ($this->request->isPost) {

            if ($model->load($this->request->post())) {

                $transaction = Yii::$app->db->beginTransaction();

                $ok = true;

                try {
                    if($entityType === 'charter') {
                        if (!$entity->save()) {
                            $model->addError('charter_route_id', Yii::t('app', 'Problem creating charter route.'));
                            $this->logError('Problem creating charter route', ['entity' => $entity, 'errors' =>$entity->getErrors()]);
                            $ok = false;
                        } else {
                            $this->logInfo('Saved charter route', ['route' => $entity]);
                            $model->charter_route_id = $entity->id;
                        }
                    }

                    if ($ok && !$model->save()) {
                        $ok = false;
                    }
                    if ($ok) {
                        $transaction->commit();
                        $this->logInfo('Created FPL', [
                            'model' => $model,
                            'user' => Yii::$app->user->identity->license
                        ]);

                        return $this->redirect(['view', 'id' => $model->id]);
                    } else {
                        $transaction->rollBack();
                    }
                } catch (\Throwable $e) {
                    $transaction->rollBack();
                    $this->logError('Problem creating fpl', ['entityType' => $entityType, 'entity' => $entity, 'model' => $model, 'e' => $e]);
                    throw $e;
                }
            }
        } else {
            $model->loadDefaultValues();
        }

        return $this->render('prepare_fpl', [
            'model' => $model,
            'aircraft' => $aircraft,
            'entity' => $entity,
            'pilotName' => $pilotName,
        ]);
    }

    /**
     * Lists all SubmittedFlightPlan models.
     *
     * @return string
     */
    public function actionMyFpl()
    {
        $searchModel = new SubmittedFlightPlanSearch();
        $searchModel->pilot_id = Yii::$app->user->identity->id;
        $dataProvider = $searchModel->search([]);

        if ($dataProvider->getTotalCount() === 0) {
            return $this->redirect(['submitted-flight-plan/select-flight']);
        } else {
            $firstId = $dataProvider->getModels()[0]->id;
            return $this->redirect(['submitted-flight-plan/view',  'id' => $firstId ]);
        }
    }

    public function actionIndex()
    {
        if (Yii::$app->user->can(Permissions::VALIDATE_VFR_FLIGHT) || Yii::$app->user->can(Permissions::VALIDATE_IFR_FLIGHT)) {
            $searchModel = new SubmittedFlightPlanSearch();
            $queryParams = $this->request->queryParams;

            if(Yii::$app->user->can(Permissions::VALIDATE_VFR_FLIGHT) && !Yii::$app->user->can(Permissions::VALIDATE_IFR_FLIGHT)){
                $searchModel->flight_rules = 'V';
            } else if(!Yii::$app->user->can(Permissions::VALIDATE_VFR_FLIGHT) && Yii::$app->user->can(Permissions::VALIDATE_IFR_FLIGHT)){
                $searchModel->flight_rules = ['I', 'Y', 'Z'];
            }

            $dataProvider = $searchModel->search($queryParams);

            return $this->render('index', [
                'searchModel' => $searchModel,
                'dataProvider' => $dataProvider,
            ]);
        } else {
            throw new ForbiddenHttpException();
        }
    }

    /**
     * Displays a single SubmittedFlightPlan model.
     * @param string $id ID
     * @return string
     * @throws NotFoundHttpException if the model cannot be found
     */
    public function actionView($id)
    {
        $model = $this->findModel($id);
        if (
            Yii::$app->user->can(Permissions::CRUD_OWN_FPL, ['submittedFlightPlan' => $model]) ||
            Yii::$app->user->can(Permissions::VALIDATE_VFR_FLIGHT) && $model->isVfrFlight() ||
            Yii::$app->user->can(Permissions::VALIDATE_IFR_FLIGHT) && $model->isIfrFlight()
        ) {
            $entity = null;
            if ($model->route_id) {
                $entity = $model->route0;
            } elseif ($model->tour_stage_id) {
                $entity = $model->tourStage;
            } else {
                $entity = $model->charterRoute;
            }
            return $this->render('view', [
                'model' => $model,
                'entity' => $entity
            ]);
        } else {
            throw new ForbiddenHttpException();
        }
    }

    /**
     * Updates an existing SubmittedFlightPlan model.
     * If update is successful, the browser will be redirected to the 'view' page.
     * @param string $id ID
     * @return string|\yii\web\Response
     * @throws NotFoundHttpException if the model cannot be found
     */
    public function actionUpdate($id)
    {
        $model = $this->findModel($id);

        if (Yii::$app->user->can(Permissions::CRUD_OWN_FPL, ['submittedFlightPlan' => $model])) {

            $entity = null;
            if ($model->route_id) {
                $entity = $model->route0;
            } elseif ($model->tour_stage_id) {
                $entity = $model->tourStage;
            } else {
                $entity = $model->charterRoute;
            }

            if ($this->request->isPost && $model->load($this->request->post()) && $model->save()) {
                $this->logInfo('Updated fpl', ['model' => $model, 'user' => Yii::$app->user->identity->license]);
                return $this->redirect(['view', 'id' => $model->id]);
            }

            return $this->render('update', [
                'model' => $model,
                'entity' => $entity
            ]);
        } else {
            throw new ForbiddenHttpException();
        }
    }

    /**
     * Deletes an existing SubmittedFlightPlan model.
     * If deletion is successful, the browser will be redirected to the 'index' page.
     * @param string $id ID
     * @return \yii\web\Response
     * @throws NotFoundHttpException if the model cannot be found
     */
    public function actionDelete($id)
    {
        $model = $this->findModel($id);

        if (Yii::$app->user->can(Permissions::CRUD_OWN_FPL, ['submittedFlightPlan' => $model])) {
            $model = $this->findModel($id);
            $tx = Yii::$app->db->beginTransaction();
            try {
                $model->delete();
                $tx->commit();
                $this->logInfo('Deleted fpl', ['id' => $id, 'user' => Yii::$app->user->identity->license]);
                return $this->redirect(['site/index']);
            } catch (\Throwable $e) {
                $tx->rollBack();
                $this->logError('Error deleting flight plan', ['fpl' => $model, 'e' => $e]);
                throw new ServerErrorHttpException(Yii::t('app', 'Error deleting flight plan. Contact administrator.'));
            }
        } else {
            throw new ForbiddenHttpException();
        }
    }

    /**
     * Finds the SubmittedFlightPlan model based on its primary key value.
     * If the model is not found, a 404 HTTP exception will be thrown.
     * @param string $id ID
     * @return SubmittedFlightPlan the loaded model
     * @throws NotFoundHttpException if the model cannot be found
     */
    protected function findModel($id)
    {
        // TODO: May be refactor that to not disclose to a visitor that one model doesn't exist
        if (($model = SubmittedFlightPlan::findOne(['id' => $id])) !== null) {
            return $model;
        }

        throw new NotFoundHttpException('The requested page does not exist.');
    }
}
