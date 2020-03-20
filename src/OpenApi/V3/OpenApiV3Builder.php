<?php

namespace CatLab\Charon\OpenApi\V3;

use CatLab\Base\Helpers\ArrayHelper;
use CatLab\Charon\Collections\HeaderCollection;
use CatLab\Charon\Enums\Action;
use CatLab\Charon\Enums\Cardinality;
use CatLab\Charon\Exceptions\RouteAlreadyDefined;
use CatLab\Charon\Factories\ResourceFactory;
use CatLab\Charon\Interfaces\Context;
use CatLab\Charon\Interfaces\DescriptionBuilder;
use CatLab\Charon\Interfaces\ResourceDefinition;
use CatLab\Charon\Interfaces\ResourceFactory as ResourceFactoryInterface;
use CatLab\Charon\Interfaces\ResourceTransformer;
use CatLab\Charon\Library\PrettyEntityNameLibrary;
use CatLab\Charon\Library\ResourceDefinitionLibrary;
use CatLab\Charon\Models\Properties\Base\Field;
use CatLab\Charon\Models\Properties\RelationshipField;
use CatLab\Charon\Models\Properties\ResourceField;
use CatLab\Charon\Models\Routing\ReturnValue;
use CatLab\Charon\Models\Routing\Route;
use CatLab\Charon\OpenApi\Authentication\Authentication;
use CatLab\Charon\OpenApi\OpenApiException;
use CatLab\Charon\OpenApi\V2\OpenApiV2Builder;
use CatLab\Requirements\Enums\PropertyType;

/**
 * Class OpenApiV3Builder
 * @package CatLab\Charon\Swagger
 */
class OpenApiV3Builder extends OpenApiV2Builder
{
    /**
     * SwaggerBuilder constructor.
     * @param string $host
     * @param string $basePath
     * @param ResourceFactoryInterface|null $resourceFactory
     */
    public function __construct(
        string $host,
        string $basePath,
        ResourceFactoryInterface $resourceFactory = null
    ) {
        parent::__construct($host, $basePath, $resourceFactory);
    }

    /**
     * @param Route $route
     * @throws RouteAlreadyDefined
     * @return $this
     */
    public function addRoute(Route $route)
    {
        $path = str_replace('?', '', $route->getPath());

        if (!isset($this->paths[$path])) {
            $this->paths[$path] = [];
        }

        $method = $route->getHttpMethod();
        if (isset($this->paths[$path][$method])) {
            throw new RouteAlreadyDefined('Route ' . $method . ' ' . $path . ' is already defined.');
        }

        $this->paths[$path][$method] = true;

        $this->routes[] = $route;
        return $this;
    }

    /**
     * @param Context $context
     * @return array
     * @throws \CatLab\Charon\Exceptions\InvalidScalarException
     * @throws \CatLab\Charon\Exceptions\InvalidResourceDefinition
     */
    public function build(Context $context)
    {
        $out = [];

        // Build routes
        foreach ($this->routes as $route) {
            $this->buildRoute($route, $context);
        }

        $out['openapi'] = '3.0';

        $out['servers'] = [
            [
                'url' => $this->host
            ]
        ];

        //$out['basePath'] = $this->basePath;
        $out['info'] = $this->getInfoObject();
        $out['components'] = [];

        if (count($this->authentications) > 0) {
            $out['components']['securitySchemes'] = [];
            foreach ($this->authentications as $security) {
                $out['components']['securitySchemes'][$security->getName()] = $security->toArray();
            }
        }

        $out['components']['schemas'] = $this->schemas;
        //$out['paths'] = $this->paths;

        return $out;
    }

    /**
     * @param $name
     * @return string
     */
    protected function getResourceDefinitionReference($name)
    {
        return '#/components/schemas/' . $name;
    }
}
