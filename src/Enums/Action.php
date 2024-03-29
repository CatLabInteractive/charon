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

    // Destroy
    const DESTROY = 'destroy';

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
        return in_array($action, [ Action::CREATE, Action::EDIT, Action::IDENTIFIER ]);
    }

    /**
     * @param $action
     * @return bool
     */
    public static function isIdentifierContext($action)
    {
        return $action == self::IDENTIFIER;
    }

    /**
     * @param string $action
     * @throws InvalidContextAction
     */
    public static function checkValid($action)
    {
        if (!self::isReadContext($action) && !self::isWriteContext($action)) {
            throw InvalidContextAction::makeTranslatable('Unknown context provided: %s.', [ $action ]);
        }
    }

    /**
     * @param string $cardinality
     * @return string
     */
    public static function getReadAction(string $cardinality): string
    {
        if ($cardinality === Cardinality::MANY) {
            return Action::INDEX;
        } else {
            return Action::VIEW;
        }
    }
}
