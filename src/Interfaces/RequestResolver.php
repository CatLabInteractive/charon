<?php

declare(strict_types=1);

namespace CatLab\Charon\Interfaces;

use CatLab\Charon\Models\Properties\ResourceField;

/**
 * Interface RequestResolver
 * @package CatLab\Charon\Interfaces
 */
interface RequestResolver
{
    /**
     * @param mixed $request
     * @param ResourceField $field
     * @return boolean
     */
    public function hasFilter($request, ResourceField $field);

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
     * Return an array of property names on which to sort.
     * @param $request
     * @return string[]
     */
    public function getSorting($request);

    /**
     * @param mixed $request
     * @param string $key
     * @return boolean
     */
    public function hasParameter($request, $key);

    /**
     * @param $request
     * @param $key
     * @return mixed
     */
    public function getParameter($request, $key);

    /**
     * @return int|null
     */
    public function getPage($request);

    /**
     * @return string|null
     */
    public function getBeforeCursor($request);

    /**
     * @return string|null
     */
    public function getAfterCursor($request);
}
