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

    public function testCalculateProbableDateOfBirth()
    {

    }

    public function testGetCityCode()
    {

    }

    public function test__toString()
    {

    }

    public function testStrMatchFormat()
    {

    }

    public function testMatchDateOfBirth()
    {

    }

    public function testParse()
    {

    }

    public function testGetProbableDateOfBirth()
    {

    }

    public function testGenerateVariations()
    {

    }

    public function testGetDateOfBirth()
    {

    }

    public function testIsDateOfBirthCorrect()
    {

    }

    public function testGetSex()
    {

    }

    public function testMatchSex()
    {

    }

    public function testCalculateBaseVariation()
    {

    }

    public function testExtractDateOfBirthRaw()
    {

    }

    public function testMatchFamilyName()
    {

    }

    public function testIsOmocodiaStr()
    {

    }

    public function testInit()
    {

    }

    public function testCalculateObj()
    {

    }

    public function testMatchCityCode()
    {

    }

    public function testGetDateOfBirthRaw()
    {

    }

    public function testValidate()
    {

    }

    public function testCalcControlDigit()
    {

    }

    public function testMatchName()
    {

    }

    public function testIsOmocodia()
    {

    }

    public function testGetBaseVariation()
    {

    }

    public function testStrMatchControlDigit()
    {

    }
}
