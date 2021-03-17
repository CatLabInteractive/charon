<?php

namespace CatLab\Charon\Resolvers;

use CatLab\Charon\Collections\PropertyValueCollection;
use CatLab\Charon\Collections\ResourceFieldCollection;
use CatLab\Charon\Interfaces\Context;
use CatLab\Charon\Interfaces\ResourceTransformer;
use CatLab\Charon\Exceptions\InvalidPropertyException;
use CatLab\Charon\Exceptions\VariableNotFoundInContext;
use CatLab\Charon\Models\Properties\Base\Field;
use CatLab\Charon\Models\Properties\ResourceField;
use InvalidArgumentException;

/**
 * Class ResolverBase
 * @package CatLab\RESTResource\Resolvers
 */
class ResolverBase
{
    const CHILDPATH_PATH_SEPARATOR = '.';
    const CHILDPATH_PARAMETER_SEPARATOR = ':';
    const CHILDPATH_VARIABLE_OPEN = '{';
    const CHILDPATH_VARIABLE_CLOSE = '}';

    const NAMESPACE_MODEL = 'model';
    const NAMESPACE_CONTEXT = 'context';
    const NAMESPACE_PARENT = 'parent';

    const REGEX_ACCOLADE_PARAMETER = '\{(?:[^{}]|(?R))+\}';
    const REGEX_REGULAR_PARAMETER = '[^{}.\s]+';

    const EAGER_LOAD_METHOD_PREFIX = 'eagerLoad';
    const FILTER_METHOD_PREFIX = 'filter';
    const SORT_METHOD_PREFIX = 'sort';

    /**
     * @var array
     */
    private $methodSniffer = [];

    /**
     * @param ResourceTransformer $transformer
     * @param $entity
     * @param $path
     * @param Context $context
     * @return string|string[]|null
     * @throws VariableNotFoundInContext
     */
    public function resolvePathParameters(
        ResourceTransformer $transformer,
        $entity,
        $path,
        Context $context
    ) {
        $self = $this;
        return preg_replace_callback(
            '/' . self::REGEX_ACCOLADE_PARAMETER . '/',
            function($matches) use ($self, $context, $entity, $transformer) {
                foreach ($matches as $parameter) {
                    if ($paramName = $self->getParameter($parameter)) {
                        return $this->parseParameter($transformer, $paramName, $context, $entity);
                    }
                }
                return null;
            },
            $path
        );
    }

    /**
     * @param string $path
     * @return mixed
     */
    protected function splitPathParameters(string $path)
    {
        // can we do this easier?
        if (strpos($path, self::CHILDPATH_VARIABLE_OPEN) === false) {
            return explode(self::CHILDPATH_PATH_SEPARATOR, $path);
        }

        // First detect all variables
        $regex = '/' . self::REGEX_ACCOLADE_PARAMETER . '|' . self::REGEX_REGULAR_PARAMETER . '/';

        preg_match_all(
            $regex,
            $path,
            $matches
        );

        $out = [];
        $curI = 0;

        foreach ($matches[0] as $v) {
            if ($v == self::CHILDPATH_PARAMETER_SEPARATOR) {
                $curI --;
            }

            if (!isset($out[$curI])) {
                $out[$curI] = $v;
            } else {
                $out[$curI] .= $v;
            }

            $lastChar = mb_substr($v, -1);
            if ($lastChar !== self::CHILDPATH_PARAMETER_SEPARATOR) {
                $curI ++;
            }
        }

        return $out;
    }

    /**
     * @param ResourceTransformer $transformer
     * @param array $parameters
     * @param Context $context
     * @param Field $field
     * @param null $entity
     * @return \mixed[]
     * @throws VariableNotFoundInContext
     */
    protected function parseParameters(
        ResourceTransformer $transformer,
        array $parameters,
        Context $context,
        Field $field = null,
        $entity = null
    ) {
        $out = [];
        foreach ($parameters as $v) {
            if ($parameter = $this->getParameter($v)) {
                $value = $this->parseParameter($transformer, $parameter, $context, $entity);
                if ($value !== null) {
                    $out[] = $value;
                } elseif ($this->isOptionalParameter($v)) {
                    $out[] = null;
                } else {
                    if ($field) {
                        throw new VariableNotFoundInContext(
                            'Field ' . $field->getName() . ' requires a parameter $' . $parameter
                            . ' to be set in the context, but no such parameter was defined.'
                        );
                    } else {
                        throw new VariableNotFoundInContext(
                            'A parameter $' . $parameter
                            . ' is required to be set in the context, but no such parameter was defined.'
                        );
                    }
                }
            } else {
                $out[] = $v;
            }
        }
        return $out;
    }

    /**
     * @param $entity
     * @param $name
     * @param array $getterParameters
     * @param Context $context
     * @return mixed|null
     */
    protected function getValueFromEntity($entity, $name, array $getterParameters, Context $context)
    {
        // Check for get method
        if ($this->methodExists($entity, 'get'.ucfirst($name))) {
            return call_user_func_array(array($entity, 'get'.ucfirst($name)), $getterParameters);
        }

        elseif ($this->methodExists($entity, 'is'.ucfirst($name))) {
            return call_user_func_array(array($entity, 'is'.ucfirst($name)), $getterParameters);
        }

        elseif (
            is_object($entity) &&
            property_exists($entity, $name)
        ) {
            return $entity->$name;
        }

        elseif (isset($entity->$name)) {
            return $entity->$name;
        }

        else {
            //throw new InvalidPropertyException;
            return null;
        }
    }

    /**
     * @param ResourceTransformer $transformer
     * @param mixed $entity
     * @param array $path
     * @param Field $field
     * @param Context $context
     * @return mixed
     * @throws InvalidPropertyException
     * @throws VariableNotFoundInContext
     */
    protected function resolveChildPath(
        ResourceTransformer $transformer,
        $entity,
        array $path,
        Field $field,
        Context $context
    ) {
        $name = array_pop($path);

        if (count($path) > 0) {
            $entity = $this->resolveChildPath($transformer, $entity, $path, $field, $context);
            if (!$entity) {
                return null;
            }
        }

        list($name, $parameters) = $this->getPropertyNameAndParameters($transformer, $name, $context, $field, $entity);

        try {
            return $this->getValueFromEntity($entity, $name, $parameters, $context);
        } catch (InvalidPropertyException $e) {
            throw new InvalidPropertyException(
                "Property $name could not be found in {$field->getResourceDefinition()->getEntityClassName()}.",
                $e->getCode(),
                $e
            );
        }
    }

    /**
     * @param ResourceTransformer $transformer
     * @param $name
     * @param Context $context
     * @param Field $field
     * @param $entity
     * @return array
     * @throws VariableNotFoundInContext
     */
    protected function getPropertyNameAndParameters(
        ResourceTransformer $transformer,
        $name,
        Context $context,
        Field $field,
        $entity = null
    ) {
        $parameters = explode(self::CHILDPATH_PARAMETER_SEPARATOR, $name);
        $name = array_shift($parameters);

        // Parse the parameters
        if (count($parameters) > 0) {
            $parameters = $this->parseParameters($transformer, $parameters, $context, $field, $entity);
        }

        return [ $name, $parameters ];
    }

    /**
     * @param ResourceTransformer $transformer
     * @param $original
     * @param PropertyValueCollection $identifiers
     * @param Context $context
     * @return bool
     * @throws InvalidPropertyException
     * @throws VariableNotFoundInContext
     */
    protected function entityEquals(
        ResourceTransformer $transformer,
        $original,
        PropertyValueCollection $identifiers,
        Context $context
    ) {
        if (count($identifiers->getValues()) === 0) {
            return false;
        }

        foreach ($identifiers->getValues() as $identifier) {

            $path = $this->splitPathParameters($identifier->getField()->getName());
            $propertyValue = $this->resolveChildPath($transformer, $original, $path, $identifier->getField(), $context);

            if (!$identifier->equals($propertyValue)) {
                return false;
            }
        }
        return true;
    }

    /**
     * @param ResourceTransformer $transformer
     * @param $original
     * @param ResourceFieldCollection $identifiers
     * @param Context $context
     * @return bool
     * @throws InvalidPropertyException
     * @throws VariableNotFoundInContext
     */
    protected function entityExists(
        ResourceTransformer $transformer,
        $original,
        ResourceFieldCollection $identifiers,
        Context $context
    ) {
        if (count($identifiers) === 0) {
            return false;
        }

        foreach ($identifiers as $identifier) {
            $path = $this->splitPathParameters($identifier->getName());
            $propertyValue = $this->resolveChildPath($transformer, $original, $path, $identifier, $context);

            if ($propertyValue === null) {
                return false;
            }
        }
        return true;
    }

    /**
     * Drop in replacement for method_exists, with caching.
     * @param $model
     * @param $method
     * @return mixed
     */
    protected function methodExists($model, $method)
    {
        return method_exists($model, $method);
    }

    /**
     * @param $parameter
     * @return null
     */
    private function getParameter($parameter)
    {
        if (mb_substr($parameter, 0, 1) === '{') {
            if (mb_substr($parameter, -2, 1) === '?') {
                return mb_substr($parameter, 1, -2);
            } else {
                return mb_substr($parameter, 1, -1);
            }
        }
        return null;
    }

    /**
     * @param $parameter
     * @return bool
     */
    private function isOptionalParameter($parameter)
    {
        if (mb_substr($parameter, 0, 1) === '{') {
            return mb_substr($parameter, -2, 1) === '?';
        }
        return null;
    }

    /**
     * @param ResourceTransformer $transformer
     * @param $parameter
     * @param Context $context
     * @param null $entity
     * @return mixed
     * @throws VariableNotFoundInContext
     */
    private function parseParameter(ResourceTransformer $transformer, $parameter, Context $context, $entity = null)
    {
        $path = explode(self::CHILDPATH_PATH_SEPARATOR, $parameter);
        $namespace = array_shift($path);

        $attributePath = array_shift($path);
        $attributePath = explode(self::CHILDPATH_PARAMETER_SEPARATOR, $attributePath);

        $parameterName = array_shift($attributePath);
        $parameters = $this->parseParameters($transformer, $attributePath, $context);

        switch ($namespace) {
            case self::NAMESPACE_MODEL:
                if ($entity) {
                    return $this->descentIntoParameter($path, $this->getValueFromEntity($entity, $parameterName, $parameters, $context), $context);
                } else {
                    return null;
                }
                break;

            case self::NAMESPACE_CONTEXT:
                return $this->descentIntoParameter($path, $context->getParameter($parameterName, $entity), $context);
                break;

            case self::NAMESPACE_PARENT:
                $parent = $transformer->getParentEntity();
                if ($parent) {
                    return $this->descentIntoParameter($path, $this->getValueFromEntity($parent, $parameterName, $parameters, $context), $context);
                } else {
                    return null;
                }
                break;

            default:
                throw new InvalidArgumentException(
                    'Getter parameter ' . $parameter . ' does not have a valid namespace. ' .
                    '\'' . self::NAMESPACE_MODEL . '\' or \'' . self::NAMESPACE_CONTEXT . '\' expected');
        }
    }

    /**
     * Try to call a static method on the resource' entity.
     * @param ResourceTransformer $transformer
     * @param Field $field
     * @param Context $context
     * @param string $methodPrefix
     * @param mixed[] $additionalParameters These parameters will be added after the route parameters
     * @return mixed|null
     */
    protected function callEntitySpecificMethodIfExists(
        ResourceTransformer $transformer,
        Field $field,
        Context $context,
        $methodPrefix,
        $additionalParameters = []
    ) {
        $path = $this->splitPathParameters($field->getName());

        // Child field
        $fieldName = array_shift($path);

        try {
            list($name, $parameters) = $this->getPropertyNameAndParameters($transformer, $fieldName, $context, $field);
        } catch (VariableNotFoundInContext $e) {
            return false;
        }

        // Entity class name
        $entityClassName = $field->getResourceDefinition()->getEntityClassName();
        $method = $methodPrefix . ucfirst($name);

        // Check if method exist
        if ($this->methodExists($entityClassName, $method)) {
            $eagerLoadMethod = $entityClassName . '::' . $method;
            call_user_func_array($eagerLoadMethod, array_merge($parameters, $additionalParameters));
            return true;
        }

        return false;
    }

    /**
     * @param array $path
     * @param $parameter
     * @param Context $context
     * @return null
     */
    private function descentIntoParameter(array $path, $parameter, Context $context)
    {
        if (count($path) === 0) {
            return $parameter;
        }

        $attr = array_shift($path);
        $value = $this->getValueFromEntity($parameter, $attr, [], $context);

        if ($value === null) {
            return null;
        }

        return $this->descentIntoParameter($path, $value, $context);
    }
}
