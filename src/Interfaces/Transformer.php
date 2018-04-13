<?php

namespace CatLab\Charon\Interfaces;


/**
* Interface Route
* @package CatLab\RESTResource\Contracts
*/
interface Transformer
{
    /**
     * @param $value
     * @param Context $context
     * @return mixed
     */
    public function toResourceValue($value, Context $context);

    /**
     * @param $value
     * @param Context $context
     * @return mixed
     */
    public function toEntityValue($value, Context $context);

    /**
     * Translate the raw input from a parameter to something usable.
     * @param $value
     * @return mixed
     */
    public function toParameterValue($value);
}