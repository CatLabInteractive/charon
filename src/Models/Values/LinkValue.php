<?php

declare(strict_types=1);

namespace CatLab\Charon\Models\Values;

use CatLab\Charon\Models\CurrentPath;
use CatLab\Requirements\Exceptions\PropertyValidationException;
use CatLab\Charon\Interfaces\Context;
use CatLab\Charon\Interfaces\EntityFactory;
use CatLab\Charon\Interfaces\PropertyResolver;
use CatLab\Charon\Interfaces\PropertySetter;
use CatLab\Charon\Interfaces\ResourceTransformer;
use CatLab\Charon\Models\Values\Base\Value;

/**
 * Class LinkValue
 * @package CatLab\RESTResource\Models\Values
 */
class LinkValue extends Value
{
    private $link;

    /**
     * @param $link
     * @return $this
     */
    public function setLink($link): static
    {
        $this->link = $link;
        return $this;
    }

    /**
     * Set a value in an entity
     * @param $entity
     * @param ResourceTransformer $resourceTransformer
     * @param PropertyResolver $propertyResolver
     * @param PropertySetter $propertySetter
     * @param EntityFactory $factory
     * @param Context $context
     */
    public function toEntity(
        $entity,
        ResourceTransformer $resourceTransformer,
        PropertyResolver $propertyResolver,
        PropertySetter $propertySetter,
        EntityFactory $factory,
        Context $context
    ): void {

    }

    /**
     * @return string
     */
    public function getValue(string $path = null)
    {
        return $this->link;
    }

    /**
     * @return mixed
     */
    public function toArray(): array
    {
        return [
            ResourceTransformer::RELATIONSHIP_LINK => $this->link
        ];
    }

    /**
     * @param Context $context
     * @param CurrentPath $path
     * @param bool $validateNonProvidedFields
     * @throws PropertyValidationException
     */
    public function validate(Context $context, CurrentPath $path, $validateNonProvidedFields = true): void
    {
        $this->getField()->validate($this->value, $path, $validateNonProvidedFields);
    }
}
