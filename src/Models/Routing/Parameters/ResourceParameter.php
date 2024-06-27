<?php

declare(strict_types=1);

namespace CatLab\Charon\Models\Routing\Parameters;

use CatLab\Charon\Enums\Action;
use CatLab\Charon\Enums\Method;
use CatLab\Charon\Enums\Cardinality;
use CatLab\Charon\Models\Routing\Parameters\Base\Parameter;

/**
 * Class ResourceParameter
 * @package App\CatLab\RESTResource\Models\Parameters\Base
 */
class ResourceParameter extends Parameter
{
    /**
     * @var mixed
     */
    private $resourceDefinition;

    private string $cardinality = Cardinality::ONE;

    /**
     * @var Action
     */
    private $resourceAction = null;

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
    public function getCardinality()
    {
        return $this->cardinality;
    }

    /**
     * @param string $action
     * @return $this
     * @throws \CatLab\Charon\Exceptions\InvalidContextAction
     */
    public function setAction($action): static
    {
        Action::checkValid($action);

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
     * @return mixed
     */
    public function getResourceDefinition()
    {
        return $this->resourceDefinition;
    }
}
