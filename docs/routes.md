Routes
======
In order to use the self documenting features of Charon you need to 
define the API endpoints in routes. These routes can then be translated 
into routes of a framework of your choice (for example Laravel).

Charon routes have been designed similar to Laravel routes.

Simple Example
--------------
```php
$routes
    ->get('books/{id}', 'BookController@show')
    ->summary('Show a book')
    ->parameters()
        ->path('id')
        ->int()
        ->required()
    ->returns()
        ->statusCode(200)
        ->one(BookResourceDefinition::class)
        ->describe('The book.');
```
        
Above code defines a route to which requires one input (a path parameter called "id") 
and returns a single object of type Book.  The output of Book is defined 
in BookResourceDefinition.

Actions
-------
Following actions are available:
- get
- post
- put
- delete
- link (will translate to http method "post")
- unlink (will translate to http method "delete)

Grouping
--------
Similar to Laravel routes, you can group routes by calling `group()`. All 
properties set in the group options array will be passed on the all routes in the group.

```php
$routes->group(
        $routes->group(
            [
                'tags' => 'books'
            ],
            function(RouteCollection $routes) {
                $routes->get('books/{id}', 'BookController@show');
            }
        )
);
```

Parameters
----------
Each route can define a set of path, query, form or body parameters.
A parameter can also be of type "resource" in which case additional parameters 
will be created based on the loaded InputParsers.

```php
$routes
    ->post('organisations/{id}/books', 'BookController@store')
    ->parameters()
        ->path('id')
        ->describe('Organisation ID')
        ->int()
        ->required()
    ->parameters()
        ->resource(BookResourceDefinition::class)
        ->required()
    ->summary('Create a new book')
    ->returns()->one(BookResourceDefinition::class);
```

If JsonBodyInputParser is set in the Context, the documentation for this route 
will contain a "body" parameter expecting data defined in BookResourceDefinition 
structure.

If, however, PostInputParser is set in the Context, the documentation will contain a 
set of formData fields for all writeable fields in BookResourceDefinition.

Note that multiple InputParsers can be combined. Only the first InputParser 
returning NOT NULL will be used. That's why the provided InputParsers use the 
request content type to select an InputParser.

Enum and allowMultiple
----------------------
On post and query parameters you can define a list of allowed values (`enum()`) 
and check if multiple values are allowed (`allowMultiple()`). By default comma separated
values are expected, but you can handle the input however you want.