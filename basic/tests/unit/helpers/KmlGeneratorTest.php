<?php

namespace tests\unit\helpers;

use app\helpers\FlightEventMarkers;
use app\helpers\KmlGenerator;
use app\models\Aircraft;
use app\models\Airport;
use app\models\Flight;
use app\models\FlightReport;
use app\models\Pilot;
use tests\unit\BaseUnitTest;
use Yii;

class KmlGeneratorTest extends BaseUnitTest
{
    private const KML_NS = 'http://www.opengis.net/kml/2.2';
    private const TEST_REPORT_ID = 987654;

    // ---------- helpers ----------

    private function makeFlight(array $attributes = []): Flight
    {
        $flight = new Flight(array_merge([
            'id' => 1,
            'pilot_id' => 1,
            'code' => 'F001',
            'departure' => 'LEMD',
            'arrival' => 'LEBL',
            'flight_rules' => 'I',
            'flight_type' => Flight::TYPE_ROUTE,
            'creation_date' => '2026-01-01 10:00:00',
        ], $attributes));
        $flight->populateRelation('pilot', new Pilot(['name' => 'John', 'surname' => 'Doe']));
        $flight->populateRelation('aircraft', new Aircraft(['registration' => 'EC-ABC', 'name' => 'A320 Madrid']));
        $flight->populateRelation('departure0', new Airport(['icao_code' => 'LEMD', 'name' => 'Madrid', 'latitude' => 40.47, 'longitude' => -3.56]));
        $flight->populateRelation('arrival0', new Airport(['icao_code' => 'LEBL', 'name' => 'Barcelona', 'latitude' => 41.29, 'longitude' => 2.07]));
        $flight->populateRelation('tourStage', null);
        $flight->populateRelation('client', null);
        return $flight;
    }

    private function event(string $time, int $phaseId, string $phase, array $values): array
    {
        return ['timestamp' => '2026-01-01 ' . $time, 'phase_id' => $phaseId, 'phase' => $phase, 'values' => $values];
    }

    private function pos(float $lat, float $lon, $alt, $agl, bool $onGround = false, array $extra = []): array
    {
        $values = ['Latitude' => (string)$lat, 'Longitude' => (string)$lon, 'onGround' => $onGround ? 'True' : 'False'];
        if ($alt !== null) {
            $values['Altitude'] = (string)$alt;
        }
        if ($agl !== null) {
            $values['AGLAltitude'] = (string)$agl;
        }
        return array_merge($values, $extra);
    }

    /** A short flight: taxi (ground), takeoff (ground → < 1000 ft → above), cruise. */
    private function sampleEvents(): array
    {
        return [
            $this->event('10:00:00', 1, 'taxi', $this->pos(40.00, -3.00, 2000, 0, true, ['Flaps' => '0', 'Gear' => 'Down'])),
            $this->event('10:01:00', 1, 'taxi', $this->pos(40.01, -3.01, 2000, 0, true, ['Flaps' => '5'])),
            $this->event('10:02:00', 2, 'takeoff', $this->pos(40.02, -3.02, 2000, 0, true)),
            $this->event('10:03:00', 2, 'takeoff', $this->pos(40.03, -3.03, 2500, 500, false, ['IASKnots' => '150'])),
            $this->event('10:04:00', 2, 'takeoff', $this->pos(40.04, -3.04, 5000, 3000, false, ['Gear' => 'Up'])),
            $this->event('10:05:00', 3, 'cruise', $this->pos(40.10, -3.10, 30000, 28000, false, ['FuelKg' => '4275,477725'])),
            $this->event('10:06:00', 3, 'cruise', $this->pos(40.20, -3.20, 30000, 29000, false)),
        ];
    }

    private function generate(array $events, array $issues = [], ?Flight $flight = null): string
    {
        $kml = KmlGenerator::generate(
            $flight ?? $this->makeFlight(),
            $events,
            FlightEventMarkers::extract($events),
            ['taxi' => 'Taxi', 'takeoff' => 'Takeoff', 'cruise' => 'Cruise'],
            $issues
        );
        $this->assertNotNull($kml);
        return $kml;
    }

    private function xml(string $kml): \SimpleXMLElement
    {
        $xml = simplexml_load_string($kml);
        $this->assertNotFalse($xml, 'KML must be valid XML');
        $xml->registerXPathNamespace('k', self::KML_NS);
        return $xml;
    }

    /** Returns the coordinates of every track LineString as lists of [lon, lat, height]. */
    private function lines(\SimpleXMLElement $xml): array
    {
        $lines = [];
        foreach ($xml->xpath('//k:Folder[k:name="Track"]/k:Placemark') as $placemark) {
            $placemark->registerXPathNamespace('k', self::KML_NS);
            $coords = trim((string)$placemark->xpath('k:LineString/k:coordinates')[0]);
            $lines[(string)$placemark->name][] = array_map(
                fn($c) => array_map('floatval', explode(',', $c)),
                preg_split('/\s+/', $coords)
            );
        }
        return $lines;
    }

    private function folderPlacemarks(\SimpleXMLElement $xml, string $folder): array
    {
        return $xml->xpath('//k:Folder[k:name="' . $folder . '"]//k:Placemark');
    }

    // ---------- general ----------

    public function testReturnsNullWithoutPositions()
    {
        $events = [$this->event('10:00:00', 1, 'taxi', ['Altitude' => '100'])];
        $this->assertNull(KmlGenerator::generate($this->makeFlight(), $events, [], []));
        $this->assertNull(KmlGenerator::generate($this->makeFlight(), [], [], []));
    }

    public function testValidXmlWithFlightTitleAndAirports()
    {
        $xml = $this->xml($this->generate($this->sampleEvents()));

        $this->assertSame('LEMD - LEBL - Route F001', (string)$xml->Document->name);
        $airports = array_map(fn($p) => (string)$p->name, $this->folderPlacemarks($xml, 'Airports'));
        $this->assertSame(['LEMD', 'LEBL'], $airports);
    }

    public function testTitleIsEscaped()
    {
        $flight = $this->makeFlight(['code' => 'A&B<C>']);
        $xml = $this->xml($this->generate($this->sampleEvents(), [], $flight));

        $this->assertSame('LEMD - LEBL - Route A&B<C>', (string)$xml->Document->name);
    }

    public function testPointsWithoutPositionAreSkipped()
    {
        $events = $this->sampleEvents();
        array_splice($events, 3, 0, [$this->event('10:03:30', 2, 'takeoff', ['Altitude' => '3000', 'AGLAltitude' => '1000'])]);
        $xml = $this->xml($this->generate($events));

        $this->assertCount(7, $xml->xpath('//k:Point[not(ancestor::k:Folder[k:name="Airports"])]'));
    }

    // ---------- track ----------

    public function testOneLinePerPhaseEvenWhenMixingGroundLowAndHigh()
    {
        $lines = $this->lines($this->xml($this->generate($this->sampleEvents())));

        $this->assertSame(['Taxi', 'Takeoff', 'Cruise'], array_keys($lines));
        foreach ($lines as $name => $phaseLines) {
            $this->assertCount(1, $phaseLines, "Phase $name must be a single line");
        }
        // takeoff: its 3 points + the first cruise point
        $this->assertCount(4, $lines['Takeoff'][0]);
    }

    public function testConsecutivePhasesAreConnected()
    {
        $lines = array_values(array_map(fn($l) => $l[0], $this->lines($this->xml($this->generate($this->sampleEvents())))));

        for ($i = 0; $i < count($lines) - 1; $i++) {
            $this->assertSame(end($lines[$i]), $lines[$i + 1][0], "Phase $i must end where phase " . ($i + 1) . ' starts');
        }
    }

    public function testSamePhaseTypeTwiceGivesTwoLines()
    {
        $events = $this->sampleEvents();
        $events[] = $this->event('10:07:00', 4, 'taxi', $this->pos(40.30, -3.30, 2000, 0, true));
        $events[] = $this->event('10:08:00', 4, 'taxi', $this->pos(40.31, -3.31, 2000, 0, true));
        $lines = $this->lines($this->xml($this->generate($events)));

        $this->assertCount(2, $lines['Taxi']);
    }

    public function testEverythingRelativeToGround()
    {
        $xml = $this->xml($this->generate($this->sampleEvents()));

        $modes = array_map('strval', $xml->xpath('//k:altitudeMode'));
        $this->assertNotEmpty($modes);
        $this->assertSame(['relativeToGround'], array_values(array_unique($modes)));
        $this->assertCount(3, $xml->xpath('//k:LineString[k:extrude="1"]'));
    }

    public function testHeightIsAglInMetersAndZeroOnGround()
    {
        $lines = $this->lines($this->xml($this->generate($this->sampleEvents())));

        $this->assertSame(0.0, $lines['Taxi'][0][0][2]);
        $this->assertSame([-3.03, 40.03, 152.4], $lines['Takeoff'][0][1]);  // 500 ft
        $this->assertSame([-3.1, 40.1, 8534.4], $lines['Cruise'][0][0]);   // 28000 ft
    }

    public function testOnGroundForcesZeroHeight()
    {
        $events = [
            $this->event('10:00:00', 1, 'taxi', $this->pos(40.00, -3.00, 2010, 10, true)),
            $this->event('10:01:00', 1, 'taxi', $this->pos(40.01, -3.01, 2010, 10, true)),
        ];
        $lines = $this->lines($this->xml($this->generate($events)));

        $this->assertSame(0.0, $lines['Taxi'][0][0][2]);
        $this->assertSame(0.0, $lines['Taxi'][0][1][2]);
    }

    public function testMissingAglUsesLastKnownTerrain()
    {
        $events = [
            $this->event('10:00:00', 2, 'takeoff', $this->pos(40.00, -3.00, 2500, 500)),  // terrain 2000 ft
            $this->event('10:01:00', 2, 'takeoff', $this->pos(40.01, -3.01, 3000, null)), // AGL = 1000 ft
        ];
        $lines = $this->lines($this->xml($this->generate($events)));

        $this->assertSame(304.8, $lines['Takeoff'][0][1][2]);
    }

    public function testMissingAglWithoutTerrainUsesAltitude()
    {
        $events = [
            $this->event('10:00:00', 3, 'cruise', $this->pos(40.00, -3.00, 1000, null)),
            $this->event('10:01:00', 3, 'cruise', $this->pos(40.01, -3.01, 1000, null)),
        ];
        $lines = $this->lines($this->xml($this->generate($events)));

        $this->assertSame(304.8, $lines['Cruise'][0][0][2]);
    }

    // ---------- event markers ----------

    public function testEventMarkersUseMapLabels()
    {
        $events = $this->sampleEvents();
        $events[] = $this->event('10:07:00', 3, 'cruise', $this->pos(40.30, -3.30, 30000, 29000, false, ['AP' => 'Off']));
        $events[] = $this->event('10:08:00', 3, 'cruise', $this->pos(40.40, -3.40, 30000, 29000, false, ['AP' => 'On']));
        $events[] = $this->event('10:09:00', 3, 'cruise', $this->pos(40.50, -3.50, 2000, 0, true, ['LandingVSFpm' => '-402', 'Gear' => 'Down']));
        $xml = $this->xml($this->generate($events));

        $placemarks = $this->folderPlacemarks($xml, 'Events');
        $names = array_map(fn($p) => (string)$p->name, $placemarks);
        $this->assertSame(['Flaps 5%', 'Lift-off', 'Gear Up', 'AP ON', 'Gear Down · Touchdown -402 fpm'], $names);
        // Several markers on one point: styled as the most relevant one
        $this->assertSame('#marker_touchdown', (string)end($placemarks)->styleUrl);
    }

    public function testKeyMarkersAreWhite()
    {
        $xml = $this->xml($this->generate($this->sampleEvents()));

        foreach (['marker_liftoff', 'marker_touchdown'] as $id) {
            $style = $xml->xpath('//k:Style[@id="' . $id . '"]')[0];
            $this->assertSame('ffffffff', (string)$style->IconStyle->color);
            $this->assertSame('ffffffff', (string)$style->LabelStyle->color);
        }
        $flaps = $xml->xpath('//k:Style[@id="marker_flaps"]')[0];
        $this->assertNotSame('ffffffff', (string)$flaps->IconStyle->color);
    }

    public function testEventPointsAreNotDuplicatedAsDots()
    {
        $xml = $this->xml($this->generate($this->sampleEvents()));

        $events = count($this->folderPlacemarks($xml, 'Events'));
        $dots = count($this->folderPlacemarks($xml, 'Event points'));
        $this->assertSame(3, $events); // flaps 5, lift-off, gear up
        $this->assertSame(7, $events + $dots);
    }

    public function testMarkerBalloonHasLongTextAndValues()
    {
        $xml = $this->xml($this->generate($this->sampleEvents()));

        $flaps = $this->folderPlacemarks($xml, 'Events')[0];
        $description = (string)$flaps->description;
        $this->assertStringContainsString('Flaps 0% → 5%', $description);
        $this->assertStringContainsString('<td><b>Time (UTC)</b></td><td>2026-01-01 10:01:00</td>', $description);
        $this->assertStringContainsString('<td><b>Phase</b></td><td>Taxi</td>', $description);
    }

    public function testDecimalValuesAreRounded()
    {
        $xml = $this->xml($this->generate($this->sampleEvents()));

        $this->assertStringContainsString('<td><b>Fuel (kg)</b></td><td>4275.5</td>', $xml->asXML());
    }

    // ---------- issues ----------

    private function issue(string $time, string $description, ?int $penalty = 10): array
    {
        return ['timestamp' => '2026-01-01 ' . $time, 'description' => $description, 'penalty' => $penalty];
    }

    public function testIssueOnPlainPointIsIconOnly()
    {
        $xml = $this->xml($this->generate($this->sampleEvents(), [$this->issue('10:06:00', 'High speed')]));

        $placemarks = $this->folderPlacemarks($xml, 'Detected Issues');
        $this->assertCount(1, $placemarks);
        $this->assertSame('#issue', (string)$placemarks[0]->styleUrl);
        $this->assertSame('⚠ High speed', (string)$placemarks[0]->name);
        $style = $xml->xpath('//k:Style[@id="issue"]')[0];
        $this->assertSame('0', (string)$style->LabelStyle->scale);
    }

    public function testIssueOnEventPointKeepsEventLabel()
    {
        $xml = $this->xml($this->generate($this->sampleEvents(), [$this->issue('10:03:00', 'Bad takeoff')]));

        $placemark = $this->folderPlacemarks($xml, 'Detected Issues')[0];
        $this->assertSame('#issue_event', (string)$placemark->styleUrl);
        $this->assertSame('Lift-off', (string)$placemark->name);
        // Not repeated in the Events folder
        $names = array_map(fn($p) => (string)$p->name, $this->folderPlacemarks($xml, 'Events'));
        $this->assertNotContains('Lift-off', $names);
    }

    public function testIssueBalloonShowsIssueAndPenaltyBeforeEventAndTable()
    {
        $xml = $this->xml($this->generate($this->sampleEvents(), [$this->issue('10:03:00', 'Bad takeoff', 20)]));

        $description = (string)$this->folderPlacemarks($xml, 'Detected Issues')[0]->description;
        $issuePos = strpos($description, '⚠ Bad takeoff (Penalty: 20)');
        $eventPos = strpos($description, 'Lift-off');
        $tablePos = strpos($description, '<table>');
        $this->assertNotFalse($issuePos);
        $this->assertNotFalse($eventPos);
        $this->assertTrue($issuePos < $eventPos && $eventPos < $tablePos);
    }

    public function testIssueWithoutPenalty()
    {
        $xml = $this->xml($this->generate($this->sampleEvents(), [$this->issue('10:06:00', 'Info', null)]));

        $description = (string)$this->folderPlacemarks($xml, 'Detected Issues')[0]->description;
        $this->assertStringContainsString('⚠ Info</b>', $description);
        $this->assertStringNotContainsString('Penalty', $description);
    }

    public function testIssueGoesToNearestPointInTime()
    {
        $xml = $this->xml($this->generate($this->sampleEvents(), [$this->issue('10:05:40', 'Near cruise end')]));

        $description = (string)$this->folderPlacemarks($xml, 'Detected Issues')[0]->description;
        $this->assertStringContainsString('2026-01-01 10:06:00', $description);
    }

    public function testSeveralIssuesOnSamePointAreOnePlacemark()
    {
        $issues = [$this->issue('10:06:00', 'First'), $this->issue('10:06:00', 'Second')];
        $xml = $this->xml($this->generate($this->sampleEvents(), $issues));

        $placemarks = $this->folderPlacemarks($xml, 'Detected Issues');
        $this->assertCount(1, $placemarks);
        $this->assertSame('⚠ First · Second', (string)$placemarks[0]->name);
    }

    public function testIssuePointsAreNotDuplicatedAsDots()
    {
        $xml = $this->xml($this->generate($this->sampleEvents(), [$this->issue('10:06:00', 'High speed')]));

        $issues = count($this->folderPlacemarks($xml, 'Detected Issues'));
        $events = count($this->folderPlacemarks($xml, 'Events'));
        $dots = count($this->folderPlacemarks($xml, 'Event points'));
        $this->assertSame(7, $issues + $events + $dots);
    }

    public function testNoIssuesFolderWithoutIssues()
    {
        $xml = $this->xml($this->generate($this->sampleEvents()));

        $this->assertEmpty($xml->xpath('//k:Folder[k:name="Detected Issues"]'));
    }

    // ---------- loadEvents (database) ----------

    private function phaseTypeId(string $code): int
    {
        $db = Yii::$app->db;
        $id = $db->createCommand('SELECT id FROM flight_phase_type WHERE code = :c', [':c' => $code])->queryScalar();
        if ($id === false) {
            $db->createCommand()->insert('flight_phase_type', ['code' => $code])->execute();
            $id = $db->getLastInsertID();
        }
        return (int)$id;
    }

    private function attributeId(string $code): int
    {
        $db = Yii::$app->db;
        $id = $db->createCommand('SELECT id FROM flight_event_attribute WHERE code = :c', [':c' => $code])->queryScalar();
        if ($id === false) {
            $db->createCommand()->insert('flight_event_attribute', ['code' => $code, 'name' => $code])->execute();
            $id = $db->getLastInsertID();
        }
        return (int)$id;
    }

    private function insertPhase(string $type, string $start, array $events): int
    {
        $db = Yii::$app->db;
        $db->createCommand()->insert('flight_phase', [
            'flight_report_id' => self::TEST_REPORT_ID,
            'flight_phase_type_id' => $this->phaseTypeId($type),
            'start' => $start,
            'end' => $start,
        ])->execute();
        $phaseId = (int)$db->getLastInsertID();
        foreach ($events as $timestamp => $values) {
            $db->createCommand()->insert('flight_event', ['phase_id' => $phaseId, 'timestamp' => $timestamp])->execute();
            $eventId = (int)$db->getLastInsertID();
            foreach ($values as $code => $value) {
                $db->createCommand()->insert('flight_event_data', [
                    'event_id' => $eventId,
                    'attribute_id' => $this->attributeId($code),
                    'value' => $value,
                ])->execute();
            }
        }
        return $phaseId;
    }

    public function testLoadEventsGroupsValuesAndOrdersByPhaseAndTime()
    {
        $db = Yii::$app->db;
        $db->createCommand('SET FOREIGN_KEY_CHECKS = 0')->execute();
        try {
            // Inserted out of order on purpose
            $cruise = $this->insertPhase('cruise', '2026-01-01 10:10:00', [
                '2026-01-01 10:12:00' => ['Latitude' => '41', 'Longitude' => '-2'],
                '2026-01-01 10:11:00' => ['Latitude' => '40.5', 'Longitude' => '-2.5', 'Altitude' => '30000'],
            ]);
            $taxi = $this->insertPhase('taxi', '2026-01-01 10:00:00', [
                '2026-01-01 10:00:00' => ['Latitude' => '40', 'Longitude' => '-3', 'onGround' => 'True'],
            ]);

            $events = KmlGenerator::loadEvents(new FlightReport(['id' => self::TEST_REPORT_ID]));

            $this->assertCount(3, $events);
            $this->assertSame(['2026-01-01 10:00:00', '2026-01-01 10:11:00', '2026-01-01 10:12:00'], array_column($events, 'timestamp'));
            $this->assertSame([$taxi, $cruise, $cruise], array_column($events, 'phase_id'));
            $this->assertSame(['taxi', 'cruise', 'cruise'], array_column($events, 'phase'));
            // The order of the values inside an event is not guaranteed by the query
            $values = $events[1]['values'];
            ksort($values);
            $this->assertSame(['Altitude' => '30000', 'Latitude' => '40.5', 'Longitude' => '-2.5'], $values);
        } finally {
            $db->createCommand()->delete('flight_phase', ['flight_report_id' => self::TEST_REPORT_ID])->execute();
            $db->createCommand('SET FOREIGN_KEY_CHECKS = 1')->execute();
        }
    }

    public function testLoadEventsEmptyReport()
    {
        $this->assertSame([], KmlGenerator::loadEvents(new FlightReport(['id' => self::TEST_REPORT_ID])));
    }
}
