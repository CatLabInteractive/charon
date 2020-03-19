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
 * Class FileParameter
 * @package App\CatLab\RESTResource\Models\Parameters\Base
 */
class FileParameter extends Parameter
{
    /**
     * PathParameter constructor.
     * @param string $name
     * @throws \CatLab\Charon\Exceptions\InvalidScalarException
     */
    public function __construct($name)
    {
        parent::__construct($name, self::IN_FORM);
        $this->setType('file');
    }
}
