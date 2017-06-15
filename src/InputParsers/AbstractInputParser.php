<?php

namespace CatLab\Charon\InputParsers;

use CatLab\Charon\Interfaces\ResourceDefinition;
use CatLab\Charon\Models\Identifier;
use CatLab\Requirements\Enums\PropertyType;
use \Request;

/**
 * Class AbstractInputParser
 *
 * @deprecated Please use framework specific input parsers.
 *
 * @package CatLab\Charon\Laravel\InputParsers
 */
abstract class AbstractInputParser
{
    /*
     * Any framework should overload following methods
     */

    /**
     * @param $name
     * @return mixed|null
     */
    protected function getHeader($name)
    {
        $headers = getallheaders();
        return $headers[$name] ?? null;
    }

    /**
     * @return bool|string
     */
    protected function getRawContent()
    {
        return file_get_contents("php://input");
    }

    /*
     * End code to replace
     */

    /**
     * @return mixed|string
     */
    protected function getContentType()
    {
        $contentType = mb_strtolower($this->getHeader('Content-Type'));
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

    /**
     * @return array
     */
    private function getAllHeaders()
    {
        if (function_exists('getAllHeaders')) {
            return $this->getAllHeaders();
        } else {
            $headers = [];
            foreach ($_SERVER as $name => $value) {
                if (substr($name, 0, 5) == 'HTTP_') {
                    $headers[str_replace(' ', '-', ucwords(strtolower(str_replace('_', ' ', substr($name, 5)))))] = $value;
                }
            }
            return $headers;
        }
    }
}