<?php

namespace IvanoMatteo\CodiceFiscale\Tests;

use IvanoMatteo\CodiceFiscale\CodiceFiscale;
use IvanoMatteo\CodiceFiscale\CodicefiscaleException;
use IvanoMatteo\CodiceFiscale\Tests\DataProviders\InvalidCityDataProvider;
use IvanoMatteo\CodiceFiscale\Tests\DataProviders\InvalidNameDataProvider;
use IvanoMatteo\CodiceFiscale\Tests\DataProviders\ValidFemaleDataProvider;
use IvanoMatteo\CodiceFiscale\Tests\DataProviders\InvalidFamilyNameDataProvider;
use IvanoMatteo\CodiceFiscale\Tests\DataProviders\ValidMaleDataProvider;
use PHPUnit\Framework\TestCase;

/**
 * @covers \IvanoMatteo\CodiceFiscale\CodiceFiscale
 */
class CodiceFiscaleTest extends TestCase
{
    /**
     * @dataProvider validPeopleDataProvider
     */
    public function testCalculate($person)
    {
        $cf = CodiceFiscale::calculate(
            $person->firstName,
            $person->lastName,
            $person->birthDate,
            $person->sex,
            $person->cityCode
        );

        $this->assertSame($person->cf, (string)$cf);
    }

    public function validPeopleDataProvider()
    {
        return [
            'valid male' => [new ValidMaleDataProvider()],
            'valid female' => [new ValidFemaleDataProvider()],
        ];
    }

    /**
     * @dataProvider invalidPeopleDataProvider
     */
    public function testCalculateException($person)
    {
        $this->expectException(CodicefiscaleException::class);
        $this->expectExceptionMessage($person->exception);
        CodiceFiscale::calculate(
            $person->firstName,
            $person->lastName,
            $person->birthDate,
            $person->sex,
            $person->cityCode
        );
    }

    public function invalidPeopleDataProvider()
    {
        return [
            'invalid city' => [new InvalidCityDataProvider()],
            'invalid name' => [new InvalidNameDataProvider()],
            'invalid family name' => [new InvalidFamilyNameDataProvider()],
        ];
    }
}
