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
 * Class HeaderParameter
 * @package App\CatLab\RESTResource\Models\Parameters\Base
 */
class HeaderParameter extends Parameter
{
    /**
     * PathParameter constructor.
     * @param string $name
     */
    public function __construct($name)
    {
        parent::__construct($name, self::IN_FORM);
    }

    public function getIn()
    {
        return 'header';
    }

    /**
     * @param DescriptionBuilder $builder
     * @param Context $context
     * @return array
     */
    public function toSwagger(DescriptionBuilder $builder, Context $context)
    {
        $out = parent::toSwagger($builder, $context);
        return $out;
    }
}