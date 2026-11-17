<?php

namespace tests\functional\flight;

use tests\fixtures\AuthAssignmentFixture;
use tests\fixtures\ConfigFixture;
use tests\fixtures\FlightReportFixture;
use Yii;

/**
 * Black box KML download (flight/kml).
 *
 * Flight 3: pilot 7, IFR, pending validation; black box data is created in _before().
 * Flight 1: pilot 5, IFR, finished, no black box data.
 * Users (AuthAssignmentFixture): 2 = admin, 4 = VFR validator, 5 = IFR validator, 6 = pilot.
 */
class FlightKmlCest
{
    private const FLIGHT_WITH_ACARS = 3;
    private const OWNER = 7;
    private const ADMIN = 2;
    private const VFR_VALIDATOR = 4;
    private const IFR_VALIDATOR = 5;
    private const OTHER_PILOT = 6;

    public function _fixtures()
    {
        return [
            'authAssignment' => AuthAssignmentFixture::class,
            'config' => ConfigFixture::class,
            'flightReport' => FlightReportFixture::class,
        ];
    }

    public function _before(\FunctionalTester $I)
    {
        $this->createBlackBox(self::FLIGHT_WITH_ACARS);
    }

    private function phaseTypeId(string $code): int
    {
        $db = Yii::$app->db;
        $id = $db->createCommand('SELECT id FROM flight_phase_type WHERE code = :c', [':c' => $code])->queryScalar();
        if ($id === false) {
            $db->createCommand()->insert('flight_phase_type', ['code' => $code])->execute();
            $id = $db->getLastInsertID();
        }
        foreach (['en', 'es'] as $language) {
            $exists = $db->createCommand(
                'SELECT 1 FROM flight_phase_type_lang WHERE flight_phase_type_id = :id AND language = :l',
                [':id' => $id, ':l' => $language]
            )->queryScalar();
            if ($exists === false) {
                $db->createCommand()->insert('flight_phase_type_lang', [
                    'flight_phase_type_id' => $id,
                    'language' => $language,
                    'name' => ucfirst($code),
                ])->execute();
            }
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

    private function createBlackBox(int $reportId): void
    {
        $phases = [
            'taxi' => [
                '2025-01-02 01:11:00' => ['Latitude' => '40.47', 'Longitude' => '-3.56', 'Altitude' => '2000', 'AGLAltitude' => '0', 'onGround' => 'True', 'Gear' => 'Down'],
                '2025-01-02 01:12:00' => ['Latitude' => '40.48', 'Longitude' => '-3.57', 'Altitude' => '2000', 'AGLAltitude' => '0', 'onGround' => 'True'],
            ],
            'takeoff' => [
                '2025-01-02 01:13:00' => ['Latitude' => '40.49', 'Longitude' => '-3.58', 'Altitude' => '2500', 'AGLAltitude' => '500', 'onGround' => 'False'],
                '2025-01-02 01:14:00' => ['Latitude' => '40.50', 'Longitude' => '-3.59', 'Altitude' => '5000', 'AGLAltitude' => '3000', 'onGround' => 'False', 'Gear' => 'Up'],
            ],
        ];
        $db = Yii::$app->db;
        foreach ($phases as $type => $events) {
            $timestamps = array_keys($events);
            $db->createCommand()->insert('flight_phase', [
                'flight_report_id' => $reportId,
                'flight_phase_type_id' => $this->phaseTypeId($type),
                'start' => reset($timestamps),
                'end' => end($timestamps),
            ])->execute();
            $phaseId = $db->getLastInsertID();
            foreach ($events as $timestamp => $values) {
                $db->createCommand()->insert('flight_event', ['phase_id' => $phaseId, 'timestamp' => $timestamp])->execute();
                $eventId = $db->getLastInsertID();
                foreach ($values as $code => $value) {
                    $db->createCommand()->insert('flight_event_data', [
                        'event_id' => $eventId,
                        'attribute_id' => $this->attributeId($code),
                        'value' => $value,
                    ])->execute();
                }
            }
        }
    }

    private function seeKml(\FunctionalTester $I): void
    {
        $I->seeResponseCodeIs(200);
        $headers = Yii::$app->response->headers;
        $I->assertSame('application/vnd.google-earth.kml+xml', $headers->get('Content-Type'));
        $I->assertSame('attachment; filename="flight-3-LEMD-LEBL.kml"', $headers->get('Content-Disposition'));
        $body = $I->grabPageSource();
        $I->assertStringContainsString('<kml xmlns="http://www.opengis.net/kml/2.2">', $body);
        $I->assertStringContainsString('<name>LEMD - LEBL - Route F003</name>', $body);
        $I->assertNotFalse(simplexml_load_string($body));
    }

    // ---------- download ----------

    public function ownerCanDownload(\FunctionalTester $I)
    {
        $I->amLoggedInAs(self::OWNER);
        $I->amOnRoute('flight/kml', ['id' => self::FLIGHT_WITH_ACARS]);

        $this->seeKml($I);
    }

    public function validatorOfFlightRulesCanDownload(\FunctionalTester $I)
    {
        $I->amLoggedInAs(self::IFR_VALIDATOR);
        $I->amOnRoute('flight/kml', ['id' => self::FLIGHT_WITH_ACARS]);

        $this->seeKml($I);
    }

    public function adminCanDownload(\FunctionalTester $I)
    {
        $I->amLoggedInAs(self::ADMIN);
        $I->amOnRoute('flight/kml', ['id' => self::FLIGHT_WITH_ACARS]);

        $this->seeKml($I);
    }

    public function validatorOfOtherFlightRulesCannotDownload(\FunctionalTester $I)
    {
        $I->amLoggedInAs(self::VFR_VALIDATOR);
        $I->amOnRoute('flight/kml', ['id' => self::FLIGHT_WITH_ACARS]);

        $I->seeResponseCodeIs(403);
    }

    public function otherPilotCannotDownload(\FunctionalTester $I)
    {
        $I->amLoggedInAs(self::OTHER_PILOT);
        $I->amOnRoute('flight/kml', ['id' => self::FLIGHT_WITH_ACARS]);

        $I->seeResponseCodeIs(403);
    }

    public function guestIsSentToLogin(\FunctionalTester $I)
    {
        $I->amOnRoute('flight/kml', ['id' => self::FLIGHT_WITH_ACARS]);

        $I->seeCurrentUrlMatches('~login~');
    }

    public function flightWithoutBlackBoxIsNotFound(\FunctionalTester $I)
    {
        $I->amLoggedInAs(5); // owner of flight 1
        $I->amOnRoute('flight/kml', ['id' => 1]);

        $I->seeResponseCodeIs(404);
    }

    public function unknownFlightIsNotFound(\FunctionalTester $I)
    {
        $I->amLoggedInAs(self::OWNER);
        $I->amOnRoute('flight/kml', ['id' => 999999]);

        $I->seeResponseCodeIs(404);
    }

    // ---------- button ----------

    public function buttonVisibleForOwner(\FunctionalTester $I)
    {
        $I->amLoggedInAs(self::OWNER);
        $I->amOnRoute('flight/view', ['id' => self::FLIGHT_WITH_ACARS]);

        $I->seeResponseCodeIs(200);
        $I->seeElement('#kml-download');
        $I->see('Download KML (Google Earth)');
    }

    public function buttonVisibleForValidatorOfFlightRules(\FunctionalTester $I)
    {
        $I->amLoggedInAs(self::IFR_VALIDATOR);
        $I->amOnRoute('flight/view', ['id' => self::FLIGHT_WITH_ACARS]);

        $I->seeElement('#kml-download');
    }

    public function buttonHiddenForOtherPilot(\FunctionalTester $I)
    {
        $I->amLoggedInAs(self::OTHER_PILOT);
        $I->amOnRoute('flight/view', ['id' => self::FLIGHT_WITH_ACARS]);

        $I->seeResponseCodeIs(200);
        $I->dontSeeElement('#kml-download');
    }

    public function buttonHiddenForValidatorOfOtherFlightRules(\FunctionalTester $I)
    {
        $I->amLoggedInAs(self::VFR_VALIDATOR);
        $I->amOnRoute('flight/view', ['id' => self::FLIGHT_WITH_ACARS]);

        $I->dontSeeElement('#kml-download');
    }

    public function buttonHiddenWithoutBlackBox(\FunctionalTester $I)
    {
        $I->amLoggedInAs(5);
        $I->amOnRoute('flight/view', ['id' => 1]);

        $I->dontSeeElement('#kml-download');
    }
}
