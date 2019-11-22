<?php

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
    protected $reverted = false;

    /**
     * @var int
     */
    protected $records = 10;

    /**
     * @var \CatLab\Charon\Interfaces\Context
     */
    protected $context;

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
    public function setQueryBuilder($queryBuilder)
    {
        $this->queryBuilder = $queryBuilder;
        return $this;
    }

    /**
     * @return bool
     */
    public function isReverted(): bool
    {
        return $this->reverted;
    }

    /**
     * @param bool $reverted
     * @return FilterResults
     */
    public function setReverted(bool $reverted): FilterResults
    {
        $this->reverted = $reverted;
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
}
