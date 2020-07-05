<?php

use IvanoMatteo\CodiceFiscale\CodiceFiscale;

include __DIR__.'/../vendor/autoload.php';



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
    echo "\n"."getDateOfBirthObj ".json_encode($c->getDateOfBirthRaw());

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


