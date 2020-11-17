<?php

namespace CatLab\Charon\Pagination;

use CatLab\Base\Helpers\ArrayHelper;
use CatLab\Base\Interfaces\Pagination\Navigation;
use CatLab\Base\Models\Database\LimitParameter;
use CatLab\Base\Models\Database\OrderParameter;
use CatLab\Base\Models\Database\SelectQueryParameters;
use CatLab\Charon\Interfaces\HasRequestResolver;
use CatLab\Charon\Interfaces\RequestResolver;
use CatLab\Charon\Models\FilterResults;
use InvalidArgumentException;

/**
 * Class PaginationBuilder
 *
 * Regular limit based pagination.
 *
 * @package CatLab\Charon\Pagination
 */
class PaginationBuilder implements \CatLab\Base\Interfaces\Pagination\PaginationBuilder, HasRequestResolver
{
    const REQUEST_PARAM_NEXT = 'next';
    const REQUEST_PARAM_PREVIOUS = 'previous';

    /**
     * @var OrderParameter[]
     */
    private $sort = [];

    /**
     * @var int
     */
    private $records = 10;

    /**
     * @var int
     */
    private $page = 1;

    /**
     * @var bool
     */
    private $hasNextPage = false;

    /**
     * @var bool
     */
    private $hasPreviousPage = false;

    /**
     * @var RequestResolver
     */
    private $requestResolver;

    /**
     * @param RequestResolver $requestResolver
     * @return PaginationBuilder
     */
    public function setRequestResolver(RequestResolver $requestResolver)
    {
        $this->requestResolver = $requestResolver;
        return $this;
    }

    /**
     * @param OrderParameter $order
     * @return \CatLab\Base\Interfaces\Pagination\PaginationBuilder
     */
    public function orderBy(OrderParameter $order)
    {
        $this->sort[] = $order;
        return $this;
    }

    /**
     * @param string $column
     * @param string $publicName
     * @param \closure|null $transformer
     * @return \CatLab\Base\Interfaces\Pagination\PaginationBuilder
     */
    public function registerPropertyName(string $column, string $publicName, \closure $transformer = null)
    {
        // not required
        return $this;
    }

    /**
     * @param int $records
     * @return \CatLab\Base\Interfaces\Pagination\PaginationBuilder
     */
    public function limit(int $records)
    {
        $this->records = $records;
        return $this;
    }

    /**
     * @param SelectQueryParameters $queryBuilder
     * @return SelectQueryParameters
     */
    public function build(SelectQueryParameters $queryBuilder = null)
    {
        if (!isset($queryBuilder)) {
            $queryBuilder = new SelectQueryParameters();
        }

        if ($this->records) {
            $offset = ($this->page - 1) * $this->records;
            $limit = $this->records;

            $queryBuilder->limit(new LimitParameter($offset, $limit));
        }

        if (isset($this->sort)) {
            foreach ($this->sort as $sort) {
                $dir = $sort->getDirection();
                $queryBuilder->orderBy(new OrderParameter($sort->getColumn(), $dir, $sort->getEntity()));
            }
        }

        return $queryBuilder;
    }

    /**
     * @return Navigation
     */
    public function getNavigation(): Navigation
    {
        $nav = new \CatLab\Charon\Pagination\Navigation();

        $nav->setCurrentPage($this->page);
        $nav->setHasNextPage($this->hasNextPage);
        $nav->setHasPreviousPage($this->hasPreviousPage);

        return $nav;
    }

    /**
     * @param array $properties
     * @return \CatLab\Base\Interfaces\Pagination\PaginationBuilder
     */
    public function setRequest(array $properties)
    {
        $this->page = $this->requestResolver->getPage($properties);
        if (!$this->page) {
            $this->page = 1;
        }
        return $this;
    }

    /**
     * @return OrderParameter[]
     */
    public function getOrderBy()
    {
        return $this->sort;
    }

    /**
     * @param SelectQueryParameters $parameters
     * @param mixed[] $collection
     * @return mixed[]
     * @throws \CatLab\Base\Helpers\Exceptions\ArrayHelperException
     */
    public function processResults(SelectQueryParameters $query, $results)
    {
        if (!ArrayHelper::isIterable($results)) {
            throw new InvalidArgumentException("Results should be iterable.");
        }

        if ($query->isReverse()) {
            $results = ArrayHelper::reverse($results);
        }

        return $this->processCollection($results);
    }

    /**
     * @param $results
     * @param FilterResults|null $filterResults
     * @return mixed[]
     */
    public function processCollection($results, FilterResults $filterResults = null)
    {
        $this->hasPreviousPage = $this->page > 1;
        $this->hasNextPage = count($results) >= $this->records;

        if ($filterResults) {
            $filterResults->setCurrentPage($this->page);
        }

        return $results;
    }

    /**
     * @param array $properties
     * @return \CatLab\Base\Interfaces\Pagination\PaginationBuilder
     */
    public function setFirst($properties): \CatLab\Base\Interfaces\Pagination\PaginationBuilder
    {
        // not required
        return $this;
    }

    /**
     * @param array $properties
     * @return \CatLab\Base\Interfaces\Pagination\PaginationBuilder
     */
    public function setLast($properties): \CatLab\Base\Interfaces\Pagination\PaginationBuilder
    {
        // not required
        return $this;
    }
}
