<?php

declare(strict_types=1);

namespace CatLab\Charon\Enums;

/**
 * Class Method
 * @package CatLab\RESTResource\Enums
 */
class Method
{
    public const GET = 'get';

    public const POST = 'post';

    public const PUT = 'put';

    public const DELETE = 'delete';

    public const HEAD = 'head';

    public const LINK = 'link';

    public const UNLINK = 'unlink';

    /**
     * @param string $method
     * @param string $cardinality
     * @return string
     */
    public static function toAction(string $method, string $cardinality): string
    {
        // Default value based on action
        switch ($method) {
            case self::GET:
                return Action::getReadAction($cardinality);

            case self::LINK:
            case self::UNLINK:
                return Action::IDENTIFIER;

            case self::POST:
                return Action::CREATE;

            case self::PUT:
                return Action::EDIT;

            default:
                return Action::VIEW;
        }
    }
}
