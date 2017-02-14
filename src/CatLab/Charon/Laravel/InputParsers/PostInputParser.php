<?php

namespace CatLab\Charon\Laravel\InputParsers;

use CatLab\Charon\Collections\IdentifierCollection;
use CatLab\Charon\Collections\ResourceCollection;
use CatLab\Charon\Interfaces\Context;
use CatLab\Charon\Interfaces\InputParser;
use CatLab\Charon\Interfaces\ResourceDefinition;
use CatLab\Charon\Interfaces\ResourceTransformer;

/**
 * Class PostInputParser
 * @package CatLab\Charon\InputParsers
 */
class PostInputParser extends AbstractInputParser implements InputParser
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
        // TODO: Implement getIdentifiers() method.
        return null;
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
        // @TODO this can probably be improved at some point
        $content = $_POST;

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
            case 'multipart/form-data':
            case 'application/x-www-form-urlencoded':
                return true;
        }

        return false;
    }
}