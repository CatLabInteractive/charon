<?php

declare(strict_types=1);

namespace CatLab\Charon\Exceptions;

use CatLab\Charon\Interfaces\ErrorMessage;

/**
 *
 */
class TranslatableErrorMessage implements ErrorMessage
{
    /**
     * @var string
     */
    private $template;

    /**
     * @var mixed[]
     */
    private array $values;

    /**
     * @param $template
     * @param array $values
     */
    public function __construct($template, array $values = [])
    {
        $this->template = $template;
        $this->values = $values;
    }

    /**
     * @return string
     */
    public function getMessage(): string
    {
        return vsprintf($this->template, $this->values);
    }


    /**
     * @return string
     */
    public function getTemplate()
    {
        return $this->template;
    }

    /**
     * @return mixed[]
     */
    public function getValues(): array
    {
        return $this->values;
    }

    /**
     * @return string
     */
    public function __toString(): string
    {
        return $this->getMessage();
    }
}
