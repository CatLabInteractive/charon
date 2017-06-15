<?php

namespace CatLab\Charon\Models\Routing\Parameters;

use CatLab\Charon\Interfaces\Context;
use CatLab\Charon\Interfaces\DescriptionBuilder;
use CatLab\Charon\Enums\Cardinality;
use CatLab\Charon\Enums\Method;
use CatLab\Charon\Library\ResourceDefinitionLibrary;
use CatLab\Charon\Models\ResourceDefinition;
use CatLab\Charon\Models\Routing\Parameters\Base\Parameter;

/**
 * Class BodyParameter
 * @package App\CatLab\RESTResource\Models\Parameters\Base
 */
class BodyParameter extends Parameter
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
        $out = parent::toSwagger($builder, $context);
        unset($out['type']);

        $resourceDefinition = ResourceDefinitionLibrary::make($this->resourceDefinition);
        $context = Method::toAction($this->route->getMethod(), $this->cardinality);

        $out['schema'] = [
            '$ref' => $builder->addResourceDefinition($resourceDefinition, $context, $this->cardinality)
        ];

        return $out;
    }


    public function merge(Parameter $parameter)
    {
        parent::merge($parameter);

        if ($parameter instanceof ResourceParameter) {
            $this->cardinality = $parameter->getCardinality();
        }

        return $this;
    }
}