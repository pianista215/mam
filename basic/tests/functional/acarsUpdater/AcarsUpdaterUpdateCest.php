<?php

namespace tests\functional\acarsUpdater;

use app\config\Config;
use app\config\ConfigHelper as CK;
use app\models\Pilot;
use tests\fixtures\AuthAssignmentFixture;
use Yii;

class AcarsUpdaterUpdateCest
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
        Yii::$app->cache->flush();
    }

    public function _after(\FunctionalTester $I)
    {
        $files = ['RELEASES', 'releases.stable.json', 'TestApp-1.0.0-full.nupkg'];
        foreach ($files as $file) {
            $filePath = $this->testReleasesPath . '/' . $file;
            if (file_exists($filePath)) {
                unlink($filePath);
            }
        }

        if (is_dir($this->testReleasesPath)) {
            rmdir($this->testReleasesPath);
        }
    }

    private function getAccessToken(int $userId): string
    {
        return Pilot::find()->where(['id' => $userId])->one()->access_token;
    }

    public function accessWithoutBearerTokenReturns404(\FunctionalTester $I)
    {
        $I->amOnRoute('acars-updater/update', ['file' => 'RELEASES']);

        $I->seeResponseCodeIs(404);
    }

    public function accessWithInvalidBearerTokenReturns404(\FunctionalTester $I)
    {
        $I->haveHttpHeader('Authorization', 'Bearer invalid_token_12345');
        $I->amOnRoute('acars-updater/update', ['file' => 'RELEASES']);

        $I->seeResponseCodeIs(404);
    }

    public function accessWithEmptyBearerTokenReturns404(\FunctionalTester $I)
    {
        $I->haveHttpHeader('Authorization', 'Bearer ');
        $I->amOnRoute('acars-updater/update', ['file' => 'RELEASES']);

        $I->seeResponseCodeIs(404);
    }

    public function accessWithValidTokenButFileMissingReturns404(\FunctionalTester $I)
    {
        $token = $this->getAccessToken(1);
        $I->haveHttpHeader('Authorization', 'Bearer ' . $token);
        $I->amOnRoute('acars-updater/update', ['file' => 'RELEASES']);

        $I->seeResponseCodeIs(404);
    }

    public function accessWithValidTokenDownloadsReleasesFile(\FunctionalTester $I)
    {
        $releasesPath = $this->testReleasesPath . '/RELEASES';
        file_put_contents($releasesPath, 'fake releases content');

        $token = $this->getAccessToken(1);
        $I->haveHttpHeader('Authorization', 'Bearer ' . $token);
        $I->amOnRoute('acars-updater/update', ['file' => 'RELEASES']);

        $I->seeResponseCodeIs(200);
        $I->assertStringContainsString(
            'attachment',
            Yii::$app->response->headers->get('Content-Disposition')
        );
    }

    public function accessWithValidTokenDownloadsJsonFile(\FunctionalTester $I)
    {
        $jsonPath = $this->testReleasesPath . '/releases.stable.json';
        file_put_contents($jsonPath, '{"version": "1.0.0"}');

        $token = $this->getAccessToken(1);
        $I->haveHttpHeader('Authorization', 'Bearer ' . $token);
        $I->amOnRoute('acars-updater/update', ['file' => 'releases.stable.json']);

        $I->seeResponseCodeIs(200);
        $I->assertStringContainsString(
            'attachment',
            Yii::$app->response->headers->get('Content-Disposition')
        );
    }

    public function accessWithValidTokenDownloadsNupkgFile(\FunctionalTester $I)
    {
        $nupkgPath = $this->testReleasesPath . '/TestApp-1.0.0-full.nupkg';
        file_put_contents($nupkgPath, 'fake nupkg content');

        $token = $this->getAccessToken(1);
        $I->haveHttpHeader('Authorization', 'Bearer ' . $token);
        $I->amOnRoute('acars-updater/update', ['file' => 'TestApp-1.0.0-full.nupkg']);

        $I->seeResponseCodeIs(200);
        $I->assertStringContainsString(
            'attachment',
            Yii::$app->response->headers->get('Content-Disposition')
        );
    }

    public function accessWithValidTokenButDisallowedExtensionReturns404(\FunctionalTester $I)
    {
        $exePath = $this->testReleasesPath . '/malicious.exe';
        file_put_contents($exePath, 'fake exe content');

        $token = $this->getAccessToken(1);
        $I->haveHttpHeader('Authorization', 'Bearer ' . $token);
        $I->amOnRoute('acars-updater/update', ['file' => 'malicious.exe']);

        $I->seeResponseCodeIs(404);

        unlink($exePath);
    }

    public function accessWithValidTokenButDirectoryTraversalReturns404(\FunctionalTester $I)
    {
        $token = $this->getAccessToken(1);
        $I->haveHttpHeader('Authorization', 'Bearer ' . $token);
        $I->amOnRoute('acars-updater/update', ['file' => '../../../etc/passwd']);

        $I->seeResponseCodeIs(404);
    }

    public function accessWithValidTokenButEmptyFileParamReturns404(\FunctionalTester $I)
    {
        $token = $this->getAccessToken(1);
        $I->haveHttpHeader('Authorization', 'Bearer ' . $token);
        $I->amOnRoute('acars-updater/update', ['file' => '']);

        $I->seeResponseCodeIs(404);
    }
}
