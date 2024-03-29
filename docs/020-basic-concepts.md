---
id: basic-concepts
title: Basic concepts
sidebar_label: Basic concepts
---

:::info
While Charon was built framework agnostic, in this documentation we assume you are 
using our [Laravel branch](https://github.com/catlabinteractive/charon-laravel).
:::

Getting started with Charon can seem a bit daunting, so we've listed here a bunch of concepts that we used to develop 
Charon.

## What is Charon?
Charon is a layer between your projects domain models and the outside world. It translates domain models (or data 
transfer models) into resources to be used by the outside world, and back again.

## Framework agnostic
Charon is a framework agnostic library, but each framework needs to implement a bunch of interfaces in order for Charon 
to work. As Charon is mainly used in Laravel projects for now, in this documentation we are focussing on the 
[Laravel branch](https://github.com/catlabinteractive/charon-laravel).

All the concepts on this page still apply for other frameworks as well.

## Entity
An entity is an object in the business logic of your application. It is not accessible to the outside world.

## Resource
A resource is a data structure that is exposed to the outside world. It is always built from one (or multiple) entities.
The structure of a resource is defined by the resource definition + the client request (if the client is allowed to 
load specific fields).

## Resource definition

A resource definition is a contract with the outside world that defines how resources are created. A resource definition
also defines how entities are to be transformed into resources.

## (Resource definition) Field

Resource definitions define fields that the resulting resource will contain. A field should be a simple data structure
(string, number, date, ...).

## (Resource definition) Relationship

A relationship is a special type of field that defines a relationship between two resources. A relationship can have 
cardinality "one" or "many". Relationships are, by default, not 'extended', resulting in a relationship field being 
populated with a url (string) pointing to the related resource.

If, however, a relationship is extendable (= a user can choose to include the related resource) or extended (= the 
related resource is always included), the resource will be loaded on request and included in the output.

## Context

Charon requires a context to be set. The context defines the capabilities of the API (like how data can be received 
etc), but it can also contain variables that are required by entity getters. Entities could, for example, return 
data based on the currently authenticated user. This can be achieved by setting the user in the context and assigning 
the context.user variable to the entity getter.

## Resolvers

A resolver is in charge of loading data from entities or setting data into entities. For example, the included Laravel 
resolvers will use the Laravel magic getters to load data from entities.

Data loaded from resolvers will then be set in the resources.

## Entity factory
The entity factory is a special kind of resolver that loads entiteis based on identifiers. It allows Charon to load 
existing entities based on identifiers sent by the client.

## Transformers
Each field in a resource definition can get a transformer assigned that is in charge of translating the raw value
(fetched from the resolver) into a usable format.

## Processor
A processor is called at various times during the Charon execution. It allows setting additional filters. An example is 
the included Pagination processor which adds cursor based pagination the an API.

## InputParser
The InputParsers take raw data from the http request and translate it to resources. These resources can then be 
validated and transformed into new or existing entities.

## Linking / Unlinking
Linking and unlinking are imaginary http requests to link or unlink resources with each other. A list of identifiers 
(most of the times this is the resource id) are sent in a post or delete request to add or remove related items.

