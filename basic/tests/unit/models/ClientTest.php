<?php

namespace tests\unit\models;

use app\models\Airport;
use app\models\Client;
use app\models\ClientHub;
use app\models\Country;
use tests\unit\BaseUnitTest;

class ClientTest extends BaseUnitTest
{
    protected function _before()
    {
        parent::_before();
        $this->clearDatabase();

        (new Country(['id' => 1, 'name' => 'Spain', 'iso2_code' => 'ES']))->save(false);

        foreach (['LEMD' => 'Madrid', 'LEBL' => 'Barcelona', 'LEVC' => 'Valencia'] as $icao => $city) {
            (new Airport([
                'icao_code' => $icao,
                'name' => $city,
                'latitude' => 0.0,
                'longitude' => 0.0,
                'city' => $city,
                'country_id' => 1,
            ]))->save(false);
        }
    }

    private function createClient(array $attributes = []): Client
    {
        $client = new Client(array_merge([
            'name' => 'Iberian Logistics',
            'headquarters_icao' => 'LEMD',
        ], $attributes));
        $this->assertTrue($client->save(), json_encode($client->errors));
        return $client;
    }

    public function testCreateValidClient()
    {
        $client = $this->createClient();

        $this->assertNotEmpty($client->id);
        $this->assertSame([], $client->getHubIcaoList());
    }

    public function testRequiredFields()
    {
        $client = new Client();

        $this->assertFalse($client->save());
        $this->assertArrayHasKey('name', $client->errors);
        $this->assertArrayHasKey('headquarters_icao', $client->errors);
    }

    public function testDuplicateNameIsRejected()
    {
        $this->createClient();

        $client = new Client(['name' => 'Iberian Logistics', 'headquarters_icao' => 'LEBL']);

        $this->assertFalse($client->save());
        $this->assertArrayHasKey('name', $client->errors);
    }

    public function testUnknownHeadquartersIsRejected()
    {
        $client = new Client(['name' => 'Ghost Corp', 'headquarters_icao' => 'XXXX']);

        $this->assertFalse($client->save());
        $this->assertArrayHasKey('headquarters_icao', $client->errors);
    }

    public function testHeadquartersIsNormalized()
    {
        $client = $this->createClient(['headquarters_icao' => ' lemd ']);

        $this->assertSame('LEMD', $client->headquarters_icao);
    }

    public function testHubsAreSavedNormalizedAndWithoutDuplicates()
    {
        $client = $this->createClient(['hubIcaos' => ['levc', 'LEBL', ' LEBL ']]);

        $this->assertSame(['LEBL', 'LEVC'], $client->getHubIcaoList());
    }

    public function testHeadquartersIsRemovedFromHubs()
    {
        $client = $this->createClient(['hubIcaos' => ['LEMD', 'LEBL']]);

        $this->assertSame(['LEBL'], $client->getHubIcaoList());
    }

    public function testHubsAreReplacedOnUpdate()
    {
        $client = $this->createClient(['hubIcaos' => ['LEBL']]);

        $client->hubIcaos = ['LEVC'];
        $this->assertTrue($client->save());

        $this->assertSame(['LEVC'], $client->getHubIcaoList());
    }

    public function testHubsUntouchedWhenNotProvided()
    {
        $client = $this->createClient(['hubIcaos' => ['LEBL']]);

        $reloaded = Client::findOne($client->id);
        $reloaded->name = 'Renamed';
        $this->assertTrue($reloaded->save());

        $this->assertSame(['LEBL'], $reloaded->getHubIcaoList());
    }

    public function testChangingHeadquartersToAHubRemovesThatHub()
    {
        $client = $this->createClient(['hubIcaos' => ['LEBL', 'LEVC']]);

        $reloaded = Client::findOne($client->id);
        $reloaded->headquarters_icao = 'LEBL';
        $this->assertTrue($reloaded->save());

        $this->assertSame(['LEVC'], $reloaded->getHubIcaoList());
    }

    public function testUnknownHubIsRejectedAndNothingIsSaved()
    {
        $client = new Client([
            'name' => 'Iberian Logistics',
            'headquarters_icao' => 'LEMD',
            'hubIcaos' => ['LEBL', 'ZZZZ'],
        ]);

        $this->assertFalse($client->save());
        $this->assertArrayHasKey('hubIcaos', $client->errors);
        $this->assertSame(0, (int)Client::find()->count());
        $this->assertSame(0, (int)ClientHub::find()->count());
    }

    public function testHubsAreDeletedWithClient()
    {
        $client = $this->createClient(['hubIcaos' => ['LEBL', 'LEVC']]);

        $client->delete();

        $this->assertSame(0, (int)ClientHub::find()->count());
    }

    public function testHeadquartersAirportCannotBeDeleted()
    {
        $this->createClient();

        $this->expectException(\yii\db\IntegrityException::class);
        Airport::findOne(['icao_code' => 'LEMD'])->delete();
    }

    public function testDeletingHubAirportRemovesHub()
    {
        $client = $this->createClient(['hubIcaos' => ['LEBL', 'LEVC']]);

        Airport::findOne(['icao_code' => 'LEBL'])->delete();

        $this->assertSame(['LEVC'], $client->getHubIcaoList());
    }
}
