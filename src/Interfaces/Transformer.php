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
}