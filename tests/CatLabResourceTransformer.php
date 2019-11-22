<?php
namespace CatLab\RESTResource\Tests;

use CatLab\Base\Models\Database\SelectQueryParameters;

/**
 * Class CatLabResourceTransformer
 * @package CatLab\RESTResource\Tests
 */
class CatLabResourceTransformer extends \CatLab\Charon\ResourceTransformer
{

    /**
     * Apply processor filters (= filters that are created by processors) and translate them to the system specific
     * query builder.
     * @param $queryBuilder
     * @param SelectQueryParameters $parameters
     * @return void
     */
    protected function applyProcessorFilters($queryBuilder, SelectQueryParameters $parameters)
    {
        if (! ($queryBuilder instanceof SelectQueryParameters)) {
            throw new \InvalidArgumentException(SelectQueryParameters::class . ' expected');
        }

        foreach ($parameters->getWhere() as $where) {
            $queryBuilder->where($where);
        }

        $queryBuilder->limit($parameters->getLimit());
        $queryBuilder->reverse($parameters->isReverse());
        foreach ($parameters->getSort() as $v) {
            $queryBuilder->orderBy($v);
        }
    }
}
