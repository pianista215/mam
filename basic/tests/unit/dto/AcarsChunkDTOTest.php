<?php

namespace tests\unit\dto;

use app\modules\api\dto\v1\request\AcarsChunkDTO;
use tests\unit\BaseUnitTest;


class AcarsChunkDTOTest extends BaseUnitTest
{

    public function testValidChunk()
    {
        $data = [
            'id' => 1,
            'sha256' => str_repeat('a', 64),
        ];

        $chunk = new AcarsChunkDTO();
        $chunk->load($data, '');

        $this->assertTrue($chunk->validate(), 'Valid chunk data should pass validation.');
    }

    public function testChunkWithoutSha256()
    {
        $data = [
            'id' => 1,
        ];

        $chunk = new AcarsChunkDTO();
        $chunk->load($data, '');

        $this->assertFalse($chunk->validate(), 'Chunk without sha256 should fail validation.');
        $this->assertArrayHasKey('sha256', $chunk->getErrors(), 'Error message for sha256 should be present.');
    }

    public function testChunkWithInvalidSha256Length()
    {
        $data = [
            'id' => 1,
            'sha256' => str_repeat('a', 63),
        ];

        $chunk = new AcarsChunkDTO();
        $chunk->load($data, '');

        $this->assertFalse($chunk->validate(), 'Chunk with invalid sha256 length should fail validation.');
        $this->assertArrayHasKey('sha256', $chunk->getErrors(), 'Error message for sha256 should be present.');
    }

    public function testChunkWithoutId()
    {
        $data = [
            'sha256' => str_repeat('a', 64),
        ];

        $chunk = new AcarsChunkDTO();
        $chunk->load($data, '');

        $this->assertFalse($chunk->validate(), 'Chunk without id should fail validation.');
        $this->assertArrayHasKey('id', $chunk->getErrors(), 'Error message for id should be present.');
    }

}
