<?php

namespace CatLab\Charon\Interfaces;

use CatLab\Charon\Models\Properties\ResourceField;

/**
 * Interface RequestResolver
 * @package CatLab\Charon\Interfaces
 */
interface RequestResolver
{
    /**
     * @param $request
     * @param ResourceField $field
     * @return string|null
     */
    public function getFilter($request, ResourceField $field);

    /**
     * @param $request
     * @return mixed
     */
    public function getRecords($request);

    /**
     * @param $request
     * @return mixed
     */
    public function getSorting($request);

    /**
     * @param $request
     * @param ResourceTransformer $
     * @return string|null
     */
    public function getParameter($request, $key);
}
