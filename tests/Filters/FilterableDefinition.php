<?php

declare(strict_types=1);

namespace Tests\Filters;

use Tests\Models\MockEntityModel;

class FilterableDefinition extends \CatLab\Charon\Models\ResourceDefinition
{
    public function __construct()
    {
        parent::__construct(MockEntityModel::class);

        $this->identifier('id');

        $this->field('url')
            ->string()
            ->visible()
            ->filterable();

        $this->field('title')
            ->string()
            ->visible()
            ->filterable()
            ->searchable();

        $this->field('name')
            ->string()
            ->visible()
            ->searchable();
    }
}
