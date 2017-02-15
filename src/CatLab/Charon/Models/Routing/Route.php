<?php

namespace CatLab\Charon\Models\Routing;

use CatLab\Base\Helpers\ArrayHelper;
use CatLab\Charon\Collections\RouteCollection;
use CatLab\Charon\Enums\Cardinality;
use CatLab\Charon\Enums\Method;
use CatLab\Charon\Interfaces\Context;
use CatLab\Charon\Interfaces\DescriptionBuilder;
use CatLab\Charon\Interfaces\ResourceTransformer;
use CatLab\Charon\Interfaces\RouteMutator;
use CatLab\Charon\Models\Properties\Base\Field;
use CatLab\Charon\Models\Properties\RelationshipField;
use CatLab\Charon\Models\Routing\Parameters\Base\Parameter;
use CatLab\Charon\Models\Routing\Parameters\QueryParameter;
use CatLab\Requirements\Enums\PropertyType;
use CatLab\Charon\Library\ResourceDefinitionLibrary;
use CatLab\Requirements\InArray;

/**
 * Class Route
 * @package CatLab\RESTResource\Models
 */
class Route extends RouteProperties implements RouteMutator
{
    /**
     * @var string
     */
    private $method;

    /**
     * @var string
     */
    private $path;

    /**
     * @var string|callable
     */
    private $action;

    /**
     * Route constructor.
     * @param RouteCollection $routeCollection
     * @param string $method
     * @param string $path
     * @param string|callable $action
     * @param array $options
     */
    public function __construct(RouteCollection $routeCollection, $method, $path, $action, array $options = [])
    {
        parent::__construct($options);
        $this->setParent($routeCollection);

        $this->method = $method;
        $this->path = $path;
        $this->action = $action;
    }

    /**
     * @return string
     */
    public function getPath()
    {
        return $this->processPath($this->path);
    }

    /**
     * @return string
     */
    public function getMethod()
    {
        return $this->method;
    }

    /**
     * Return a HTTP safe method.
     * @return string
     */
    public function getHttpMethod()
    {
        switch ($this->method) {
            case Method::LINK:
                return Method::POST;
            case Method::UNLINK:
                return Method::DELETE;

            default:
                return $this->method;
        }
    }

    /**
     * @return callable|string
     */
    public function getAction()
    {
        return $this->processAction($this->action);
    }

    /**
     * @param DescriptionBuilder $builder
     * @param Context $context
     * @return array
     */
    public function toSwagger(DescriptionBuilder $builder, Context $context)
    {
        $options = $this->getOptions();

        $out = [];

        $parameters = $this->getParameters();

        // Check return
        $returnValues = $this->getReturnValues();
        $hasManyReturnValue = false;
        foreach ($returnValues as $returnValue) {
            $out['responses'][$returnValue->getStatusCode()] = $returnValue->toSwagger($builder);
            $hasManyReturnValue =
                $hasManyReturnValue || $returnValue->getCardinality() == Cardinality::MANY;
        }

        foreach ($this->getExtraParameters($hasManyReturnValue) as $parameter) {
            $parameters[] = $parameter;
        }

        $out['summary'] = $this->getSummary();
        $out['parameters'] = [];

        if (isset($options['tags'])) {
            if (is_array($options['tags'])) {
                $out['tags'] = $options['tags'];
            } else {
                $out['tags'] = [ $options['tags'] ];
            }
        }

        foreach ($parameters as $parameter) {
            // Sometimes one parameter can result in multiple swagger parameters being added
            $parameterSwaggerDescription = $parameter->toSwagger($builder, $context);
            if (ArrayHelper::isAssociative($parameterSwaggerDescription)) {
                $out['parameters'][] = $parameterSwaggerDescription;
            } else {
                $out['parameters'] = array_merge($out['parameters'], $parameterSwaggerDescription);
            }

        }

        // Sort parameters: required first
        usort($out['parameters'], function ($a, $b) {
            if ($a['required'] && !$b['required']) {
                return -1;
            } elseif ($b['required'] && !$a['required']) {
                return 1;
            } else {
                return 0;
            }
        });

        // Check consumes
        $consumes = $this->getConsumeValues();
        if ($consumes) {
            $out['consumes'] = $consumes;
        }

        $security = $this->getOption('security');
        if (isset($security)) {
            $out['security'] = $security;
        }

        return $out;
    }

    /**
     * @param bool TRUE if at least one return value consists of multiple models.
     * @return Parameter[]
     */
    private function getExtraParameters($hasCardinalityMany)
    {
        $returnValues = $this->getReturnValues();

        $sortValues = [];
        $expandValues = [];
        $selectValues = [];
        $visibleValues = [];

        $parameters = [];

        foreach ($returnValues as $returnValue) {

            // Look for sortable fields
            $resourceDefinition = $returnValue->getResourceDefinition();
            if ($resourceDefinition) {

                foreach ($resourceDefinition->getFields() as $field) {

                    // Sortable field
                    if ($field->isSortable() && $hasCardinalityMany) {
                        $sortValues[] = $field->getDisplayName();
                        $sortValues[] = '!' . $field->getDisplayName();
                    }

                    // Expandable field
                    if ($field instanceof RelationshipField) {
                        if ($field->isExpandable()) {
                            $expandValues[] = $field->getDisplayName();
                        }
                    }

                    // Filterable fields
                    if ($field->isFilterable() && $hasCardinalityMany) {
                        $parameters[] = $this->getFilterField($field);
                    }

                    // Searchable fields
                    if ($field->isSearchable() && $hasCardinalityMany) {
                        $parameters[] = $this->getSearchField($field);
                    }

                    // Visible
                    if ($field->isVisible()) {
                        $visibleValues[] = $field->getDisplayName();
                    }

                    $selectValues[] = $field->getDisplayName();
                }
            }
        }

        if (count($sortValues) > 0) {
            $parameters[] = (new QueryParameter(ResourceTransformer::SORT_PARAMETER))
                ->setType('string')
                ->enum($sortValues)
                ->allowMultiple()
                ->describe('Define the sort parameter. Separate multiple values with comma.')
            ;
        }

        if (count($expandValues) > 0) {
            $parameters[] = (new QueryParameter(ResourceTransformer::EXPAND_PARAMETER))
                ->setType('string')
                ->enum($expandValues)
                ->allowMultiple()
                ->describe('Expand relationships. Separate multiple values with comma. Values: '
                    . implode(', ', $expandValues))
            ;
        }

        if (count($visibleValues) > 0) {
            $parameters[] = (new QueryParameter(ResourceTransformer::FIELDS_PARAMETER))
                ->setType('string')
                ->enum($visibleValues)
                ->allowMultiple()
                ->describe('Define fields to return. Separate multiple values with comma. Values: '
                    . implode(', ', $visibleValues))
            ;
        }

        return $parameters;
    }

    /**
     * @param Field $field
     * @return Parameter
     */
    private function getFilterField(Field $field)
    {
        $filter = (new QueryParameter($field->getDisplayName()))
            ->setType($field->getType())
            ->describe('Filter results on ' . $field->getDisplayName());

        // Check for applicable requirements
        foreach ($field->getRequirements() as $requirement) {
            if ($requirement instanceof InArray) {
                $filter->enum($requirement->getValues());
            }
        }

        return $filter;
    }

    /**
     * @param Field $field
     * @return Parameter
     */
    private function getSearchField(Field $field)
    {
        $filter = (new QueryParameter($field->getDisplayName()))
            ->setType($field->getType())
            ->describe('Search results on ' . $field->getDisplayName());

        return $filter;
    }
}