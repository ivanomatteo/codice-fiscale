# codice ficale

[![Latest Version on Packagist](https://img.shields.io/packagist/v/ivanomatteo/CodiceFiscale.svg?style=flat-square)](https://packagist.org/packages/ivanomatteo/codicefiscale)
[![Total Downloads](https://img.shields.io/packagist/dt/ivanomatteo/CodiceFiscale.svg?style=flat-square)](https://packagist.org/packages/ivanomatteo/codicefiscale)

Calcolo ed estrazione dati (calcolo inverso) dal codice ficale, con omocodie.

Supporta inoltre le espansioni dei caratteri diacritici, come ad esempio:

'Ä' => 'AE',  'ß' => 'SS'





## Installation

You can install the package via composer:

```bash
composer require ivanomatteo/CodiceFiscale
```

## Usage

``` php
use IvanoMatteo\CodiceFiscale\CodiceFiscale;


try{
    
    // solleva un'eccezione se il codice ficale ha un foramto errato 
    // o il carattere di controllo non corrisponde

    $c = CodiceFiscale::parse("RSSMRAULRL1H50MM",1900); // verifica meglio se l'anno è bisestile fornendo il secolo
    $c = CodiceFiscale::parse("RSSMRAULRL1H50MM");

    echo "\n"."matchName ".($c->matchName('Mario')? 'si' : 'no');
    echo "\n"."isOmocodia ".($c->isOmocodia()? 'si' : 'no');
    
    // per estrarre la data in formato DateTime è necessario fornire il secolo di riferimento
    echo "\n"."getDateOfBirth ".$c->getDateOfBirth(1900)->format('Y-m-d'); 

    // { "anno":"aa", "mese":"mm", "giorno":"gg" }
    echo "\n"."getDateOfBirthObj ".json_encode($c->getDateOfBirthObj());

    // restituisce la data di nascita nel secolo passato più vicino 
    echo "\n"."getProbableDateOfBirth ".$c->getProbableDateOfBirth()->format('Y-m-d');

    // è possibile specificare l'erà minima e la data corrente di riferimento
    echo "\n"."getProbableDateOfBirth_18 ".$c->getProbableDateOfBirth(18,'2019-01-01')->format('Y-m-d'); 

}catch(\Exception $ex){ 
  // se il formato, la data o il carattere di controllo non sono validi
  echo $ex->getMessage();
}


//
// calcola a partire dai dati
//
$name = 'Mario';
$familyName = 'Rossi';
$dateOfBirth = '1980-10-01';
$sex = 'M';
$cityCode = 'H501';

$cf = CodiceFiscale::calculate($name, $familyName, $dateOfBirth, $sex, $cityCode);

//
// calcola da oggetto o array
//
$person = [
    'name' => $name,
    'familyName' => $familyName,
    'dateOfBirth' => $dateOfBirth,
    'sex' => $sex,
    'cityCode' => $cityCode,
];
$mappa = [  // rimappa i campi nel caso abbiano nomi diversi
    'datanascita' => 'dateOfBirth' 
];
$cfx = CodiceFiscale::calculateObj($person, $mappa);


// genera tutte le 127 variazioni omocodiche
$variazioni = $cf->generateVariations();

// genera la variazione n. 7, 
$variazioni = $cf->generateVariations(7);

```

### Testing

``` bash
composer test
```

### Changelog

Please see [CHANGELOG](CHANGELOG.md) for more information what has changed recently.

## Contributing

Please see [CONTRIBUTING](CONTRIBUTING.md) for details.

### Security

If you discover any security related issues, please email ivanomatteo@gmail.com instead of using the issue tracker.

## Credits

- [Ivano Matteo](https://github.com/ivanomatteo)
- [All Contributors](../../contributors)

## License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.

