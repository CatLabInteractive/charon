<?php

declare(strict_types=1);

namespace CatLab\Charon\Exceptions;

use CatLab\Charon\Interfaces\ErrorMessage;
use Throwable;

/**
 * Class CharonException
 * @package CatLab\Charon\Exceptions
 */
class CharonException extends \Exception
{
    private \CatLab\Charon\Interfaces\ErrorMessage $errorMessage;

    /**
     * @param string $template
     * @param array $values
     * @param int $code
     * @param Throwable|null $previous
     * @return CharonException
     */
    public static function makeTranslatable(string $template, array $values = [], $code = 0, Throwable $previous = null): static
    {
        $message = new TranslatableErrorMessage($template, $values);
        return new static($message, $code, $previous);
    }

    /**
     * @param ErrorMessage|string $message
     * @param int $code
     * @param Throwable|null $previous
     */
    public function __construct(ErrorMessage $message, $code = 0, Throwable $previous = null)
    {
        $this->errorMessage = $message;
        parent::__construct($message->getMessage(), $code, $previous);
    }

    /**
     * @return ErrorMessage
     */
    public function getErrorMessage()
    {
        return $this->errorMessage;
    }
}
