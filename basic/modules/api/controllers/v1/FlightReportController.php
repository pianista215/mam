<?php

namespace app\modules\api\controllers\v1;

use app\models\AirportSearch;
use app\models\SubmittedFlightPlan;
use app\modules\api\dto\v1\response\FlightPlanDTO;
use app\modules\api\dto\v1\response\ReportSavedDTO;
use yii\filters\auth\HttpBearerAuth;
use yii\rest\Controller;
use yii\web\BadRequestHttpException;
use yii\web\NotFoundHttpException;
use yii\web\Response;
use Yii;

/**
 * FlightReport controller in charge of receive FlightReports and its Acars files
 */
class FlightReportController extends Controller
{
    public function behaviors()
    {
        $behaviors = parent::behaviors();

        $behaviors['authenticator'] = [
            'class' => HttpBearerAuth::class,
        ];

        return $behaviors;
    }

    /**
     * Close the flight plan, create the flight, the report and prepare the acars files to be uploaded
     */
    public function actionSubmitReport($flightPlanId)
    {
        $submittedFlightPlan = SubmittedFlightPlan::findOne(['pilot_id' => Yii::$app->user->identity->id]);
        if(!$submittedFlightPlan){
            throw new NotFoundHttpException("The user hasn't any submitted flight plan.");
        } else if($submittedFlightPlan->id != $flightPlanId){
            throw new NotFoundHttpException("User flight plan and sent flight plan doesn't match.");
        }

        $dto = new SubmitReportDTO();
        if ($dto->load(Yii::$app->request->post(), '') && $dto->validate()) {
            $transaction = Yii::$app->db->beginTransaction();
            try {

                $flight = $this->fillFlightData($submittedFlightPlan, $dto);
                if(!$flight->save()){
                    throw new ServerErrorHttpException('Failed to save flight:'. json_encode($flight->getErrors()));
                }

                $report = $this->fillReport($flight, $dto);
                if(!$report->save()){
                    throw new ServerErrorHttpException('Failed to save report:'. json_encode($report->getErrors()));
                }

                $chunks = $this->fillChunks($report, $dto);
                foreach ($chunks as $chunk) {
                    if(!$chunk->save()){
                        throw new ServerErrorHttpException('Failed to save chunk:'. json_encode($chunk->getErrors()));
                    }
                }

                $nearestAirport = AirportSearch::findNearestAirport($dto->last_position_lat, $dto->last_position_lon);

                if(!$nearestAirport){
                    throw new ServerErrorHttpException('Error finding nearest airport of'.$dto->last_position_lat.' '.$dto->last_position_lon);
                }

                $this->movePilotToLocation($submittedFlightPlan->pilot_id, $nearestAirport);
                $this->moveAircraftToLocation($submittedFlightPlan->pilot_id, $nearestAirport);

                $submittedFlightPlan->delete();

                $response = new ReportSavedDTO();
                $response->flight_report_id = $report->id;

                $transaction->commit();

                return $response;

            } catch (\Throwable $e) {
                $transaction->rollBack();
                // TODO: Think logs globally, don't let the user know a lot. Better log and answer without much details
                throw new ServerErrorHttpException('An error occurred while processing the report:'. $e->message);
            }
        } else {
            $errorMessages = $this->getFirstErrors();
            throw new BadRequestHttpException("Invalid data: " . implode(', ', $errorMessages));
        }
    }

    private function fillFlightData(SubmittedFlightPlan $submittedFpl, SubmitReportDTO $dto)
    {
        $flight = new Flight();
        $flight->pilot_id = $submittedFpl->pilot_id;
        $flight->aircraft_id = $submittedFpl->aircraft_id;
        $flight->code = $submittedFpl->route0->code;
        $flight->departure = $submittedFpl->route0->departure;
        $flight->arrival = $submittedFpl->route0->arrival;
        $flight->alternative1_icao = $submittedFpl->alternative1_icao;
        $flight->alternative2_icao = $submittedFpl->alternative2_icao;
        $flight->cruise_speed_value = $submittedFpl->cruise_speed_value;
        $flight->cruise_speed_unit = $submittedFpl->cruise_speed_unit;
        $flight->flight_level_value = $submittedFpl->flight_level_value;
        $flight->flight_level_unit = $submittedFpl->flight_level_unit;
        $flight->route = $submittedFpl->route;
        $flight->estimated_time = $submittedFpl->estimated_time;
        $flight->other_information = $submittedFpl->other_information;
        $flight->endurance_time = $submittedFpl->endurance_time;

        $flight->report_tool = $dto->report_tool;
        $flight->network = $dto->network;

        return $flight;
    }

    private function fillReport(Flight $flight, SubmitReportDTO $dto)
    {
        $report = new FlightReport();
        $report->flight_id = $flight->id;
        $report->start_time = $dto->start_time;
        $report->end_time = $dto->end_time;
        $report->pilot_comments = $dto->pilot_comments;

        return $report;
    }

    private function fillChunks(FlightReport $report, SubmitReportDTO $dto)
    {
        $chunks = [];

        foreach ($dto->chunks as $ch){
            $acarsFile = new AcarsFile();
            $acarsFile->flight_report_id = $report->id;
            $acarsFile->chunk_id = $ch->id;
            $acarsFile->sha256sum = $ch->sha256sum;

            $chunks[] = $acarsFile;
        }

        return $chunks;
    }

    private function movePilotToLocation($pilot_id, $airport)
    {
        $pilot = Pilot::findOne(['id' => $pilot_id]);
        $pilot->location = $airport->id;
        if(!$pilot->save()){
            throw new ServerErrorHttpException('Error moving pilot'. $pilot_id. ' to location '. $airport->id);
        }
    }

    private function moveAircraftToLocation($aircraft_id, $airport)
    {
        $aircraft = Aircraft::findOne(['id' => $aircraft_id]);
        $aircraft->location = $airport->id;
        if(!$aircraft->save()){
            throw new ServerErrorHttpException('Error moving aircraft'. $aircraft_id. ' to location '. $airport->id);
        }
    }

    public function actionCurrentFpl()
    {
        $submittedFlightPlan = SubmittedFlightPlan::findOne(['pilot_id' => Yii::$app->user->identity->id]);
        if(!$submittedFlightPlan){
            throw new NotFoundHttpException("Flight plan not found.");
        }

        $dto = FlightPlanDTO::fromModel($submittedFlightPlan);

        return $dto;
    }
}
