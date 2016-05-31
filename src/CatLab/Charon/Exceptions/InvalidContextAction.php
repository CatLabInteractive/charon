<?php

namespace CatLab\Charon\Exceptions;

/**
 * Class InvalidContextAction
 * @package CatLab\RESTResource\Exceptions
 */
class InvalidContextAction extends ResourceException
{
    public static function create($expected, $actual)
    {
        return new self($expected . ' context is expected, instead got ' . $actual . '.');
    }
}