<?php

namespace CatLab\Charon\Library;
use CatLab\Charon\Interfaces\ResourceDefinition;
use CatLab\Charon\Exceptions\InvalidResourceDefinition;
use Doctrine\Instantiator\Exception\InvalidArgumentException;

/**
 * Class ResourceDefinitionLibrary
 * @package CatLab\RESTResource\Library
 */
class ResourceDefinitionLibrary
{
    /**
     * @return ResourceDefinitionLibrary
     */
    private static function instance()
    {
        static $in;
        if (!isset($in)) {
            $in = new self();
        }
        return $in;
    }

    /**
     * @var ResourceDefinition[]
     */
    private $descriptions;

    private function __construct()
    {
        $this->descriptions = [];
    }

    /**
     * @param string $classname
     * @return ResourceDefinition
     * @throws InvalidResourceDefinition
     */
    public static function make($classname)
    {
        if ($classname instanceof ResourceDefinition) {
            return $classname;
        } else {
            return self::instance()->makeResourceDescription($classname);
        }
    }

    /**
     * @param $classname
     * @return ResourceDefinition
     * @throws InvalidResourceDefinition
     */
    private function makeResourceDescription($classname)
    {
        if (!isset($this->descriptions[$classname])) {
            try {
                $this->descriptions[$classname] = new $classname;
            }
            catch (\Exception $e) {
                throw new InvalidResourceDefinition(
                    "Could not instantiate {$classname}: " . $e->getMessage(),
                    $e->getCode(),
                    $e
                );
            }
            
            if (! ($this->descriptions[$classname] instanceof ResourceDefinition)) {
                throw new InvalidResourceDefinition(
                    "All resources definitions must implement " . ResourceDefinition::class . "; " .
                    $classname . " does not."
                );
            }
        }

        return $this->descriptions[$classname];
    }
}
