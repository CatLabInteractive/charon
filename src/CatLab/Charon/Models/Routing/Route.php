<?php

namespace CatLab\Charon\Models\Routing;

use CatLab\Charon\Collections\RouteCollection;
use CatLab\Charon\Enums\Method;
use CatLab\Charon\Interfaces\DescriptionBuilder;
use CatLab\Charon\Interfaces\ResourceTransformer;
use CatLab\Charon\Interfaces\RouteMutator;
use CatLab\Charon\Models\Properties\RelationshipField;
use CatLab\Charon\Models\Routing\Parameters\Base\Parameter;
use CatLab\Charon\Models\Routing\Parameters\QueryParameter;
use CatLab\Requirements\Enums\PropertyType;
use CatLab\Charon\Library\ResourceDefinitionLibrary;

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
     * @return array
     */
    public function toSwagger(DescriptionBuilder $builder)
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
                $hasManyReturnValue || $returnValue->getCardinality() == ReturnValue::CARDINALITY_MANY;
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
            $out['parameters'][] = $parameter->toSwagger($builder);
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

                    $selectValues[] = $field->getDisplayName();
                }
            }
        }

        $parameters = [];

        if (count($sortValues) > 0) {
            $parameters[] = (new QueryParameter(ResourceTransformer::SORT_PARAMETER))
                ->setType('string')
                ->enum($sortValues)
                ->describe('Define the sort parameter. Separate multiple values with comma.')
            ;
        }

        if (count($expandValues) > 0) {
            $parameters[] = (new QueryParameter(ResourceTransformer::EXPAND_PARAMETER))
                ->setType('string')
                ->enum($expandValues)
                ->describe('Expand relationships. Separate multiple values with comma.')
            ;
        }


        return $parameters;
    }
}