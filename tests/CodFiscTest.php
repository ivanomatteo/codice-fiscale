<?php

namespace IvanoMatteo\CodiceFiscale\Tests;

use IvanoMatteo\CodiceFiscale\CodiceFiscale;
use IvanoMatteo\CodiceFiscale\CodicefiscaleException;
use Orchestra\Testbench\TestCase;


class CodFiscTest extends TestCase
{

    /** @test */
    public function test_vari()
    {
        echo "\n";

        $name = 'Mario';
        $familyName = 'Rossi';
        $dateOfBirth = '1980-10-01';
        $sex = 'M';
        $cityCode = 'H501';

        $cf = CodiceFiscale::calculate($name, $familyName, $dateOfBirth, $sex, $cityCode);



        $person = [
            'name' => $name,
            'familyName' => $familyName,
            'dateOfBirth' => $dateOfBirth,
            'sex' => $sex,
            'cityCode' => $cityCode,
        ];
        $mappa = [
            'dateOfBirth' => 'dateOfBirth'
        ];

        $cfx = CodiceFiscale::calculateObj($person, $mappa);

        $this->assertTrue($cf->__toString() === $cfx->__toString());

        echo "Cofice Fiscale: $cf\n\n";

        echo "variazione[7]: " . $cf->generateVariations(7) . "\n";

        $variazioni = $cf->generateVariations();
        print_r($variazioni);

        $variazioni[] = ('' . $cf);

        foreach ($variazioni as $cod) {
            $c = CodiceFiscale::parse($cod);

            $this->assertTrue($c->matchName($name));
            $this->assertTrue($c->matchFamilyName($familyName));
            $this->assertTrue($c->matchCityCode($cityCode));
            $this->assertTrue($c->matchDateOfBirth($dateOfBirth));
            $this->assertTrue($c->matchSex($sex));

            $this->assertTrue($c->match((object) compact('name', 'familyName', 'dateOfBirth', 'cityCode', 'sex')));


            $this->assertTrue($c->getCityCode() === $cityCode);
            $this->assertTrue($c->getSex() === $sex);
            $this->assertTrue($c->getDateOfBirth(1900)->format('Y-m-d') === $dateOfBirth);
        }


        $this->assertTrue($c->getDateOfBirth(1900)->format('Y-m-d') === $dateOfBirth);


        $cfStr = 'RSSMRA81B29H501B';
        $this->assertTrue(CodiceFiscale::strMatchFormat($cfStr));
        $this->assertFalse(CodiceFiscale::isDateOfBirthCorrect($cfStr));
        $this->assertFalse(CodiceFiscale::strMatchControlDigit($cfStr));



        $this->assertTrue(CodiceFiscale::calculateProbableDateOfBirth('12', '01', '01', null, mktime(0, 0, 0, 1, 1, 2020))->format('Y-m-d') === '2012-01-01');
        $this->assertTrue(CodiceFiscale::calculateProbableDateOfBirth('12', '01', '01', 18, mktime(0, 0, 0, 1, 1, 2020))->format('Y-m-d') === '1912-01-01');

        $this->assertTrue(CodiceFiscale::calculateProbableDateOfBirth('20', '01', '01', null, mktime(0, 0, 0, 2, 1, 2020))->format('Y-m-d') === '2020-01-01');
        $this->assertTrue(CodiceFiscale::calculateProbableDateOfBirth('20', '01', '01', null, mktime(0, 0, 0, 1, 1, 2020))->format('Y-m-d') === '1920-01-01');
    }

    /** @test */
    public function test_db()
    {
        $envfile = __DIR__ . '/../env.php';
        if (!file_exists($envfile)) {
            $this->assertTrue(true);
            return;
        }

        $cfg = include($envfile);
        $cfg = $cfg['db'];

        $conn = new \PDO('mysql:host=' . $cfg['host'] . ';dbname=' . $cfg['db'], $cfg['user'], $cfg['pass']);
        $query = $conn->query("SELECT codiceFiscale FROM " . $cfg['table']);

        foreach ($query as $row) {

            if (!empty($row['codiceFiscale'])) {
                try {
                    $c = CodiceFiscale::parse($row['codiceFiscale']);

                    if ($c->isOmocodia()) {
                        echo "omocodia: " . $row['codiceFiscale'] . "\n";
                    }

                    $this->assertTrue((bool) preg_match("/^[A-Z][0-9]{3}$/", $c->getCityCode()));
                } catch (\Exception $ex) {
                    echo $row['codiceFiscale'] . " - " . $ex->getMessage() . "\n";
                }
            }
        }
    }
}
