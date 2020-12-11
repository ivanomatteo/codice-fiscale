<?php

namespace IvanoMatteo\CodiceFiscale;

use DateTime;

/**
 * Description of Codicefiscale
 *
 * @author Ivano Matteo
 *
 *
 */
class CodiceFiscale
{
    /** @var bool */
    private $omocodia;

    /** @var string */
    private $codiceFiscale;

    /** @var string */
    private $codiceFiscaleBase;

    /** @var object  {"day":"dd","month":"mm","year":"yy"} */
    private $dateOfBirth;

    /** @var string */
    private $sex;

    /** private constructor, use static methods to obtain an instance of this class */
    private function __construct()
    {
    }

    /**
     * @param string $codfisc the fiscal code
     * @param string|int $century the year  4 or 2 digits - the last 2 digits will be replaced with 00. if < 100 will be multiplied by 100
     * @return CodiceFiscale
     * @throws CodicefiscaleException
     */
    public static function parse($codfisc = null, $century = null)
    {
        $cod = new CodiceFiscale();
        if ($codfisc) {
            $cod->codiceFiscale = strtoupper(trim($codfisc));
        }
        if (!static::strMatchFormat($cod->codiceFiscale)) {
            throw new CodicefiscaleException('invalid-format');
        }
        if (!static::isDateOfBirthCorrect($cod->codiceFiscale, $century)) {
            throw new CodicefiscaleException('dob-not-match');
        }
        if (!static::strMatchControlDigit($cod->codiceFiscale)) {
            throw new CodicefiscaleException('cdigit-not-match');
        }

        return $cod;
    }

    /**
     * @param string $name
     * @param string $familyName
     * @param string $dateOfBirth
     * @param string $sex
     * @param string $cityCode
     * @return CodiceFiscale
     * @throws CodicefiscaleException
     *
     * calculate the fiscal code using person data, (note: is not possible to determine by the data if an "omocodia" is present)
     */
    public static function calculate($name, $familyName, $dateOfBirth, $sex, $cityCode)
    {
        $c = new CodiceFiscale();
        $c->codiceFiscale = static::_calculate($name, $familyName, $dateOfBirth, $sex, $cityCode);
        return $c;
    }

    /**
     * @param object|array $person expected fields: name, familyName, dateOfBirth, sex, cityCode
     * @param object|array $fieldMap if provided, allow to remap field names
     * @return CodiceFiscale
     * @throws CodicefiscaleException
     * @see calculate
     */
    public static function calculateObj($person, $fieldMap = null)
    {
        if (is_array($person)) {
            $person = (object) $person;
        }

        if (is_array($fieldMap)) {
            $fieldMap = (object) $fieldMap;
        }

        return static::calculate(
            $person->{isset($fieldMap->name) ? $fieldMap->name : 'name'},
            $person->{isset($fieldMap->familyName) ? $fieldMap->familyName : 'familyName'},
            $person->{isset($fieldMap->dateOfBirth) ? $fieldMap->dateOfBirth : 'dateOfBirth'},
            $person->{isset($fieldMap->sex) ? $fieldMap->sex : 'sex'},
            $person->{isset($fieldMap->cityCode) ? $fieldMap->cityCode : 'cityCode'}
        );
    }


    //#####################################################
    // methods
    //#####################################################

    /**
     * @return bool determine if the fiscal code is "omocodia"
     */
    public function isOmocodia()
    {
        if ($this->omocodia === NULL) {
            $this->omocodia = static::isOmocodiaStr($this->codiceFiscale);
        }
        return $this->omocodia;
    }



    /**
     * check if the fiscal code match the given fields
     * @param object|array $person expected fields: name, familyName, dateOfBirth, sex, cityCode
     * @param object|array $fieldMap if provided, allow to remap field names
     * @param bool $partial validate only present fields
     * @return false|array ok: null, error: array with names of fields that don't match
     */
    public function validate($person, $fieldMap = null, $partial = false)
    {
        if (is_array($person)) {
            $person = (object) $person;
        }

        if (is_array($fieldMap)) {
            $fieldMap = (object) $fieldMap;
        }

        $f_name = isset($fieldMap->name) ? $fieldMap->name : 'name';
        $f_familyName = isset($fieldMap->familyName) ? $fieldMap->familyName : 'familyName';
        $f_dateOfBirth = isset($fieldMap->dateOfBirth) ? $fieldMap->dateOfBirth : 'dateOfBirth';
        $f_sex = isset($fieldMap->sex) ? $fieldMap->sex : 'sex';
        $f_cityCode = isset($fieldMap->cityCode) ? $fieldMap->cityCode : 'cityCode';

        $errs = [];

        if (!(($partial && !property_exists($person, $f_name)) || $this->matchName($person->{$f_name}))) {
            $errs[] = 'name';
        }
        if (!(($partial && !property_exists($person, $f_familyName)) || $this->matchFamilyName($person->{$f_familyName}))) {
            $errs[] = 'familyName';
        }
        if (!(($partial && !property_exists($person, $f_dateOfBirth)) || $this->matchDateOfBirth($person->{$f_dateOfBirth}))) {
            $errs[] = 'dateOfBirth';
        }
        if (!(($partial && !property_exists($person, $f_sex)) || $this->matchSex($person->{$f_sex}))) {
            $errs[] = 'sex';
        }
        if (!(($partial && !property_exists($person, $f_cityCode)) || $this->matchCityCode($person->{$f_cityCode}))) {
            $errs[] = 'cityCode';
        }

        if (empty($errs)) {
            return null;
        }

        return $errs;
    }

    /**
     * @param string $name
     * @return bool
     */
    public function matchName($name)
    {
        $nm = static::processName($name);
        $nm2 = substr($this->codiceFiscale, 3, 3);
        return $nm === $nm2;
    }

    /**
     * @param string $familyName
     * @return bool
     */
    public function matchFamilyName($familyName)
    {
        $fn = static::processFamilyName($familyName);
        $fn2 = substr($this->codiceFiscale, 0, 3);
        return $fn === $fn2;
    }

    /**
     * @param DateTime|string|int|object $dateOfBirth @see self::parseDate()
     * @return bool
     * @throws CodicefiscaleException
     */
    public function matchDateOfBirth($dateOfBirth)
    {
        $input = static::parseDate($dateOfBirth);
        $d = $this->getDateOfBirthRaw();
        $cf_date = $d->year . '-' . $d->month . '-' . $d->day;

        return $input->format('y-m-d') === $cf_date;
    }

    /**
     * @param string $sex 'M' or 'F'
     * @return bool
     */
    public function matchSex($sex)
    {
        $s = strtoupper(trim($sex));
        $s1 = $this->getSex();
        return $s === $s1;
    }

    /**
     * @param string $cityCode - the code of the city: "codice catastale" also known as "codice belfiore"
     * @return bool
     */
    public function matchCityCode($cityCode)
    {
        $cc = strtoupper(trim($cityCode));
        $cc2 = $this->getCityCode();

        return $cc === $cc2;
    }


    /**
     * @return string 'M' or 'F'
     */
    public function getSex()
    {
        if ($this->sex === NULL) {
            $day = substr($this->getBaseVariation(), 9, 2);

            if (intval($day) > 40) {
                $this->sex = 'F';
            } else {
                $this->sex = 'M';
            }
        }

        return $this->sex;
    }


    /**
     * extract the date of birth, according to the century
     * @param string|int $century the year  4 or 2 digits - the last 2 digits will be replaced with 00. if < 100 will be multiplied by 100
     * @return DateTime
     */
    public function getDateOfBirth($century)
    {
        $century = static::century4digits($century);

        $d = $this->getDateOfBirthRaw();
        $mm = (int) $d->month;
        $dd = (int) $d->day;
        $yy = (int) $d->year;

        return (new DateTime())->setDate($century + $yy, $mm, $dd);
    }

    /**
     * extract raw date of birth
     * @return object  {"day":"dd","month":"mm","year":"yy"}
     */
    public function getDateOfBirthRaw()
    {
        if ($this->dateOfBirth === NULL) {
            $this->dateOfBirth = static::extractDateOfBirthRaw($this->codiceFiscale);
        }
        return $this->dateOfBirth;
    }

    /**
     * @return string - the code of the city: "codice catastale" also known as "codice belfiore"
     */
    public function getCityCode()
    {
        return substr($this->getBaseVariation(), 11, 4);
    }


    /**
     * @return string the fiscal code without "omocodia" variations
     */
    public function getBaseVariation()
    {
        if (!$this->isOmocodia()) {
            return $this->codiceFiscale;
        }
        if (!isset($this->codiceFiscaleBase)) {
            $this->codiceFiscaleBase = static::calculateBaseVariation($this->codiceFiscale);
        }
        return $this->codiceFiscaleBase;
    }

    /**
     * the variations are calculated using a binary number increment pattern:
     * XXXXXX00X00X001X
     * XXXXXX00X00X010X
     * XXXXXX00X00X011X
     * XXXXXX00X00X100X
     * ......
     * @param integer $num the index of variation,
     * @return array if $num === null then: all 127 possible "omocodia" variations else: the requested variation
     * @throws CodicefiscaleException
     */
    public function generateVariations($num = null)
    {
        if (isset($num)) {
            if ($num < 1 || $num > 127) {
                throw new CodicefiscaleException('variation-not-exists', compact('num'));
            }
        }
        $ind = array_reverse(static::$omocodieIndexes);
        $map = array_flip(static::$omocodiaMap);

        $res = [];
        for ($i = (isset($num) ? $num : 1); $i < (isset($num) ? ($num + 1) : 128); $i++) {
            $tmp = $this->getBaseVariation();
            $pattern = strrev(decbin($i));
            $len = strlen($pattern);

            for ($j = 0; $j < $len; $j++) {
                if ($pattern[$j]) {
                    $tmp[$ind[$j]] = $map[$tmp[$ind[$j]]];
                }
            }
            $tmp[15] = static::calcControlDigit($tmp);
            $res[] = $tmp;
        }

        if (isset($num)) {
            return $res[0];
        }

        return $res;
    }

    /**
     * @param int $minAge
     * @param DateTime|string|int|object $currDateTime @see self::parseDate()
     *
     * @return DateTime return the most probable date of birth,
     * basing on the current date and the minimum age specified
     */
    public function getProbableDateOfBirth($minAge = null, $currDateTime = null)
    {
        $arr = $this->getDateOfBirthRaw();

        $dd = (int) $arr->day;
        $mm = (int) $arr->month;
        $yy = (int) $arr->year;

        return static::calculateProbableDateOfBirth($yy, $mm, $dd, $minAge, $currDateTime);
    }


    /** @return string */
    public function __toString()
    {
        return $this->codiceFiscale;
    }



    //#####################################################
    // static methods
    //#####################################################

    /**
     * @param  string $cod the fiscal code - must be an uppercase string
     * @return bool determine if the fiscal code is "omocodia"
     */
    public static function isOmocodiaStr($cod)
    {
        $ind = static::$omocodieIndexes;
        foreach ($ind as $i) {
            if (isset(static::$omocodiaMap[$cod[$i]])) {
                return true;
            }
        }
        return false;
    }


    /**
     * check if the date of birth is coherent with te code
     * @param  string $cod the fiscal code - must be an uppercase string
     * @param string|int $century the year  4 or 2 digits - the last 2 digits will be replaced with 00. if < 100 will be multiplied by 100
     * @return bool
     */
    public static function isDateOfBirthCorrect($cod, $century = null)
    {
        if (isset($century)) {
            $century = static::century4digits($century);
        }

        $d = static::extractDateOfBirthRaw($cod);

        $year = (int) $d->year;
        $month = (int) $d->month;
        $day = (int) $d->day;

        if (in_array($month, [11, 4, 6, 9])) {
            $max_days = 30;
        } else if ($month == 2) {

            if ($century) {
                if (static::isLeapYear($century + $year)) {
                    $max_days = 29;
                } else {
                    $max_days = 28;
                }
            } else {
                if ($year !== 0 && ($year % 4) !== 0) { // for sure,it's not leap year
                    $max_days = 28;
                } else {
                    $max_days = 29;
                }
            }
        } else {
            $max_days = 31;
        }

        return $day <= $max_days;
    }

    /**
     * check if the fiscal code format is correct
     * @param  string $cod the fiscal code - must be an uppercase string
     * @return bool
     */
    public static function strMatchFormat($cod)
    {
        return (bool) preg_match(static::$regex_format, $cod);
    }


    /**
     * calculate the control digit using the first 15 characters, and match it against the 16st digit
     * @param  string $cod the fiscal code - must be an uppercase string
     * @return bool
     * @throws CodicefiscaleException
     */
    public static function strMatchControlDigit($cod)
    {
        $len = strlen($cod);
        if ($len != 16) {
            return false;
        }
        $tmp = substr($cod, 0, $len - 1);
        $c = static::calcControlDigit($tmp);
        return ($c === $cod[$len - 1]);
    }



    /**
     * @param  string $cod the fiscal code - must be an uppercase string
     * @return object  {"day":"dd","month":"mm","year":"yy"}
     */
    public static function extractDateOfBirthRaw($cod)
    {
        $cfbase = static::calculateBaseVariation($cod);

        $year = substr($cfbase, 6, 2);
        $month = substr($cfbase, 8, 1);
        $day = substr($cfbase, 9, 2);

        $monthIndex = strpos(static::$monthMap, $month);

        if ($day >= 40) { // in women
            $day = '' . ($day - 40);
        }
        $month = $monthIndex + 1;
        $month = str_pad($month, 2, '0', STR_PAD_LEFT);


        return (object) compact('year', 'month', 'day');
    }


    /**
     * @param  string $cf the fiscal code - must be an uppercase string
     * @return string the fiscal code without "omocodia" variations
     */
    public static function calculateBaseVariation($cf)
    {
        $ind = static::$omocodieIndexes;
        foreach ($ind as $i) {
            if (!ctype_digit($cf[$i])) {
                $cf[$i] = static::$omocodiaMap[$cf[$i]];
            }
        }
        return $cf;
    }


    /**
     * @param string $yy year 2 digits
     * @param string $mm month 2 digits
     * @param string $dd day 2 digits
     * @param int $minAge
     * @param DateTime|string|int|object $currDateTime @see self::parseDate()
     *
     * @return DateTime return the most probable date of birth,
     * basing on the current date and the minimum age specified
     * @throws CodicefiscaleException
     */
    public static function calculateProbableDateOfBirth($yy, $mm, $dd, $minAge = null, $currDateTime = null)
    {
        if (!isset($currDateTime)) {
            $currDateTime = new DateTime();
        }

        $currDateTime = static::parseDate($currDateTime);
        $currYear = (int) $currDateTime->format('Y');
        $century = static::century4digits($currYear);

        $yy = (int)$yy;
        $before = ($century - 100) + $yy;
        $after = $century + $yy;

        $afterDT = (new DateTime())->setDate($after, $mm, $dd);
        $beforeDT = (new DateTime())->setDate($before, $mm, $dd);

        if ($afterDT > $currDateTime) {
            return $beforeDT;
        }
        if (!$minAge) {
            return $afterDT;
        }
        if (($currYear - $after) >= $minAge) {
            return $afterDT;
        }

        return $beforeDT;
    }


    /**
     * @param string|int $century the year  4 or 2 digits - the last 2 digits will be replaced with 00. if < 100 will be multiplied by 100
     * @return int the century for example: 1980 => 1900, 19=>1900,  2020 => 2000, 20 => 2000
     */
    private static function century4digits($century)
    {
        $res = floor(((int) $century) / 100) * 100;
        if ($res < 100) {
            $res *= 100;
        }
        return $res;
    }


    /**
     * @param DateTime|string|int|object $date
     * accept various formats
     * - DateTime
     * - string: strtotime() format
     * - int o numeric string: unix timestamp format
     * @return DateTime
     * @throws CodicefiscaleException
     */
    private static function parseDate($date)
    {
        $dt = null;
        if ($date instanceof DateTime) {
            $dt = $date;
        } else if (is_string($date) && !static::isIntStr($date)) {
            try {
                $dt = new DateTime($date);
            } catch (\Exception $e) {
                throw new CodicefiscaleException('date-parse-failed', compact('date'));
            }
        } else if (is_int($date) || static::isIntStr($date)) {
            $dt = new DateTime();
            $dt->setTimestamp($date);
        }

        if ($dt === null) {
            throw new CodicefiscaleException('date-parse-failed', compact('date'));
        }

        return $dt;
    }

    /**
     * @param string $input
     * @return bool
     */
    private static function isIntStr($input)
    {
        if (!is_string($input)) {
            return false;
        }

        if ($input[0] == '-') {
            return ctype_digit(substr($input, 1));
        }
        return ctype_digit($input);
    }


    /**
     * @param int $year
     * @return bool
     */
    private static function isLeapYear($year)
    {
        return ((($year % 4) == 0) && ((($year % 100) != 0) || (($year % 400) == 0)));
    }

    /**
     * @param string $name
     * @param string $familyName
     * @param string $dateOfBirth
     * @param string $sex
     * @param string $cityCode
     * @return string
     * @throws CodicefiscaleException
     */
    private static function _calculate($name, $familyName, $dateOfBirth, $sex, $cityCode)
    {

        $cityCode = strtoupper(trim($cityCode));
        if (!preg_match(static::$regex_city_code, $cityCode)) {
            throw new CodicefiscaleException('wrong-city-code-format');
        }

        $tmp = static::processFamilyName($familyName) . static::processName($name) . static::processDateOfBirth($dateOfBirth, $sex) . $cityCode;
        $c = static::calcControlDigit($tmp);

        return $tmp . $c;
    }

    /**
     * @param string $name
     * @return string
     * @throws CodicefiscaleException
     */
    private static function processName($name)
    {
        $nm = static::preProcessNames($name);

        $result = '';
        $count = 0;
        $l = strlen($nm);

        for ($i = 0; $i < $l; $i++) {
            if (!static::isVowel($nm[$i])) {
                if ($count === 0 || $count === 2 || $count === 3) {
                    $result .= $nm[$i];
                }
                if ($count === 3) {
                    break;
                }
                $count++;
            }
        }

        if (strlen($result) === 3) {
            return $result;
        } else {
            return static::processNames($nm);
        }
    }

    /**
     * @param string $familyName
     * @return string
     * @throws CodicefiscaleException
     */
    private static function processFamilyName($familyName)
    {
        return static::processNames(static::preProcessNames($familyName));
    }

    /**
     * @param $string $name
     * @return string
     * @throws CodicefiscaleException
     *
     * family name processing
     * used also as first step for first name processing
     */
    private static function processNames($name)
    {
        $l = strlen($name);
        if ($l < 2) {
            throw new CodicefiscaleException("name-or-familyname-too-short");
        }

        $result = '';
        for ($i = 0; $i < $l && strlen($result) < 3; $i++) {
            if (!static::isVowel($name[$i])) { //consonants
                $result .= $name[$i];
            }
        }
        for ($i = 0; $i < $l && strlen($result) < 3; $i++) {
            if (static::isVowel($name[$i])) { //vowels
                $result .= $name[$i];
            }
        }
        if (strlen($result) < 3) {
            $result .= 'X';
        }
        return $result;
    }

    /**
     * @param string $data_str
     * @param string $sesso
     * @return string
     * @throws CodicefiscaleException
     */
    private static function processDateOfBirth($data_str, $sesso)
    {
        $dt = static::parseDate($data_str);
        return static::processDateOfBirthDT($dt, $sesso);
    }

    /**
     * @param DateTime $datetime
     * @param string $sex
     * @return string
     * @throws CodicefiscaleException
     */
    private static function processDateOfBirthDT($datetime, $sex)
    {
        $year = $datetime->format('y');
        $month = $datetime->format('n');
        $day = $datetime->format('j');

        $sex = strtoupper(trim($sex));
        if ($sex === 'F') {
            $day += 40;
        } else if ($sex !== 'M') {
            throw new CodicefiscaleException("sex-wrong-format");
        }
        return str_pad($year, 2, '0', STR_PAD_LEFT) . static::$monthMap[$month - 1] . str_pad($day, 2, '0', STR_PAD_LEFT);
    }


    /**
     * @param string $str
     * @return string|string[]|null
     */
    private static function preProcessNames($str)
    {
        return preg_replace('/[^A-Z]/', '', strtoupper(static::convertSpecialChars($str)));
    }

    /**
     * @param string $c
     * @return bool
     * @throws CodicefiscaleException
     */
    private static function isVowel($c)
    {
        if (empty($c) || strlen($c) !== 1) {
            throw new CodicefiscaleException("bad-character-format", ['character' => $c]);
        }
        return strpos(static::$vowels, $c) !== FALSE;
    }

    /**
     * @param string $str
     * @return string
     */
    private static function convertSpecialChars($str)
    {
        return str_replace(static::$specialChars, static::$specialCharsReplace, $str);
    }

    /**
     * @param string $cf
     * @return string
     * @throws CodicefiscaleException
     */
    public static function calcControlDigit($cf)
    {
        $sum = 0;

        $l = strlen($cf);
        if ($l < 15) {
            throw new CodicefiscaleException('too-short-for-checkdigit', ['length' => $l]);
        }

        for ($i = 0; $i < 15; $i++) {
            if ($i % 2 === 1) {
                $sum += static::$even_codes[$cf[$i]];
            } else {
                $sum += static::$odd_codes[$cf[$i]];
            }
        }

        return static::$alphabet[$sum % 26];
    }

    public static function init()
    {

        /*
         to avoid problems with file characters encoding,
         is better to encode special character into json

          $specialChars = ['Ä', 'ä', 'Æ', 'æ', 'Ö', 'ö', 'Œ', 'œ', 'Ü', 'ü', 'ß',
          'à', 'á', 'â', 'ã', 'ç', 'è', 'é', 'ê', 'ë', 'ì', 'í', 'î', 'ï', 'ñ', 'ò',
          'ó', 'ô', 'õ', 'ù', 'ú', 'û', 'ý', 'ÿ', 'č', 'š', 'ž', 'À', 'Á', 'Â', 'Ã',
          'Ç', 'È', 'É', 'Ê', 'Ë', 'Ì', 'Í', 'Î', 'Ï', 'Ñ', 'Ò', 'Ó', 'Ô', 'Õ', 'Ù',
          'Ú', 'Û', 'Ý', 'Č', 'Š', 'Ž'];

          echo "\n" . json_encode($specialChars);

         */

        static::$specialChars = json_decode(
            '["\u00c4","\u00e4","\u00c6","\u00e6","\u00d6","\u00f6","\u0152",
                "\u0153","\u00dc","\u00fc","\u00df","\u00e0","\u00e1","\u00e2","\u00e3",
                "\u00e7","\u00e8","\u00e9","\u00ea","\u00eb","\u00ec","\u00ed","\u00ee",
                "\u00ef","\u00f1","\u00f2","\u00f3","\u00f4","\u00f5","\u00f9","\u00fa",
                "\u00fb","\u00fd","\u00ff","\u010d","\u0161","\u017e","\u00c0","\u00c1",
                "\u00c2","\u00c3","\u00c7","\u00c8","\u00c9","\u00ca","\u00cb","\u00cc",
                "\u00cd","\u00ce","\u00cf","\u00d1","\u00d2","\u00d3","\u00d4","\u00d5",
                "\u00d9","\u00da","\u00db","\u00dd","\u010c","\u0160","\u017d"]',
            true
        );
    }

    /** @var string */
    private static $regex_city_code = '/^[A-Z]\\d{3}$/';
    /** @var string */
    private static $regex_format = '/^[A-Z]{6}[LMNPQRSTUV0-9]{2}[ABCDEHLMPRST][LMNPQRSTUV0-9]{2}[A-Z][LMNPQRSTUV0-9]{3}[A-Z]$/';
    /** @var string */
    private static $alphabet = "ABCDEFGHIJKLMNOPQRSTUVWXYZ";
    /** @var string */
    private static $monthMap = "ABCDEHLMPRST";
    /** @var string */
    private static $vowels = "AEIOU";
    /** @var */
    private static $specialChars;

    /** @var string[] */
    private static $specialCharsReplace = [
        'AE', 'AE', 'AE', 'AE', 'OE', 'OE', 'OE', 'OE', 'UE', 'UE', 'SS',
        'A', 'A', 'A', 'A', 'C', 'E', 'E', 'E', 'E', 'I', 'I', 'I', 'I', 'N', 'O', 'O', 'O', 'O',
        'U', 'U', 'U', 'Y', 'Y', 'C', 'S', 'Z', 'A', 'A', 'A', 'A', 'C', 'E', 'E', 'E', 'E', 'I',
        'I', 'I', 'I', 'N', 'O', 'O', 'O', 'O', 'U', 'U', 'U', 'Y', 'C', 'S', 'Z'
    ];

    /** @var int[] */
    private static $omocodieIndexes = [6, 7, 9, 10, 12, 13, 14];

    /** @var int[] */
    private static $omocodiaMap = [
        'L' => 0, 'M' => 1, 'N' => 2, 'P' => 3, 'Q' => 4, 'R' => 5, 'S' => 6, 'T' => 7, 'U' => 8, 'V' => 9
    ];
    /** @var int[] */
    private static $even_codes = [
        '0' => 0, '1' => 1, '2' => 2, '3' => 3, '4' => 4, '5' => 5, '6' => 6, '7' => 7, '8' => 8, '9' => 9, 'A' => 0, 'B' => 1, 'C' => 2, 'D' => 3, 'E' => 4, 'F' => 5, 'G' => 6, 'H' => 7, 'I' => 8, 'J' => 9, 'K' => 10, 'L' => 11, 'M' => 12, 'N' => 13, 'O' => 14, 'P' => 15, 'Q' => 16, 'R' => 17, 'S' => 18, 'T' => 19, 'U' => 20, 'V' => 21, 'W' => 22, 'X' => 23, 'Y' => 24, 'Z' => 25
    ];
    /** @var int[] */
    private static $odd_codes = [
        '0' => 1, '1' => 0, '2' => 5, '3' => 7, '4' => 9, '5' => 13, '6' => 15, '7' => 17, '8' => 19, '9' => 21, 'A' => 1, 'B' => 0, 'C' => 5, 'D' => 7, 'E' => 9, 'F' => 13, 'G' => 15, 'H' => 17, 'I' => 19, 'J' => 21, 'K' => 2, 'L' => 4, 'M' => 18, 'N' => 20, 'O' => 11, 'P' => 3, 'Q' => 6, 'R' => 8, 'S' => 12, 'T' => 14, 'U' => 16, 'V' => 10, 'W' => 22, 'X' => 25, 'Y' => 24, 'Z' => 23
    ];
}

/**
 * static initializzation
 */
CodiceFiscale::init();
