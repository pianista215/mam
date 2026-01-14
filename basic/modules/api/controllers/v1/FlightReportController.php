<?php

namespace app\modules\api\controllers\v1;

use app\config\ConfigHelper as CK;
use app\helpers\GeoUtils;
use app\helpers\LoggerTrait;
use app\models\AcarsFile;
use app\models\Aircraft;
use app\models\Airport;
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
use yii\web\UploadedFile;
use Yii;

/**
 * FlightReport controller in charge of receive FlightReports and its Acars files
 */
class FlightReportController extends Controller
{
    use LoggerTrait;

    public function behaviors()
    {
        $behaviors = parent::behaviors();

        $behaviors['authenticator'] = [
            'class' => HttpBearerAuth::class,
        ];

        return $behaviors;
    }

    // TODO: IN general we need to log all the methods in that an other controllers

    /**
     * Close the flight plan, create the flight, the report and prepare the acars files to be uploaded
     */
    public function actionSubmitReport($flight_plan_id)
    {
        $this->logInfo('Submit report',['fp_id' => $flight_plan_id, 'body' => Yii::$app->request->bodyParams]);
        $dto = new SubmitReportDTO();
        if ($dto->load(Yii::$app->request->post(), '') && $dto->validate()) {
            $lastReportedFlight = Flight::find()
                ->where([
                    'pilot_id' => Yii::$app->user->identity->id,
                    'status' => 'C',
                ])
                ->orderBy(['creation_date' => SORT_DESC])
                ->limit(1)
                ->one();

            $this->logInfo('Checking lastReported with newReport', ['new' => $flight_plan_id, 'last' => $lastReportedFlight]);

            if ($lastReportedFlight && $lastReportedFlight->flightReport) {
                $this->logInfo('Comparing chunks to see if its same flight', ['new_chunks' => $dto->chunks, 'lastReport_chunks' => $lastReportedFlight->flightReport->acarsFiles]);
                $existingReport = $lastReportedFlight->flightReport;
                $existingChunks = array_map(function ($chunk) {
                    return $chunk->sha256sum;
                }, $existingReport->acarsFiles);

                $submittedChunks = array_map(function ($chunk) {
                    return $chunk['sha256sum'];
                }, $dto->chunks);

                if (array_diff($submittedChunks, $existingChunks) === array_diff($existingChunks, $submittedChunks)) {
                    $this->logInfo('Chunks match, return existing report_id', $existingReport->id);
                    return new ReportSavedDTO(['flight_report_id' => $existingReport->id]);
                }
            }

            $submittedFlightPlan = SubmittedFlightPlan::findOne(['pilot_id' => Yii::$app->user->identity->id]);
            if(!$submittedFlightPlan){
                $this->logError('User without submitted flight plan', Yii::$app->user->identity->license);
                throw new NotFoundHttpException("The user hasn't any submitted flight plan.");
            } else if($submittedFlightPlan->id != $flight_plan_id){
                $this->logError('User flight plan and sent mismatch', ['submitted' => $submittedFlightPlan->id, 'db' => $flight_plan_id, 'user' => Yii::$app->user->identity->license]);
                throw new NotFoundHttpException("User flight plan and sent flight plan doesn't match.");
            }

            $transaction = Yii::$app->db->beginTransaction();
            try {

                $flight = $this->fillFlightData($submittedFlightPlan, $dto);
                if(!$flight->save()){
                    $this->logError('Failed to save flight', $flight->getErrors());
                    throw new ServerErrorHttpException('Failed to save flight:'. json_encode($flight->getErrors()));
                }

                $nearestAirport = AirportSearch::findNearestAirport($dto->last_position_lat, $dto->last_position_lon);

                if(!$nearestAirport){
                    $this->logError('Error finding nearest airport', $dto);
                    throw new ServerErrorHttpException('Error finding nearest airport of '.$dto->last_position_lat.' '.$dto->last_position_lon);
                }

                $distanceKm = GeoUtils::haversine($dto->last_position_lat, $dto->last_position_lon, $nearestAirport->latitude, $nearestAirport->longitude, 'km');

                $landingAirport = null;

                if ($distanceKm <= 8) {
                    $landingAirport = $nearestAirport;
                } else {
                    $this->logInfo($dto->last_position_lat.' '.$dto->last_position_lon.' more than 8 km to the nearest airport', $nearestAirport);
                }

                $this->logInfo('Moving pilot and aircraft to', $nearestAirport);
                $this->movePilotToLocation($submittedFlightPlan->pilot_id, $nearestAirport);
                $this->moveAircraftToLocation($submittedFlightPlan->aircraft_id, $nearestAirport);

                $report = $this->fillReport($flight, $dto, $landingAirport);
                if(!$report->save()){
                    $this->logError('Failed to save report', $report->getErrors());
                    throw new ServerErrorHttpException('Failed to save report:'. json_encode($report->getErrors()));
                }

                $chunks = $this->fillChunks($report, $dto);
                foreach ($chunks as $chunk) {
                    if(!$chunk->save()){
                        $this->logError('Failed to save chunk', $chunk->getErrors());
                        throw new ServerErrorHttpException('Failed to save chunk:'. json_encode($chunk->getErrors()));
                    }
                }

                $submittedFlightPlan->delete();

                $response = new ReportSavedDTO();
                $response->flight_report_id = $report->id;

                $transaction->commit();

                return $response;

            } catch (\Throwable $e) {
                $transaction->rollBack();
                $this->logError('Error while processing report', ['ex' => $e, 'request' => Yii::$app->request]);
                throw new ServerErrorHttpException('An error occurred while processing the report:'. $e->getMessage());
            }
        } else {
            $errorMessages = $dto->getFirstErrors();
            if(empty($errorMessages)) {
                $this->logError('Invalid data. No data provided', Yii::$app->request);
                throw new BadRequestHttpException('Invalid data. No data was provided.');
            } else {
                $this->logError('Invalid data.', ['messages' => $errorMessages, 'request' => Yii::$app->request]);
                throw new BadRequestHttpException('Invalid data: ' . implode(', ', $errorMessages));
            }
        }
    }

    private function generateTourStageCode($stage)
    {
        $name = $stage->tour->name;
        $words = preg_split('/\s+/', trim($name));

        $initials = '';
        foreach ($words as $word) {
            $initials .= mb_strtoupper(mb_substr($word, 0, 1));
        }
        // Take 8 chars (code limit is 10)
        $initials = mb_substr($initials, 0, 8);
        return $initials . $stage->sequence;
    }

    private function fillFlightData(SubmittedFlightPlan $submittedFpl, SubmitReportDTO $dto)
    {
        $flight = new Flight();
        $flight->pilot_id = $submittedFpl->pilot_id;
        $flight->aircraft_id = $submittedFpl->aircraft_id;
        if(!empty($submittedFpl->tour_stage_id)){
            $flight->tour_stage_id = $submittedFpl->tour_stage_id;
            $flight->code = $this->generateTourStageCode($submittedFpl->tourStage);
            $flight->departure = $submittedFpl->tourStage->departure;
            $flight->arrival = $submittedFpl->tourStage->arrival;
            $flight->flight_type = Flight::TYPE_TOUR;
        } else if(!empty($submittedFpl->route_id)){
            $flight->code = $submittedFpl->route0->code;
            $flight->departure = $submittedFpl->route0->departure;
            $flight->arrival = $submittedFpl->route0->arrival;
            $flight->flight_type = Flight::TYPE_ROUTE;
        } else {
            $flight->departure = $submittedFpl->charterRoute->departure;
            $flight->arrival = $submittedFpl->charterRoute->arrival;
            $flight->code = 'CHARTER';
            $flight->flight_type = Flight::TYPE_CHARTER;
        }

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

    private function fillReport(Flight $flight, SubmitReportDTO $dto, ?Airport $landingAirport)
    {
        $report = new FlightReport();
        $report->flight_id = $flight->id;
        $report->start_time = $dto->start_time;
        $report->end_time = $dto->end_time;
        $report->pilot_comments = $dto->pilot_comments;
        $report->sim_aircraft_name = $dto->sim_aircraft_name;

        if($landingAirport !== null){
            $report->landing_airport = $landingAirport->icao_code;
        }

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
            $this->logError('Error moving pilot', ['pilot' => $pilot_id, 'airport' => $airport]);
            throw new ServerErrorHttpException('Error moving pilot '. $pilot_id. ' to location '. $airport->icao_code);
        }
    }

    private function moveAircraftToLocation($aircraft_id, $airport)
    {
        $aircraft = Aircraft::findOne(['id' => $aircraft_id]);
        $aircraft->location = $airport->icao_code;
        if(!$aircraft->save()){
            $this->logError('Error moving aircraft', ['aircraft' => $aircraft, 'airport' => $airport]);
            throw new ServerErrorHttpException('Error moving aircraft '. $aircraft_id. ' to location '. $airport->icao_code);
        }
    }

    public function actionUploadChunk($flight_report_id, $chunk_id)
    {
        $storagePath = CK::getChunksStoragePath();

        $flightReport = FlightReport::findOne(['id' => $flight_report_id]);
        if (!$flightReport) {
            $this->logError('Flight report not found', $flight_report_id);
            throw new NotFoundHttpException("Flight report not found.");
        }

        $flight = Flight::findOne(['id' => $flightReport->flight_id, 'pilot_id' => Yii::$app->user->id]);
        if (!$flight || $flight->isProcessed()) {
            $this->logError('Flight access denied or not available for chunk uploads', ['id' => $flight_report_id, 'flight' => $flight]);
            throw new NotFoundHttpException("Flight access denied or not available for chunk uploads.");
        }

        // Check if chunk is already uploaded
        $chunk = AcarsFile::findOne(['chunk_id' => $chunk_id, 'flight_report_id' => $flight_report_id]);
        if (!$chunk) {
            $this->logError('Chunk not found', ['chunk_id' => $chunk_id, 'flight_report_id' => $flight_report_id]);
            throw new NotFoundHttpException("Chunk not found for this flight report.");
        }

        if ($chunk->isUploaded()) {
            $this->logWarn('Chunk already uploaded', $chunk);
            return ['status' => 'success'];
        }

        $transaction = Yii::$app->db->beginTransaction();
        try {

            $flightReportPath = $storagePath . DIRECTORY_SEPARATOR . $flight_report_id;
            $this->logInfo('Report path', $flightReportPath);
            if (!file_exists($flightReportPath)) {
                if (!mkdir($flightReportPath, 0755, true)) {
                    $this->logError('Failed to create directory', $flightReportPath);
                    throw new ServerErrorHttpException('Failed to create directory for flight report.');
                }
            }

            $chunkFilePath = $flightReportPath . DIRECTORY_SEPARATOR . $chunk_id;
            $this->logInfo('Chunk path', $chunkFilePath);
            if (file_exists($chunkFilePath)) {
                $this->logError('File already exists', ['file' => $chunkFilePath, 'id' => $chunk_id]);
                throw new ConflictHttpException("Chunk $chunk_id already exists.");
            }

            $uploadedFile = UploadedFile::getInstanceByName('chunkFile');
            if (!$uploadedFile) {
                $this->logError('No file uploaded');
                throw new BadRequestHttpException('No file uploaded.');
            }

            # TODO: Ensure file size is limited (for example 1MB per chunk_id and a max of chunks per report)

            $tempFilePath = $chunkFilePath . '.tmp';
            $this->logInfo('Tmp file path', $tempFilePath);
            // Problem to be fixed by yii2 and codeception (https://github.com/yiisoft/yii2/issues/14260)
            if (!$uploadedFile->saveAs($tempFilePath, !YII_ENV_TEST)) {
                $this->logError('Failed to save the uploaded chunk', $uploadedFile);
                throw new ServerErrorHttpException('Failed to save the uploaded chunk.'.json_encode($uploadedFile));
            }

            $expectedSha256 = $chunk->sha256sum;
            $this->logInfo('Expected sha256', $expectedSha256);
            $actualSha256 = base64_encode(hash_file('sha256', $tempFilePath, true));
            $this->logInfo('Actual sha256', $actualSha256);
            if ($expectedSha256 !== $actualSha256) {
                $this->logError('SHA256 mismatch', ['expected' => $expectedSha256, 'actual' => $actualSha256]);
                unlink($tempFilePath);
                throw new BadRequestHttpException('SHA256 mismatch. Expected: '.$expectedSha256. ' Actual: '.$actualSha256);
            }

            rename($tempFilePath, $chunkFilePath);

            $chunk->upload_date = date('Y-m-d H:i:s');
            if (!$chunk->save()) {
                $this->logError('Failed to update chunk upload date', $chunk);
                throw new ServerErrorHttpException('Failed to update chunk upload date: ' . json_encode($chunk->getErrors()));
            }

            $pendingChunks = AcarsFile::find()
                ->where(['flight_report_id' => $flight_report_id, 'upload_date' => null])
                ->exists();

            if (!$pendingChunks) {
                $flight->status = 'S';
                if (!$flight->save()) {
                    $this->logError('Failed to update flight status', $flight);
                    throw new ServerErrorHttpException('Failed to update flight status: ' . json_encode($flightReport->getErrors()));
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
