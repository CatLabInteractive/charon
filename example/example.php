<?php

require '../vendor/autoload.php';

echo "Charon very simple example script:\n";
echo "----------------------------------\n";

class Person {
    public int $personId;
    public string $personName;
}

class Pet {
    public int $petId;
    public string $petName;
    public Person $petOwner;
}

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

$owner = new Person();
$owner->personId = 1;
$owner->personName = 'Batman';

$pet = new Pet();
$pet->petId = 1;
$pet->petName = 'Robin';
$pet->petOwner = $owner;

echo "Done generating all required classes!\n\n";

$charon = new \CatLab\Charon\SimpleResolvers\SimpleResourceTransformer();

echo "We define a context that we can use to set preferences.\n";
$readContext = new \CatLab\Charon\Models\Context(\CatLab\Charon\Enums\Action::VIEW);

echo "... and convert the entity into a resource. Done!\n";
$resource = $charon->toResource(PetResourceDefinition::class, $pet, $readContext);

echo "\n\n";

echo "Which we can then output in any syntax we want (simple array in this example):\n";
print_r($resource->toArray());

echo "\n";
echo "(Note that the attributes have been translated to their 'display' names, ie `petName` is called `name` in the resource)\n\n";

echo "Additionally, we can change the context so that we also include the 'owner' relationship:\n";
$expandedReadContext = new \CatLab\Charon\Models\Context(\CatLab\Charon\Enums\Action::VIEW);
$expandedReadContext->expandField('owner');

echo "Context with 'owner' attribute expanded:\n\n";

print_r($charon->toResource(PetResourceDefinition::class, $pet, $expandedReadContext)->toArray());

echo "\n";
echo "Pretty neat, huh? Now let's to in the opposite direction:\n";
echo "Let's start with this simple array content:\n\n";

// And of course it works both ways (we'll start from an array for this example)
$content = [
    'name' => 'CO',
    'owner' => [
        'name' => 'The Queen'
    ]
];

print_r($content);

echo "\nWe need to define a 'create' context (in order to create a resource from input)\n";
$writeContext = new \CatLab\Charon\Models\Context(\CatLab\Charon\Enums\Action::CREATE);

echo "... and bam! We have our resource:\n\n";
$inputResource = $charon->fromArray(PetResourceDefinition::class, $content, $writeContext);

print_r($inputResource->toArray());

echo "Now lets validate that";
try {
    $inputResource->validate($writeContext);
} catch (\CatLab\Requirements\Exceptions\ResourceValidationException $e) {
    print_r ($e->getMessages()->toArray());
}

/**
 * With correct validation
 */

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

echo "And once the resource is validated, we can then turn it into an entity again:\n";
$entity = $charon->toEntity($inputResource, new \CatLab\Charon\Factories\EntityFactory(), $writeContext);

echo "Ready to be stored in your database!\n\n";
var_dump($entity);
