<?php

namespace CatLab\Charon\Interfaces;

use CatLab\Requirements\Exceptions\ValidationException;


/**
* Interface Transformer
* @package CatLab\RESTResource\Contracts
*/
interface Validator extends Serializable
{
    /**
     * @param $value
     * @throws ValidationException
     * @return mixed
     */
    public function validate($value);
}