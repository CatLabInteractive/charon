<?php

declare(strict_types=1);

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
    private array $descriptions = [];

    private function __construct()
    {
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
        }
        return self::instance()->makeResourceDescription($classname);
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
                throw InvalidResourceDefinition::makeTranslatable(
                    'Could not instantiate %s: %s',
                    [
                        $classname,
                        $e->getMessage()
                    ],
                    $e->getCode(),
                    $e
                );
            }
            
            if (! ($this->descriptions[$classname] instanceof ResourceDefinition)) {
                throw InvalidResourceDefinition::makeTranslatable(
                    'All resources definitions must implement %s; %s does not.',
                    [
                        ResourceDefinition::class,
                        $classname
                    ]
                );
            }
        }

        return $this->descriptions[$classname];
    }
}
