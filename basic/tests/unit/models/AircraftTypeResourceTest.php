<?php

namespace tests\unit\models;

use app\config\Config;
use app\models\AircraftType;
use app\models\AircraftTypeResource;
use tests\unit\BaseUnitTest;

class AircraftTypeResourceTest extends BaseUnitTest
{
    private function createAircraftType(): AircraftType
    {
        $type = new AircraftType(['icao_type_code' => 'B738', 'name' => 'Boeing 737-800', 'max_nm_range' => 2900]);
        $type->save();
        return $type;
    }

    public function testValidRecordSaves()
    {
        $type     = $this->createAircraftType();
        $resource = new AircraftTypeResource([
            'aircraft_type_id' => $type->id,
            'filename'         => 'abc123.pdf',
            'original_name'    => 'manual.pdf',
            'size_bytes'       => 1024,
        ]);

        $this->assertTrue($resource->save());
        $this->assertNotEmpty($resource->id);
    }

    public function testRequiredFields()
    {
        $resource = new AircraftTypeResource([]);
        $this->assertFalse($resource->save());
        $this->assertArrayHasKey('aircraft_type_id', $resource->errors);
        $this->assertArrayHasKey('filename', $resource->errors);
        $this->assertArrayHasKey('original_name', $resource->errors);
        $this->assertArrayHasKey('size_bytes', $resource->errors);
    }

    public function testAircraftTypeIdMustExist()
    {
        $resource = new AircraftTypeResource([
            'aircraft_type_id' => 999999,
            'filename'         => 'abc.pdf',
            'original_name'    => 'doc.pdf',
            'size_bytes'       => 512,
        ]);

        $this->assertFalse($resource->save());
        $this->assertArrayHasKey('aircraft_type_id', $resource->errors);
    }

    public function testGetPathReturnsCorrectPath()
    {
        Config::set('files_storage_path', '/tmp/mam-files-test');

        $type     = $this->createAircraftType();
        $resource = new AircraftTypeResource([
            'aircraft_type_id' => $type->id,
            'filename'         => 'randomfile.pdf',
            'original_name'    => 'checklist.pdf',
            'size_bytes'       => 2048,
        ]);
        $resource->save();

        $path = $resource->getPath();
        $this->assertStringContainsString('/tmp/mam-files-test', $path);
        $this->assertStringContainsString((string) $type->id, $path);
        $this->assertStringContainsString('randomfile.pdf', $path);
    }

    public function testGetTotalSizeMb()
    {
        $type = $this->createAircraftType();

        $r1 = new AircraftTypeResource([
            'aircraft_type_id' => $type->id,
            'filename' => 'f1.pdf', 'original_name' => 'f1.pdf',
            'size_bytes' => 1024 * 1024,
        ]);
        $r1->save();

        $r2 = new AircraftTypeResource([
            'aircraft_type_id' => $type->id,
            'filename' => 'f2.pdf', 'original_name' => 'f2.pdf',
            'size_bytes' => 2 * 1024 * 1024,
        ]);
        $r2->save();

        $this->assertEqualsWithDelta(3.0, AircraftTypeResource::getTotalSizeMb(), 0.01);
    }

    public function testAfterDeleteRemovesFileFromDisk()
    {
        Config::set('files_storage_path', '/tmp');

        $type     = $this->createAircraftType();
        $resource = new AircraftTypeResource([
            'aircraft_type_id' => $type->id,
            'filename'         => 'testdoc.pdf',
            'original_name'    => 'test.pdf',
            'size_bytes'       => 100,
        ]);
        $resource->save();

        $dir = '/tmp/aircraft_type/' . $type->id;
        if (!is_dir($dir)) {
            mkdir($dir, 0777, true);
        }
        $filePath = $dir . '/testdoc.pdf';
        file_put_contents($filePath, 'dummy content');

        $this->assertFileExists($filePath);

        $resource->delete();

        $this->assertFileDoesNotExist($filePath);
    }

    public function testCascadeDeleteWithAircraftType()
    {
        $type     = $this->createAircraftType();
        $resource = new AircraftTypeResource([
            'aircraft_type_id' => $type->id,
            'filename'         => 'doc.zip',
            'original_name'    => 'archive.zip',
            'size_bytes'       => 512,
        ]);
        $resource->save();
        $resourceId = $resource->id;

        $type->delete();

        $this->assertNull(AircraftTypeResource::findOne(['id' => $resourceId]));
    }
}
