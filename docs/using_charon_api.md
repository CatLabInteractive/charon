Using a Charon API
==================

Generally any Charon API is self documented using Swagger-ui. However, there are some things to keep in mind when 
communicating with the API.

Contexts
--------
The API decides which fields to show based on the resource definition and the context that is provided. 4 'regular' 
contexts exist:
- INDEX is used when a list of resources is generated
- VIEW is used when a single resource is returned
- CREATE is used when creating new resources
- EDIT is used when editing an existing resource

A 5th context, called 'IDENTIFIER' can be used in combination with relationships to 
only show the identifier fields or to link / unlink resources to related resources.

Expandable relationships
------------------------
A relationship can be defined as 'expandable' or 'expanded'.
- An expandable relationship can be 'expanded' by providing the 'expand' parameter in the query string.
- An expanded relationship will always be 'expanded'.

Visible fields
--------------
Fields are shown or hidden based on the resource definition, the context and the 'fields' query parameter. By default, 
only a resources identifier is visible in any context, but it is common to set some fields to be displayed in both 
VIEW and INDEX context (for example a resource 'name' attribute).

All 'Identifier' fields are always shown.

The visible fields can be overwritten by providing a 'fields' query parameter with a comma separated list of fields to 
show.

```
/api/v1/animals?fields=name,image
```

Relationship fields can be selected by using dots.

```
/api/v1/animals?fields=name,family.name,family.type
```

If no fields attribute has been provided, OR only the expanded relationship name is defined, the default visible fields 
are loaded.

```
/api/v1/animals?fields=name,family
```

You can use the asterisk wildcard to select all default visible fields in combination with normally invisible fields.

```
/api/v1/animals?fields=someInvisibleField,*
```

And this of course also works for relationships:
```
/api/v1/animals?fields=name,family.someInvibleField,family.*
```

Recursive relationships
-----------------------
Both the 'expand' and 'fields' parameters can be made recursively by adding an asterisk at the end of the 
field name. Imagine a data structure where one element can have multiple children:

```
/api/v1/family/1?fields=name,children*,expand=children*
```

This would get you a family resource with all its children (expanded) and all the children of the children as well. 

Note that a maxDepth() has to be set in the resource definition (relationship field).

This can also be combined with the field wildcard:

```
/api/v1/family/1?fields=name,children*.*,children*.someInvisibleField,expand=children*
```