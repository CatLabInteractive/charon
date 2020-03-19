<?php

namespace CatLab\Charon\Models\Routing\Parameters;

use CatLab\Base\Collections\Collection;
use CatLab\Charon\Enums\Action;
use CatLab\Charon\Enums\Method;
use CatLab\Charon\Exceptions\SwaggerMultipleInputParsers;
use CatLab\Charon\Interfaces\Context;
use CatLab\Charon\Interfaces\DescriptionBuilder;
use CatLab\Charon\Enums\Cardinality;
use CatLab\Charon\Library\ResourceDefinitionLibrary;
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

    /**
     * @var string
     */
    private $cardinality = Cardinality::ONE;

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
    public function one()
    {
        $this->cardinality = Cardinality::ONE;
        return $this;
    }

    /**
     * @return $this
     */
    public function many()
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
    public function setAction($action)
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
