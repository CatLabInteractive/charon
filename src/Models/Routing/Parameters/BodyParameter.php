<?php

declare(strict_types=1);

namespace CatLab\Charon\Models\Routing\Parameters;

use CatLab\Charon\Enums\Action;
use CatLab\Charon\Enums\Cardinality;
use CatLab\Charon\Enums\Method;
use CatLab\Charon\Models\Routing\Parameters\Base\Parameter;

/**
 * Class BodyParameter
 * @package App\CatLab\RESTResource\Models\Parameters\Base
 */
class BodyParameter extends Parameter
{
    public $resourceAction;

    /**
     * @var mixed
     */
    private $resourceDefinition;

    private string $cardinality = Cardinality::ONE;

    /**
     * PathParameter constructor.
     * @param $resourceDefinition
     */
    public function __construct($resourceDefinition)
    {
        $this->resourceDefinition = $resourceDefinition;
        parent::__construct('body', self::IN_BODY);
    }

    /**
     * @return $this
     */
    public function one(): static
    {
        $this->cardinality = Cardinality::ONE;
        return $this;
    }

    /**
     * @return $this
     */
    public function many(): static
    {
        $this->cardinality = Cardinality::MANY;
        return $this;
    }

    /**
     * @return string
     */
    public function getCardinality(): string
    {
        return $this->cardinality;
    }

    /**
     * @param Action $action
     * @return $this
     */
    public function setAction($action): static
    {
        $this->resourceAction = $action;
        return $this;
    }

    /**
     * @return string
     */
    public function getAction()
    {
        if ($this->resourceAction !== null) {
            return $this->resourceAction;
        }

        return Method::toAction($this->route->getMethod(), $this->cardinality);
    }

    /**
     * @param Parameter $parameter
     * @return $this|Parameter
     */
    public function merge(Parameter $parameter): static
    {
        parent::merge($parameter);

        if ($parameter instanceof ResourceParameter) {
            $this->cardinality = $parameter->getCardinality();
        }

        return $this;
    }

    /**
     * @return mixed
     */
    public function getResourceDefinition()
    {
        return $this->resourceDefinition;
    }
}
