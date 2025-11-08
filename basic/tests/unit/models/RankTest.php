<?php

namespace tests\unit\models;

use app\models\Rank;
use tests\unit\BaseUnitTest;

class RankTest extends BaseUnitTest
{
    public function testCreateValidRank()
    {
        $rank = new Rank([
            'name' => 'Captain',
            'position' => 1,
        ]);

        $this->assertTrue($rank->save());
        $this->assertNotEmpty($rank->id);
    }

    public function testCreateRankWithoutRequiredFields()
    {
        $rank = new Rank([
            'name' => '',
            'position' => null,
        ]);

        $this->assertFalse($rank->save());
        $this->assertArrayHasKey('name', $rank->errors);
        $this->assertArrayHasKey('position', $rank->errors);
    }

    public function testCreateRankWithNonIntegerPosition()
    {
        $rank = new Rank([
            'name' => 'Invalid Rank',
            'position' => 'not-integer',
        ]);

        $this->assertFalse($rank->save());
        $this->assertArrayHasKey('position', $rank->errors);
    }

    public function testCreateRankWithDuplicateName()
    {
        $existingRank = new Rank([
            'name' => 'First Officer',
            'position' => 2,
        ]);
        $existingRank->save();

        $rank = new Rank([
            'name' => 'First Officer',
            'position' => 3,
        ]);

        $this->assertFalse($rank->save());
        $this->assertArrayHasKey('name', $rank->errors);
    }

    public function testCreateRankWithDuplicatePosition()
    {
        $existingRank = new Rank([
            'name' => 'Senior Captain',
            'position' => 4,
        ]);
        $existingRank->save();

        $rank = new Rank([
            'name' => 'Junior Captain',
            'position' => 4,
        ]);

        $this->assertFalse($rank->save());
        $this->assertArrayHasKey('position', $rank->errors);
    }
}
