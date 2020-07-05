<?php

namespace IvanoMatteo\CodiceFiscale;


class CodicefiscaleException extends \Exception
{

    const FORMAT = 1;
    const DATE_OF_BIRTH = 2;
    const CONTROL_DIGIT = 3;

}
