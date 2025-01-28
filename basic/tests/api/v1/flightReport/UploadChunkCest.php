<?php

namespace tests\api\v1\flightReport;

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
        return codecept_data_dir('chunks') . DIRECTORY_SEPARATOR . $fileName;
    }

    public function testUserUnauthenticated(ApiTester $I)
    {
        $filePath = $this->getTestFilePath('1_1.tmp');
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
        $filePath = $this->getTestFilePath('1_1.tmp');
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
        $filePath = $this->getTestFilePath('1_1.tmp');
        $I->sendPOST('/flight-report/upload-chunk/?flight_report_id=2&chunk_id=99', [], ['chunkFile' => $filePath]);
        $I->seeResponseCodeIs(404);
        $I->seeResponseContainsJson([
            'name' => 'Not Found',
            'message' => 'Chunk not found for this flight report.',
        ]);
    }

    public function testChunkAlreadyUploaded(ApiTester $I)
    {
        $chunk = \app\models\AcarsFile::findOne(['chunk_id' => 1, 'flight_report_id' => 2]);
        $chunk->upload_date = date('Y-m-d H:i:s');
        $chunk->save();

        $this->loginAsUser(5, $I);
        $filePath = $this->getTestFilePath('1_1.tmp');
        $I->sendPOST('/flight-report/upload-chunk/?flight_report_id=2&chunk_id=1', [], ['chunkFile' => $filePath]);
        $I->seeResponseCodeIs(409);
        $I->seeResponseContainsJson([
            'name' => 'Conflict',
            'message' => 'Chunk 1 already uploaded.',
        ]);
    }

    public function testFlightClosed(ApiTester $I)
    {
        $this->loginAsUser(5, $I);

        $filePath = $this->getTestFilePath('1_1.tmp');
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

        $I->sendPOST('/flight-report/upload-chunk/?flight_report_id=4&chunk_id=1', [], ['chunkFile' => $filePath]);
        $I->seeResponseCodeIs(404);
        $I->seeResponseContainsJson([
            'name' => 'Not Found',
            'message' => 'Flight access denied or not available for chunk uploads.',
        ]);
    }

    /*public function testSha256Mismatch(ApiTester $I)
    {
        $this->loginAsUser(1, $I);
        $filePath = $this->getTestFilePath('1_invalid.tmp');

        $I->sendPOST('/api/v1/flight-report/1/chunk/1', [], ['chunkFile' => $filePath]);
        $I->seeResponseCodeIs(400);
        $I->seeResponseContainsJson([
            'name' => 'Bad Request',
            'message' => 'SHA256 mismatch.',
        ]);
    }

    public function testValidChunkUpload(ApiTester $I)
    {
        $this->loginAsUser(1, $I);
        $filePath = $this->getTestFilePath('1_1.tmp');

        $chunk = \app\models\AcarsFile::findOne(['chunk_id' => 1, 'flight_report_id' => 1]);
        $chunk->sha256sum = hash_file('sha256', $filePath);
        $chunk->save();

        $I->sendPOST('/api/v1/flight-report/1/chunk/1', [], ['chunkFile' => $filePath]);
        $I->seeResponseCodeIs(200);
        $I->seeResponseContainsJson(['status' => 'success']);
    }

    public function testAllChunksUploaded(ApiTester $I)
    {
        $this->loginAsUser(1, $I);

        $chunks = \app\models\AcarsFile::findAll(['flight_report_id' => 1]);
        foreach ($chunks as $chunk) {
            $filePath = $this->getTestFilePath("1_{$chunk->chunk_id}.tmp");
            $chunk->sha256sum = hash_file('sha256', $filePath);
            $chunk->save();

            $I->sendPOST("/api/v1/flight-report/1/chunk/{$chunk->chunk_id}", [], ['chunkFile' => $filePath]);
            $I->seeResponseCodeIs(200);
            $I->seeResponseContainsJson(['status' => 'success']);
        }

        $flightReport = \app\models\FlightReport::findOne(1);
        $I->assertEquals('S', $flightReport->status, 'Flight report status should be updated to "S".');
    }*/
}