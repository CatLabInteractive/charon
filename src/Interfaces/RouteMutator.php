<?php

declare(strict_types=1);

namespace CatLab\Charon\Interfaces;

use CatLab\Base\Interfaces\Database\OrderParameter;
use CatLab\Charon\Collections\ParameterCollection;
use CatLab\Charon\Models\Routing\ReturnValue;

/**
 * Interface Route
 * @package CatLab\RESTResource\Contracts
 */
interface RouteMutator
{
    /**
     * @param string $type
     * @param string $action
     * @return ReturnValue
     */
    public function returns($type = null, string $action = null) : ReturnValue;

    /**
     * @param string $tag
     * @return RouteMutator
     */
    public function tag(string $tag) : RouteMutator;

    /**
     * @return ParameterCollection
     */
    public function parameters() : ParameterCollection;

    /**
     * @param string|callable $summary
     * @return RouteMutator
     */
    public function summary($summary) : RouteMutator;

    /**
     * @param string $mimetype
     * @return RouteMutator
     */
    public function consumes(string $mimetype) : RouteMutator;

    /**
     * @param string $order
     * @param $direction
     * @return mixed
     */
    public function defaultOrder(string $order, $direction = OrderParameter::ASC) : RouteMutator;

    /**
     * @param int $maxDepth
     * @return RouteMutator
     */
    public function maxExpandDepth(int $maxDepth): RouteMutator;
}
