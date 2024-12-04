<?php

declare(strict_types=1);

namespace CatLab\Charon\Models\Routing\Parameters;

use CatLab\Charon\Models\Routing\Parameters\Base\Parameter;

/**
 * Class PathParameter
 * @package App\CatLab\RESTResource\Models\Parameters\Base
 */
class PathParameter extends Parameter
{
    /**
     * PathParameter constructor.
     * @param string $name
     */
    public function __construct(string $name)
    {
        parent::__construct($name, self::IN_PATH);
    }
}
