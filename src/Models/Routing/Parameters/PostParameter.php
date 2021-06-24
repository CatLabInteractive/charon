<?php

namespace CatLab\Charon\Models\Routing\Parameters;

use CatLab\Charon\Models\Routing\Parameters\Base\Parameter;

/**
 * Class PostParameter
 * @package App\CatLab\RESTResource\Models\Parameters\Base
 */
class PostParameter extends Parameter
{
    /**
     * PathParameter constructor.
     * @param string $name
     */
    public function __construct($name)
    {
        parent::__construct($name, self::IN_FORM);
    }
}
