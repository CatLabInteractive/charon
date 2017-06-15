<?php

namespace CatLab\Charon\InputParsers;

use CatLab\Charon\Collections\IdentifierCollection;
use CatLab\Charon\Collections\ParameterCollection;
use CatLab\Charon\Collections\ResourceCollection;
use CatLab\Charon\Interfaces\Context;
use CatLab\Charon\Interfaces\DescriptionBuilder;
use CatLab\Charon\Interfaces\InputParser;
use CatLab\Charon\Interfaces\ResourceDefinition;
use CatLab\Charon\Interfaces\ResourceTransformer;

use CatLab\Charon\Models\Routing\Parameters\ResourceParameter;
use CatLab\Charon\Models\Routing\Route;
use Request;

/**
 * Class JsonBodyInputParser
 *
 * @deprecated Please use framework specific input parsers.
 *
 * @package CatLab\Charon\InputParsers
 */
class JsonBodyInputParser extends AbstractInputParser implements InputParser
{
    /**
     * Look for identifier input
     * @param ResourceTransformer $resourceTransformer
     * @param ResourceDefinition $resourceDefinition
     * @param Context $context
     * @return IdentifierCollection|null
     */
    public function getIdentifiers(
        ResourceTransformer $resourceTransformer,
        ResourceDefinition $resourceDefinition,
        Context $context
    ) {
        if (!$this->hasApplicableContentType()) {
            return null;
        }

        $content = $this->getRawContent();
        $content = json_decode($content, true);

        if (!$content) {
            throw new \InvalidArgumentException("Could not decode body.");
        }

        $identifierCollection = new IdentifierCollection();

        if (isset($content[ResourceTransformer::RELATIONSHIP_ITEMS])) {
            // This is a list of items
            foreach ($content[ResourceTransformer::RELATIONSHIP_ITEMS] as $item) {
                $identifier = $this->arrayToIdentifier($resourceDefinition, $item);
                if ($identifier) {
                    $identifierCollection->add($identifier);
                }
            }
        } else {
            $identifier = $this->arrayToIdentifier($resourceDefinition, $content);
            if ($identifier) {
                $identifierCollection->add($identifier);
            }
        }

        return $identifierCollection;
    }

    /**
     * Look for
     * @param ResourceTransformer $resourceTransformer
     * @param ResourceDefinition $resourceDefinition
     * @param Context $context
     * @return ResourceCollection|null
     */
    public function getResources(
        ResourceTransformer $resourceTransformer,
        ResourceDefinition $resourceDefinition,
        Context $context
    ) {
        if (!$this->hasApplicableContentType()) {
            return null;
        }

        $rawContent = $this->getRawContent();
        $content = json_decode($rawContent, true);

        if (!$content) {
            throw new \InvalidArgumentException("Could not decode body: " . $rawContent);
        }

        $resource = $resourceTransformer->fromArray(
            $resourceDefinition,
            $content,
            $context
        );

        $resourceCollection = new ResourceCollection();
        $resourceCollection->add($resource);

        return $resourceCollection;
    }

    /**
     * @return bool
     */
    protected function hasApplicableContentType()
    {
        switch ($this->getContentType()) {
            case 'application/json':
            case 'text/json':
                return true;
        }

        return false;
    }


    /**
     * @param DescriptionBuilder $builder
     * @param Route $route
     * @param ResourceParameter $parameter
     * @param ResourceDefinition $resourceDefinition
     * @return ParameterCollection
     */
    public function getResourceRouteParameters(
        DescriptionBuilder $builder,
        Route $route,
        ResourceParameter $parameter,
        ResourceDefinition $resourceDefinition
    ): ParameterCollection
    {
        $route->consumes('application/json');

        $parameterCollection = new ParameterCollection($route);
        $parameterCollection
            ->body($resourceDefinition)
            ->merge($parameter);

        return $parameterCollection;
    }
}