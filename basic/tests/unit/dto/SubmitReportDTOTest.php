<?php

namespace tests\unit\dto;

use app\modules\api\dto\v1\request\AcarsChunkDTO;
use app\modules\api\dto\v1\request\SubmitReportDTO;
use tests\unit\BaseUnitTest;


class SubmitReportDTOTest extends BaseUnitTest
{

    public function testValidReportWithSingleChunks()
    {
        $data = [
            'pilot_comments' => 'Test flight report',
            'last_position_lat' => 45.0,
            'last_position_lon' => -73.0,
            'network' => 'TestNet',
            'sim_aircraft_name' => 'TestAircraft',
            'report_tool' => 'TestTool',
            'start_time' => '2025-01-01 12:00:00',
            'end_time' => '2025-01-01 14:00:00',
            'chunks' => [
                ['id' => 1, 'sha256sum' => str_repeat('a', 44)],
            ],
        ];

        $dto = new SubmitReportDTO();
        $dto->load($data, '');

        $this->assertTrue($dto->validate(), 'Valid report with chunks should pass validation.');
    }

    public function testValidReportWithMultipleChunks()
    {
        $data = [
            'pilot_comments' => 'Test flight report',
            'last_position_lat' => 45.0,
            'last_position_lon' => -73.0,
            'network' => 'TestNet',
            'sim_aircraft_name' => 'TestAircraft',
            'report_tool' => 'TestTool',
            'start_time' => '2025-01-01 12:00:00',
            'end_time' => '2025-01-01 14:00:00',
            'chunks' => [
                ['id' => 1, 'sha256sum' => str_repeat('a', 44)],
                ['id' => 2, 'sha256sum' => str_repeat('b', 44)],
                ['id' => 3, 'sha256sum' => str_repeat('c', 44)],
            ],
        ];

        $dto = new SubmitReportDTO();
        $dto->load($data, '');

        $this->assertTrue($dto->validate(), 'Valid report with chunks should pass validation.');
    }

    public function testReportWithoutChunks()
    {
        $data = [
            'pilot_comments' => 'Test flight report',
            'last_position_lat' => 45.0,
            'last_position_lon' => -73.0,
            'network' => 'TestNet',
            'sim_aircraft_name' => 'TestAircraft',
            'report_tool' => 'TestTool',
            'start_time' => '2025-01-01 12:00:00',
            'end_time' => '2025-01-01 14:00:00',
            'chunks' => [], // No chunks
        ];

        $dto = new SubmitReportDTO();
        $dto->load($data, '');

        $this->assertFalse($dto->validate(), 'Report without chunks should fail validation.');
        $this->assertArrayHasKey('chunks', $dto->getErrors(), 'Error message for missing chunks should be present.');
    }

    public function testReportWithDuplicateChunkIds()
    {
        $data = [
            'pilot_comments' => 'Test flight report',
            'last_position_lat' => 45.0,
            'last_position_lon' => -73.0,
            'network' => 'TestNet',
            'sim_aircraft_name' => 'TestAircraft',
            'report_tool' => 'TestTool',
            'start_time' => '2025-01-01 12:00:00',
            'end_time' => '2025-01-01 14:00:00',
            'chunks' => [
                ['id' => 1, 'sha256sum' => str_repeat('a', 44)],
                ['id' => 1, 'sha256sum' => str_repeat('b', 44)], // Duplicate ID
            ],
        ];

        $dto = new SubmitReportDTO();
        $dto->load($data, '');

        $this->assertFalse($dto->validate(), 'Report with duplicate chunk IDs should fail validation.');
        $this->assertArrayHasKey('chunks', $dto->getErrors(), 'Error message for duplicate chunk IDs should be present.');
    }

    public function testReportWithMissingChunkInSequence()
    {
        $data = [
            'pilot_comments' => 'Test flight report',
            'last_position_lat' => 45.0,
            'last_position_lon' => -73.0,
            'network' => 'TestNet',
            'sim_aircraft_name' => 'TestAircraft',
            'report_tool' => 'TestTool',
            'start_time' => '2025-01-01 12:00:00',
            'end_time' => '2025-01-01 14:00:00',
            'chunks' => [
                ['id' => 1, 'sha256sum' => str_repeat('a', 44)],
                ['id' => 2, 'sha256sum' => str_repeat('b', 44)],
                ['id' => 4, 'sha256sum' => str_repeat('c', 44)], // Missing ID 3
            ],
        ];

        $dto = new SubmitReportDTO();
        $dto->load($data, '');

        $this->assertFalse($dto->validate(), 'Report with missing chunk in sequence should fail validation.');
        $this->assertArrayHasKey('chunks', $dto->getErrors(), 'Error message for missing chunk in sequence should be present.');
    }

    public function testReportWithInvalidChunk()
    {
        $data = [
            'pilot_comments' => 'Test flight report',
            'last_position_lat' => 45.0,
            'last_position_lon' => -73.0,
            'network' => 'TestNet',
            'sim_aircraft_name' => 'TestAircraft',
            'report_tool' => 'TestTool',
            'start_time' => '2025-01-01 12:00:00',
            'end_time' => '2025-01-01 14:00:00',
            'chunks' => [
                ['id' => 1, 'sha256sum' => str_repeat('a', 44)],
                ['id' => 2, 'sha256sum' => 'short'], // Invalid sha256
            ],
        ];

        $dto = new SubmitReportDTO();
        $dto->load($data, '');

        $this->assertFalse($dto->validate(), 'Report with invalid chunk should fail validation.');
        $this->assertArrayHasKey('chunks', $dto->getErrors(), 'Error message for invalid chunk should be present.');
    }

    public function testReportWithInvalidLatitude()
    {
        $data = [
            'pilot_comments' => 'Test flight report',
            'last_position_lat' => 100.0, // Invalid latitude
            'last_position_lon' => -73.0,
            'network' => 'TestNet',
            'sim_aircraft_name' => 'TestAircraft',
            'report_tool' => 'TestTool',
            'start_time' => '2025-01-01 12:00:00',
            'end_time' => '2025-01-01 14:00:00',
            'chunks' => [
                ['id' => 1, 'sha256sum' => str_repeat('a', 44)],
            ],
        ];

        $dto = new SubmitReportDTO();
        $dto->load($data, '');

        $this->assertFalse($dto->validate(), 'Report with invalid latitude should fail validation.');
        $this->assertArrayHasKey('last_position_lat', $dto->getErrors(), 'Error message for invalid latitude should be present.');
    }

    public function testReportWithInvalidLongitude()
    {
        $data = [
            'pilot_comments' => 'Test flight report',
            'last_position_lat' => 45.0,
            'last_position_lon' => -200.0, // Invalid longitude
            'network' => 'TestNet',
            'sim_aircraft_name' => 'TestAircraft',
            'report_tool' => 'TestTool',
            'start_time' => '2025-01-01 12:00:00',
            'end_time' => '2025-01-01 14:00:00',
            'chunks' => [
                ['id' => 1, 'sha256sum' => str_repeat('a', 44)],
            ],
        ];

        $dto = new SubmitReportDTO();
        $dto->load($data, '');

        $this->assertFalse($dto->validate(), 'Report with invalid longitude should fail validation.');
        $this->assertArrayHasKey('last_position_lon', $dto->getErrors(), 'Error message for invalid longitude should be present.');
    }

    public function testReportWithInvalidDateFormat()
    {
        $data = [
            'pilot_comments' => 'Test flight report',
            'last_position_lat' => 45.0,
            'last_position_lon' => -73.0,
            'network' => 'TestNet',
            'sim_aircraft_name' => 'TestAircraft',
            'report_tool' => 'TestTool',
            'start_time' => '01-01-2025 12:00:00', // Invalid format
            'end_time' => '2025-01-01T14:00:00', // Invalid format
            'chunks' => [
                ['id' => 1, 'sha256sum' => str_repeat('a', 44)],
            ],
        ];

        $dto = new SubmitReportDTO();
        $dto->load($data, '');

        $this->assertFalse($dto->validate(), 'Report with invalid date format should fail validation.');
        $this->assertArrayHasKey('start_time', $dto->getErrors(), 'Error message for invalid start_time format should be present.');
        $this->assertArrayHasKey('end_time', $dto->getErrors(), 'Error message for invalid end_time format should be present.');
    }

    public function testReportWithEmptyRequiredFields()
    {
        $data = [
            'pilot_comments' => '',
            'last_position_lat' => 45.0,
            'last_position_lon' => -73.0,
            'network' => '',
            'sim_aircraft_name' => '',
            'report_tool' => 'TestTool',
            'start_time' => '2025-01-01 12:00:00',
            'end_time' => '2025-01-01 14:00:00',
            'chunks' => [
                ['id' => 1, 'sha256sum' => str_repeat('a', 44)],
            ],
        ];

        $dto = new SubmitReportDTO();
        $dto->load($data, '');

        $this->assertFalse($dto->validate(), 'Report with empty required fields should fail validation.');
        $this->assertArrayHasKey('network', $dto->getErrors(), 'Error message for empty network should be present.');
        $this->assertArrayHasKey('sim_aircraft_name', $dto->getErrors(), 'Error message for empty sim_aircraft_name should be present.');
    }

}