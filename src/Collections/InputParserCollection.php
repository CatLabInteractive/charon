<?php

declare(strict_types=1);

namespace CatLab\Charon\Collections;

use CatLab\Base\Collections\Collection;
use CatLab\Charon\Enums\Action;
use CatLab\Charon\Exceptions\InputDecodeException;
use CatLab\Charon\Exceptions\NoInputParsersSet;
use CatLab\Charon\Interfaces\Context;
use CatLab\Charon\Interfaces\DescriptionBuilder;
use CatLab\Charon\Interfaces\InputParser;
use CatLab\Charon\Interfaces\ResourceDefinition;
use CatLab\Charon\Interfaces\ResourceDefinitionFactory;
use CatLab\Charon\Library\InputParserLibrary;
use CatLab\Charon\Interfaces\ResourceTransformer;
use CatLab\Charon\Models\Routing\Parameters\ResourceParameter;
use CatLab\Charon\Models\Routing\Route;

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
     * @param null $request
     * @return IdentifierCollection|null
     * @throws NoInputParsersSet
     */
    public function getIdentifiers(
        ResourceTransformer $resourceTransformer,
        ResourceDefinitionFactory $resourceDefinition,
        Context $context,
        $request = null
    ) {
        $this->checkExists();

        $lastException = null;

        /** @var InputParser $inputParser */
        foreach ($this as $inputParser) {
            $inputParser = InputParserLibrary::make($inputParser);

            try {
                $content = $inputParser->getIdentifiers($resourceTransformer, $resourceDefinition, $context, $request);
            } catch (InputDecodeException $e) {
                $lastException = $lastException;
                $content = null;
            }

            if ($content) {
                return $content;
            }
        }

        if ($lastException) {
            throw $lastException;
        }

        return null;
    }

    /**
     * Look for
     * @param ResourceTransformer $resourceTransformer
     * @param ResourceDefinition $resourceDefinition
     * @param Context $context
     * @param null $request
     * @return ResourceCollection|null
     * @throws NoInputParsersSet
     * @throws InputDecodeException
     */
    public function getResources(
        ResourceTransformer $resourceTransformer,
        ResourceDefinitionFactory $resourceDefinition,
        Context $context,
        $request = null
    ) {
        $this->checkExists();

        $lastException = null;

        /** @var InputParser $inputParser */
        foreach ($this as $inputParser) {
            $inputParser = InputParserLibrary::make($inputParser);

            try {
                $content = $inputParser->getResources($resourceTransformer, $resourceDefinition, $context, $request);
            } catch (InputDecodeException $e) {
                $lastException = $e;
                $content = null;
            }

            if ($content) {
                return $content;
            }
        }

        if ($lastException instanceof \CatLab\Charon\Exceptions\InputDecodeException) {
            throw $lastException;
        }

        return null;
    }

    /**
     * @param DescriptionBuilder $builder
     * @param Route $route
     * @param ResourceParameter $parameter
     * @param ResourceDefinition $resourceDefinition
     * @param string $action (from Action::ENUM)
     * @param null $request
     * @return ParameterCollection
     * @throws NoInputParsersSet
     * @throws \CatLab\Charon\Exceptions\InvalidContextAction
     */
    public function getResourceRouteParameters(
        DescriptionBuilder $builder,
        Route $route,
        ResourceParameter $parameter,
        ResourceDefinition $resourceDefinition,
                           $action,
                           $request = null
    ): ParameterCollection
    {
        Action::checkValid($action);

        $this->checkExists();

        $out = new ParameterCollection($route);

        foreach ($this as $inputParser) {
            $inputParser = InputParserLibrary::make($inputParser);

            $parameters = $inputParser->getResourceRouteParameters(
                $builder,
                $route,
                $parameter,
                $resourceDefinition,
                $action,
                $request
            );

            $out->merge($parameters);
        }

        return $out;
    }

    /**
     * @throws NoInputParsersSet
     */
    protected function checkExists()
    {
        if ($this->count() === 0) {
            throw NoInputParsersSet::make();
        }
    }
}
