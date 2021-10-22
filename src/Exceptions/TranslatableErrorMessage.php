<?php

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
    private $values;

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
    public function __toString()
    {
        return $this->getMessage();
    }
}
