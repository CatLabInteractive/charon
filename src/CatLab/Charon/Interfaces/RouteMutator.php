<?php

namespace CatLab\Charon\Interfaces;
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
    public function returns(string $type = null, string $action = null) : ReturnValue;

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
     * @param string $summary
     * @return RouteMutator
     */
    public function summary(string $summary) : RouteMutator;

    /**
     * @param string $mimetype
     * @return RouteMutator
     */
    public function consumes(string $mimetype) : RouteMutator;
}