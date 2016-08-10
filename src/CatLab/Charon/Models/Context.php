<?php

namespace CatLab\Charon\Models;

use CatLab\Charon\Collections\ProcessorCollection;
use CatLab\Charon\Enums\Action;
use CatLab\Charon\Exceptions\VariableNotFoundInContext;
use CatLab\Charon\Interfaces\Processor;
use CatLab\Charon\Models\Properties\Base\Field;
use CatLab\Charon\Interfaces\Context as ContextContract;

/**
 * Class Context
 * @package CatLab\RESTResource\Models
 */
class Context implements ContextContract
{
    /**
     * @var string
     */
    private $action;

    /**
     * @var mixed[]
     */
    private $parameters;

    /**
     * @var Context[]
     */
    private $childContext = [];

    /**
     * @var string
     */
    private $fieldsToShow;

    /**
     * @var string
     */
    private $fieldsToExpand;

    /**
     * @var ProcessorCollection
     */
    private $processors;

    /**
     * @var string
     */
    private $url;

    const FIELD_PATH_DELIMITER = '.';

    /**
     * Context constructor.
     * @param string $action
     * @param array $parameters
     */
    public function __construct($action, array $parameters = [])
    {
        $this->processors = new ProcessorCollection();

        $this->action = $action;
        $this->parameters = $parameters;

        $this->fieldsToExpand = [];
        $this->fieldsToShow = [];
    }

    /**
     * @param Processor $processor
     * @return $this
     */
    public function addProcessor(Processor $processor)
    {
        $this->processors->add($processor);
        return $this;
    }

    /**
     * @param string $name
     * @param string $parameter
     * @return ContextContract
     */
    public function setParameter(string $name, $parameter) : ContextContract
    {
        $this->childContext = [];

        $this->parameters[$name] = $parameter;
        return $this;
    }

    /**
     * @return string
     */
    public function getAction() : string
    {
        return $this->action;
    }

    /**
     * @param Field $field
     * @param string $action
     * @return ContextContract
     */
    public function getChildContext(Field $field, $action = Action::INDEX) : ContextContract
    {
        $className = spl_object_hash($field) . '|' . $action;

        if (!isset($this->childContext[$className])) {
            $childContext = new self($action);

            $childContext->parameters = $this->parameters;
            $childContext->fieldsToShow = $this->fieldsToShow;
            $childContext->fieldsToExpand = $this->fieldsToExpand;
            $childContext->processors = $this->processors;

            $this->childContext[$className] = $childContext;
        }

        return $this->childContext[$className];
    }

    /**
     * @param string $name
     * @param mixed $entity
     * @return \mixed
     * @throws VariableNotFoundInContext
     */
    public function getParameter(string $name, $entity = null)
    {
        return $this->parameters[$name] ?? null;
    }

    /**
     * @param string $field
     * @return $this
     */
    public function showField($field)
    {
        $path = explode(self::FIELD_PATH_DELIMITER, $field);
        $this->touchArrayPath($this->fieldsToShow, $path);

        return $this;
    }

    /**
     * @param string $field
     * @return $this
     */
    public function expandField($field)
    {
        $path = explode(self::FIELD_PATH_DELIMITER, $field);
        $this->touchArrayPath($this->fieldsToExpand, $path);

        return $this;
    }

    /**
     * @param string[] $fields
     * @return $this
     */
    public function showFields(array $fields)
    {
        foreach ($fields as $field) {
            $this->showField($field);
        }
        return $this;
    }

    /**
     * @param string[] $fields
     * @return $this
     */
    public function expandFields(array $fields)
    {
        foreach ($fields as $field) {
            $this->expandField($field);
        }

        return $this;
    }

    /**
     * @param string[] $fieldPath
     * @return bool|null
     */
    public function shouldShowField(array $fieldPath)
    {
        if (count($this->fieldsToShow) === 0) {
            return null;
        }

        return $this->arrayPathExists($this->fieldsToShow, $fieldPath);
    }

    /**
     * @param string[] $fieldPath
     * @return bool|null
     */
    public function shouldExpandField(array $fieldPath)
    {
        if (count($this->fieldsToExpand) === 0) {
            return null;
        }

        return $this->arrayPathExists($this->fieldsToExpand, $fieldPath);
    }

    /**
     * @return ProcessorCollection
     */
    public function getProcessors() : ProcessorCollection
    {
        return $this->processors;
    }

    /**
     * @return string
     */
    public function getUrl()
    {
        return $this->url;
    }

    /**
     * @param string $url
     */
    public function setUrl(string $url)
    {
        $this->url = $url;
    }

    /**
     * @param bool[] $target
     * @param string[] $path
     */
    private function touchArrayPath(array &$target, array $path)
    {
        $key = array_shift($path);
        if (!$key) {
            return;
        }

        if (!isset($target[$key])) {
            $target[$key] = [];
        }
        $this->touchArrayPath($target[$key], $path);
    }

    /**
     * @param array $source
     * @param array $path
     * @return bool
     */
    private function arrayPathExists(array &$source, array $path)
    {
        $key = array_shift($path);

        // Check for recursive (always valid; currently we don't allow recursive on non leave nodes)
        if (
            isset($source[$key . '*']) &&
            count($source[$key . '*']) === 0 /*&&
            count($path) === 0*/

        ) {
            return true;
        }

        if (isset($source[$key])) {
            if (count($source[$key]) === 0) {
                return count($path) > 0 ? null : true;
            } elseif (count($path) > 0) {
                return $this->arrayPathExists($source[$key], $path);
            } else {
                return true;
            }
        } else {
            return false;
        }
    }
}