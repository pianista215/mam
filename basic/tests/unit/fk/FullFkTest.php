<?php

namespace tests\unit\fk;

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
use app\models\FlightPhaseMetric;
use app\models\FlightPhaseMetricType;
use app\models\FlightPhaseMetricTypeLang;
use app\models\FlightPhaseType;
use app\models\FlightPhaseTypeLang;
use app\models\FlightReport;
use app\models\IssueType;
use app\models\IssueTypeLang;
use app\models\Pilot;
use app\models\PilotTourCompletion;
use app\models\Rank;
use app\models\Route;
use app\models\SubmittedFlightPlan;
use app\models\Tour;
use app\models\TourStage;
use app\models\FlightPhaseIssue;
use app\models\Page;
use app\models\PageContent;
use app\models\Image;
use tests\unit\BaseUnitTest;
use Yii;

class FullFkTest extends BaseUnitTest
{

    private function createPilot($country, $rank)
    {
        $pilot = new Pilot([
            'name' => 'John',
            'surname' => 'Doe',
            'email' => 'john.doe@example.com',
            'country_id' => $country->id,
            'rank_id' => $rank->id,
            'city' => 'New York',
            'location' => 'LEMD',
            'password' => 'SecurePass123!',
            'license' => 'lic123',
            'date_of_birth' => '1990-01-01',
        ]);
        $this->assertTrue($pilot->save(), 'pilot saved');
        return $pilot;
    }

    private function createPilot2($country, $rank)
    {
        $pilot2 = new Pilot([
            'name' => 'John2',
            'surname' => 'Doe',
            'email' => 'john.doe2@example.com',
            'country_id' => $country->id,
            'rank_id' => $rank->id,
            'city' => 'New York',
            'location' => 'LEMD',
            'password' => 'SecurePass123!',
            'license' => 'lic345',
            'date_of_birth' => '1990-01-01',
        ]);
        $this->assertTrue($pilot2->save(), 'pilot2 saved');
        return $pilot2;
    }

    private function createLEMD($country)
    {
        $lemd = new Airport([
            'icao_code' => 'LEMD',
            'name' => 'Madrid-Barajas',
            'latitude' => 40.471926,
            'longitude' => -3.56264,
            'city' => 'Madrid',
            'country_id' => $country->id,
        ]);
        $this->assertTrue($lemd->save(), 'lemd saved');
        return $lemd;
    }

    private function createLEVC($country)
    {
        $levc = new Airport([
            'icao_code' => 'LEVC',
            'name' => 'Valencia',
            'latitude' => 39.489,
            'longitude' => -0.481,
            'city' => 'Valencia',
            'country_id' => $country->id,
        ]);
        $this->assertTrue($levc->save(), 'levc saved');
        return $levc;
    }

    private function createAircraft($aircraftConfiguration)
    {
        $aircraft = new Aircraft([
            'aircraft_configuration_id' => $aircraftConfiguration->id,
            'registration' => 'STD123',
            'name' => 'Boeing 737 Std',
            'location' => 'LEMD',
            'hours_flown' => 1000.5,
        ]);
        $this->assertTrue($aircraft->save(), 'aircraft saved');
        return $aircraft;
    }

    private function createStage($tour)
    {
        $stage = new TourStage([
            'tour_id' => $tour->id,
            'departure' => 'LEMD',
            'arrival' => 'LEVD',
            'sequence' => 1,
            'description' => 'Stage 1'
        ]);
        $this->assertTrue($stage->save(), 'tour stage saved');
        return $stage;
    }

    private function createSubmittedFpl($aircraft, $pilot, $route, $stage)
    {
        $sfp = null;
        if($route !== null){
            $sfp = new SubmittedFlightPlan([
                'aircraft_id' => $aircraft->id,
                'flight_rules' => 'V',
                'alternative1_icao' => 'LEVC',
                'alternative2_icao' => 'LEMD',
                'cruise_speed_value' => '400',
                'cruise_speed_unit' => 'N',
                'flight_level_value' => '350',
                'flight_level_unit' => 'F',
                'route' => 'NAND UM871',
                'estimated_time' => '0031',
                'other_information' => 'PBN/A1B1D1',
                'endurance_time' => '0500',
                'route_id' => $route->id,
                'pilot_id' => $pilot->id,
                'tour_stage_id' => null,
            ]);
            $this->assertTrue($sfp->save(), 'sfpRoute saved');
        } else {
            $sfp = new SubmittedFlightPlan([
                'aircraft_id' => $aircraft->id,
                'flight_rules' => 'I',
                'alternative1_icao' => 'LEVC',
                'alternative2_icao' => 'LEMD',
                'cruise_speed_value' => '380',
                'cruise_speed_unit' => 'N',
                'flight_level_value' => '320',
                'flight_level_unit' => 'F',
                'route' => 'NAND UM871',
                'estimated_time' => '0045',
                'other_information' => 'PBN/A1',
                'endurance_time' => '0600',
                'route_id' => null,
                'pilot_id' => $pilot->id,
                'tour_stage_id' => $stage->id,
            ]);
            $this->assertTrue($sfp->save(), 'sfpStage saved');
        }
        return $sfp;
    }

    private function createFlight($aircraft, $pilot, $stage, $validator)
    {
        $flight = new Flight();
        $flight->pilot_id = $pilot->id;
        $flight->aircraft_id = $aircraft->id;
        $flight->departure = 'LEMD';
        $flight->arrival = 'LEBL';
        $flight->alternative1_icao = 'LEVC';
        $flight->alternative2_icao = 'LEAL';
        $flight->flight_rules = 'V';
        $flight->code = 'FLT001';
        $flight->cruise_speed_value = '450';
        $flight->cruise_speed_unit = 'N';
        $flight->flight_level_value = '360';
        $flight->flight_level_unit = 'F';
        $flight->route = 'ROUTE';
        $flight->estimated_time = '0200';
        $flight->other_information = 'Other flight details';
        $flight->endurance_time = '0500';
        $flight->report_tool = 'ToolName';
        $flight->tour_stage_id = $stage->id;
        $flight->validator_id = $validator->id;
        $this->assertTrue($flight->save(), 'flight saved');
    }

    public function testFullFkIntegrityAndCascades()
    {
        $country = new Country(['id' => 1, 'name' => 'Spain', 'iso2_code' => 'ES']);
        $this->assertTrue($country->save(), 'Spain saved');
        $country2 = new Country(['id' => 2, 'name' => 'USA', 'iso2_code' => 'US']);
        $this->assertTrue($country2->save(), 'USA saved');

        // Country & airport
        $lemd = $this->createLEMD($country);

        // Check we can't delete country with airport
        try {
            $country->delete();
            $this->fail('Deleting country should have failed due to existing airports.');
        } catch (\yii\db\IntegrityException $e) {
            $this->assertTrue(true, 'country deletion blocked by airports');
        }

        // Rank & Pilot
        $rank = new Rank(['name' => 'Captain', 'position' => 1]);
        $this->assertTrue($rank->save(), 'rank saved');

        $pilot = $this->createPilot($country2, $rank);

        // Check we can't delete country with pilot associated
        try {
            $country2->delete();
            $this->fail('Deleting country should have failed due to existing pilot.');
        } catch (\yii\db\IntegrityException $e) {
            $this->assertTrue(true, 'country deletion blocked by pilot');
        }

        // Check we can't delete rank with pilot associated
        try {
            $rank->delete();
            $this->fail('Deleting rank should have failed due to existing pilot.');
        } catch (\yii\db\IntegrityException $e) {
            $this->assertTrue(true, 'rank deletion blocked by pilot');
        }

        $this->assertEquals(1, $pilot->delete());

        // Aircraft type & aircraft configuration & aircraft
        $atype = new AircraftType([
            'icao_type_code' => 'B738',
            'name' => 'Boeing 737-800',
            'max_nm_range' => 2900,
        ]);
        $this->assertTrue($atype->save(), 'aircraft type saved');

        $aconf = new AircraftConfiguration([
            'aircraft_type_id' => $atype->id,
            'name' => 'Standard',
            'pax_capacity' => 180,
            'cargo_capacity' => 2000,
        ]);
        $this->assertTrue($aconf->save(), 'aircraft config saved');

        // Check we can't delete aircraft type with aircraft config associated
        try {
            $atype->delete();
            $this->fail('Deleting aircraft type should have failed due to existing aircraft configuration.');
        } catch (\yii\db\IntegrityException $e) {
            $this->assertTrue(true, 'aircraft type deletion blocked by aircraft configuration');
        }

        $aircraft = $this->createAircraft($aconf);

        // Check we can't delete aircraft configuration with aircraft associated
        try {
            $aconf->delete();
            $this->fail('Deleting aircraft configuration should have failed due to existing aircraft.');
        } catch (\yii\db\IntegrityException $e) {
            $this->assertTrue(true, 'aircraft configuration deletion blocked by aircraft');
        }

        // Check we can't delete airport with aircraft associated
        try {
            $lemd->delete();
            $this->fail('Deleting airport should have failed due to existing aircraft.');
        } catch (\yii\db\IntegrityException $e) {
            $this->assertTrue(true, 'airport deletion blocked by aircraft');
        }

        $this->assertEquals(1, $aircraft->delete(), 'I can delete aircraft without nothing associated');
        $this->assertEquals(1, $lemd->delete(), 'I can delete airport without nothing associated');

        $lemd = $this->createLEMD($country);

        $aircraft = $this->createAircraft($aconf);

        // Create additional airports and pilot for routes and alternatives in submittedFpl and flights
        $pilot = $this->createPilot($country2, $rank);

        $lebl = new Airport([
            'icao_code' => 'LEBL',
            'name' => 'Barcelona-El Prat',
            'latitude' => 41.297445,
            'longitude' => 2.0833,
            'city' => 'Barcelona',
            'country_id' => $country->id,
        ]);
        $this->assertTrue($lebl->save(), 'lebl saved');

        $levd = new Airport([
            'icao_code' => 'LEVD',
            'name' => 'Valladolid',
            'latitude' => 41.705,
            'longitude' => -4.878,
            'city' => 'Valladolid',
            'country_id' => $country->id,
        ]);
        $this->assertTrue($levd->save(), 'levd saved');

        $levc = $this->createLEVC($country);

        $leal = new Airport([
            'icao_code' => 'LEAL',
            'name' => 'Almeria',
            'latitude' => 36.843,
            'longitude' => -2.371,
            'city' => 'Almeria',
            'country_id' => $country->id,
        ]);
        $this->assertTrue($leal->save(), 'leal saved');

        // Route (needs departure+arrival airports)
        $route = new Route(['code' => 'MAD-BCN', 'departure' => 'LEMD', 'arrival' => 'LEBL', 'distance_nm' => 300]);
        $this->assertTrue($route->save(), 'route saved');

        // Check we can't delete airport with route associated
        try {
            $lebl->delete();
            $this->fail('Deleting airport should have failed due to existing route.');
        } catch (\yii\db\IntegrityException $e) {
            $this->assertTrue(true, 'airport deletion blocked by route');
        }

        // Tour + stage
        $tour = new Tour(['name' => 'Test', 'description' => 'Description', 'start' => '2020-01-01', 'end' => '2023-01-01']);
        $this->assertTrue($tour->save(), 'tour saved');

        $stage = $this->createStage($tour);

        // Check we can't delete tour with tour stage associated
        try {
            $tour->delete();
            $this->fail('Deleting tour should have failed due to existing tour stage.');
        } catch (\yii\db\IntegrityException $e) {
            $this->assertTrue(true, 'tour deletion blocked by tour stage');
        }

        // Check we can't delete airport with tour stage associated
        try {
            $levd->delete();
            $this->fail('Deleting airport should have failed due to existing tour stage.');
        } catch (\yii\db\IntegrityException $e) {
            $this->assertTrue(true, 'airport deletion blocked by tour stage');
        }

        // Pilot tour completion (ON DELETE CASCADE on pilot and tour)
        $tour2 = new Tour(['name' => 'Test2', 'description' => 'Description', 'start' => '2020-01-01', 'end' => '2023-01-01']);
        $this->assertTrue($tour2->save(), 'tour saved');

        $ptc = new PilotTourCompletion(['pilot_id' => $pilot->id, 'tour_id' => $tour2->id, 'completed_at' => '2020-01-02']);
        $this->assertTrue($ptc->save(), 'pilot tour completion saved');

        $this->assertEquals(1, $tour2->delete(), 'tour 2 can be deleted');
        $this->assertEquals(0, PilotTourCompletion::find()->count(), 'Pilot completition should be deleted with tour');

        $pilot2 = $this->createPilot2($country2, $rank);

        $tour3 = new Tour(['name' => 'Test3', 'description' => 'Description', 'start' => '2020-01-01', 'end' => '2023-01-01']);
        $this->assertTrue($tour3->save(), 'tour saved');

        $ptc = new PilotTourCompletion(['pilot_id' => $pilot2->id, 'tour_id' => $tour3->id, 'completed_at' => '2020-01-02']);
        $this->assertTrue($ptc->save(), 'pilot tour 2 completion saved');

        $this->assertEquals(1, $pilot2->delete(), 'pilot 2 can be deleted');
        $this->assertEquals(0, PilotTourCompletion::find()->count(), 'Pilot completition should be deleted with pilot');

        $this->assertEquals(1, $tour3->save(), 'Tour 3 can be delete without stages associated');

        // Submitted flight plan associated to route
        $sfpRoute = $this->createSubmittedFpl($aircraft, $pilot, $route, null);

        // Check we can't delete route with submitted fpl associated
        try {
            $route->delete();
            $this->fail('Deleting route should have failed due to existing submittedFpl with route associated.');
        } catch (\yii\db\IntegrityException $e) {
            $this->assertTrue(true, 'route deletion blocked by submitted fpl with route');
        }

        // Check we can't delete airport with submitted fpl associated
        try {
            $levc->delete();
            $this->fail('Deleting airport should have failed due to submittedFpl with airport associated.');
        } catch (\yii\db\IntegrityException $e) {
            $this->assertTrue(true, 'airport deletion blocked by submitted fpl');
        }

        $this->assertEquals(1, $sfpRoute->delete(), 'SubmittedFlightPlan with route associated can be deleted');
        $this->assertEquals(1, $levc->delete(), 'Airport without submitted flight plan associated can be deleted');

        $levc = $this->createLEVC($country);

        // Submitted flight plan associated to tour_stage
        $sfpStage = $this->createSubmittedFpl($aircraft, $pilot, null, $stage);

        // Check we can't delete tour stage with submitted fpl associated
        try {
            $stage->delete();
            $this->fail('Deleting tour stage should have failed due to existing submittedFpl with tour stage associated.');
        } catch (\yii\db\IntegrityException $e) {
            $this->assertTrue(true, 'tour stage deletion blocked by submitted fpl with tour stage');
        }

        $this->assertEquals(1, $sfpStage->delete(), 'SubmittedFlightPlan with tour stage associated can be deleted');
        $this->assertEquals(1, $stage->delete(), 'Stage without nothing associated can be deleted');

        // Flight with tour stage and validator
        $pilot2 = $this->createPilot2($country2, $rank);
        $stage = $this->createStage($tour);
        $flight = $this->createFlight($aircraft, $pilot, $stage, $pilot2);

        // Check we can't delete tour stage with flight associated
        try {
            $stage->delete();
            $this->fail('Deleting tour stage should have failed due to existing flight with tour stage associated.');
        } catch (\yii\db\IntegrityException $e) {
            $this->assertTrue(true, 'tour stage deletion blocked by flight with tour stage');
        }

        // Check we can't delete pilot with flight associated
        /* We don't allow pilot cascade deletion in order to not alter the statistics
         * We always can set the license to null, and the pilot will dissapear from the pilot main, but the flights will remain
         **/
        try {
            $pilot->delete();
            $this->fail('Deleting pilot should have failed due to existing flight associated.');
        } catch (\yii\db\IntegrityException $e) {
            $this->assertTrue(true, 'pilot deletion blocked by flight');
        }

        // Check we can't delete validator with flight associated
        try {
            $pilot2->delete();
            $this->fail('Deleting pilot validator should have failed due to existing flight associated.');
        } catch (\yii\db\IntegrityException $e) {
            $this->assertTrue(true, 'pilot validator deletion blocked by flight');
        }

        // Flight report -> ACARS file (flight_report has ON DELETE CASCADE from flight)
        $freport = new FlightReport(['flight_id' => $flight->id, 'landing_airport' => 'LEBL']);
        $this->assertTrue($freport->save(), 'flight report saved');

        $acars = new AcarsFile(['flight_report_id' => $freport->id, 'chunk_id' => 1, 'sha256sum' => str_repeat('a', 44)]);
        $this->assertTrue($acars->save(), 'acars saved');

        // Flight phase types / langs (cascade from type)
        $phaseType = new FlightPhaseType(['code' => 'TAKEOFF']);
        $this->assertTrue($phaseType->save(), 'phase type saved');

        $phaseTypeLang = new FlightPhaseTypeLang(['flight_phase_type_id' => $phaseType->id, 'language' => 'en', 'name' => 'Takeoff']);
        $this->assertTrue($phaseTypeLang->save(), 'phase type lang saved');

        // Flight phase that depends on flight_report (ON DELETE CASCADE)
        $phase = new FlightPhase(['flight_report_id' => $freport->id, 'flight_phase_type_id' => $phaseType->id, 'start' => '2024-01-01 10:00:00', 'end' => '2024-01-01 10:10:00']);
        $this->assertTrue($phase->save(), 'phase saved');

        // Flight phase metric types & metric (metric type cascades on phase_type deletion)
        $metricType = new FlightPhaseMetricType(['flight_phase_type_id' => $phaseType->id, 'code' => 'SPEED']);
        $this->assertTrue($metricType->save(), 'metric type saved');

        $metricTypeLang = new FlightPhaseMetricTypeLang(['flight_phase_metric_type_id' => $metricType->id, 'language' => 'en', 'name' => 'Speed']);
        $this->assertTrue($metricTypeLang->save(), 'metric type lang saved');

        $metric = new FlightPhaseMetric(['flight_phase_id' => $phase->id, 'metric_type_id' => $metricType->id, 'value' => '150']);
        $this->assertTrue($metric->save(), 'metric saved');

        // Flight event attribute / event / data (cascade down from phase)
        $feAttr = new FlightEventAttribute(['name' => 'Speed', 'code' => 'SPD']);
        $this->assertTrue($feAttr->save(), 'event attribute saved');

        $fe = new FlightEvent(['phase_id' => $phase->id, 'timestamp' => '2024-01-01 10:05:00']);
        $this->assertTrue($fe->save(), 'flight event saved');

        $fed = new FlightEventData(['event_id' => $fe->id, 'attribute_id' => $feAttr->id, 'value' => '150']);
        $this->assertTrue($fed->save(), 'flight event data saved');

        // Issue type + lang and issue on phase
        $issueType = new IssueType(['code' => 'TOO_FAST', 'penalty' => 1]);
        $this->assertTrue($issueType->save(), 'issue type saved');

        $issueTypeLang = new IssueTypeLang(['issue_type_id' => $issueType->id, 'language' => 'en', 'description' => 'Too fast']);
        $this->assertTrue($issueTypeLang->save(), 'issue type lang saved');

        $phaseIssue = new FlightPhaseIssue(['phase_id' => $phase->id, 'issue_type_id' => $issueType->id, 'timestamp' => '2024-01-01 10:06:00']);
        $this->assertTrue($phaseIssue->save(), 'phase issue saved');

        // Page + page_content (page_content ON DELETE CASCADE)
        $page = new Page(['code' => 'home', 'public' => 1]);
        $this->assertTrue($page->save(), 'page saved');

        $pageContent = new PageContent(['page_id' => $page->id, 'language' => 'en', 'title' => 'Home', 'content_md' => '...']);
        $this->assertTrue($pageContent->save(), 'page content saved');

        // Image (no FK constraints in DDL)
        $image = new Image(['type' => 'pilot', 'related_id' => $pilot->id, 'element' => 0, 'filename' => 'avatar.jpg']);
        $this->assertTrue($image->save(), 'image saved');


        // -------------------------
        // 2) TRY DELETING PARENTS -> EXPECT INTEGRITY ERRORS WHERE APPLICABLE
        // -------------------------

        // 2.b Pilot deletion should be blocked because Flight references pilot (flight.pilot_id has no ON DELETE)
        try {
            $pilot->delete();
            $this->fail('Deleting pilot should have failed because Flight exists referencing it.');
        } catch (\yii\db\IntegrityException $e) {
            $this->assertTrue(true, 'pilot deletion blocked by flight');
        }

        // 2.c Tour deletion should be blocked because TourStage references it (tour_stage.tour_id has no ON DELETE)
        try {
            $tour->delete();
            $this->fail('Deleting tour should have failed because TourStage exists referencing it.');
        } catch (\yii\db\IntegrityException $e) {
            $this->assertTrue(true, 'tour deletion blocked by tour_stage');
        }

        // 2.d Route deletion should be blocked because SubmittedFlightPlan references it
        try {
            $route->delete();
            $this->fail('Deleting route should have failed because sfpRoute exists referencing it.');
        } catch (\yii\db\IntegrityException $e) {
            $this->assertTrue(true, 'route deletion blocked by sfpRoute');
        }

        // 2.d tour_stage deletion should be blocked because SubmittedFlightPlan references it
        try {
            $stage->delete();
            $this->fail('Deleting tour stage should have failed because sfpStage references it.');
        } catch (\yii\db\IntegrityException $e) {
            $this->assertTrue(true, 'tour stage deletion blocked by sfpStage');
        }

        // -------------------------
        // 3) DELETE IN DEPENDENCY ORDER AND VERIFY CASCADES
        // -------------------------

        // 3.a Delete Flight -> should cascade to FlightReport -> AcarsFile, FlightPhase, FlightEvent, FlightEventData, FlightPhaseMetric, FlightPhaseIssue
        $this->assertNotFalse($flight->delete(), 'flight deleted');

        // flight should be gone
        $this->assertNull(Flight::findOne($flight->id), 'flight removed');

        // flight_report should have been cascade deleted
        $this->assertNull(FlightReport::findOne($freport->id), 'flight_report cascade removed');

        // acars file should be gone
        $this->assertNull(AcarsFile::findOne(['flight_report_id' => $freport->id]), 'acars cascade removed');

        // flight_phase should be gone
        $this->assertNull(FlightPhase::findOne($phase->id), 'flight_phase cascade removed');

        // flight event & data should be gone
        $this->assertNull(FlightEvent::findOne($fe->id), 'flight_event cascade removed');
        $this->assertNull(FlightEventData::findOne(['event_id' => $fe->id, 'attribute_id' => $feAttr->id]), 'flight_event_data cascade removed');

        // flight phase metric should be gone (it cascades via flight_phase)
        $this->assertNull(FlightPhaseMetric::findOne(['flight_phase_id' => $phase->id, 'metric_type_id' => $metricType->id]), 'flight_phase_metric cascade removed');

        // phase issue should be gone as well
        $this->assertNull(FlightPhaseIssue::findOne($phaseIssue->id), 'flight_phase_issue cascade removed');

        // 3.b Now delete SubmittedFlightPlan explicitly (it references aircraft and pilot; pilot deletion later will cascade submitted plan too but we removed flight which blocked pilot)
        $this->assertNotFalse($sfp->delete(), 'submitted flight plan deleted');

        // 3.c Delete Pilot -> this should now succeed and cascade to pilot_tour_completion (ON DELETE CASCADE) and to any submitted_flight_plans (if any left)
        $this->assertNotFalse($pilot->delete(), 'pilot deleted after flight removed');

        $this->assertNull(Pilot::findOne($pilot->id), 'pilot removed');

        // pilot_tour_completion must be gone (cascade)
        $this->assertNull(PilotTourCompletion::findOne($ptc->id), 'pilot_tour_completion cascade removed');

        // submitted flight plan is deleted already (we removed), assert absence
        $this->assertNull(SubmittedFlightPlan::findOne($sfp->id), 'submitted flight plan removed');

        // 3.d Delete TourStage -> then delete Tour (tour_stage had blocked tour)
        $this->assertNotFalse($stage->delete(), 'tour stage deleted');
        $this->assertNull(TourStage::findOne($stage->id), 'tour stage removed');

        // Now Tour should delete (pilot_tour_completion was cascade removed when pilot was deleted; even if present it would cascade)
        $this->assertNotFalse($tour->delete(), 'tour deleted');
        $this->assertNull(Tour::findOne($tour->id), 'tour removed');

        // 3.e Delete Route now that submitted plans referencing it were removed
        $this->assertNotFalse($route->delete(), 'route deleted');
        $this->assertNull(Route::findOne($route->id), 'route removed');

        // 3.f Delete aircraft -> aircraft_configuration -> aircraft_type
        // Aircraft has no ON DELETE on configuration, but we can remove aircraft then configuration
        $this->assertNotFalse($aircraft->delete(), 'aircraft deleted');
        $this->assertNull(Aircraft::findOne($aircraft->id), 'aircraft removed');

        $this->assertNotFalse($aconf->delete(), 'aircraft configuration deleted');
        $this->assertNull(AircraftConfiguration::findOne($aconf->id), 'aircraft configuration removed');

        $this->assertNotFalse($atype->delete(), 'aircraft type deleted');
        $this->assertNull(AircraftType::findOne($atype->id), 'aircraft type removed');

        // 3.g Delete page -> page_content should cascade
        $this->assertNotFalse($page->delete(), 'page deleted');
        $this->assertNull(PageContent::findOne($pageContent->id), 'page_content cascade removed');

        // 3.h Delete flight phase type -> its langs & metric types & metric type langs should cascade
        $this->assertNotFalse($phaseType->delete(), 'phase type deleted');
        $this->assertNull(FlightPhaseType::findOne($phaseType->id), 'phase type removed');
        $this->assertNull(FlightPhaseTypeLang::findOne($phaseTypeLang->id), 'phase type lang cascade removed');
        $this->assertNull(FlightPhaseMetricType::findOne($metricType->id), 'metric type cascade removed');
        $this->assertNull(FlightPhaseMetricTypeLang::findOne($metricTypeLang->id), 'metric type lang cascade removed');

        // 3.i Issue type deletion should cascade its langs but issue references from phase_issue already removed earlier
        $this->assertNotFalse($issueType->delete(), 'issue type deleted');
        $this->assertNull(IssueType::findOne($issueType->id), 'issue type removed');
        $this->assertNull(IssueTypeLang::findOne($issueTypeLang->id), 'issue type lang cascade removed');

        // 3.j Image has no FK, so delete it directly
        $this->assertNotFalse($image->delete(), 'image deleted');
        $this->assertNull(Image::findOne($image->id), 'image removed');

        // 3.k Now try deleting airports: they should be deletable after all dependents removed
        $this->assertNotFalse($lemd->delete(), 'lemd deleted');
        $this->assertNull(Airport::findOne($lemd->id), 'lemd removed');

        $this->assertNotFalse($lebl->delete(), 'lebl deleted');
        $this->assertNull(Airport::findOne($lebl->id), 'lebl removed');

        $this->assertNotFalse($levd->delete(), 'levd deleted');
        $this->assertNull(Airport::findOne($levd->id), 'levd removed');

        $this->assertNotFalse($levc->delete(), 'levc deleted');
        $this->assertNull(Airport::findOne($levc->id), 'levc removed');

        $this->assertNotFalse($leal->delete(), 'leal deleted');
        $this->assertNull(Airport::findOne($leal->id), 'leal removed');

        // 3.l Delete rank
        $this->assertNotFalse($rank->delete(), 'rank deleted');
        $this->assertNull(Rank::findOne($rank->id), 'rank removed');

        // Finally delete country (should succeed now)
        $this->assertNotFalse($country->delete(), 'country deleted');
        $this->assertNull(Country::findOne($country->id), 'country removed');
    }
}
