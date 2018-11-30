<?php

namespace CatLab\Charon\Validation;

use CatLab\Requirements\Interfaces\Validator;

/**
 * Class ResourceValidator
 * @package CatLab\Charon\Validation
 */
abstract class ResourceValidator implements Validator
{
    /**
     * @var mixed
     */
    protected $original;

    /**
     * @return bool
     */
    public function isNew()
    {
        return !isset($this->original);
    }

    /**
     * @return mixed
     */
    public function getOriginal()
    {
        return $this->original;
    }

    /**
     * @param mixed $original
     * @return ResourceValidator
     */
    public function setOriginal($original)
    {
        $this->original = $original;
        return $this;
    }
}