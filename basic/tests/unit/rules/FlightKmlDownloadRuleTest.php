<?php

namespace tests\unit\rules;

use app\models\Flight;
use app\rbac\constants\Permissions;
use app\rbac\constants\Roles;
use app\rbac\rules\FlightKmlDownloadRule;
use tests\unit\BaseUnitTest;
use Yii;

class FlightKmlDownloadRuleTest extends BaseUnitTest
{
    private const OWNER = 1;
    private const OTHER_PILOT = 2;
    private const VFR_VALIDATOR = 3;
    private const IFR_VALIDATOR = 4;
    private const ADMIN = 5;
    private const VALIDATOR_WITHOUT_PILOT_ROLE = 6;

    protected function _before()
    {
        parent::_before();

        $auth = Yii::$app->authManager;
        $auth->removeAll();

        // Same hierarchy as the RBAC migrations
        $rule = new FlightKmlDownloadRule();
        $auth->add($rule);
        $download = $auth->createPermission(Permissions::DOWNLOAD_FLIGHT_KML);
        $download->ruleName = $rule->name;
        $auth->add($download);

        $validateVfr = $auth->createPermission(Permissions::VALIDATE_VFR_FLIGHT);
        $auth->add($validateVfr);
        $validateIfr = $auth->createPermission(Permissions::VALIDATE_IFR_FLIGHT);
        $auth->add($validateIfr);

        $pilot = $auth->createRole(Roles::PILOT);
        $auth->add($pilot);
        $auth->addChild($pilot, $download);

        $vfrValidator = $auth->createRole(Roles::VFR_VALIDATOR);
        $auth->add($vfrValidator);
        $auth->addChild($vfrValidator, $validateVfr);
        $auth->addChild($vfrValidator, $download);

        $ifrValidator = $auth->createRole(Roles::IFR_VALIDATOR);
        $auth->add($ifrValidator);
        $auth->addChild($ifrValidator, $validateIfr);
        $auth->addChild($ifrValidator, $download);

        $admin = $auth->createRole(Roles::ADMIN);
        $auth->add($admin);
        $auth->addChild($admin, $pilot);
        $auth->addChild($admin, $vfrValidator);
        $auth->addChild($admin, $ifrValidator);

        $auth->assign($pilot, self::OWNER);
        $auth->assign($pilot, self::OTHER_PILOT);
        $auth->assign($pilot, self::VFR_VALIDATOR);
        $auth->assign($vfrValidator, self::VFR_VALIDATOR);
        $auth->assign($pilot, self::IFR_VALIDATOR);
        $auth->assign($ifrValidator, self::IFR_VALIDATOR);
        $auth->assign($admin, self::ADMIN);
        $auth->assign($ifrValidator, self::VALIDATOR_WITHOUT_PILOT_ROLE);
    }

    private function flight(string $rules, int $pilotId = self::OWNER): Flight
    {
        return new Flight(['id' => 1, 'pilot_id' => $pilotId, 'flight_rules' => $rules]);
    }

    private function canDownload(?int $userId, ?Flight $flight, bool $withParam = true): bool
    {
        return Yii::$app->authManager->checkAccess(
            $userId,
            Permissions::DOWNLOAD_FLIGHT_KML,
            $withParam ? ['flight' => $flight] : []
        );
    }

    public function testGuestCannotDownload()
    {
        $this->assertFalse($this->canDownload(null, $this->flight('I')));
    }

    public function testOwnerCanDownload()
    {
        $this->assertTrue($this->canDownload(self::OWNER, $this->flight('I')));
        $this->assertTrue($this->canDownload(self::OWNER, $this->flight('V')));
    }

    public function testOtherPilotCannotDownload()
    {
        $this->assertFalse($this->canDownload(self::OTHER_PILOT, $this->flight('I')));
        $this->assertFalse($this->canDownload(self::OTHER_PILOT, $this->flight('V')));
    }

    public function testVfrValidatorOnlyVfrFlights()
    {
        $this->assertTrue($this->canDownload(self::VFR_VALIDATOR, $this->flight('V')));
        $this->assertFalse($this->canDownload(self::VFR_VALIDATOR, $this->flight('I')));
    }

    public function testIfrValidatorOnlyIfrFlights()
    {
        $this->assertTrue($this->canDownload(self::IFR_VALIDATOR, $this->flight('I')));
        $this->assertFalse($this->canDownload(self::IFR_VALIDATOR, $this->flight('V')));
    }

    public function testValidatorCanDownloadOwnFlightOfOtherRules()
    {
        $this->assertTrue($this->canDownload(self::VFR_VALIDATOR, $this->flight('I', self::VFR_VALIDATOR)));
    }

    public function testValidatorWithoutPilotRoleCanDownload()
    {
        $this->assertTrue($this->canDownload(self::VALIDATOR_WITHOUT_PILOT_ROLE, $this->flight('I')));
        $this->assertFalse($this->canDownload(self::VALIDATOR_WITHOUT_PILOT_ROLE, $this->flight('V')));
    }

    public function testAdminCanDownloadBothRules()
    {
        $this->assertTrue($this->canDownload(self::ADMIN, $this->flight('I')));
        $this->assertTrue($this->canDownload(self::ADMIN, $this->flight('V')));
    }

    public function testNullFlightReturnsFalse()
    {
        $this->assertFalse($this->canDownload(self::OWNER, null));
    }

    public function testMissingFlightParamReturnsFalse()
    {
        $this->assertFalse($this->canDownload(self::OWNER, null, false));
    }
}
