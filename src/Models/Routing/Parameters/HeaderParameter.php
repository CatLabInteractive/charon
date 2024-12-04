<?php

declare(strict_types=1);

namespace CatLab\Charon\Models\Routing\Parameters;

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

    public function getIn(): string
    {
        return 'header';
    }
}
