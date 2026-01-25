<?php
namespace app\commands;

use app\config\ConfigHelper as CK;
use app\models\FlightEvent;
use app\models\FlightEventAttribute;
use app\models\FlightEventData;
use app\models\FlightPhase;
use app\models\FlightPhaseIssue;
use app\models\FlightPhaseMetric;
use app\models\FlightPhaseMetricType;
use app\models\FlightPhaseType;
use app\models\IssueType;
use yii\console\Controller;
use yii\console\ExitCode;
use Yii;

class FlightReportController extends Controller
{
    # TODO: PENDING TO IMPLEMENT TESTING

    protected function joinAcarsFiles($report): ?string
    {
        $storagePath = CK::getChunksStoragePath();
        $reportPath  = $storagePath . DIRECTORY_SEPARATOR . $report->id;
        $outputFile  = $reportPath . DIRECTORY_SEPARATOR . 'concat.gz';

        $chunks = $report->acarsFiles;

        // Open output file (overwrite if exists)
        $out = fopen($outputFile, 'wb');
        if (!$out) {
            throw new \RuntimeException("❌ Cannot open output file: $outputFile");
        }

        foreach ($chunks as $chunk) {
            $chunkPath = $reportPath . DIRECTORY_SEPARATOR . $chunk->chunk_id;
            if (!is_readable($chunkPath)) {
                fclose($out);
                throw new \RuntimeException("❌ Missing or unreadable chunk: $chunkPath");
            }

            // Append chunk contents
            $in = fopen($chunkPath, 'rb');
            stream_copy_to_stream($in, $out);
            fclose($in);
        }

        fclose($out);

        return $outputFile;
    }

    protected function isValidGzip($gzipFile)
    {
        $file = fopen($gzipFile, 'rb');

        // Read first two bytes
        $bytes = fread($file, 2);
        fclose($file);

        return $bytes === "\x1f\x8b";
    }

    protected function decompress($gzipFile, $report)
    {
        $storagePath = CK::getChunksStoragePath();
        $reportPath  = $storagePath . DIRECTORY_SEPARATOR . $report->id;
        $outputFile  = $reportPath . DIRECTORY_SEPARATOR . 'report.json';

        $gz = gzopen($gzipFile, 'rb');
        if ($gz === false) {
            throw new \RuntimeException("Cannot open gzip file: $gzipFile");
        }

        $out = fopen($outputFile, 'wb');
        if ($out === false) {
            gzclose($gz);
            throw new \RuntimeException("Cannot create output file: $outputFile");
        }

        // Use blocks of 4KB
        while (!gzeof($gz)) {
            $data = gzread($gz, 4096);
            if ($data === false) {
                fclose($out);
                gzclose($gz);
                throw new \RuntimeException("Error reading gzip file: $gzipFile");
            }
            fwrite($out, $data);
        }

        gzclose($gz);
        fclose($out);

        return $outputFile;
    }

    /**
     * Assemble pending reports acars file to be analyze by mam-analyzer
     * @return int Exit code
     */
    public function actionAssemblePendingAcars()
    {
        $flightsPendingToAnalyze = \app\models\Flight::find()
        ->where(['status' => 'S'])
        ->all();

        foreach ($flightsPendingToAnalyze as $flight) {
            $report = $flight->flightReport;
            $gzipFile = $this->joinAcarsFiles($report);
            if(!$this->isValidGzip($gzipFile)){
                 $this->stderr("File is not valid gzip: $gzipFile\n");
                 return ExitCode::NOINPUT;
            }
            $finalPath = $this->decompress($gzipFile, $report);
            # Remove gzip file
            unlink($gzipFile);
            $this->stdout("{$finalPath}\n");
        }

        return ExitCode::OK;
    }

    protected function strDataValue($value)
    {
        $result = "";
        if(is_array($value)){
            $result = implode(" | ", $value);
        } else {
            $result = strVal($value);
        }
        return $result;
    }

    protected function importPhaseAnalysis($phase, $phaseType, $phaseAnalysis)
    {
        $phaseAnalysisMetrics = $phaseAnalysis['phase_metrics'];
        if(!empty($phaseAnalysisMetrics)){
            $this->importPhaseMetrics($phase, $phaseType, $phaseAnalysisMetrics);
        }

        $phaseAnalysisIssues = $phaseAnalysis['issues'];
        $this->importPhaseIssues($phase, $phaseType, $phaseAnalysisIssues);
    }

    protected function checkLandingInAirport($phase)
    {
        $report = $phase->flightReport;
        $flight = $report->flight;

        $landing_icao = $report->landing_airport;
        $arrival = $flight->arrival;
        $alt1 = $flight->alternative1_icao;
        $alt2 = $flight->alternative2_icao;

        $issue_code = null;
        $value = null;

        if($landing_icao == null){
            // Add landing issue in middle of no where
            $issue_code = 'LandingOutOfAirport';
        } else {
            if($landing_icao != $arrival){
                // Add warning that one of the alternatives were used or if a different one was used
                $value = $landing_icao;
                if($landing_icao == $alt1){
                    $issue_code = 'LandingAirportAlternative';
                } else if($alt2 !== null && $landing_icao == $alt2){
                    $issue_code = 'LandingAirportAlternative';
                } else {
                    $issue_code = 'LandingAirportNotPlanned';
                }
            }
        }

        if($issue_code !== null){
            $issueType = IssueType::findOne(['code' => $issue_code]);
            $phaseIssue = new FlightPhaseIssue([
                'phase_id' => $phase->id,
                'issue_type_id' => $issueType->id,
                'timestamp' => $phase->start,
                'value' => $value
            ]);

            if (!$phaseIssue->save()) {
                throw new \Exception("Error saving PhaseIssue airport alternative: " . json_encode($phaseIssue->errors));
            }
        }
    }

    protected function importPhaseIssues($phase, $phaseType, $issues)
    {
        if($phaseType->code == 'final_landing'){
            $this->checkLandingInAirport($phase);
        }
        foreach($issues as $issue){
            $code = $issue['code'];
            $timestamp = $issue['timestamp'] ?? null;
            $value = $issue['value'] ?? null;

            if($timestamp === null){
                throw new \RuntimeException("Issue timestamp can't be null, for issue with code " . $code);
            }

            $issueType = IssueType::findOne(['code' => $code]);

            if(!$issueType){
                throw new \RuntimeException("Not found issue type with code: ".$code);
            }

            if($value !== null){
                $value = $this->strDataValue($value);
            }
            $phaseIssue = new FlightPhaseIssue([
                'phase_id' => $phase->id,
                'issue_type_id' => $issueType->id,
                'timestamp' => $timestamp,
                'value' => $value
            ]);


            if (!$phaseIssue->save()) {
                throw new \Exception("Error saving PhaseIssue: " . json_encode($phaseIssue->errors));
            }
        }
    }

    protected function importPhaseMetrics($phase, $phaseType, $metrics)
    {
        foreach($metrics as $key => $value) {

            if ($value !== null && $value !== '' && $value !== []) {
                $finalValue = $this->strDataValue($value);
                $metricType = FlightPhaseMetricType::findOne(
                    ['flight_phase_type_id' => $phaseType->id, 'code' => $key]
                );

                if(!$metricType){
                    throw new \RuntimeException("Not found metric type with code: $key for phase type: " . $phaseType->id);
                }

                $phaseMetric = new FlightPhaseMetric([
                    'flight_phase_id' => $phase->id,
                    'metric_type_id' => $metricType->id,
                    'value' => $finalValue,
                ]);
                if (!$phaseMetric->save()) {
                    throw new \Exception("Error saving PhaseMetric: " . json_encode($phaseMetric->errors));
                }
            } else {
                $this->stdout("Metric: {$key} is empty, omitting\n");
            }
        }
    }

    protected function importEventChanges($event, $changes)
    {
        foreach($changes as $key => $value){
            if ($value !== null && $value !== '' && $value !== []) {
                $finalValue = $this->strDataValue($value);

                if ($key === 'Latitude' || $key === 'Longitude') {
                    $finalValue = str_replace(',', '.', $finalValue);
                }

                $attrType = FlightEventAttribute::findOne(['code' => $key]);

                if(!$attrType){
                    throw new \RuntimeException("Not found event attribute with code: $key");
                }

                $eventData = new FlightEventData([
                    'event_id' => $event->id,
                    'attribute_id' => $attrType->id,
                    'value' => $finalValue,
                ]);
                if (!$eventData->save()) {
                    throw new \Exception("Error saving FlightEventData: " . json_encode($eventData->errors));
                }
            } else {
                $this->stdout("Ev data:: {$key} is empty, omitting\n");
            }
        }
    }

    protected function importPhaseEvents($phase, $events)
    {
        foreach($events as $eventJson) {

            $timestamp = $eventJson['Timestamp'];
            $changes = $eventJson['Changes'];

            $event = new FlightEvent([
                'phase_id' => $phase->id,
                'timestamp' => $timestamp,
            ]);

            if (!$event->save()) {
                throw new \Exception("Error saving event: " . json_encode($event->errors));
            }

            $this->importEventChanges($event, $changes);
        }
    }



    protected function importPhase($report, $phaseJson)
    {
        if (empty($phaseJson['name'])) {
            throw new \RuntimeException("Phase with empty name: $phaseJson");
        }
        $phaseName = $phaseJson['name'];

        $phaseType = FlightPhaseType::findOne(['code' => $phaseName]);
        if(!$phaseType){
            throw new \RuntimeException("Not found phase type with code: $phaseName");
        }

        $phase = new FlightPhase([
            'flight_report_id' => $report->id,
            'flight_phase_type_id' => $phaseType->id,
            'start' => $phaseJson['start'],
            'end' => $phaseJson['end']
        ]);
        if (!$phase->save()) {
            throw new \Exception("Error saving FlightPhase: " . json_encode($phase->errors));
        }

        $phaseAnalysis = $phaseJson['analysis'];
        if(!empty($phaseAnalysis)) {
            $this->importPhaseAnalysis($phase, $phaseType, $phaseAnalysis);
        }

        $phaseEvents = $phaseJson['events'];
        $this->importPhaseEvents($phase, $phaseEvents);
    }

    /**
     * Import events and analysis generated by mam-analyzer for all the pending reports flight report.
     */
    public function actionImportPendingReportsAnalysis()
    {
        $flightsPendingToAnalyze = \app\models\Flight::find()
            ->where(['status' => 'S']);

        $storagePath = CK::getChunksStoragePath();

        foreach ($flightsPendingToAnalyze->each(1) as $flight) {
            $report = $flight->flightReport;
            $reportPath  = $storagePath . DIRECTORY_SEPARATOR . $report->id;
            $analysis  = $reportPath . DIRECTORY_SEPARATOR . 'analysis.json';

            $this->stdout("Importing analysis $analysis for flight:". $flight->id ."\n");

            if (!file_exists($analysis)) {
                $this->stderr("File not found: $analysis\n");
                return ExitCode::NOINPUT;
            }

            $json = file_get_contents($analysis);
            $data = json_decode($json, true);

            if ($data === null) {
                $this->stderr("Error parsing JSON: " . json_last_error_msg() . "\n");
                return ExitCode::DATAERR;
            }

            $db = Yii::$app->db;
            $transaction = $db->beginTransaction();
            try {
                if (empty($data['phases'])){
                    $this->stderr("Not found phases in analysis: $analysis");
                    return ExitCode::DATAERR;
                }

                foreach ($data['phases'] as $phase) {
                    $this->importPhase($report, $phase);
                }

                if(empty($data['global'])){
                    $this->stderr("Not global metrics in analysis: $analysis");
                    return ExitCode::DATAERR;
                } else {
                    $report->flight_time_minutes = $data['global']['airborne_time_minutes'];
                    $report->initial_fuel_on_board = $data['global']['initial_fob_kg'];
                    $report->total_fuel_burn_kg = $data['global']['fuel_consumed_kg'];
                    $report->distance_nm = $data['global']['distance_nm'];

                    if(!empty($data['global']['block_time_minutes'])){
                        $report->block_time_minutes = $data['global']['block_time_minutes'];
                    }

                    if(!empty($data['global']['zfw_kg'])){
                        $report->zero_fuel_weight = $data['global']['zfw_kg'];
                    }

                    if (!$report->save()) {
                        throw new \Exception("Error updating flight report: " . json_encode($report->errors));
                    }
                }

                $flight_time_hours = (float)$report->flight_time_minutes / 60.0;

                $pilot = $flight->pilot;
                $pilot->hours_flown = $pilot->hours_flown + $flight_time_hours;
                if (!$pilot->save()) {
                    throw new \Exception("Error updating pilot hours flown: " . json_encode($pilot->errors));
                }

                $aircraft = $flight->aircraft;
                $aircraft->hours_flown = $aircraft->hours_flown + $flight_time_hours;
                if (!$aircraft->save()) {
                    throw new \Exception("Error updating aircraft hours flown: " . json_encode($aircraft->errors));
                }

                $flight->status = 'V';
                if (!$flight->save()) {
                    throw new \Exception("Error updating flight status: " . json_encode($flight->errors));
                }

                $transaction->commit();
                unlink($analysis);
                $this->stdout("Analysis succesfully imported for flight ". $flight->id . "\n");
            } catch (\Throwable $e) {
                $transaction->rollBack();
                $this->stderr("Failed importing analysis for flight ". $flight->id .": " . $e . "\n");
                return ExitCode::UNSPECIFIED_ERROR;
            }
        }

        return ExitCode::OK;
    }
}
