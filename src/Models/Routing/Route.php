<?php

namespace CatLab\Charon\Models\Routing;

use CatLab\Charon\Collections\RouteCollection;
use CatLab\Charon\Enums\Method;
use CatLab\Charon\Interfaces\ResourceTransformer;
use CatLab\Charon\Interfaces\RouteMutator;
use CatLab\Charon\Models\Properties\Base\Field;
use CatLab\Charon\Models\Properties\RelationshipField;
use CatLab\Charon\Models\Routing\Parameters\Base\Parameter;
use CatLab\Charon\Models\Routing\Parameters\QueryParameter;
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
     * @param $method
     * @param $requestPath
     * @return MatchedRoute|boolean
     */
    public function matches($requestPath, $method)
    {
        if ($method) {
            if (strtolower($method) != $this->getMethod()) {
                return false;
            }
        }

        $path = $this->getPath();

        $path = preg_replace ('/.{\w+\\?}/', '(\.\w+)?', $path);
        $path = preg_replace ('/\/\{\w+\\?}/', '(/\w+)?', $path);
        $path = preg_replace ('/\/\{\w+\}/', '(/\w+)', $path);

        if (preg_match_all('#^' . $path . '$#', $requestPath, $matches, PREG_OFFSET_CAPTURE)) {

            // Rework matches to only contain the matches, not the orig string
            $matches = array_slice($matches, 1);
            // Extract the matched URL parameters (and only the parameters)
            $params = array_map(function($match, $index) use ($matches) {
                // We have a following parameter: take the substring from the current param position until the next one's position (thank you PREG_OFFSET_CAPTURE)
                if (isset($matches[$index+1]) && isset($matches[$index+1][0]) && is_array($matches[$index+1][0])) {
                    return trim(substr($match[0][0], 0, $matches[$index+1][0][1] - $match[0][1]), '/');
                }
                // We have no following paramete: return the whole lot
                else {
                    return (isset($match[0][0]) ? trim($match[0][0], '/') : null);
                }
            }, $matches, array_keys($matches));

            return new MatchedRoute($this, $params);
        }
        return false;
    }

    /**
     * We support using 'static parameters', which are path parameters that have been defined already
     * by using a json-like syntax: /my-path/{"static-parameter"}/fubar will create a path parameter
     * that has a static value of 'static-parameter'.
     * @return array
     */
    public function getPathWithStaticRouteParameters()
    {
        $path = $this->getPath();

        // look for our own 'static' parameters (which we solve by using laravels 'default')
        $staticRouteParameters = [];
        preg_match_all('/\{"(.*)"\}/', $path, $out);
        foreach ($out[1] as $k => $staticVariable) {
            $path = str_replace($out[0][$k], $staticVariable, $path);
            $staticRouteParameters[$staticVariable] = $staticVariable;
        }

        return [
            $path,
            $staticRouteParameters
        ];
    }

    /**
     * @param bool TRUE if at least one return value consists of multiple models.
     * @return Parameter[]
     * @throws \CatLab\Charon\Exceptions\InvalidScalarException
     * @throws \CatLab\Charon\Exceptions\InvalidResourceDefinition
     */
    public function getExtraParameters($hasCardinalityMany)
    {
        $returnValues = $this->getReturnValues();

        $sortValues = [];
        $expandValues = [];
        $selectValues = [];
        $visibleValues = [];

        $parameters = [];

        foreach ($returnValues as $returnValue) {

            // Look for sortable fields
            foreach ($returnValue->getResourceDefinitions() as $resourceDefinition) {

                foreach ($resourceDefinition->getFields() as $field) {

                    /** @var Field $field */

                    // Sortable field
                    if ($field->isSortable() && $hasCardinalityMany) {
                        $sortValues[] = $field->getDisplayName();
                        $sortValues[] = '!' . $field->getDisplayName();
                    }

                    // Visible
                    if ($field->isViewable($returnValue->getContext())) {
                        $visibleValues[] = $field->getDisplayName();
                    }

                    // Expandable field
                    if ($field instanceof RelationshipField) {
                        if ($field->isViewable($returnValue->getContext()) && $field->isExpandable()) {
                            $expandValues[] = $field->getDisplayName();
                            $visibleValues[] = $field->getDisplayName() . '.*';

                            // Also do sectond level expandable and filterable, but no further!
                            $related = $field->getChildResource();
                            foreach ($related->getFields() as $relatedField) {
                                if ($relatedField->isVisible()) {
                                    $visibleValues[] = $field->getDisplayName() . '.' . $relatedField->getDisplayName();
                                }

                                if ($relatedField instanceof RelationshipField) {
                                    if ($relatedField->isExpandable()) {
                                        $expandValues[] = $field->getDisplayName() . '.' . $relatedField->getDisplayName();
                                    }
                                }
                            }
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

            // Add asterisk
            array_unshift($visibleValues, '*');

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
     * @throws \CatLab\Charon\Exceptions\InvalidScalarException
     */
    protected function getFilterField(Field $field)
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
     * @throws \CatLab\Charon\Exceptions\InvalidScalarException
     */
    protected function getSearchField(Field $field)
    {
        $filter = (new QueryParameter($field->getDisplayName()))
            ->setType($field->getType())
            ->describe('Search results on ' . $field->getDisplayName());

        return $filter;
    }
}
