<?php

namespace CatLab\Charon\Swagger;

use CatLab\Charon\Factories\ResourceFactory;
use CatLab\Charon\Interfaces\Context;
use CatLab\Charon\Interfaces\DescriptionBuilder;
use CatLab\Charon\Interfaces\ResourceDefinition;
use CatLab\Charon\Interfaces\ResourceFactory as ResourceFactoryInterface;
use CatLab\Charon\Enums\Cardinality;
use CatLab\Charon\Exceptions\RouteAlreadyDefined;
use CatLab\Charon\Library\PrettyEntityNameLibrary;
use CatLab\Charon\Models\Routing\Route;
use CatLab\Charon\Swagger\Authentication\Authentication;

/**
 * Class SwaggerBuilder
 * @package CatLab\Charon\Swagger
 */
class SwaggerBuilder implements DescriptionBuilder
{
    /**
     * @var string
     */
    protected $host;

    /**
     * @var string
     */
    protected $basePath;

    /**
     * @var mixed[]
     */
    protected $paths;

    /**
     * @var mixed[]
     */
    protected $schemas;

    /**
     * Keep a list of unique resource definition names.
     * @var mixed[]
     */
    protected $resourceDefinitionNames;

    /**
     * @var PrettyEntityNameLibrary
     */
    protected $entityNameLibrary;

    /**
     * @var string
     */
    protected $title;

    /**
     * @var string
     */
    protected $description;

    /**
     * @var string
     */
    protected $termsOfService;

    /**
     * @var string[]
     */
    protected $contact;

    /**
     * @var string
     */
    protected $license;

    /**
     * @var string
     */
    protected $version;

    /**
     * @var Authentication[]
     */
    protected $authentications;

    /**
     * @var Route
     */
    protected $routes;

    /**
     * @var ResourceFactoryInterface
     */
    protected $resourceFactory;

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
        $this->paths = [];
        $this->schemas = [];
        $this->authentications = [];

        $this->resourceFactory = $resourceFactory ?? new ResourceFactory();
        $this->entityNameLibrary = new PrettyEntityNameLibrary();
        $this->resourceDefinitionNames = [];

        $this->host = $host;
        $this->basePath = $basePath;

        $this->routes = [];
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
     * @param ResourceDefinition $resourceDefinition
     * @param string $action
     * @param string $cardinality
     * @return string
     */
    public function addResourceDefinition(
        ResourceDefinition $resourceDefinition,
        string $action,
        string $cardinality = Cardinality::ONE
    ) {
        $this->checkResourceDefinitionType($resourceDefinition);

        $name = $this->getResourceDefinitionName($resourceDefinition) . '_' . $action;
        if (!array_key_exists($name, $this->schemas)) {
            $this->schemas[$name] = null; // Set key to avoid circular references
            $this->schemas[$name] = $resourceDefinition->toSwagger($this, $action);
        }

        $refId = '#/definitions/' . $name;

        if ($cardinality === Cardinality::ONE) {
            return $this->addItemDefinition($this->getResourceDefinitionName($resourceDefinition), $refId, $action);
        } else {
            return $this->addItemListDefinition(
                $this->getResourceDefinitionName($resourceDefinition),
                $refId,
                $action
            );
        }
    }

    /**
     * @param ResourceDefinition $resourceDefinition
     */
    protected function checkResourceDefinitionType(ResourceDefinition $resourceDefinition)
    {
        // Nothing to do.
    }

    /**
     * Get a unique, pretty name for a resource definition.
     * @param $resourceDefinition
     * @return string
     */
    protected function getResourceDefinitionName(ResourceDefinition $resourceDefinition)
    {
        $resourceDefinitionClassName = get_class($resourceDefinition);
        if (!isset($this->resourceDefinitionNames[$resourceDefinitionClassName])) {

            $prettyName = $this->entityNameLibrary->toPretty($resourceDefinition->getEntityClassName());
            $name = $prettyName;

            // check if this name is already in use
            $counter = 1;
            while (in_array($name, $this->resourceDefinitionNames)) {
                $counter ++;
                $name = $prettyName . $counter;
            }

            $this->resourceDefinitionNames[$resourceDefinitionClassName] = $name;
        }

        return $this->resourceDefinitionNames[$resourceDefinitionClassName];
    }

    /**
     * @param ResourceDefinition $resourceDefinition
     * @param string $action
     * @param string $cardinality
     * @return array[]
     */
    public function getRelationshipSchema(ResourceDefinition $resourceDefinition, string $action, string $cardinality)
    {
        return [
            '$ref' => $this->addResourceDefinition($resourceDefinition, $action, $cardinality)
        ];
    }

    /**
     * @param ResourceDefinition $resourceDefinition
     * @param string $action
     * @param string $cardinality
     * @return array[]
     */
    public function getResponseSchema(ResourceDefinition $resourceDefinition, string $action, string $cardinality)
    {
        return [
            '$ref' => $this->addResourceDefinition($resourceDefinition, $action, $cardinality)
        ];
    }

    /**
     * @param string $title
     * @return $this
     */
    public function setTitle(string $title)
    {
        $this->title = $title;
        return $this;
    }

    /**
     * @param string $description
     * @return $this
     */
    public function setDescription(string $description)
    {
        $this->description = $description;
        return $this;
    }

    /**
     * @param string $terms
     * @return $this
     */
    public function setTermsOfService(string $terms)
    {
        $this->termsOfService = $terms;
        return $this;
    }

    /**
     * @param string $name
     * @param string $url
     * @param string $email
     * @return $this
     */
    public function setContact(string $name, string $url, string $email)
    {
        $this->contact = [
            'name' => $name,
            'url' => $url,
            'email' => $email
        ];

        return $this;
    }

    /**
     * @param string $name
     * @param string $url
     * @return $this
     */
    public function setLicense(string $name, string $url)
    {
        $this->license = [
            'name' => $name,
            'url' => $url
        ];

        return $this;
    }

    /**
     * @param string $version
     * @return $this
     */
    public function setVersion(string $version)
    {
        $this->version = $version;
        return $this;
    }

    /**
     * @param Authentication $authentication
     * @return $this
     */
    public function addAuthentication(Authentication $authentication)
    {
        $this->authentications[] = $authentication;
        return $this;
    }

    /**
     * @param Context $context
     * @return array
     * @throws \CatLab\Charon\Exceptions\InvalidScalarException
     */
    public function build(Context $context)
    {
        $out = [];

        // Build routes
        foreach ($this->routes as $route) {
            $this->buildRoute($route, $context);
        }

        $out['swagger'] = '2.0';
        $out['host'] = $this->host;
        $out['basePath'] = $this->basePath;
        $out['info'] = $this->getInfoObject();
        $out['paths'] = $this->paths;
        $out['definitions'] = $this->schemas;

        if (count($this->authentications) > 0) {
            $out['securityDefinitions'] = [];
            foreach ($this->authentications as $security) {
                $out['securityDefinitions'][$security->getName()] = $security->toArray();
            }
        }

        return $out;
    }

    /**
     * @param Route $route
     * @param Context $context
     * @throws \CatLab\Charon\Exceptions\InvalidScalarException
     */
    protected function buildRoute(Route $route, Context $context)
    {
        list ($path, $staticRouteParameters) = $route->getPathWithStaticRouteParameters();

        $path = str_replace('?', '', $path);
        $method = $route->getHttpMethod();

        $this->paths[$path][$method] = $route->toSwagger($this, $context);
    }

    /**
     * @param string $name
     * @param string $reference
     * @param string $action
     * @return mixed
     */
    protected function addItemDefinition(string $name, string $reference, string $action) : string
    {
        return $reference;
    }

    /**
     * @param string $name
     * @param string $reference
     * @param string $action
     * @return mixed
     */
    protected function addItemListDefinition(string $name, string $reference, string $action) : string
    {
        $name = $name . '_' . $action . '_items';
        if (!array_key_exists($name, $this->schemas)) {
            $resourceCollection = $this->resourceFactory->createResourceCollection();
            $this->schemas[$name] = $resourceCollection->getSwaggerDescription($reference);
        }
        return '#/definitions/' . $name;
    }

    /**
     * @return array
     */
    protected function getInfoObject()
    {
        $out = [];

        if (isset($this->title)) {
            $out['title'] = $this->title;
        }

        if (isset($this->description)) {
            $out['description'] = $this->description;
        }

        if (isset($this->termsOfService)) {
            $out['termsOfService'] = $this->termsOfService;
        }

        if (isset($this->contact)) {
            $out['contact'] = $this->contact;
        }

        if (isset($this->license)) {
            $out['license'] = $this->license;
        }

        if (isset($this->version)) {
            $out['version'] = $this->version;
        }

        return $out;
    }
}
