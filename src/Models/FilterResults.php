<?php

declare(strict_types=1);

namespace CatLab\Charon\Models;

/**
 * Class FilterResults
 * @package CatLab\Charon\Models
 */
class FilterResults
{
    /**
     * @var mixed
     */
    protected $queryBuilder;

    /**
     * @var boolean
     */
    protected $reversed = false;

    /**
     * @var int
     */
    protected $records = 10;

    /**
     * @var \CatLab\Charon\Interfaces\Context
     */
    protected $context;

    /**
     * @var int
     */
    protected $totalRecords;

    /**
     * @var string
     */
    protected $currentPage;

    /**
     * @return mixed
     */
    public function getQueryBuilder()
    {
        return $this->queryBuilder;
    }

    /**
     * @param mixed $queryBuilder
     * @return FilterResults
     */
    public function setQueryBuilder($queryBuilder): static
    {
        $this->queryBuilder = $queryBuilder;
        return $this;
    }

    /**
     * @return bool
     */
    public function isReversed(): bool
    {
        return $this->reversed;
    }

    /**
     * @param bool $reverted
     * @return FilterResults
     */
    public function setReversed(bool $reverted): FilterResults
    {
        $this->reversed = $reverted;
        return $this;
    }

    /**
     * @return int
     */
    public function getRecords(): int
    {
        return $this->records;
    }

    /**
     * @param int $records
     * @return FilterResults
     */
    public function setRecords(int $records): FilterResults
    {
        $this->records = $records;
        return $this;
    }

    /**
     * @return int
     */
    public function getTotalRecords()
    {
        return $this->totalRecords;
    }

    /**
     * @param int $totalRecords
     * @return FilterResults
     */
    public function setTotalRecords($totalRecords): FilterResults
    {
        $this->totalRecords = $totalRecords;
        return $this;
    }

    /**
     * @return \CatLab\Charon\Interfaces\Context
     */
    public function getContext(): \CatLab\Charon\Interfaces\Context
    {
        return $this->context;
    }

    /**
     * @param \CatLab\Charon\Interfaces\Context $context
     * @return FilterResults
     */
    public function setContext(\CatLab\Charon\Interfaces\Context $context): FilterResults
    {
        $this->context = $context;
        return $this;
    }

    /**
     * @return string
     */
    public function getCurrentPage()
    {
        return $this->currentPage;
    }

    /**
     * @param string $currentPage
     * @return FilterResults
     */
    public function setCurrentPage($currentPage): static
    {
        $this->currentPage = $currentPage;
        return $this;
    }
}
