<?php

namespace CatLab\Charon\Enums;

/**
 * Class Method
 * @package CatLab\RESTResource\Enums
 */
class Method
{
    const GET = 'get';
    const POST = 'post';
    const PUT = 'put';
    const DELETE = 'delete';
    const HEAD = 'head';
    const LINK = 'link';
    const UNLINK = 'unlink';

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
