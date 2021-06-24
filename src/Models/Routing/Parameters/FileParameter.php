<?php

namespace CatLab\Charon\Models\Routing\Parameters;

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
