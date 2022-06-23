# Charon
[![Build Status](https://travis-ci.org/CatLabInteractive/charon.svg?branch=master)](https://travis-ci.org/CatLabInteractive/charon)

*Fractal on steroids.*

What?
=====
Charon is a PHP framework for building self documented RESTful API's.

Please visit [our website](https://charon.catlab.eu/) for more information.

Getting started
===============
While Charon can be used with any framework, I have created a laravel
skeleton project to get you started fast.

Installation
------------
`composer create-project catlabinteractive/laravel-charon api`

Configuration
-------------
Please follow the instructions on 
https://github.com/CatLabInteractive/laravel-charon-template

Why?
====
When building a RESTful API there are a few things that keep coming back:
 * Entity to resource transformation
 * API description
 * Resource field filtering
 * Relationship and expanding these relationships
 * Filtering
 * Sorting
 * Pagination

Charon tries to take some of this work away by providing a 
framework that takes care of most of these features.
 * Instead of transformers, write definitions
 * Filter and sort based on these definitions
 * Allow clients to choose fields that should be returned
 * Expand relationships in a single request
 * Handle all pagination in one single middleware

Documentation
=============
By default, Charon generates Swagger 2.0 documentation. Other 
documentors can be implemented by implementing the interface.

Frameworks
==========
Charon works very well with Laravel, but the library is built
so that it can be incorporated in other frameworks.

An ORM is not required, but makes implementing certain functionality
(like pagination and filtering) a lot easier.

Context options
===============
For both fields and expand parameters: add a * to repeat the field (for 
recursive models).
