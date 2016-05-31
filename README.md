# Charon
*Fractal on steroids.*

What?
=====
Charon is a framework for building self documented RESTfull API's.

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