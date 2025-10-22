<?php

namespace tests\unit\models;

use app\models\Airport;
use app\models\Country;
use app\models\Tour;
use app\models\TourStage;
use tests\unit\BaseUnitTest;
use Yii;

class TourStageTest extends BaseUnitTest
{

    protected Tour $tour;

    protected function _before()
    {
        parent::_before();

        $country = new Country(['id' => 1, 'name' => 'Spain', 'iso2_code' => 'ES']);
        $country->save(false);

        $airport1 = new Airport([
            'icao_code' => 'LEMD',
            'name' => 'Madrid-Barajas',
            'latitude' => 40.471926,
            'longitude' => -3.56264,
            'city' => 'Madrid',
            'country_id' => 1,
        ]);
        $airport1->save(false);

        $airport2 = new Airport([
            'icao_code' => 'LEBL',
            'name' => 'Barcelona-El Prat',
            'latitude' => 41.297445,
            'longitude' => 2.0833,
            'city' => 'Barcelona',
            'country_id' => 1,
        ]);
        $airport2->save(false);

        $this->tour = new Tour([
            'name' => 'Test Tour',
            'description' => 'Tour for testing stages',
            'start' => '2025-01-01',
            'end' => '2030-01-01'
        ]);
        $this->tour->save(false);
    }

    public function testCanCreateValidTourStage()
    {
        $stage = new TourStage([
            'tour_id' => $this->tour->id,
            'departure' => 'LEMD',
            'arrival' => 'LEBL',
            'sequence' => 1,
            'description' => 'Stage 1',
        ]);

        $this->assertTrue($stage->save(), 'Valid TourStage should be saved.');
        $this->assertEqualsWithDelta(261, $stage->distance_nm, 1);
    }

    public function testCannotHaveDuplicateSequenceInSameTour()
    {
        $first = new TourStage([
            'tour_id' => $this->tour->id,
            'departure' => 'LEMD',
            'arrival' => 'LEBL',
            'sequence' => 1,
        ]);
        $this->assertTrue($first->save());

        $duplicate = new TourStage([
            'tour_id' => $this->tour->id,
            'departure' => 'LEBL',
            'arrival' => 'LEMD',
            'sequence' => 1,
        ]);
        $this->assertFalse($duplicate->save(), 'Duplicate sequence should not be allowed.');
        $this->assertArrayHasKey('tour_id', $duplicate->getErrors());
    }

    public function testCantCreateStageWithNonConsecutiveSequence()
    {
        $first = new TourStage([
            'tour_id' => $this->tour->id,
            'departure' => 'LEMD',
            'arrival' => 'LEBL',
            'sequence' => 1,
        ]);
        $this->assertTrue($first->save());

        $third = new TourStage([
            'tour_id' => $this->tour->id,
            'departure' => 'LEBL',
            'arrival' => 'LEMD',
            'sequence' => 3,
        ]);
        $this->assertFalse($third->save(), 'Cannot create a non-consecutive stage.');
        $this->assertArrayHasKey('sequence', $third->getErrors());
    }

    public function testCantCreateStageWithIncorrectDeparture()
    {
        $stage = new TourStage([
            'tour_id' => $this->tour->id,
            'departure' => 'XXXX',
            'arrival' => 'LEBL',
            'sequence' => 1,
        ]);

        $this->assertFalse($stage->save(), 'Invalid departure airport should not be allowed.');
        $this->assertArrayHasKey('departure', $stage->getErrors());
    }

    public function testCantCreateStageWithIncorrectArrival()
    {
        $stage = new TourStage([
            'tour_id' => $this->tour->id,
            'departure' => 'LEBL',
            'arrival' => 'XXXX',
            'sequence' => 1,
        ]);

        $this->assertFalse($stage->save(), 'Invalid arrival airport should not be allowed.');
        $this->assertArrayHasKey('arrival', $stage->getErrors());
    }

}
