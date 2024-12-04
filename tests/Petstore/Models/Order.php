<?php

declare(strict_types=1);

namespace Tests\Petstore\Models;
use DateTime;

/**
 * Class Order
 * @package CatLab\Petstore\Models
 */
class Order
{
    /**
     * @var int
     */
    private $id;

    /**
     * @var int
     */
    private $petId;

    /**
     * @var bool
     */
    private $complete;

    /**
     * @var int
     */
    private $quantity;

    /**
     * @var DateTime
     */
    private $shipDate;

    /**
     * @var string
     */
    private $status;

    /**
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @param int $id
     * @return Order
     */
    public function setId($id): static
    {
        $this->id = $id;
        return $this;
    }

    /**
     * @return int
     */
    public function getPetId()
    {
        return $this->petId;
    }

    /**
     * @param int $petId
     * @return Order
     */
    public function setPetId($petId): static
    {
        $this->petId = $petId;
        return $this;
    }

    /**
     * @return boolean
     */
    public function isComplete()
    {
        return $this->complete;
    }

    /**
     * @param boolean $complete
     * @return Order
     */
    public function setComplete($complete): static
    {
        $this->complete = $complete;
        return $this;
    }

    /**
     * @return int
     */
    public function getQuantity()
    {
        return $this->quantity;
    }

    /**
     * @param int $quantity
     * @return Order
     */
    public function setQuantity($quantity): static
    {
        $this->quantity = $quantity;
        return $this;
    }

    /**
     * @return DateTime
     */
    public function getShipDate()
    {
        return $this->shipDate;
    }

    /**
     * @param DateTime $shipDate
     * @return Order
     */
    public function setShipDate($shipDate): static
    {
        $this->shipDate = $shipDate;
        return $this;
    }

    /**
     * @return string
     */
    public function getStatus()
    {
        return $this->status;
    }

    /**
     * @param string $status
     * @return Order
     */
    public function setStatus($status): static
    {
        $this->status = $status;
        return $this;
    }
}
