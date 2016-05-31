<?php

namespace CatLab\Charon\Library;

/**
 * Class EntityNameLibrary
 * @package CatLab\RESTResource\Library
 */
class PrettyEntityNameLibrary
{
    /**
     * @var string[]
     */
    private $entityNames;

    /**
     * @var string[]
     */
    private $entityNamesReverse;

    /**
     * EntityNameLibrary constructor.
     */
    public function __construct()
    {
        $this->entityNames = [];
        $this->entityNamesReverse = [];
    }

    /**
     * @param string $name
     * @return string
     */
    public function toPretty(string $name) : string
    {
        if (!isset($this->entityNames[$name])) {
            $this->entityNames[$name] = $this->prettify($name);
        }

        return $this->entityNames[$name];
    }

    /**
     * @param string $name
     * @return string
     */
    private function prettify(string $name) : string
    {
        $parts = explode('\\', $name);
        $modelName = array_pop($parts);

        if (!isset($this->entityNamesReverse[$modelName])) {
            return $modelName;
        } else {
            $number = 1;
            while (isset($this->entityNamesReverse[$modelName . $number])) {
                $number ++;
            }
            return $modelName . $number;
        }
    }
}