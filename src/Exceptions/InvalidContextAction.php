<?php

declare(strict_types=1);

namespace CatLab\Charon\Exceptions;

/**
 * Class InvalidContextAction
 * @package CatLab\RESTResource\Exceptions
 */
class InvalidContextAction extends ResourceException
{
    public const WRITEABLE = 'Writeable';

    public const READABLE = 'Readable';

    public static function create($expected, $actual)
    {
        return self::makeTranslatable('%s context is expected, instead got %s.', [
            $expected,
            $actual
        ]);
    }

    /**
     * @param $actual
     * @return InvalidContextAction
     */
    public static function expectedWriteable($actual)
    {
        return self::create(self::WRITEABLE, $actual);
    }

    /**
     * @param $actual
     * @return InvalidContextAction
     */
    public static function expectedReadable($actual)
    {
        return self::create(self::READABLE, $actual);
    }
}
