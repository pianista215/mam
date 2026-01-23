<?php

namespace tests\functional\acarsUpdater;

use app\config\Config;
use app\config\ConfigHelper as CK;
use tests\fixtures\AuthAssignmentFixture;
use Yii;

class AcarsUpdaterInstallerCest
{
    private string $testReleasesPath;

    public function _fixtures()
    {
        return [
            'authAssignment' => AuthAssignmentFixture::class,
        ];
    }

    public function _before(\FunctionalTester $I)
    {
        $this->testReleasesPath = '/tmp/acars-releases-test';

        if (!is_dir($this->testReleasesPath)) {
            mkdir($this->testReleasesPath, 0777, true);
        }

        Config::set(CK::ACARS_RELEASES_PATH, $this->testReleasesPath);
        Config::set(CK::ACARS_INSTALLER_NAME, 'TestSetup.exe');
        Yii::$app->cache->flush();
    }

    public function _after(\FunctionalTester $I)
    {
        $installerPath = $this->testReleasesPath . '/TestSetup.exe';
        if (file_exists($installerPath)) {
            unlink($installerPath);
        }

        if (is_dir($this->testReleasesPath)) {
            rmdir($this->testReleasesPath);
        }
    }

    public function accessAsGuestRedirectsToLogin(\FunctionalTester $I)
    {
        $I->amOnRoute('acars-updater/installer');

        $I->seeCurrentUrlMatches('~login~');
        $I->see('Login');
    }

    public function accessAsUserWhenInstallerNotExistsReturns404(\FunctionalTester $I)
    {
        $I->amLoggedInAs(1);
        $I->amOnRoute('acars-updater/installer');

        $I->seeResponseCodeIs(404);
    }

    public function accessAsUserWhenInstallerExistsDownloadsFile(\FunctionalTester $I)
    {
        $installerPath = $this->testReleasesPath . '/TestSetup.exe';
        file_put_contents($installerPath, 'fake installer content');

        $I->amLoggedInAs(1);
        $I->amOnRoute('acars-updater/installer');

        $I->seeResponseCodeIs(200);
        $I->assertStringContainsString(
            'attachment',
            Yii::$app->response->headers->get('Content-Disposition')
        );
    }

    public function accessAsAdminWhenInstallerExistsDownloadsFile(\FunctionalTester $I)
    {
        $installerPath = $this->testReleasesPath . '/TestSetup.exe';
        file_put_contents($installerPath, 'fake installer content');

        $I->amLoggedInAs(2);
        $I->amOnRoute('acars-updater/installer');

        $I->seeResponseCodeIs(200);
        $I->assertStringContainsString(
            'attachment',
            Yii::$app->response->headers->get('Content-Disposition')
        );
    }
}
