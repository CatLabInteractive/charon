<?php

namespace CatLab\Charon\InputParsers;

use CatLab\Charon\Collections\IdentifierCollection;
use CatLab\Charon\Collections\ParameterCollection;
use CatLab\Charon\Collections\ResourceCollection;
use CatLab\Charon\Enums\Cardinality;
use CatLab\Charon\Enums\Method;
use CatLab\Charon\Interfaces\Context;
use CatLab\Charon\Interfaces\DescriptionBuilder;
use CatLab\Charon\Interfaces\InputParser;
use CatLab\Charon\Interfaces\ResourceDefinition;
use CatLab\Charon\Interfaces\ResourceDefinitionFactory;
use CatLab\Charon\Interfaces\ResourceTransformer;
use CatLab\Charon\Models\Properties\Base\Field;
use CatLab\Charon\Models\Properties\RelationshipField;
use CatLab\Charon\Models\Routing\Parameters\ResourceParameter;
use CatLab\Charon\Models\Routing\Route;

/**
 * Class PostInputParser
 *
 * @deprecated Please use framework specific input parsers.
 *
 * @package CatLab\Charon\InputParsers
 */
class PostInputParser extends AbstractInputParser implements InputParser
{
    /**
     * Look for identifier input
     * @param ResourceTransformer $resourceTransformer
     * @param ResourceDefinition $resourceDefinition
     * @param Context $context
     * @param null $request
     * @return IdentifierCollection|null
     */
    public function getIdentifiers(
        ResourceTransformer $resourceTransformer,
        ResourceDefinitionFactory $resourceDefinition,
        Context $context,
        $request = null
    ) {
        if (!$this->hasApplicableContentType()) {
            return;
        }

        $identifierCollection = new IdentifierCollection();

        $post = $this->getPostFromRequest($request);

        $identifier = $this->arrayToIdentifier($resourceDefinition, $post);
        if ($identifier) {
            $identifierCollection->add($identifier);
        }

        return $identifierCollection;
    }

    /**
     * Look for
     * @param ResourceTransformer $resourceTransformer
     * @param ResourceDefinition $resourceDefinition
     * @param Context $context
     * @param null $request
     * @return ResourceCollection|null
     */
    public function getResources(
        ResourceTransformer $resourceTransformer,
        ResourceDefinitionFactory $resourceDefinition,
        Context $context,
        $request = null
    ) {
        if (!$this->hasApplicableContentType()) {
            return;
        }

        // @TODO this can probably be improved at some point
        $content = $this->getPostFromRequest($request);

        $resource = $resourceTransformer->fromArray(
            $resourceDefinition,
            $content,
            $context
        );

        $resourceCollection = $resourceTransformer->getResourceFactory()->createResourceCollection();
        $resourceCollection->add($resource);

        return $resourceCollection;
    }

    /**
     * @return bool
     */
    protected function hasApplicableContentType()
    {
        switch ($this->getContentType()) {
            case 'multipart/form-data':
            case 'application/x-www-form-urlencoded':
                return true;
        }

        return false;
    }

    /**
     * @param DescriptionBuilder $builder
     * @param Route $route
     * @param ResourceParameter $parameter
     * @param ResourceDefinition $resourceDefinition
     * @param null $request
     * @return ParameterCollection
     * @throws \CatLab\Charon\Exceptions\InvalidScalarException
     */
    public function getResourceRouteParameters(
        DescriptionBuilder $builder,
        Route $route,
        ResourceParameter $parameter,
        ResourceDefinition $resourceDefinition,
        $request = null,
        $action = null
    ): ParameterCollection
    {
        $route->consumes('multipart/form-data');
        $route->consumes('application/x-www-form-urlencoded');

        $parameterCollection = new ParameterCollection($route);
        $this->postParametersFromResourceDefinition(
            $route,
            $parameterCollection,
            $resourceDefinition
        );

        return $parameterCollection;
    }

    /**
     * @param Route $route
     * @param ParameterCollection $parameterCollection
     * @param ResourceDefinition $resourceDefinition
     * @param Context|null $context
     * @throws \CatLab\Charon\Exceptions\InvalidScalarException
     */
    protected function postParametersFromResourceDefinition(
        Route $route,
        ParameterCollection $parameterCollection,
        ResourceDefinition $resourceDefinition,
        Context $context = null
    ) {
        if (!$context) {
            $context = new \CatLab\Charon\Models\Context(
                Method::toAction($route->getMethod(), Cardinality::ONE)
            );
        }

        foreach ($resourceDefinition->getFields() as $field) {

            if ($field instanceof RelationshipField) {
                continue;
            }

            /** @var Field $field */
            if ($field->hasAction($context->getAction())) {
                $this->postParameterFromField($parameterCollection, $field, $context);
            }
        }
    }

    /**
     * @param ParameterCollection $parameterCollection
     * @param Field $field
     * @param Context $context
     * @return mixed
     * @throws \CatLab\Charon\Exceptions\InvalidScalarException
     */
    protected function postParameterFromField(
        ParameterCollection $parameterCollection,
        Field $field,
        Context $context
    ) {
        $post = $parameterCollection->post($field->getDisplayName());
        $post->setType($field->getType());

        foreach ($field->getRequirements() as $v) {
            $post->setFromRequirement($v);
        }

        return $post;
    }

    /**
     * @param null $request
     * @return mixed
     */
    protected function getPostFromRequest($request = null)
    {
        return $_POST;
    }
}
