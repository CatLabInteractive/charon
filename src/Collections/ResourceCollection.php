<?php

namespace CatLab\Charon\Collections;

use CatLab\Base\Collections\Collection;
use CatLab\Charon\Interfaces\SerializableResource;
use CatLab\Charon\Models\RESTResource;
use CatLab\Charon\Transformers\ResourceTransformer;

/**
 * Class RESTResourceCollection
 * @package CatLab\RESTResource\Collections
 */
class ResourceCollection extends Collection implements SerializableResource
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
}