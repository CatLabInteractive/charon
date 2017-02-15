<?php

namespace CatLab\Charon\Library;

use CatLab\Charon\Exceptions\InvalidInputParser;
use CatLab\Charon\Interfaces\InputParser;
use CatLab\Charon\Interfaces\ResourceDefinition;

/**
 * Class InputParserLibrary
 * @package CatLab\RESTResource\Library
 */
class InputParserLibrary
{
    /**
     * @return InputParserLibrary
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
     * @var InputParser[]
     */
    private $inputParsers;

    private function __construct()
    {
        $this->inputParsers = [];
    }

    /**
     * @param string $classname
     * @return InputParser
     */
    public static function make($classname)
    {
        if ($classname instanceof InputParser) {
            self::instance()->inputParsers[get_class($classname)] = $classname;
            return $classname;
        } else {
            return self::instance()->makeInputParser($classname);
        }
    }

    /**
     * @param $classname
     * @return InputParser
     * @throws InvalidInputParser
     */
    private function makeInputParser($classname)
    {
        if (!isset($this->inputParsers[$classname])) {
            try {
                $this->inputParsers[$classname] = new $classname;
            }
            catch (\Exception $e) {
                throw new InvalidInputParser(
                    "Could not instantiate {$classname}: " . $e->getMessage(),
                    $e->getCode(),
                    $e
                );
            }
            
            if (! ($this->inputParsers[$classname] instanceof InputParser)) {
                throw new InvalidInputParser(
                    "All resources definitions must implement " . ResourceDefinition::class . "; " .
                    $classname . " does not."
                );
            }
        }

        return $this->inputParsers[$classname];
    }
}