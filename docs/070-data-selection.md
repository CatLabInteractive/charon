---
id: data-selection
title: Data selection
sidebar_label: Data selection
---

Data selection
==============

Field selection
---------------
Charon allows the API consumer to select which resource data is returned. How this is implemented depends on the API 
description, but in the default REST description this selection can be entered in the `fields` query parameter.

Internally, the `QueryResolver` will be used to read the query parameter, which will then be set in the `Context` that is
used to generate the `Resource`. The `ResourceTransformer` will read this context and decide which fields should be 
included in the `Resource`.

To return all fields that are loaded by default, use an asterix ('*'). In general use this won't be needed, but 
in case you expand relationships it could be handy (ie: `?expand=owner&fields=name,owner.*`)

Expanded relationships
----------------------
Relationship fields can be marked as `expendable`, in which case it is possible to include the content of the relationship 
in your response. The implementation depends on the API description, but in the default REST description the expanded 
fields can be entered in the `expand` query parameter.

In case a relationship is not expanded, but the field is visible and selected, Charon will return an object with a 
'link' attribute pointing to the related resource.

Eager loading
-------------
It is possible to let Charon decide what information should be 'eager loaded'. The default QueryAdapter looks 
for a static method 'eagerLoad{AttributeName}' method that will be called with the provided query builder. 

Dot notation
------------
The field selection for expanded related resources follow a DOT notation.  
For example: `?expand=avatar&fields=user.name,user.avatar.url`
will return the user name and the user avatar url. Fields marked as 'identifier' are always returned, so an expected
output of this call would be:

```json
{
  "user" : {
    "id" : 1,
    "name" : "John",
    "avatar" : {
      "id" : 2,
      "url" : "user/1/avatar.png"
    }
  }
}
```

Take note that expandable relationships that have expandable relationships themselves can also be expanded with the dot 
notation. `?expand=user.company.address` for example would expand the address field of the company as well:

```json
{
  "user" : {
    "id" : 1,
    "name" : "John",
    "company" : {
      "id" : 2,
      "name" : "Test",
      "address" : {
        "id" : 3,
        "street" : "McStreetRoad",
        "number" : "17",
        "city" : "City Town"
      }
    }
  }
}
```
