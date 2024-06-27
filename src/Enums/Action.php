<?php

declare(strict_types=1);

namespace CatLab\Charon\Enums;

use CatLab\Charon\Exceptions\InvalidContextAction;

/**
 * class Context
 * @package CatLab\RESTResource\Contracts
 */
class Action
{
    // Readable
    public const INDEX = 'index';

    public const VIEW = 'view';

    public const IDENTIFIER = 'identifier';

    // Writable
    public const CREATE = 'create';

    public const EDIT = 'edit';

    // Destroy
    public const DESTROY = 'destroy';

    /**
     * @param string $action
     * @return bool
     */
    public static function isReadContext($action): bool
    {
        return in_array($action, [ Action::INDEX, Action::VIEW, Action::IDENTIFIER ]);
    }

    /**
     * @param string $action
     * @return bool
     */
    public static function isWriteContext($action): bool
    {
        return in_array($action, [ Action::CREATE, Action::EDIT, Action::IDENTIFIER ]);
    }

    /**
     * @param $action
     * @return bool
     */
    public static function isIdentifierContext($action): bool
    {
        return $action == self::IDENTIFIER;
    }

    /**
     * @param string $action
     * @throws InvalidContextAction
     */
    public static function checkValid($action): void
    {
        if (self::isReadContext($action)) {
            return;
        }

        if (self::isWriteContext($action)) {
            return;
        }

        throw InvalidContextAction::makeTranslatable('Unknown context provided: %s.', [ $action ]);
    }

    /**
     * @param string $cardinality
     * @return string
     */
    public static function getReadAction(string $cardinality): string
    {
        if ($cardinality === Cardinality::MANY) {
            return Action::INDEX;
        }

        return Action::VIEW;
    }
}
