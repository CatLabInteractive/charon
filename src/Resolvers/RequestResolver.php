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
    const PAGE_PARAMETER = 'page';
    const CURSOR_BEFORE_PARAMETER = 'before';
    const CURSOR_AFTER_PARAMETER = 'after';

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

    /**
     * @param $request
     * @return string|null
     */
    public function getPage($request)
    {
        $page = $this->getParameter($request, self::PAGE_PARAMETER);
        if (!is_string($page)) {
            return null;
        }
        return $page;
    }

    /**
     * @param $request
     * @return string|null
     */
    public function getBeforeCursor($request)
    {
        $cursor = $this->getParameter($request, self::CURSOR_BEFORE_PARAMETER);
        if (!is_string($cursor)) {
            return null;
        }
        return $cursor;
    }

    /**
     * @param $request
     * @return string|null
     */
    public function getAfterCursor($request)
    {
        $cursor = $this->getParameter($request, self::CURSOR_AFTER_PARAMETER);
        if (!is_string($cursor)) {
            return null;
        }
        return $cursor;
    }
}
