<?php

namespace tests\unit\models;

use app\models\Tour;
use tests\unit\BaseUnitTest;
use Yii;

class TourTest extends BaseUnitTest
{

    public function testCreateValidTour()
    {
        $tour = new Tour([
            'name' => 'Test',
            'description' => 'Description',
            'start' => '2020-01-01',
            'end' => '2023-01-01',
        ]);
        $this->assertTrue($tour->save());
    }

    public function testTourNotValidWithoutDescription()
    {
        $tour = new Tour([
            'name' => 'Test',
            'description' => '',
            'start' => '2020-01-01',
            'end' => '2023-01-01',
        ]);
        $this->assertFalse($tour->save());
        $this->assertArrayHasKey('description', $tour->getErrors());
    }

    public function testTourInvalidDates()
    {
        $tour = new Tour([
            'name' => ' Test ',
            'description' => ' Description ',
            'start' => '2035-01-01',
            'end' => '2023-01-01',
        ]);
        $this->assertFalse($tour->save());
        $this->assertArrayHasKey('end', $tour->getErrors());
    }

    public function testTourTrim()
    {
        $tour = new Tour([
            'name' => ' Test ',
            'description' => ' Description ',
            'start' => '2020-01-01',
            'end' => '2023-01-01',
        ]);
        $this->assertTrue($tour->save());
        $this->assertEquals('Test', $tour->name);
        $this->assertEquals('Description', $tour->description);
    }

    public function testMaxLengthCode()
    {
        $tour = new Tour([
            'name' => str_repeat('A', 110),
            'description' => str_repeat('A', 210),
            'start' => '2035-01-01',
            'end' => '2023-01-01',
        ]);
        $this->assertFalse($tour->save());
        $this->assertArrayHasKey('name', $tour->getErrors());
        $this->assertArrayHasKey('description', $tour->getErrors());
    }

}