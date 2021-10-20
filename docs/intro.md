---
sidebar_position: 1
---

# What is Charon?

Charon is an open source php library that uses resource definitions (recipes) to transform entities to resources and 
exposes those through a customizable RESTfull API.

Very basic example of what Charon can do:

We start with defining our entities:

```php
<?php
class Person {
    public int $personId;
    public string $personName;
}

class Pet {
    public int $petId;
    public string $petName;
    public Person $petOwner;
}
```

Then we define `Resource Definitions` that describe how we want to expose these entities to the world:

```php
class PersonResourceDefinition extends \CatLab\Charon\Models\ResourceDefinition {
    public function __construct()
    {
        parent::__construct(Person::class);

        $this->setUrl('/api/users');

        $this->identifier('personId')
            ->display('id');

        $this->field('personName')
            ->display('name')
            ->writeable()
            ->visible(true, true);
    }
}

class PetResourceDefinition extends \CatLab\Charon\Models\ResourceDefinition {
    public function __construct()
    {
        parent::__construct(Pet::class);

        $this->setUrl('/api/pets');

        $this->identifier('petId')
            ->display('id');

        $this->field('petName')
            ->display('name')
            ->string()
            ->min(3)
            ->max(64)
            ->required()
            ->writeable()
            ->searchable()
            ->visible(true, true);

        $this->relationship('petOwner', PersonResourceDefinition::class)
            ->display('owner')
            ->one()
            ->visible(true, true)
            ->expandable()
            ->url('/api/users/{model.petId}')
            ->writeable();
    }
}
```

Initialize the entities that we are going to use in our example:

```php
$owner = new Person();
$owner->personId = 1;
$owner->personName = 'Batman';

$pet = new Pet();
$pet->petId = 1;
$pet->petName = 'Robin';
$pet->petOwner = $owner;
```

... and prepare these entities for exposure:

```php
$charon = new \CatLab\Charon\SimpleResolvers\SimpleResourceTransformer();

// We define a context that we can use to set preferences
$readContext = new \CatLab\Charon\Models\Context(\CatLab\Charon\Enums\Action::VIEW);

// ... and convert the entity into a resource.
$resource = $charon->toResource(PetResourceDefinition::class, $pet, $readContext);
```

We now have a resource that we can return in any format depending on our API design (JSON, XML, JSON-API, ...) But let's 
keep it to simple associative arrays for now.
 
```php
print_r($resource->toArray());
```

```
Array
(
    [id] => 1
    [name] => Robin
    [owner] => Array
        (
            [link] => /api/users/1
        )

)
```

Note that the attributes have been translated to their 'display' names, ie `petName` is called `name` in the resource

Additionally, we can change the context so that we also expand the `owner` relationship in the resource: 

```php
$expandedReadContext = new \CatLab\Charon\Models\Context(\CatLab\Charon\Enums\Action::VIEW);
$expandedReadContext->expandField('owner');

$resource = $charon->toResource(PetResourceDefinition::class, $pet, $expandedReadContext);

print_r($resource->toArray());
```

```
Array
(
    [id] => 1
    [name] => Robin
    [owner] => Array
        (
            [id] => 1
            [name] => Batman
        )

)
```

Now let's do the same in the opposite direction, from raw input to entity.
We start with a simple array of the content:

```php
$content = [
    'name' => 'Corgi',
    'owner' => [
        'name' => 'The Queen'
    ]
];
```

We need to define a `create` context (in order to create a resource from input):

```php
$writeContext = new \CatLab\Charon\Models\Context(\CatLab\Charon\Enums\Action::CREATE);
$inputResource = $charon->fromArray(PetResourceDefinition::class, $content, $writeContext);
```

`$inputResource` now contains a regular resource, the same type we saw before. However, since the identifier fields 
are not described as being writable, they are not included in this resource:

```php
print_r($inputResource->toArray());
```

```
Array
(
    [name] => CO
    [owner] => Array
        (
            [name] => The Queen
        )

)
```

To make sure our resource matches our expectations we can then run a validator on the resource. There are a few validators
built in (required, scalar types, dates, ...) but every `Resource Definition` can be extended with custom validators.

```php
try {
    $inputResource->validate($writeContext);
} catch (\CatLab\Requirements\Exceptions\ResourceValidationException $e) {
    print_r ($e->getMessages()->toArray());
}
```

```
Array
(
    [0] => Array
        (
            [property] => name
            [message] => Property 'name' must have a minimum length of 3.
        )

)
```

Oh no! The name we have given our Pet is too short. Let's fix that:

```php
$content = [
    'name' => 'Corgi',
    'owner' => [
        'name' => 'The Queen'
    ]
];

$writeContext = new \CatLab\Charon\Models\Context(\CatLab\Charon\Enums\Action::CREATE);
$inputResource = $charon->fromArray(PetResourceDefinition::class, $content, $writeContext);

try {
    $inputResource->validate($writeContext);
} catch (\CatLab\Requirements\Exceptions\ResourceValidationException $e) {
    print_r ($e->getMessages()->toArray());
}
```

Great! No more validation errors. Now convert our resource back to entities so that we can store them in our database:

```php
$entityFactory = new \CatLab\Charon\Factories\EntityFactory();
$entity = $charon->toEntity($inputResource, $entityFactory, $writeContext);

var_dump($entity);
```

```
object(Pet)#52 (2) {
  ["petId"]=>
  uninitialized(int)
  ["petName"]=>
  string(5) "Corgi"
  ["petOwner"]=>
  object(Person)#58 (1) {
    ["personId"]=>
    uninitialized(int)
    ["personName"]=>
    string(9) "The Queen"
  }
}
```

And that's a very basic description of what Charon does.
