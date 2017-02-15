<?php

namespace CatLab\Charon\Models\Routing\Parameters;

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
     * @param DescriptionBuilder $builder
     * @param Context $context
     * @return array
     */
    public function toSwagger(DescriptionBuilder $builder, Context $context)
    {
        $out = [];

        $resourceDefinition = ResourceDefinitionLibrary::make($this->resourceDefinition);

        $parameters = $context->getInputParser()->getResourceRouteParameters(
            $builder,
            $this->route,
            $this,
            $resourceDefinition
        );

        /** @var Parameter $v */
        foreach ($parameters->toArray() as $v) {
            $out[] = $v->toSwagger($builder, $context);
        }

        return $out;
    }
}