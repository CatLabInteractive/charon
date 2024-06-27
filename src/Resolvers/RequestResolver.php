<?php

declare(strict_types=1);

namespace CatLab\Charon\Resolvers;

use CatLab\Base\Enum\Operator;
use CatLab\Charon\Interfaces\ResourceTransformer;
use CatLab\Charon\Models\Properties\ResourceField;

/**
 * Class RequestResolver
 * @package CatLab\Charon\Resolvers
 */
class RequestResolver implements \CatLab\Charon\Interfaces\RequestResolver
{
    public const PAGE_PARAMETER = 'page';

    public const CURSOR_BEFORE_PARAMETER = 'before';

    public const CURSOR_AFTER_PARAMETER = 'after';

    /**
     * @param $request
     * @param ResourceField $field
     * @param string $operator
     * @return string|null
     * @throws \CatLab\Charon\Exceptions\InvalidTransformer
     */
    public function getFilter($request, ResourceField $field, $operator = Operator::EQ)
    {
        $value = $this->getParameter($request, $field->getDisplayName());
        if ($field->getTransformer()) {
            return $field->getTransformer()->toParameterValue($value);
        }

        return $value;
    }

    /**
     * @param mixed $request
     * @param ResourceField $field
     * @param string $operator
     * @return bool|void
     */
    public function hasFilter($request, ResourceField $field, $operator = Operator::EQ)
    {
        return $this->hasParameter($request, $field->getDisplayName());
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
     * @return string[]
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
        return $request[$key] ?? null;
    }

    /**
     * @param mixed $request
     * @param string $key
     * @return bool
     */
    public function hasParameter($request, $key): bool
    {
        return array_key_exists($key, $request);
    }

    /**
     * @param $request
     * @return int|null
     */
    public function getPage($request): ?int
    {
        $page = $this->getParameter($request, self::PAGE_PARAMETER);
        if (!is_string($page)) {
            return null;
        }

        return (int) $page;
    }

    /**
     * @param $request
     * @return string|null
     */
    public function getBeforeCursor($request): ?string
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
    public function getAfterCursor($request): ?string
    {
        $cursor = $this->getParameter($request, self::CURSOR_AFTER_PARAMETER);
        if (!is_string($cursor)) {
            return null;
        }

        return $cursor;
    }
}
