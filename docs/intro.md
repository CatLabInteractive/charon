---
sidebar_position: 1
---

# What is Charon?

Charon is an open source php library that uses resource definitions (recipes) to transform entities to resources and 
exposes those through a customizable RESTfull API.

Example:

Given a few simple entities:

```php
class User {
    public int $id;
    public string $name;
}

class Pet {
    public int $id;
    public string $name;
    public User $owner;
}
```

... and the recipes on how to convert them to resources:

```php
class UserResourceDefinition extends \CatLab\Charon\Models\ResourceDefinition {
    public function __construct()
    {
        parent::__construct(User::class);
        
        $this->identifier('id');
        
        $this->field('name')
            ->visible(true, true);
    }
} 

class PetResourceDefinition extends \CatLab\Charon\Models\ResourceDefinition {
    public function __construct()
    {
        parent::__construct(Pet::class);
        
        $this->identifier('id');
        
        $this->field('name')
            ->string()
            ->min(3)
            ->max(64)
            ->required()
            ->writeable()
            ->searchable()
            ->visible(true, true);
            
        $this->relationship('owner', UserResourceDefinition::class)
            ->visible(true, true)
            ->expandable()
            ->linkable();
    }
}
```

We can then turn entities into resources as such:

```php
$owner = new User();
$owner->id = 1;
$owner->name = 'Batman';

$pet = new Pet();
$pet->id = 1;
$pet->name = 'Robin';
$pet->owner = $user;

$context = new \CatLab\Charon\Models\Context(\CatLab\Charon\Enums\Action::VIEW);

$resourceTransformer = new SimpleResourceTransformer();

$resourceTransformer->to

```
