<?php

declare(strict_types=1);

namespace CatLab\Charon\Models;

use CatLab\Charon\Interfaces\InputParser;
use CatLab\Charon\Interfaces\Processor;
use CatLab\Charon\Interfaces\Context as ContextContract;
use CatLab\Charon\Collections\InputParserCollection;
use CatLab\Charon\Collections\ProcessorCollection;
use CatLab\Charon\Enums\Action;
use CatLab\Charon\Models\Properties\Base\Field;
use CatLab\Charon\Exceptions\VariableNotFoundInContext;

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
    private array $parameters;

    /**
     * @var Context[]
     */
    private array $childContext = [];

    /**
     * @var string
     */
    private array $fieldsToShow = [];

    /**
     * @var string
     */
    private array $fieldsToExpand = [];

    private \CatLab\Charon\Collections\ProcessorCollection $processors;

    private ?string $url = null;

    private \CatLab\Charon\Collections\InputParserCollection $inputParsers;

    /**
     *
     */
    public const FIELD_PATH_DELIMITER = '.';

    /**
     * Context constructor.
     * @param string $action
     * @param array $parameters
     */
    public function __construct($action, array $parameters = [])
    {
        $this->processors = new ProcessorCollection();
        $this->inputParsers = new InputParserCollection();

        $this->action = $action;
        $this->parameters = $parameters;
    }

    /**
     * @param Processor $processor
     * @return $this
     */
    public function addProcessor(Processor $processor): static
    {
        $this->processors->add($processor);
        return $this;
    }

    /**
     * @param string $inputParser
     * @return $this
     */
    public function addInputParser(string $inputParser): static
    {
        $this->inputParsers->add($inputParser);
        return $this;
    }

    /**
     * @param string $name
     * @param string $parameter
     * @return Context
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
     * @return Context
     */
    public function getChildContext(Field $field, $action = Action::INDEX) : ContextContract
    {
        $className = spl_object_hash($field) . '|' . $action;

        if (!isset($this->childContext[$className])) {
            $this->childContext[$className] = $this->createChildContext($action);
        }

        return $this->childContext[$className];
    }

    /**
     * @param string $action
     * @return static
     */
    protected function createChildContext($action = Action::INDEX): static
    {
        $childContext = new static($action);

        $childContext->parameters = $this->parameters;
        $childContext->fieldsToShow = $this->fieldsToShow;
        $childContext->fieldsToExpand = $this->fieldsToExpand;
        $childContext->processors = $this->processors;

        return $childContext;
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
    public function showField($field): static
    {
        $path = explode(self::FIELD_PATH_DELIMITER, $field);
        $this->touchArrayPath($this->fieldsToShow, $path);

        return $this;
    }

    /**
     * @param string $field
     * @return $this
     */
    public function expandField($field): static
    {
        $path = explode(self::FIELD_PATH_DELIMITER, $field);
        $this->touchArrayPath($this->fieldsToExpand, $path);

        return $this;
    }

    /**
     * @param string[] $fields
     * @return $this
     */
    public function showFields(array $fields): static
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
    public function expandFields(array $fields): static
    {
        foreach ($fields as $field) {
            $this->expandField($field);
        }

        return $this;
    }

    /**
     * @param CurrentPath $fieldPath
     * @return bool|null
     */
    public function shouldShowField(CurrentPath $fieldPath)
    {
        if ($this->fieldsToShow === []) {
            return null;
        }

        // all expanded fields are also always included by default.
        if ($this->arrayPathExists($this->fieldsToExpand, $fieldPath->toArray())) {
            return true;
        }

        return $this->arrayPathExists($this->fieldsToShow, $fieldPath->toArray());
    }

    /**
     * @param CurrentPath $fieldPath
     * @return bool|null
     */
    public function shouldExpandField(CurrentPath $fieldPath)
    {
        if ($this->fieldsToExpand === []) {
            return null;
        }

        return $this->arrayPathExists($this->fieldsToExpand, $fieldPath->toArray());
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
    public function getUrl(): ?string
    {
        return $this->url;
    }

    /**
     * @param string $url
     */
    public function setUrl(string $url): void
    {
        $this->url = $url;
    }

    /**
     * @param bool[] $target
     * @param string[] $path
     */
    private function touchArrayPath(array &$target, array $path): void
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
     * @param array $fieldsToShow
     * @param array $path
     * @param array $recursivePath
     * @return bool
     */
    private function arrayPathExists(array &$fieldsToShow, array $path, array $recursivePath = [])
    {
        // We need to start from the right and mvoe to the left.
        $key = array_shift($path);

        // Check for recursive
        if (isset($recursivePath[$key])) {
            if (count($path) == 0) {
                return true;
            }

            return $this->arrayPathExists($fieldsToShow, $path, $recursivePath);
        }

        if (count($fieldsToShow) == 0) {
            return null;
        }

        if (isset($fieldsToShow[$key . '*'])) {
            if (count($path) == 0) {
                return true;
            }

            // Check for all keys on this level that are recursive
            foreach (array_keys($fieldsToShow) as $k) {
                if (mb_substr($k, -1) === '*') {
                    $recursivePath[mb_substr($k, 0, -1)] = true;
                }
            }

            return $this->arrayPathExists($fieldsToShow[$key . '*'], $path, $recursivePath);
        }

        if (isset($fieldsToShow[$key])) {
            if (count($fieldsToShow[$key]) === 0) {
                return $path !== [] ? null : true;
            }

            if ($path !== []) {
                return $this->arrayPathExists($fieldsToShow[$key], $path);
            }

            return true;
        }

        // Finally, check for asterisk, which means we should keep the regular fields (and return NULL)
        if (isset($fieldsToShow['*'])) {
            return null;
        }

        // Nope, fail. Don't show.
        return false;
    }

    /**
     * @return InputParser
     */
    public function getInputParser(): InputParser
    {
        return $this->inputParsers;
    }

    /**
     * Return a fork of the context.
     * @return self
     */
    public function fork(): static
    {
        return clone $this;
    }
}
