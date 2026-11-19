<?php

declare(strict_types=1);

namespace Tests;

use CatLab\Base\Enum\Operator;
use CatLab\Charon\Collections\FilterCollection;
use CatLab\Charon\Enums\Action;
use CatLab\Charon\Enums\FilterOperator;
use CatLab\Charon\Exceptions\InvalidFilterException;
use CatLab\Charon\Models\Context;
use CatLab\Charon\Models\Filter;
use Tests\Filters\FilterableDefinition;

final class FilterOperatorTest extends BaseTest
{
    private function collectionFor(array $query): FilterCollection
    {
        return $this->getResourceTransformer()->getFilters(
            $query,
            FilterableDefinition::class,
            new Context(Action::INDEX)
        );
    }

    /**
     * @return array<int, array{0: string, 1: string, 2: mixed}> [field, operator, value]
     */
    private function filtersFor(array $query): array
    {
        $out = [];
        foreach ($this->collectionFor($query) as $filter) {
            /** @var Filter $filter */
            $out[] = [ $filter->getField()->getDisplayName(), $filter->getOperator(), $filter->getValue() ];
        }

        return $out;
    }

    public function testAPlainValueStillFiltersOnEquality(): void
    {
        $this->assertSame([ [ 'url', Operator::EQ, 'https://example.com' ] ], $this->filtersFor([ 'url' => 'https://example.com' ]));
    }

    public function testAListStillFiltersWithThePlainOperator(): void
    {
        $this->assertSame([ [ 'url', Operator::EQ, [ 'a', 'b' ] ] ], $this->filtersFor([ 'url' => [ 'a', 'b' ] ]));
    }

    public function testEachConditionIsAKey(): void
    {
        $this->assertSame([ [ 'url', Operator::EQ, 'a' ] ], $this->filtersFor([ 'url' => [ 'eq' => 'a' ] ]));
        $this->assertSame([ [ 'url', Operator::NEQ, 'a' ] ], $this->filtersFor([ 'url' => [ 'neq' => 'a' ] ]));
        $this->assertSame([ [ 'url', FilterOperator::EMPTY, null ] ], $this->filtersFor([ 'url' => [ 'empty' => '1' ] ]));
        $this->assertSame([ [ 'url', FilterOperator::NOT_EMPTY, null ] ], $this->filtersFor([ 'url' => [ 'not_empty' => '' ] ]));
    }

    public function testSeveralConditionsOnOneField(): void
    {
        $this->assertSame(
            [ [ 'url', Operator::NEQ, 'a' ], [ 'url', FilterOperator::NOT_EMPTY, null ] ],
            $this->filtersFor([ 'url' => [ 'neq' => 'a', 'not_empty' => '1' ] ])
        );
    }

    public function testAConditionWithoutItsValueFiltersNothing(): void
    {
        $this->assertSame([], $this->filtersFor([ 'url' => [ 'neq' => '' ] ]));
        $this->assertSame([], $this->filtersFor([ 'url' => [ 'eq' => null ] ]));
        $this->assertSame([], $this->filtersFor([ 'url' => [ 'neq' => [ 'a' ] ] ]));
    }

    public function testAnUnknownConditionIsRefused(): void
    {
        $this->expectException(InvalidFilterException::class);
        $this->expectExceptionMessage('Unknown filter condition "NEQ" on "url"');
        $this->filtersFor([ 'url' => [ 'NEQ' => 'a' ] ]);
    }

    public function testContainsNeedsASearchableField(): void
    {
        $this->assertSame([ [ 'title', Operator::SEARCH, 'oo' ] ], $this->filtersFor([ 'title' => [ 'contains' => 'oo' ] ]));

        $this->expectException(InvalidFilterException::class);
        $this->expectExceptionMessage('"contains" is not available on "url"');
        $this->filtersFor([ 'url' => [ 'contains' => 'oo' ] ]);
    }

    public function testASearchableOnlyFieldOnlySearches(): void
    {
        $this->assertSame([ [ 'name', Operator::SEARCH, 'foo' ] ], $this->filtersFor([ 'name' => 'foo' ]));
        $this->assertSame([ [ 'name', Operator::SEARCH, 'foo' ] ], $this->filtersFor([ 'name' => [ 'contains' => 'foo' ] ]));

        $this->expectException(InvalidFilterException::class);
        $this->filtersFor([ 'name' => [ 'empty' => '1' ] ]);
    }

    public function testGetFromNameOnlyReturnsAPlainFilter(): void
    {
        // An index policy reading "owner is me" must not read "owner is not me".
        $neq = $this->collectionFor([ 'url' => [ 'neq' => 'mine' ] ]);
        $this->assertNull($neq->getFromName('url'));
        $this->assertNull($neq->getFromDisplayName('url'));
        $this->assertCount(1, $neq->getAllFromName('url'));

        $eq = $this->collectionFor([ 'url' => 'mine' ]);
        $this->assertSame('mine', $eq->getFromName('url')->getValue());
    }

    public function testRequestNames(): void
    {
        $this->assertSame([ 'eq', 'neq', 'contains', 'empty', 'not_empty' ], FilterOperator::getRequestNames());
        $this->assertNull(FilterOperator::fromRequest('nope'));
        $this->assertNull(FilterOperator::fromRequest(0));
        $this->assertFalse(FilterOperator::takesValue(FilterOperator::EMPTY));
        $this->assertTrue(FilterOperator::isConditionOnly(Operator::NEQ));
        $this->assertFalse(FilterOperator::isConditionOnly(Operator::GT));
    }
}
