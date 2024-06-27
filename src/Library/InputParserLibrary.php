<?php

declare(strict_types=1);

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
    private array $inputParsers = [];

    private function __construct()
    {
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
        }
        return self::instance()->makeInputParser($classname);
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
                throw InvalidInputParser::makeTranslatable(
                    'Could not instantiate %s: %s.',
                    [
                        $classname,
                        $e->getMessage()
                    ],
                    $e->getCode(),
                    $e
                );
            }
            
            if (! ($this->inputParsers[$classname] instanceof InputParser)) {
                throw InvalidInputParser::makeTranslatable(
                    'All resources definitions must implement %s; %s does not.',
                    [
                        ResourceDefinition::class,
                        $classname
                    ]
                );
            }
        }

        return $this->inputParsers[$classname];
    }
}
