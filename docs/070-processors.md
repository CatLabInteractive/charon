---
id: processors
title: Processors (& Pagination)
sidebar_label: Processors
---

Pagination
==========
Pagination is handled by a Processor.

Processors
----------
`Processors` can be added to the `Context` that is passed to the `ResourceTransformer`. Processors must implement the 
`CatLab\Charon\Interfaces\Processor` interface, which defines 3 methods:
 * `processFilters()` is called when the filters are being parsed
 * `processCollection()` is called when any collection of resources is generated
 * `processResource()` is called for every resource that gets generated

Processors are meant to alter the behaviour of the API. The PaginationProcessor (available in this package) for example 
makes sure that every collection of resources is paginated. The provided paginator itself is actually an adapter for 
the actual paginator algorithm. In the template project we use a cursor pagination instead of a limit based paginator.
