<?php

namespace CatLab\Charon\Resolvers;

use CatLab\Charon\Interfaces\ResourceTransformer;
use CatLab\Charon\Models\Properties\ResourceField;

/**
 * Class RequestResolver
 * @package CatLab\Charon\Resolvers
 */
class RequestResolver implements \CatLab\Charon\Interfaces\RequestResolver
{
    /**
     * @param $request
     * @param ResourceField $field
     * @return string|null
     */
    public function getFilter($request, ResourceField $field)
    {
        return $this->getParameter($request, $field->getDisplayName());
    }

    /**
     * @param $request
     * @return mixed
     */
    public function getRecords($request)
    {
        return $this->getParameter($request, ResourceTransformer::LIMIT_PARAMETER);
    }

    /**
     * @param $request
     * @return mixed
     */
    public function getSorting($request)
    {
        return $this->getParameter($request, ResourceTransformer::SORT_PARAMETER);
    }

    /**
     * @param $request
     * @param $key
     * @return string|null
     */
    public function getParameter($request, $key)
    {
        if (isset($request[$key])) {
            return $request[$key];
        }
        return null;
    }
}
