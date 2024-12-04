<?php

declare(strict_types=1);

namespace CatLab\Charon\Pagination;

/**
 * Class Navigation
 * @package CatLab\Charon\Pagination
 */
class Navigation implements \CatLab\Base\Interfaces\Pagination\Navigation
{
    private ?int $currentPage = null;

    private ?bool $hasNextPage = null;

    private ?bool $hasPreviousPage = null;

    private ?int $records = null;

    /**
     * @return mixed[]
     */
    public function toArray()
    {
        return [];
    }

    /**
     * @return mixed[]
     */
    public function getNext()
    {
        if ($this->hasNextPage) {
            return [
                'page' => $this->currentPage + 1,
                'records' => $this->records
            ];
        }

        return null;
    }

    /**
     * @return mixed[]
     */
    public function getPrevious()
    {
        if ($this->hasPreviousPage) {
            return [
                'page' => $this->currentPage - 1,
                'records' => $this->records
            ];
        }

        return null;
    }

    /**
     * @param int $currentPage
     * @return Navigation
     */
    public function setCurrentPage(int $currentPage): Navigation
    {
        $this->currentPage = $currentPage;
        return $this;
    }

    /**
     * @param bool $hasNextPage
     * @return Navigation
     */
    public function setHasNextPage(bool $hasNextPage): Navigation
    {
        $this->hasNextPage = $hasNextPage;
        return $this;
    }

    /**
     * @param bool $hasPreviousPage
     * @return Navigation
     */
    public function setHasPreviousPage(bool $hasPreviousPage): Navigation
    {
        $this->hasPreviousPage = $hasPreviousPage;
        return $this;
    }

    /**
     * @param int $records
     * @return Navigation
     */
    public function setRecords(int $records): Navigation
    {
        $this->records = $records;
        return $this;
    }
}
