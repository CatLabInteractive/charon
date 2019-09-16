<?php

namespace CatLab\Charon\Collections;

use CatLab\Base\Collections\Collection;
use CatLab\Charon\Models\RESTResource;
use CatLab\Charon\Transformers\ResourceTransformer;

/**
 * Class RESTResourceCollection
 * @package CatLab\RESTResource\Collections
 */
class ResourceCollection extends Collection implements \CatLab\Charon\Interfaces\ResourceCollection
{
    /**
     * @var string
     */
    private $meta;

    /**
     * RESTResourceCollection constructor.
     */
    public function __construct()
    {
        $this->meta = [];
    }

    /**
     * @param $name
     * @param mixed $data
     * @return $this
     */
    public function addMeta($name, $data)
    {
        $this->meta[$name] = $data;
        return $this;
    }

    /**
     * @return array|string
     */
    public function getMeta()
    {
        return $this->meta;
    }

    /**
     * @return array
     */
    public function toArray()
    {
        $items = [];
        foreach ($this as $child) {
            /** @var RESTResource $child */
            $items[] = $child->toArray();
        }

        $out = [
            ResourceTransformer::RELATIONSHIP_ITEMS => $items,
        ];

        if (!empty($this->meta)) {
            $out['meta'] = $this->meta;
        }

        return $out;
    }

    /**
     * @param $reference
     * @return array
     */
    public function getSwaggerDescription($reference)
    {
        return [
            'type' => 'object',
            'properties' => [
                ResourceTransformer::RELATIONSHIP_ITEMS => [
                    'type' => 'array',
                    'items' => [
                        '$ref' => $reference
                    ]
                ]
            ]
        ];
    }
}
