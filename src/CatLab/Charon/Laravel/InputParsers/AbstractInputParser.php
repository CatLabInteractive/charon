<?php

namespace CatLab\Charon\Laravel\InputParsers;

use CatLab\Charon\Interfaces\ResourceDefinition;
use CatLab\Charon\Models\Identifier;
use \Request;

/**
 * Class AbstractInputParser
 * @package CatLab\Charon\Laravel\InputParsers
 */
abstract class AbstractInputParser
{
    /**
     * @return mixed|string
     */
    protected function getContentType()
    {
        $contentType = mb_strtolower(Request::header('content-type'));
        $parts = explode(';', $contentType);
        return $parts[0];
    }

    /**
     * @param ResourceDefinition $resourceDefinition
     * @param array $data
     * @return Identifier
     */
    protected function arrayToIdentifier(ResourceDefinition $resourceDefinition, array $data)
    {
        return Identifier::fromArray($resourceDefinition, $data);
    }
}