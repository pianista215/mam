<?php

namespace tests\unit\models;

use app\models\Country;
use tests\unit\DbTestCase;

class CountryTest extends DbTestCase
{
    public function testCreateValidCountry()
    {
        $country = new Country([
            'name' => 'Spain',
            'iso2_code' => 'ES',
        ]);

        $this->assertTrue($country->save());
        $this->assertNotEmpty($country->id);
    }

    public function testCreateCountryWithoutRequiredFields()
    {
        $country = new Country(['name' => '']);
        $this->assertFalse($country->save());
        $this->assertArrayHasKey('name', $country->errors);
        $this->assertArrayHasKey('iso2_code', $country->errors);
    }

    public function testCreateCountryWithInvalidISO2CodeLength()
    {
        $country = new Country([
            'name' => 'Invalid Country',
            'iso2_code' => 'ABC',
        ]);

        $this->assertFalse($country->save());
        $this->assertArrayHasKey('iso2_code', $country->errors);
    }

    public function testCreateCountryWithDuplicateISO2Code()
    {
        $existingCountry = new Country([
            'name' => 'Country 1',
            'iso2_code' => 'US',
        ]);
        $existingCountry->save();

        $country = new Country([
            'name' => 'Country 2',
            'iso2_code' => 'US',
        ]);

        $this->assertFalse($country->save());
        $this->assertArrayHasKey('iso2_code', $country->errors);
    }
}
