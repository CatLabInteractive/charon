<?php

declare(strict_types=1);

namespace CatLab\Charon\Interfaces;

use CatLab\Base\Models\Database\SelectQueryParameters;
use CatLab\Charon\CharonConfig;
use CatLab\Charon\Models\FilterResults;
use CatLab\Charon\Models\Properties\Base\Field;
use CatLab\Charon\Models\Values\Base\RelationshipValue;

/**
 * Interface ResourceTransformer
 * @package CatLab\RESTResource\Contracts
 */
interface ResourceTransformer
{
    public const RELATIONSHIP_LINK = 'link';

    public const RELATIONSHIP_ITEMS = 'items';

    public const SORT_PARAMETER = 'sort';

    public const EXPAND_PARAMETER = 'expand';

    public const LIMIT_PARAMETER = 'records';

    public const FIELDS_PARAMETER = 'fields';

    /**
     * @return mixed
     */
    public function getParentEntity();

    /**
     * @param ResourceDefinition|string $resourceDefinition
     * @param $entities
     * @param Context $context
     * @param FilterResults|null $filterResults
     * @param RelationshipValue $parent
     * @param null $parentEntity
     * @return ResourceCollection
     */
    public function toResources(
        $resourceDefinition,
        $entities,
        Context $context,
        FilterResults $filterResults = null,
        RelationshipValue $parent = null,
        $parentEntity = null
    ) : ResourceCollection;

    /**
     * @param ResourceDefinition|string $resourceDefinition
     * @param $entity
     * @param Context $context
     * @param RelationshipValue $parent
     * @param null $parentEntity
     * @return RESTResource
     */
    public function toResource(
        $resourceDefinition,
        $entity,
        Context $context,
        RelationshipValue $parent = null,
        $parentEntity = null
    ) : RESTResource;

    /**
     * @param $resourceDefinition
     * @param $body
     * @param Context $context
     * @return RESTResource
     */
    public function fromArray(
        $resourceDefinition,
        array $body,
        Context $context
    ) : RESTResource;

    /**
     * @param RESTResource $resource
     * @param EntityFactory $factory
     * @param Context $context
     * @param mixed|null $entity
     * @return mixed $entity
     */
    public function toEntity(
        RESTResource $resource,
        EntityFactory $factory,
        Context $context,
        $entity = null
    );

    /**
     * @return PropertyResolver
     */
    public function getPropertyResolver() : PropertyResolver;

    /**
     * @return RequestResolver
     */
    public function getRequestResolver(): RequestResolver;

    /**
     * @return PropertySetter
     */
    public function getPropertySetter() : PropertySetter;

    /**
     * @return ResourceFactory
     */
    public function getResourceFactory(): ResourceFactory;

    /**
     * @return QueryAdapter
     */
    public function getQueryAdapter(): QueryAdapter;

    /**
     * @param Field $field
     * @return string
     */
    public function getQualifiedName(Field $field) : string;

    /**
     * @param $entities
     * @param $resourceDefinition
     * @param Context $context
     * @return void
     */
    public function processEagerLoading($entities, $resourceDefinition = null, Context $context = null);

    /**
     * Create resources from whatever is in the inputs defined from the input parsers.
     * @param $resourceDefinition
     * @param Context $context
     * @param null $request
     * @return ResourceCollection
     */
    public function fromInput(
        $resourceDefinition,
        Context $context,
        $request = null
    ) : ResourceCollection;

    /**
     * Create resource identifiers from whatever is in the inputs defined from the input parsers
     * @param $resourceDefinition
     * @param Context $context
     * @param null $request
     * @return IdentifierCollection
     */
    public function identifiersFromInput(
        $resourceDefinition,
        Context $context,
        $request = null
    ) : IdentifierCollection;
}
