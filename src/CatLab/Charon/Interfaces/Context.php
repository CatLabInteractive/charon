<?php

namespace CatLab\Charon\Interfaces;

use CatLab\Charon\Collections\ProcessorCollection;
use CatLab\Charon\Enums\Action;
use CatLab\Charon\Models\Properties\Base\Field;

/**
 * Interface Context
 * @package CatLab\RESTResource\Contracts
 */
interface Context
{
    /**
     * Return the context that will be used for children
     * @param Field $field
     * @param string $action
     * @return Context
     */
    public function getChildContext(Field $field, $action = Action::INDEX) : Context;

    /**
     * @param string $name
     * @param $value
     * @return Context
     */
    public function setParameter(string $name, $value) : Context;

    /**
     * @param string $name
     * @param mixed $entity
     * @return mixed[]
     */
    public function getParameter(string $name, $entity);

    /**
     * @return string
     */
    public function getAction() : string;

    /**
     * @param string[] $fieldPath
     * @return bool|null
     */
    public function shouldShowField(array $fieldPath);

    /**
     * @param string[] $fieldPath
     * @return bool|null
     */
    public function shouldExpandField(array $fieldPath);

    /**
     * @return ProcessorCollection
     */
    public function getProcessors() : ProcessorCollection;
}