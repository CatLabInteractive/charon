<?php

namespace CatLab\Charon\OpenApi\V3;

use CatLab\Charon\Exceptions\RouteAlreadyDefined;
use CatLab\Charon\Interfaces\Context;
use CatLab\Charon\Interfaces\ResourceFactory as ResourceFactoryInterface;
use CatLab\Charon\Models\Routing\Route;
use CatLab\Charon\OpenApi\V2\OpenApiV2Builder;

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
            throw RouteAlreadyDefined::makeTranslatable('Route %s %s is already defined.', [ $method, $path ]);
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
