<?php
namespace tests\unit\models;

use app\models\AcarsFile;
use tests\unit\BaseUnitTest;

class AcarsFileTest extends BaseUnitTest
{
    public function testValidationRules()
    {
        $model = new AcarsFile();

        // Empty model
        $this->assertFalse($model->validate(), 'Model should not validate when empty.');
        $this->assertArrayHasKey('chunk_id', $model->getErrors(), 'chunk_id is required.');
        $this->assertArrayHasKey('flight_report_id', $model->getErrors(), 'flight_report_id is required.');
        $this->assertArrayHasKey('sha256sum', $model->getErrors(), 'sha256sum is required.');

        // Valid
        $model->chunk_id = 1;
        $model->flight_report_id = 1;
        $model->sha256sum = base64_decode('YTNjMjU2NGYyM2U3MThkODFkNjM4OWI3YTdkZjc3ZWE=');
        $this->assertTrue($model->validate(), 'Model should validate with correct data.');

        // Wrong sha256
        $model->sha256sum = 'short';
        $this->assertFalse($model->validate(), 'Model should not validate if sha256sum is too short.');
        $this->assertArrayHasKey('sha256sum', $model->getErrors(), 'sha256sum should have a maximum length of 32.');

        $model->sha256sum = str_repeat('a', 33);
        $this->assertFalse($model->validate(), 'Model should not validate if sha256sum is too long.');
        $this->assertArrayHasKey('sha256sum', $model->getErrors(), 'sha256sum should have a maximum length of 32.');
    }

    public function testUniqueConstraint()
    {
        $model1 = new AcarsFile([
            'chunk_id' => 1,
            'flight_report_id' => 1,
            'sha256sum' => base64_decode('YTNjMjU2NGYyM2U3MThkODFkNjM4OWI3YTdkZjc3ZWE='),
        ]);
        $this->assertTrue($model1->save(), 'First model should save successfully.');

        $model2 = new AcarsFile([
            'chunk_id' => 1,
            'flight_report_id' => 1,
            'sha256sum' => base64_decode('YTNjMjU2NGYyM2U3MThUODFkNjM4OWI3YTdkZjc3ZWE='),
        ]);
        $this->expectException(IntegrityException::class);
        $model2->save();
    }

}