<?php

namespace CatLab\Charon\Enums;

use CatLab\Charon\Exceptions\InvalidContextAction;

/**
 * class Context
 * @package CatLab\RESTResource\Contracts
 */
class Action
{
    // Readable
    const INDEX = 'index';
    const VIEW = 'view';

    const IDENTIFIER = 'identifier';

    // Writable
    const CREATE = 'create';
    const EDIT = 'edit';

    /**
     * @param string $action
     * @return bool
     */
    public static function isReadContext($action)
    {
        return in_array($action, [ Action::INDEX, Action::VIEW, Action::IDENTIFIER ]);
    }

    /**
     * @param string $action
     * @return bool
     */
    public static function isWriteContext($action)
    {
        return in_array($action, [ Action::CREATE, Action::EDIT ]);
    }

    /**
     * @param string $action
     * @throws InvalidContextAction
     */
    public static function checkValid($action)
    {
        if (!self::isReadContext($action) && !self::isWriteContext($action)) {
            throw new InvalidContextAction("Unknown context provided: " . $action);
        }
    }
}