<?php

namespace CatLab\Charon\Library;

use CatLab\Charon\Exceptions\InvalidTransformer;
use CatLab\Charon\Interfaces\ResourceDefinition;
use CatLab\Charon\Interfaces\Transformer;

/**
 * Class TransformerLibrary
 * @package CatLab\RESTResource\Library
 */
class TransformerLibrary
{
    /**
     * @return TransformerLibrary
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
     * @var Transformer[]
     */
    private $transformers;

    private function __construct()
    {
        $this->transformers = [];
    }

    /**
     * @param string $classname
     * @return Transformer
     */
    public static function make($classname)
    {
        if ($classname instanceof Transformer) {
            self::instance()->transformers[get_class($classname)] = $classname;
            return $classname;
        } else {
            return self::instance()->makeTransformer($classname);
        }
    }

    /**
     * @param $classname
     * @return Transformer
     * @throws InvalidTransformer
     */
    private function makeTransformer($classname)
    {
        if (!isset($this->transformers[$classname])) {
            try {
                $this->transformers[$classname] = new $classname;
            }
            catch (\Exception $e) {
                throw new InvalidTransformer(
                    "Could not instantiate {$classname}: " . $e->getMessage(),
                    $e->getCode(),
                    $e
                );
            }
            
            if (! ($this->transformers[$classname] instanceof Transformer)) {
                throw new InvalidTransformer(
                    "All resources definitions must implement " . ResourceDefinition::class . "; " .
                    $classname . " does not."
                );
            }
        }

        return $this->transformers[$classname];
    }
}