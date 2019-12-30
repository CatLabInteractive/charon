<?php

namespace CatLab\Charon\Transformers;

use CatLab\Base\Helpers\ArrayHelper;
use CatLab\Charon\Interfaces\Context;
use CatLab\Charon\Interfaces\Transformer;
use CatLab\Charon\Library\TransformerLibrary;
use LogicException;

/**
 * Class TransformerQueue
 *
 * Transformer that executes a list of transformers in sequence.
 *
 * @package CatLab\Charon\Transformers
 */
class TransformerQueue implements Transformer
{
    /**
     * @var Transformer[]|string[]
     */
    protected $transformers;

    /**
     * TransformerQueue constructor.
     * @param $transformers
     */
    public function __construct($transformers)
    {
        if (!ArrayHelper::isIterable($transformers)) {
            throw new \InvalidArgumentException("TransformerQueue requires an array of transformers");
        }

        $this->transformers = $transformers;
    }

    /**
     * @param $value
     * @param Context $context
     * @return mixed
     * @throws \CatLab\Charon\Exceptions\InvalidTransformer
     */
    public function toResourceValue($value, Context $context)
    {
        foreach ($this->getTransformers() as $transformer) {
            $value = $transformer->toResourceValue($value, $context);
        }

        return $value;
    }

    /**
     * @param $value
     * @param Context $context
     * @return mixed
     * @throws \CatLab\Charon\Exceptions\InvalidTransformer
     */
    public function toEntityValue($value, Context $context)
    {
        foreach ($this->getTransformers() as $transformer) {
            $value = $transformer->toEntityValue($value, $context);
        }

        return $value;
    }

    /**
     * Translate the raw input from a parameter to something usable.
     * @param $value
     * @return mixed
     * @throws \CatLab\Charon\Exceptions\InvalidTransformer
     */
    public function toParameterValue($value)
    {
        foreach ($this->getTransformers() as $transformer) {
            $value = $transformer->toParameterValue($value);
        }

        return $value;
    }

    /**
     * Get a list of all transformers.
     * @return Transformer[]
     * @throws \CatLab\Charon\Exceptions\InvalidTransformer
     */
    protected function getTransformers()
    {
        $out = [];

        foreach ($this->transformers as $transformer) {
            if ($transformer instanceof Transformer) {
                $out[] = $transformer;
            } else if (is_string($transformer)) {
                $out[] = TransformerLibrary::make($transformer);
            } else {
                throw new LogicException("All transformers must implement " . Transformer::class);
            }
        }

        return $out;
    }

    /**
     * Serialize object
     * @throws \CatLab\Charon\Exceptions\InvalidTransformer
     */
    public function __sleep()
    {
        foreach ($this->getTransformers() as $k => $v) {
            $this->transformers[$k] = TransformerLibrary::serialize($v);
        }

        return [
            'transformers'
        ];
    }
}
