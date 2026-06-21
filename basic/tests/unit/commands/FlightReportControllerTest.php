<?php

namespace tests\unit\commands;

use app\commands\FlightReportController;
use app\config\Config;
use app\config\ConfigHelper;
use app\models\AcarsFile;
use app\models\Aircraft;
use app\models\AircraftConfiguration;
use app\models\AircraftType;
use app\models\Airport;
use app\models\Country;
use app\models\Flight;
use app\models\FlightEvent;
use app\models\FlightEventAttribute;
use app\models\FlightEventData;
use app\models\FlightPhase;
use app\models\FlightPhaseIssue;
use app\models\FlightPhaseMetric;
use app\models\FlightPhaseMetricType;
use app\models\FlightPhaseType;
use app\models\FlightReport;
use app\models\IssueType;
use app\models\Pilot;
use app\models\Runway;
use app\models\RunwayEnd;
use tests\unit\BaseUnitTest;
use Yii;
use yii\console\ExitCode;

class FlightReportControllerTest extends BaseUnitTest
{
    private FlightReportController $controller;
    private string $chunksDir;

    private Country $country;
    private Airport $airportDeparture;
    private Airport $airportArrival;
    private Airport $airportAlternative;
    private AircraftType $aircraftType;
    private AircraftConfiguration $aircraftConfig;

    protected function _before()
    {
        parent::_before();
        $this->clearDatabase();

        $this->chunksDir = sys_get_temp_dir() . '/mam_test_' . uniqid();
        mkdir($this->chunksDir, 0777, true);

        Config::set(ConfigHelper::CHUNKS_STORAGE_PATH, $this->chunksDir);

        $this->controller = new FlightReportController('flight-report', Yii::$app);

        $this->createSeedData();
        $this->createBaseEntities();
    }

    protected function _after()
    {
        $this->removeDir($this->chunksDir);
        parent::_after();
    }

    private function createSeedData(): void
    {
        foreach (['taxi', 'takeoff', 'approach', 'final_landing'] as $code) {
            (new FlightPhaseType(['code' => $code]))->save(false);
        }

        $metricsByPhase = [
            'takeoff'       => ['TakeoffGroundDistance'],
            'approach'      => ['MinVSFpm'],
            'final_landing' => ['LandingVSFpm', 'LandingBounces', 'BrakeDistance'],
        ];
        foreach ($metricsByPhase as $phaseCode => $metricCodes) {
            $phaseType = FlightPhaseType::findOne(['code' => $phaseCode]);
            foreach ($metricCodes as $code) {
                (new FlightPhaseMetricType(['flight_phase_type_id' => $phaseType->id, 'code' => $code]))->save(false);
            }
        }

        $issueDefs = [
            ['code' => 'TaxiOverspeed',           'penalty' => 5],
            ['code' => 'Refueling',               'penalty' => 50],
            ['code' => 'LandingHardFpm',          'penalty' => 20],
            ['code' => 'LandingAllEnginesStopped','penalty' => null],
            ['code' => 'AppHighVsBelow1000AGL',   'penalty' => 10],
        ];
        foreach ($issueDefs as $def) {
            (new IssueType($def))->save(false);
        }

        $attrCodes = [
            'Latitude', 'Longitude', 'onGround', 'Altitude', 'AGLAltitude',
            'Altimeter', 'VSFpm', 'LandingVSFpm', 'Heading', 'GSKnots',
            'IASKnots', 'QNHSet', 'Flaps', 'Gear', 'FuelKg', 'Squawk',
            'AP', 'Engine 1',
        ];
        foreach ($attrCodes as $code) {
            (new FlightEventAttribute(['code' => $code, 'name' => $code]))->save(false);
        }
    }

    private function createBaseEntities(): void
    {
        $this->country = new Country(['name' => 'Spain', 'iso2_code' => 'ES']);
        $this->country->save(false);

        $this->airportDeparture = new Airport([
            'icao_code'  => 'LEVD',
            'name'       => 'Valladolid Airport',
            'latitude'   => 41.706,
            'longitude'  => -4.852,
            'city'       => 'Valladolid',
            'country_id' => $this->country->id,
        ]);
        $this->airportDeparture->save(false);

        $this->airportArrival = new Airport([
            'icao_code'  => 'LEBL',
            'name'       => 'Barcelona Airport',
            'latitude'   => 41.298,
            'longitude'  => 2.071,
            'city'       => 'Barcelona',
            'country_id' => $this->country->id,
        ]);
        $this->airportArrival->save(false);

        $this->airportAlternative = new Airport([
            'icao_code'  => 'LEMD',
            'name'       => 'Madrid Airport',
            'latitude'   => 40.471,
            'longitude'  => -3.562,
            'city'       => 'Madrid',
            'country_id' => $this->country->id,
        ]);
        $this->airportAlternative->save(false);

        $this->aircraftType = new AircraftType([
            'icao_type_code' => 'C172',
            'name'           => 'Cessna 172',
            'max_nm_range'   => 800,
        ]);
        $this->aircraftType->save(false);

        $this->aircraftConfig = new AircraftConfiguration([
            'aircraft_type_id' => $this->aircraftType->id,
            'name'             => 'Standard',
            'pax_capacity'     => 3,
            'cargo_capacity'   => 50,
            'crew'             => 1,
            'mtow'             => 1111,
            'oew'              => 767,
        ]);
        $this->aircraftConfig->save(false);
    }

    private static int $pilotSeq = 0;

    private function createFlightWithReport(): array
    {
        $pilot = new Pilot([
            'license'       => 'T' . str_pad(++self::$pilotSeq, 7, '0', STR_PAD_LEFT),
            'name'          => 'Test',
            'surname'       => 'Pilot',
            'email'         => uniqid() . '@test.com',
            'password'      => '$2y$10$72JM.DYpddpessTYjHI0kuH/0NKNYeLP.YoU2AZwGY1kHY.Aow0Mu',
            'country_id'    => $this->country->id,
            'city'          => 'Valladolid',
            'location'      => 'LEVD',
            'date_of_birth' => '1990-01-01',
            'hours_flown'   => 0.0,
        ]);
        $pilot->save(false);

        $aircraft = new Aircraft([
            'aircraft_configuration_id' => $this->aircraftConfig->id,
            'registration'              => 'EC-' . strtoupper(substr(uniqid(), -3)),
            'name'                      => 'Test Aircraft',
            'location'                  => 'LEVD',
            'hours_flown'               => 0.0,
        ]);
        $aircraft->save(false);

        $flight = new Flight([
            'pilot_id'          => $pilot->id,
            'aircraft_id'       => $aircraft->id,
            'code'              => 'TST001',
            'departure'         => 'LEVD',
            'arrival'           => 'LEBL',
            'alternative1_icao' => 'LEMD',
            'flight_rules'      => 'I',
            'cruise_speed_unit' => 'N',
            'cruise_speed_value'=> '110',
            'flight_level_unit' => 'F',
            'flight_level_value'=> '085',
            'route'             => 'DCT',
            'estimated_time'    => '0130',
            'other_information' => 'Test',
            'endurance_time'    => '0300',
            'report_tool'       => 'TestTool',
            'status'            => 'S',
            'flight_type'       => 'R',
        ]);
        $flight->save(false);

        $report = new FlightReport([
            'flight_id'      => $flight->id,
            'landing_airport'=> 'LEBL',
        ]);
        $report->save(false);

        mkdir($report->getChunksDirectory(), 0777, true);

        return [$flight, $report, $pilot, $aircraft];
    }

    private function writeAnalysisJson(FlightReport $report, array $data): string
    {
        $path = $report->getChunksDirectory() . DIRECTORY_SEPARATOR . 'analysis.json';
        file_put_contents($path, json_encode($data));
        return $path;
    }

    private function writeGzipChunk(string $path, string $content): void
    {
        $gz = gzopen($path, 'wb9');
        gzwrite($gz, $content);
        gzclose($gz);
    }

    private function removeDir(string $dir): void
    {
        if (!is_dir($dir)) {
            return;
        }
        foreach (glob($dir . '/*') ?: [] as $file) {
            is_dir($file) ? $this->removeDir($file) : unlink($file);
        }
        rmdir($dir);
    }

    private function buildMinimalAnalysis(): array
    {
        return [
            'phases' => [
                [
                    'name'     => 'taxi',
                    'start'    => '2025-09-16T17:20:06.846883',
                    'end'      => '2025-09-16T17:20:42.855282',
                    'analysis' => [
                        'phase_metrics' => [],
                        'issues'        => [
                            ['code' => 'TaxiOverspeed', 'timestamp' => '2025-09-16T17:20:28.860873', 'value' => 36],
                        ],
                    ],
                    'events' => [
                        [
                            'Timestamp' => '2025-09-16T17:20:06.8468833',
                            'Changes'   => [
                                'Latitude' => '41,71587',
                                'Longitude'=> '-4,83921',
                                'onGround' => 'True',
                                'GSKnots'  => '0',
                            ],
                        ],
                    ],
                ],
                [
                    'name'     => 'final_landing',
                    'start'    => '2025-09-16T17:23:00.865315',
                    'end'      => '2025-09-16T17:23:00.865315',
                    'analysis' => [
                        'phase_metrics' => [
                            'LandingVSFpm' => -9249,
                            'LandingBounces'=> [],
                            'BrakeDistance' => null,
                        ],
                        'issues' => [
                            ['code' => 'LandingHardFpm',           'timestamp' => '2025-09-16T17:23:00.865315', 'value' => -9249],
                            ['code' => 'LandingAllEnginesStopped', 'timestamp' => '2025-09-16T17:23:00.865315', 'value' => null],
                        ],
                    ],
                    'events' => [
                        [
                            'Timestamp' => '2025-09-16T17:23:00.8653158',
                            'Changes'   => [
                                'Latitude' => '41,71814',
                                'Longitude'=> '-4,83522',
                                'onGround' => 'True',
                            ],
                        ],
                    ],
                ],
            ],
            'global' => [
                'airborne_time_minutes' => 2,
                'initial_fob_kg'        => 79,
                'fuel_consumed_kg'      => 33,
                'distance_nm'           => 3,
            ],
        ];
    }

    // =========================================================================
    // actionAssemblePendingAcars
    // =========================================================================

    public function testAssembleNoPendingFlights(): void
    {
        $result = $this->controller->actionAssemblePendingAcars();
        $this->assertEquals(ExitCode::OK, $result);
    }

    public function testAssembleSuccess(): void
    {
        [, $report] = $this->createFlightWithReport();
        $chunksPath = $report->getChunksDirectory();

        $runway = new Runway([
            'airport_icao' => 'LEVD',
            'designators'  => '23/05',
            'width_m'      => 45.0,
            'length_m'     => 2700.0,
        ]);
        $runway->save(false);

        (new RunwayEnd([
            'runway_id'              => $runway->id,
            'designator'             => '23',
            'latitude'               => 41.706,
            'longitude'              => -4.852,
            'true_heading_deg'       => 230.0,
            'displaced_threshold_m'  => 0.0,
            'stopway_m'              => 0.0,
        ]))->save(false);

        $this->writeGzipChunk($chunksPath . '/1', '{"part":1}');
        $this->writeGzipChunk($chunksPath . '/2', '{"part":2}');

        foreach ([1, 2] as $chunkId) {
            (new AcarsFile([
                'flight_report_id' => $report->id,
                'chunk_id'         => $chunkId,
                'sha256sum'        => str_repeat('A', 44),
            ]))->save(false);
        }

        $result = $this->controller->actionAssemblePendingAcars();

        $this->assertEquals(ExitCode::OK, $result);
        $this->assertFileExists($chunksPath . '/report.json');
        $this->assertNotEmpty(file_get_contents($chunksPath . '/report.json'));
        $this->assertFileExists($chunksPath . '/context.json');
        $this->assertFileDoesNotExist($chunksPath . '/concat.gz');

        $context = json_decode(file_get_contents($chunksPath . '/context.json'), true);
        $this->assertEquals('LEVD', $context['departure']['icao']);
        $this->assertCount(1, $context['departure']['runways']);
        $this->assertEquals('LEBL', $context['destination']['icao']);
        $this->assertEquals('LEBL', $context['landing']['icao']);
    }

    public function testAssembleInvalidGzip(): void
    {
        [, $report] = $this->createFlightWithReport();
        $chunksPath = $report->getChunksDirectory();

        file_put_contents($chunksPath . '/1', 'not gzip content');
        (new AcarsFile([
            'flight_report_id' => $report->id,
            'chunk_id'         => 1,
            'sha256sum'        => str_repeat('A', 44),
        ]))->save(false);

        $result = $this->controller->actionAssemblePendingAcars();

        $this->assertEquals(ExitCode::NOINPUT, $result);
    }

    public function testAssembleMissingChunk(): void
    {
        [, $report] = $this->createFlightWithReport();

        // Register a chunk in DB but provide no physical file
        (new AcarsFile([
            'flight_report_id' => $report->id,
            'chunk_id'         => 1,
            'sha256sum'        => str_repeat('A', 44),
        ]))->save(false);

        $this->expectException(\RuntimeException::class);
        $this->controller->actionAssemblePendingAcars();
    }

    // =========================================================================
    // actionImportPendingReportsAnalysis
    // =========================================================================

    public function testImportNoPendingFlights(): void
    {
        $result = $this->controller->actionImportPendingReportsAnalysis();
        $this->assertEquals(ExitCode::OK, $result);
    }

    public function testImportFileNotFound(): void
    {
        $this->createFlightWithReport();
        $result = $this->controller->actionImportPendingReportsAnalysis();
        $this->assertEquals(ExitCode::NOINPUT, $result);
    }

    public function testImportInvalidJson(): void
    {
        [, $report] = $this->createFlightWithReport();
        file_put_contents($report->getChunksDirectory() . '/analysis.json', '{ bad json ');

        $result = $this->controller->actionImportPendingReportsAnalysis();
        $this->assertEquals(ExitCode::DATAERR, $result);
    }

    public function testImportEmptyPhases(): void
    {
        [, $report] = $this->createFlightWithReport();
        $this->writeAnalysisJson($report, [
            'phases' => [],
            'global' => ['airborne_time_minutes' => 2, 'initial_fob_kg' => 79, 'fuel_consumed_kg' => 33, 'distance_nm' => 3],
        ]);

        $result = $this->controller->actionImportPendingReportsAnalysis();
        $this->assertEquals(ExitCode::DATAERR, $result);
    }

    public function testImportEmptyGlobal(): void
    {
        [, $report] = $this->createFlightWithReport();
        $this->writeAnalysisJson($report, [
            'phases' => [
                ['name' => 'taxi', 'start' => '2025-09-16T17:20:06.846883', 'end' => '2025-09-16T17:20:42.855282',
                 'analysis' => ['phase_metrics' => [], 'issues' => []], 'events' => []],
            ],
        ]);

        $result = $this->controller->actionImportPendingReportsAnalysis();
        $this->assertEquals(ExitCode::DATAERR, $result);
    }

    public function testImportSuccess(): void
    {
        [$flight, $report, $pilot, $aircraft] = $this->createFlightWithReport();
        $this->writeAnalysisJson($report, $this->buildMinimalAnalysis());
        $analysisPath = $report->getChunksDirectory() . '/analysis.json';

        $result = $this->controller->actionImportPendingReportsAnalysis();

        $this->assertEquals(ExitCode::OK, $result);

        $this->assertEquals('V', Flight::findOne($flight->id)->status);

        $this->assertEquals(2, FlightPhase::find()->where(['flight_report_id' => $report->id])->count());

        // taxi: TaxiOverspeed | final_landing: LandingHardFpm + LandingAllEnginesStopped
        $this->assertEquals(3, FlightPhaseIssue::find()->count());

        // LandingVSFpm only (LandingBounces=[] and BrakeDistance=null are skipped)
        $this->assertEquals(1, FlightPhaseMetric::find()->count());

        // 1 event per phase
        $this->assertEquals(2, FlightEvent::find()->count());

        // taxi event: Latitude + Longitude + onGround + GSKnots = 4
        // landing event: Latitude + Longitude + onGround = 3
        $this->assertEquals(7, FlightEventData::find()->count());

        $updatedReport = FlightReport::findOne($report->id);
        $this->assertEquals(2, $updatedReport->flight_time_minutes);
        $this->assertEquals(79, $updatedReport->initial_fuel_on_board);
        $this->assertEquals(33, $updatedReport->total_fuel_burn_kg);
        $this->assertEquals(3, $updatedReport->distance_nm);

        $expectedHours = 2.0 / 60.0;
        $this->assertEqualsWithDelta($expectedHours, (float)Pilot::findOne($pilot->id)->hours_flown, 0.0001);
        $this->assertEqualsWithDelta($expectedHours, (float)Aircraft::findOne($aircraft->id)->hours_flown, 0.0001);

        $this->assertFileDoesNotExist($analysisPath);
    }

    public function testImportSuccessWithOptionalGlobalFields(): void
    {
        [$flight, $report] = $this->createFlightWithReport();
        $analysis = $this->buildMinimalAnalysis();
        $analysis['global']['block_time_minutes'] = 5;
        $analysis['global']['zfw_kg'] = 52000;
        $this->writeAnalysisJson($report, $analysis);

        $result = $this->controller->actionImportPendingReportsAnalysis();

        $this->assertEquals(ExitCode::OK, $result);
        $updatedReport = FlightReport::findOne($report->id);
        $this->assertEquals(5, $updatedReport->block_time_minutes);
        $this->assertEquals(52000, $updatedReport->zero_fuel_weight);
    }

    public function testImportSuccessLatLonCommaConvertedToDot(): void
    {
        [, $report] = $this->createFlightWithReport();
        $this->writeAnalysisJson($report, $this->buildMinimalAnalysis());

        $this->controller->actionImportPendingReportsAnalysis();

        $latAttr = FlightEventAttribute::findOne(['code' => 'Latitude']);
        $latData = FlightEventData::findOne(['attribute_id' => $latAttr->id]);
        $this->assertNotNull($latData);
        $this->assertStringNotContainsString(',', $latData->value);
        $this->assertStringContainsString('.', $latData->value);
    }

    public function testImportEmptyArrayMetricSkipped(): void
    {
        [, $report] = $this->createFlightWithReport();
        $this->writeAnalysisJson($report, [
            'phases' => [
                [
                    'name'     => 'final_landing',
                    'start'    => '2025-09-16T17:23:00.865315',
                    'end'      => '2025-09-16T17:23:00.865315',
                    'analysis' => ['phase_metrics' => ['LandingBounces' => []], 'issues' => []],
                    'events'   => [],
                ],
            ],
            'global' => ['airborne_time_minutes' => 2, 'initial_fob_kg' => 79, 'fuel_consumed_kg' => 33, 'distance_nm' => 3],
        ]);

        $this->controller->actionImportPendingReportsAnalysis();

        $this->assertEquals(0, FlightPhaseMetric::find()->count());
    }

    public function testImportNullMetricSkipped(): void
    {
        [, $report] = $this->createFlightWithReport();
        $this->writeAnalysisJson($report, [
            'phases' => [
                [
                    'name'     => 'final_landing',
                    'start'    => '2025-09-16T17:23:00.865315',
                    'end'      => '2025-09-16T17:23:00.865315',
                    'analysis' => ['phase_metrics' => ['BrakeDistance' => null], 'issues' => []],
                    'events'   => [],
                ],
            ],
            'global' => ['airborne_time_minutes' => 2, 'initial_fob_kg' => 79, 'fuel_consumed_kg' => 33, 'distance_nm' => 3],
        ]);

        $this->controller->actionImportPendingReportsAnalysis();

        $this->assertEquals(0, FlightPhaseMetric::find()->count());
    }

    public function testImportPipeSeparatedIssueValue(): void
    {
        [, $report] = $this->createFlightWithReport();
        $this->writeAnalysisJson($report, [
            'phases' => [
                [
                    'name'     => 'approach',
                    'start'    => '2025-09-16T17:21:44.855177',
                    'end'      => '2025-09-16T17:23:00.865314',
                    'analysis' => [
                        'phase_metrics' => ['MinVSFpm' => -7797],
                        'issues'        => [
                            ['code' => 'AppHighVsBelow1000AGL', 'timestamp' => '2025-09-16T17:22:56.864496', 'value' => '-2980|550|-2000'],
                        ],
                    ],
                    'events' => [],
                ],
            ],
            'global' => ['airborne_time_minutes' => 2, 'initial_fob_kg' => 79, 'fuel_consumed_kg' => 33, 'distance_nm' => 3],
        ]);

        $this->controller->actionImportPendingReportsAnalysis();

        $issue = FlightPhaseIssue::find()->one();
        $this->assertNotNull($issue);
        $this->assertEquals('-2980|550|-2000', $issue->value);
    }

    public function testImportUnknownPhaseTypeRollsBack(): void
    {
        [$flight, $report] = $this->createFlightWithReport();
        $this->writeAnalysisJson($report, [
            'phases' => [
                ['name' => 'nonexistent_phase', 'start' => '2025-09-16T17:20:06.846883', 'end' => '2025-09-16T17:20:42.855282',
                 'analysis' => ['phase_metrics' => [], 'issues' => []], 'events' => []],
            ],
            'global' => ['airborne_time_minutes' => 2, 'initial_fob_kg' => 79, 'fuel_consumed_kg' => 33, 'distance_nm' => 3],
        ]);

        $result = $this->controller->actionImportPendingReportsAnalysis();

        $this->assertEquals(ExitCode::UNSPECIFIED_ERROR, $result);
        $this->assertEquals('S', Flight::findOne($flight->id)->status);
        $this->assertEquals(0, FlightPhase::find()->count());
    }

    public function testImportUnknownIssueTypeRollsBack(): void
    {
        [$flight, $report] = $this->createFlightWithReport();
        $this->writeAnalysisJson($report, [
            'phases' => [
                ['name' => 'taxi', 'start' => '2025-09-16T17:20:06.846883', 'end' => '2025-09-16T17:20:42.855282',
                 'analysis' => [
                     'phase_metrics' => [],
                     'issues' => [['code' => 'UnknownIssueXYZ', 'timestamp' => '2025-09-16T17:20:28.860873', 'value' => 1]],
                 ],
                 'events' => []],
            ],
            'global' => ['airborne_time_minutes' => 2, 'initial_fob_kg' => 79, 'fuel_consumed_kg' => 33, 'distance_nm' => 3],
        ]);

        $result = $this->controller->actionImportPendingReportsAnalysis();

        $this->assertEquals(ExitCode::UNSPECIFIED_ERROR, $result);
        $this->assertEquals('S', Flight::findOne($flight->id)->status);
    }

    public function testImportUnknownMetricTypeRollsBack(): void
    {
        [$flight, $report] = $this->createFlightWithReport();
        $this->writeAnalysisJson($report, [
            'phases' => [
                ['name' => 'final_landing', 'start' => '2025-09-16T17:23:00.865315', 'end' => '2025-09-16T17:23:00.865315',
                 'analysis' => ['phase_metrics' => ['UnknownMetricXYZ' => 42], 'issues' => []],
                 'events' => []],
            ],
            'global' => ['airborne_time_minutes' => 2, 'initial_fob_kg' => 79, 'fuel_consumed_kg' => 33, 'distance_nm' => 3],
        ]);

        $result = $this->controller->actionImportPendingReportsAnalysis();

        $this->assertEquals(ExitCode::UNSPECIFIED_ERROR, $result);
        $this->assertEquals('S', Flight::findOne($flight->id)->status);
    }

    public function testImportNullIssueTimestampRollsBack(): void
    {
        [$flight, $report] = $this->createFlightWithReport();
        $this->writeAnalysisJson($report, [
            'phases' => [
                ['name' => 'taxi', 'start' => '2025-09-16T17:20:06.846883', 'end' => '2025-09-16T17:20:42.855282',
                 'analysis' => [
                     'phase_metrics' => [],
                     'issues' => [['code' => 'TaxiOverspeed', 'value' => 36]],
                 ],
                 'events' => []],
            ],
            'global' => ['airborne_time_minutes' => 2, 'initial_fob_kg' => 79, 'fuel_consumed_kg' => 33, 'distance_nm' => 3],
        ]);

        $result = $this->controller->actionImportPendingReportsAnalysis();

        $this->assertEquals(ExitCode::UNSPECIFIED_ERROR, $result);
        $this->assertEquals('S', Flight::findOne($flight->id)->status);
    }
}
