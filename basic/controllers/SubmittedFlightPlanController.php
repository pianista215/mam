<?php

namespace app\controllers;

use app\helpers\LoggerTrait;
use app\models\Aircraft;
use app\models\AircraftSearch;
use app\models\Route;
use app\models\RouteSearch;
use app\models\SubmittedFlightPlan;
use app\models\SubmittedFlightPlanSearch;
use app\models\TourStage;
use yii\web\Controller;
use yii\web\BadRequestHttpException;
use yii\web\ForbiddenHttpException;
use yii\web\NotFoundHttpException;
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
                    'only' => ['select-route', 'select-aircraft', 'prepare-fpl', 'my-fpl', 'index', 'view', 'update', 'delete'],
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

    protected function getCurrentFpl() {
        return SubmittedFlightPlan::findOne(['pilot_id' => Yii::$app->user->identity->id]);
    }

    public function actionSelectRoute()
    {
        if(Yii::$app->user->can('submitFpl') && isset(Yii::$app->user->identity->location)){
            $model = $this->getCurrentFpl();
            if($model !== null){
                $this->logInfo('Returning user current fpl (sel route)', ['model' => $model, 'user' => Yii::$app->user->identity->license]);
                return $this->redirect(['view', 'id' => $model->id]);
            } else {
                $searchModel = new RouteSearch();
                $dataProvider = $searchModel->searchWithFixedDeparture(Yii::$app->user->identity->location);
                $this->logInfo('User selecting route', ['location' => Yii::$app->user->identity->location, 'user' => Yii::$app->user->identity->license]);
                return $this->render('select_route', [
                    'dataProvider' => $dataProvider,
                ]);
            }
        } else {
            throw new ForbiddenHttpException();
        }
    }

    public function actionSelectAircraft($route_id)
    {
        $route = Route::findOne(['id' => $route_id]);
        return $this->selectAircraft('route', $route);
    }

    public function actionSelectAircraftTour($tour_stage_id)
    {
        $stage = TourStage::findOne(['id' => $tour_stage_id]);
        return $this->selectAircraft('stage', $stage);
    }

    protected function selectAircraft(string $type, $entity)
    {
        if (!Yii::$app->user->can('submitFpl')) {
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
            $departure = $entity->departure;
            $arrival = $entity->arrival;
            $distance = $entity->distance_nm;
            $label = "Stage: {$departure}-{$arrival}";
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

    public function actionPrepareFpl($route_id, $aircraft_id)
    {
        if(Yii::$app->user->can('submitFpl')){
            $model = $this->getCurrentFpl();

            if($model !== null){
                $this->logInfo('Returning user current fpl (prepare)', ['model' => $model, 'user' => Yii::$app->user->identity->license]);
                return $this->redirect(['view', 'id' => $model->id]);
            } else {
                $route = Route::findOne(['id' => $route_id]);
                $aircraft = Aircraft::findOne(['id' => $aircraft_id]);
                if(
                    $this->checkRouteIsUserLocation($route) &&
                    $this->checkAircraftIsOnLocation($aircraft, $route->departure) &&
                    $this->checkAircraftHaveValidRange($aircraft, $route->distance_nm) &&
                    $this->checkAircraftIsAvailable($aircraft)
                ){
                    $model = new SubmittedFlightPlan();
                    $pilotName = Yii::$app->user->identity->fullName;

                    $model->aircraft_id = $aircraft->id;
                    $model->pilot_id = Yii::$app->user->identity->id;
                    $model->route_id = $route->id;

                     if ($this->request->isPost) {
                        if ($model->load($this->request->post()) && $model->save()) {
                            $this->logInfo('Created fpl', ['model' => $model, 'user' => Yii::$app->user->identity->license]);
                            return $this->redirect(['view', 'id' => $model->id]);
                        }
                     } else {
                        $model->loadDefaultValues();
                     }

                    return $this->render('prepare_fpl', [
                        'route' => $route,
                        'aircraft' => $aircraft,
                        'pilotName' => $pilotName,
                        'model' => $model,
                    ]);
                } else {
                    throw new ForbiddenHttpException();
                }
            }
        } else {
            throw new ForbiddenHttpException();
        }
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
            return $this->redirect(['submitted-flight-plan/select-route']);
        } else {
            $firstId = $dataProvider->getModels()[0]->id;
            return $this->redirect(['submitted-flight-plan/view',  'id' => $firstId ]);
        }
    }

    public function actionIndex()
    {
        if (Yii::$app->user->can('validateVfrFlight') || Yii::$app->user->can('validateIfrFlight')) {
            $searchModel = new SubmittedFlightPlanSearch();
            $queryParams = $this->request->queryParams;

            if(Yii::$app->user->can('validateVfrFlight') && !Yii::$app->user->can('validateIfrFlight')){
                $searchModel->flight_rules = 'V';
            } else if(!Yii::$app->user->can('validateVfrFlight') && Yii::$app->user->can('validateIfrFlight')){
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
            Yii::$app->user->can('crudOwnFpl', ['submittedFlightPlan' => $model]) ||
            Yii::$app->user->can('validateVfrFlight') && $model->isVfrFlight() ||
            Yii::$app->user->can('validateIfrFlight') && $model->isIfrFlight()
            ) {
            return $this->render('view', [
                'model' => $model,
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

        if (Yii::$app->user->can('crudOwnFpl', ['submittedFlightPlan' => $model])) {

            if ($this->request->isPost && $model->load($this->request->post()) && $model->save()) {
                $this->logInfo('Updated fpl', ['model' => $model, 'user' => Yii::$app->user->identity->license]);
                return $this->redirect(['view', 'id' => $model->id]);
            }

            return $this->render('update', [
                'model' => $model,
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

        if (Yii::$app->user->can('crudOwnFpl', ['submittedFlightPlan' => $model])) {
            $this->findModel($id)->delete();
            $this->logInfo('Deleted fpl', ['id' => $id, 'user' => Yii::$app->user->identity->license]);
            return $this->redirect(['site/index']);
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
