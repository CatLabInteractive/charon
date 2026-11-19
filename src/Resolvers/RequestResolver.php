<?php

declare(strict_types=1);

namespace CatLab\Charon\Resolvers;

use CatLab\Base\Enum\Operator;
use CatLab\Charon\Interfaces\ResourceTransformer;
use CatLab\Charon\Enums\FilterOperator;
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
        return $this->transformFilterValue($field, $value);
    }

    /**
     * Run a filter value through the field's transformer. For an array of
     * conditions ([ 'neq' => '2024-01-01' ], see FilterOperator) or a list,
     * each scalar value is transformed, except those of conditions that do
     * not compare with the field's value (empty, not_empty, contains).
     * @param ResourceField $field
     * @param mixed $value
     * @return mixed
     */
    protected function transformFilterValue(ResourceField $field, $value)
    {
        $transformer = $field->getTransformer();
        if (!$transformer) {
            return $value;
        }

        if (!is_array($value)) {
            return $transformer->toParameterValue($value);
        }

        $untransformed = [
            FilterOperator::REQUEST_EMPTY,
            FilterOperator::REQUEST_NOT_EMPTY,
            FilterOperator::REQUEST_CONTAINS
        ];

        foreach ($value as $key => $operand) {
            if (is_scalar($operand) && !in_array($key, $untransformed, true)) {
                $value[$key] = $transformer->toParameterValue($operand);
            }
        }

        return $value;
    }

    /**
     * @param mixed $request
     * @param ResourceField $field
     * @param string $operator
     * @return bool|void
     */
    public function hasFilter($request, ResourceField $field, $operator = Operator::EQ): bool
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
