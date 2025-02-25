<?php

namespace tests\api\v1\flightReport;

use app\config\Config;
use app\modules\api\dto\v1\response\FlightPlanDTO;
use tests\fixtures\AcarsFileFixture;
use tests\fixtures\AuthAssignmentFixture;
use \ApiTester;

class UploadChunkCest
{
    public function _fixtures()
    {
        return [
            'acarsFile' => AcarsFileFixture::class,
            'authAssignment' => AuthAssignmentFixture::class,
        ];
    }

    private function loginAsUser($id, ApiTester $I)
    {
        $access_token = \app\models\Pilot::find()->where(['id' => $id])->one()->access_token;
        $I->amBearerAuthenticated($access_token);
    }

    private function getTestFilePath($fileName)
    {
        Config::set('chunks_storage_path', '/tmp/chunk_tests');
        shell_exec("rm -rf /tmp/chunk_tests");
        return codecept_data_dir('chunks') . DIRECTORY_SEPARATOR . $fileName;
    }

    public function testUserUnauthenticated(ApiTester $I)
    {
        $filePath = $this->getTestFilePath('2_1.tmp');
        $I->sendPOST('/flight-report/upload-chunk/?flight_report_id=2&chunk_id=1', [], ['chunkFile' => $filePath]);
        $I->seeResponseCodeIs(401);
        $I->seeResponseContainsJson([
            'name' => 'Unauthorized',
            'message' => 'Your request was made with invalid credentials.',
        ]);
    }

    public function testUserAuthenticatedTryingOtherUserFlightReport(ApiTester $I)
    {
        $this->loginAsUser(1, $I);
        $filePath = $this->getTestFilePath('2_1.tmp');
        $I->sendPOST('/flight-report/upload-chunk/?flight_report_id=2&chunk_id=1', [], ['chunkFile' => $filePath]);
        $I->seeResponseCodeIs(404);
        $I->seeResponseContainsJson([
            'name' => 'Not Found',
            'message' => 'Flight access denied or not available for chunk uploads.',
        ]);
    }

    public function testChunkDoesNotExist(ApiTester $I)
    {
        $this->loginAsUser(5, $I);
        $filePath = $this->getTestFilePath('2_1.tmp');
        $I->sendPOST('/flight-report/upload-chunk/?flight_report_id=2&chunk_id=99', [], ['chunkFile' => $filePath]);
        $I->seeResponseCodeIs(404);
        $I->seeResponseContainsJson([
            'name' => 'Not Found',
            'message' => 'Chunk not found for this flight report.',
        ]);
    }

    public function testFlightClosed(ApiTester $I)
    {
        $this->loginAsUser(5, $I);

        $filePath = $this->getTestFilePath('2_1.tmp');
        $I->sendPOST('/flight-report/upload-chunk/?flight_report_id=1&chunk_id=1', [], ['chunkFile' => $filePath]);
        $I->seeResponseCodeIs(404);
        $I->seeResponseContainsJson([
            'name' => 'Not Found',
            'message' => 'Flight access denied or not available for chunk uploads.',
        ]);

        $this->loginAsUser(7, $I);
        $I->sendPOST('/flight-report/upload-chunk/?flight_report_id=3&chunk_id=2', [], ['chunkFile' => $filePath]);
        $I->seeResponseCodeIs(404);
        $I->seeResponseContainsJson([
            'name' => 'Not Found',
            'message' => 'Flight access denied or not available for chunk uploads.',
        ]);
    }

    public function testSha256Mismatch(ApiTester $I)
    {
        $this->loginAsUser(5, $I);
        $filePath = $this->getTestFilePath('2_1.tmp');

        $I->sendPOST('/flight-report/upload-chunk/?flight_report_id=2&chunk_id=2', [], ['chunkFile' => $filePath]);
        $I->seeResponseCodeIs(400);
        $I->seeResponseContainsJson([
            'name' => 'Bad Request',
            'message' => 'SHA256 mismatch. Expected: PmtFqus0mL0i3oG98cmbUhXanlxWY4OL8EZ7PrWjsis= Actual: jAZt2dCY/JiFOgRk5IKYJRJ7dWAw4PHzvOmtDaAd1Lk=',
        ]);
    }

    public function testValidChunkUploadPendingChunks(ApiTester $I)
    {
        $this->loginAsUser(5, $I);
        $filePath = $this->getTestFilePath('2_1.tmp');

        $I->sendPOST('/flight-report/upload-chunk/?flight_report_id=2&chunk_id=1', [], ['chunkFile' => $filePath]);
        $I->seeResponseCodeIs(200);
        $I->seeResponseContainsJson(['status' => 'success']);

        $chunk = \app\models\AcarsFile::findOne(['flight_report_id' => 2,'chunk_id' => 1]);
        $I->assertNotNull($chunk->upload_date);

        $flight_report = \app\models\FlightReport::findOne(['id' => 2]);
        $flight = \app\models\Flight::findOne(['id' => $flight_report->flight_id]);
        $I->assertEquals('C', $flight->status);
    }

    public function testValidUploadLastPendingChunk(ApiTester $I)
    {
        $this->loginAsUser(5, $I);
        $filePath = $this->getTestFilePath('5_1.tmp');

        $I->sendPOST('/flight-report/upload-chunk/?flight_report_id=5&chunk_id=1', [], ['chunkFile' => $filePath]);
        $I->seeResponseCodeIs(200);
        $I->seeResponseContainsJson(['status' => 'success']);

        $chunk = \app\models\AcarsFile::findOne(['flight_report_id' => 5,'chunk_id' => 1]);
        $I->assertNotNull($chunk->upload_date);

        $flight_report = \app\models\FlightReport::findOne(['id' => 5]);
        $flight = \app\models\Flight::findOne(['id' => $flight_report->flight_id]);
        $I->assertEquals('S', $flight->status);
    }

    public function ignoreUploadSameChunkIfFlightIsNotProcessed(ApiTester $I)
    {
        // If the flight is not processed('C' or 'S') and chunk is already uploaded ignore and return 200 for client
        $this->loginAsUser(7, $I);

        $filePath = $this->getTestFilePath('2_1.tmp');

        $I->sendPOST('/flight-report/upload-chunk/?flight_report_id=4&chunk_id=1', [], ['chunkFile' => $filePath]);
        $I->seeResponseCodeIs(200);
        $I->seeResponseContainsJson(['status' => 'success']);

        $chunk = \app\models\AcarsFile::findOne(['flight_report_id' => 4,'chunk_id' => 1]);
        $I->assertSame('2025-01-01 02:01:05', $chunk->upload_date);
    }
}