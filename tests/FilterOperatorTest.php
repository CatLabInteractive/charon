<?php

declare(strict_types=1);

namespace Tests;

use CatLab\Base\Enum\Operator;
use CatLab\Charon\Enums\Action;
use CatLab\Charon\Enums\FilterOperator;
use CatLab\Charon\Models\Context;
use CatLab\Charon\Models\Filter;
use Tests\Filters\FilterableDefinition;

final class FilterOperatorTest extends BaseTest
{
    /**
     * @return array<string, array{0: string, 1: mixed}> field => [operator, value]
     */
    private function filtersFor(array $query): array
    {
        $filters = $this->getResourceTransformer()->getFilters(
            $query,
            FilterableDefinition::class,
            new Context(Action::INDEX)
        );

        $out = [];
        foreach ($filters as $filter) {
            /** @var Filter $filter */
            $out[$filter->getField()->getDisplayName()] = [ $filter->getOperator(), $filter->getValue() ];
        }

        return $out;
    }

    public function testAPlainValueStillFiltersOnEquality(): void
    {
        $this->assertSame(
            [ 'url' => [ Operator::EQ, 'https://example.com' ] ],
            $this->filtersFor([ 'url' => 'https://example.com' ])
        );
    }

    public function testAnArrayPicksTheOperator(): void
    {
        $this->assertSame([ 'url' => [ Operator::EQ, 'a' ] ], $this->filtersFor([ 'url' => [ 'op' => 'eq', 'value' => 'a' ] ]));
        $this->assertSame([ 'url' => [ Operator::EQ, 'a' ] ], $this->filtersFor([ 'url' => [ 'op' => '', 'value' => 'a' ] ]));
        $this->assertSame([ 'url' => [ Operator::EQ, 'a' ] ], $this->filtersFor([ 'url' => [ 'value' => 'a' ] ]));
        $this->assertSame([ 'url' => [ Operator::NEQ, 'a' ] ], $this->filtersFor([ 'url' => [ 'op' => 'neq', 'value' => 'a' ] ]));
        $this->assertSame([ 'url' => [ Operator::SEARCH, 'a' ] ], $this->filtersFor([ 'url' => [ 'op' => 'contains', 'value' => 'a' ] ]));
    }

    public function testEmptyAndNotEmptyNeedNoValue(): void
    {
        $this->assertSame([ 'url' => [ FilterOperator::EMPTY, null ] ], $this->filtersFor([ 'url' => [ 'op' => 'empty' ] ]));
        $this->assertSame(
            [ 'url' => [ FilterOperator::NOT_EMPTY, null ] ],
            $this->filtersFor([ 'url' => [ 'op' => 'not_empty', 'value' => 'ignored' ] ])
        );
    }

    public function testNothingIsFilteredOnAnUnknownOperatorOrAMissingValue(): void
    {
        $this->assertSame([], $this->filtersFor([ 'url' => [ 'op' => 'like', 'value' => 'a' ] ]));
        $this->assertSame([], $this->filtersFor([ 'url' => [ 'op' => [ 'x' ], 'value' => 'a' ] ]));
        $this->assertSame([], $this->filtersFor([ 'url' => [ 'op' => 'contains', 'value' => '' ] ]));
        $this->assertSame([], $this->filtersFor([ 'url' => [ 'op' => 'eq' ] ]));
        $this->assertSame([], $this->filtersFor([ 'url' => [ 'op' => 'eq', 'value' => [ 'a' ] ] ]));
    }

    public function testSearchableFieldsAreUnchanged(): void
    {
        $this->assertSame([ 'name' => [ Operator::SEARCH, 'foo' ] ], $this->filtersFor([ 'name' => 'foo' ]));
    }

    public function testRequestNames(): void
    {
        $this->assertSame([ 'eq', 'neq', 'contains', 'empty', 'not_empty' ], FilterOperator::getRequestNames());
        $this->assertNull(FilterOperator::fromRequest('nope'));
        $this->assertFalse(FilterOperator::takesValue(FilterOperator::EMPTY));
        $this->assertTrue(FilterOperator::takesValue(Operator::SEARCH));
    }
}
