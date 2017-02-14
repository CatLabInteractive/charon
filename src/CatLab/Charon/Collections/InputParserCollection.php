<?php

namespace CatLab\Charon\Collections;

use CatLab\Base\Collections\Collection;
use CatLab\Charon\Interfaces\Context;
use CatLab\Charon\Interfaces\InputParser;
use CatLab\Charon\Interfaces\ResourceDefinition;
use CatLab\Charon\Library\InputParserLibrary;
use CatLab\Charon\Interfaces\ResourceTransformer;

/**
 * Class InputParserCollection
 * @package CatLab\Charon\Collections
 */
class InputParserCollection extends Collection implements InputParser
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
        /** @var InputParser $inputParser */
        foreach ($this as $inputParser) {
            $inputParser = InputParserLibrary::make($inputParser);
            $content = $inputParser->getIdentifiers($resourceTransformer, $resourceDefinition, $context);
            if ($content) {
                return $content;
            }
        }

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
        /** @var InputParser $inputParser */
        foreach ($this as $inputParser) {
            $inputParser = InputParserLibrary::make($inputParser);
            $content = $inputParser->getResources($resourceTransformer, $resourceDefinition, $context);
            if ($content) {
                return $content;
            }
        }

        return null;
    }
}