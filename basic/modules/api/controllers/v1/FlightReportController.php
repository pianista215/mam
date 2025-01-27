<?php

namespace app\modules\api\controllers\v1;

use app\config\Config;
use app\models\AcarsFile;
use app\models\Aircraft;
use app\models\AirportSearch;
use app\models\Flight;
use app\models\FlightReport;
use app\models\Pilot;
use app\models\SubmittedFlightPlan;
use app\modules\api\dto\v1\request\SubmitReportDTO;
use app\modules\api\dto\v1\response\FlightPlanDTO;
use app\modules\api\dto\v1\response\ReportSavedDTO;
use yii\filters\auth\HttpBearerAuth;
use yii\rest\Controller;
use yii\web\BadRequestHttpException;
use yii\web\ConflictHttpException;
use yii\web\NotFoundHttpException;
use yii\web\ServerErrorHttpException;
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
    public function actionSubmitReport($flight_plan_id)
    {
        $submittedFlightPlan = SubmittedFlightPlan::findOne(['pilot_id' => Yii::$app->user->identity->id]);
        if(!$submittedFlightPlan){
            throw new NotFoundHttpException("The user hasn't any submitted flight plan.");
        } else if($submittedFlightPlan->id != $flight_plan_id){
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
                $this->moveAircraftToLocation($submittedFlightPlan->aircraft_id, $nearestAirport);

                $submittedFlightPlan->delete();

                $response = new ReportSavedDTO();
                $response->flight_report_id = $report->id;

                $transaction->commit();

                return $response;

            } catch (\Throwable $e) {
                $transaction->rollBack();
                // TODO: Think logs globally, don't let the user know a lot. Better log and answer without much details
                throw new ServerErrorHttpException('An error occurred while processing the report:'. $e->getMessage());
            }
        } else {
            $errorMessages = $dto->getFirstErrors();
            if(empty($errorMessages)) {
                throw new BadRequestHttpException('Invalid data. No data was provided.');
            } else {
                throw new BadRequestHttpException('Invalid data: ' . implode(', ', $errorMessages));
            }
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
        $flight->flight_rules = $submittedFpl->flight_rules;
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
        $report->sim_aircraft_name = $dto->sim_aircraft_name;

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
        $pilot->location = $airport->icao_code;
        if(!$pilot->save()){
            throw new ServerErrorHttpException('Error moving pilot '. $pilot_id. ' to location '. $airport->icao_code);
        }
    }

    private function moveAircraftToLocation($aircraft_id, $airport)
    {
        $aircraft = Aircraft::findOne(['id' => $aircraft_id]);
        $aircraft->location = $airport->icao_code;
        if(!$aircraft->save()){
            throw new ServerErrorHttpException('Error moving aircraft '. $aircraft_id. ' to location '. $airport->icao_code);
        }
    }

    public function actionUploadChunk($flight_report_id, $chunk_id)
    {
        $storagePath = Config::get('chunks_storage_path', '/tmp/chunks_storage_path');

        $transaction = Yii::$app->db->beginTransaction();
        try {

            $flightReport = FlightReport::findOne(['id' => $flight_report_id]);
            if (!$flightReport) {
                throw new NotFoundHttpException("Flight report not found.");
            }

            $flight = Flight::findOne(['id' => $flightReport->flight_id, 'pilot_id' => Yii::$app->user->id]);
            if (!$flight || !$flight->isOpenForUpload()) {
                throw new NotFoundHttpException("Flight access denied or not available for chunk uploads.");
            }

            $chunk = AcarsFile::findOne(['chunk_id' => $chunk_id, 'flight_report_id' => $flight_report_id]);
            if (!$chunk) {
                throw new NotFoundHttpException("Chunk not found for this flight report.");
            }

            if ($chunk->isUploaded()) {
                throw new ConflictHttpException("Chunk $chunk_id already uploaded.");
            }

            $flightReportPath = $storagePath . DIRECTORY_SEPARATOR . $flight_report_id;
            if (!file_exists($flightReportPath)) {
                if (!mkdir($flightReportPath, 0755, true)) {
                    throw new ServerErrorHttpException("Failed to create directory for flight report.");
                }
            }

            $chunkFilePath = $flightReportPath . DIRECTORY_SEPARATOR . $chunk_id;
            if (file_exists($chunkFilePath)) {
                throw new ConflictHttpException("Chunk $chunk_id already exists.");
            }

            $uploadedFile = UploadedFile::getInstanceByName('chunkFile');
            if (!$uploadedFile) {
                throw new BadRequestHttpException("No file uploaded.");
            }

            $tempFilePath = $chunkFilePath . '.tmp';
            if (!$uploadedFile->saveAs($tempFilePath)) {
                throw new ServerErrorHttpException("Failed to save the uploaded chunk.");
            }

            $expectedSha256 = $chunk->sha256sum;
            $actualSha256 = hash_file('sha256', $tempFilePath);
            if ($expectedSha256 !== $actualSha256) {
                unlink($tempFilePath);
                throw new BadRequestHttpException("SHA256 mismatch. Expected: $expectedSha256, Actual: $actualSha256");
            }

            rename($tempFilePath, $chunkFilePath);

            $chunk->upload_date = date('Y-m-d H:i:s');
            if (!$chunk->save()) {
                throw new ServerErrorHttpException('Failed to update chunk upload date: ' . json_encode($chunk->getErrors()));
            }

            $pendingChunks = AcarsFile::find()
                ->where(['flight_report_id' => $flight_report_id, 'upload_date' => null])
                ->exists();

            if (!$pendingChunks) {
                $flightReport->status = 'S';
                if (!$flightReport->save()) {
                    throw new ServerErrorHttpException('Failed to update flight report status: ' . json_encode($flightReport->getErrors()));
                }
            }

            $transaction->commit();
            return ['status' => 'success'];

        } catch (\Throwable $e) {
            if (isset($tempFilePath) && file_exists($tempFilePath)) {
                unlink($tempFilePath);
            }
            if (isset($chunkFilePath) && file_exists($chunkFilePath)) {
                unlink($chunkFilePath);
            }

            $transaction->rollBack();
            throw $e;
        }
    }
}
