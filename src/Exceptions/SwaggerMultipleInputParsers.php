<?php

namespace CatLab\Charon\Exceptions;

/**
 * Class SwaggerMultipleInputParsers
 * @package CatLab\Charon\Exceptions
 */
class SwaggerMultipleInputParsers extends ResourceException
{
    /**
     * @return SwaggerMultipleInputParsers
     */
    public static function make()
    {
        return new self('Swagger cannot handle multiple input parsers. If you do set multiple input parsers, create multiple SwaggerBuilders with multiple Contexts.');
    }
}