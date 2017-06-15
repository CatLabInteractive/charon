<?php

namespace CatLab\Charon\Exceptions;

/**
 * Class InvalidContextAction
 * @package CatLab\RESTResource\Exceptions
 */
class InvalidContextAction extends ResourceException
{
    const WRITEABLE = 'Writeable';
    const READABLE = 'Readable';

    public static function create($expected, $actual)
    {
        return new self($expected . ' context is expected, instead got ' . $actual . '.');
    }

    /**
     * @param $actual
     * @return InvalidContextAction
     */
    public static function expectedWriteable($actual)
    {
        return new self(self::WRITEABLE, $actual);
    }

    /**
     * @param $actual
     * @return InvalidContextAction
     */
    public static function expectedReadable($actual)
    {
        return new self(self::READABLE, $actual);
    }
}