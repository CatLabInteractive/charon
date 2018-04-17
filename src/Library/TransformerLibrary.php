<?php

namespace CatLab\Charon\Library;

use CatLab\Base\Helpers\StringHelper;
use CatLab\Charon\Exceptions\InvalidTransformer;
use CatLab\Charon\Interfaces\ResourceDefinition;
use CatLab\Charon\Interfaces\Serializable;
use CatLab\Charon\Interfaces\Transformer;

/**
 * Class TransformerLibrary
 * @package CatLab\RESTResource\Library
 */
class TransformerLibrary
{
    const SERIALIZED_PREFIX = 'serialized|';

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
     * @throws InvalidTransformer
     */
    public static function make($classname)
    {
        if ($classname instanceof Transformer) {
            self::instance()->transformers[self::serialize($classname)] = $classname;
            return $classname;
        } else {
            return self::instance()->makeTransformer($classname);
        }
    }

    /**
     * Serialize a transformer.
     * A serialized transformed can be made/wake up by the TransformerLibrary::make function.
     * @param Transformer $transformer
     * @return string
     */
    public static function serialize($transformer = null)
    {
        if ($transformer === null) {
            return null;
        }

        // Instance of transformer?
        if ($transformer instanceof Serializable) {
            return self::SERIALIZED_PREFIX . base64_encode(serialize($transformer));
        }

        // Instance of transformer?
        if ($transformer instanceof Transformer) {
            return get_class($transformer);
        }

        return $transformer;
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
                // is this serialized data?
                if (StringHelper::startsWith($classname, self::SERIALIZED_PREFIX)) {
                    $serializedData = base64_decode(
                        StringHelper::substr($classname, StringHelper::length(self::SERIALIZED_PREFIX))
                    );
                    $this->transformers[$classname] = unserialize($serializedData);
                } else {
                    $this->transformers[$classname] = new $classname;
                }
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
                    "All Transformers must implement " . Transformer::class . "; " .
                    $classname . " does not."
                );
            }
        }

        return $this->transformers[$classname];
    }
}