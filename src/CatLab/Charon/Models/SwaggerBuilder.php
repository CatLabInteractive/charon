<?php

namespace CatLab\Charon\Models;

use CatLab\Charon\Interfaces\DescriptionBuilder;
use CatLab\Charon\Interfaces\ResourceDefinition;
use CatLab\Charon\Interfaces\ResourceTransformer;
use CatLab\Charon\Enums\Cardinality;
use CatLab\Charon\Exceptions\RouteAlreadyDefined;
use CatLab\Charon\Library\PrettyEntityNameLibrary;
use CatLab\Charon\Models\Properties\RelationshipField;
use CatLab\Charon\Models\Routing\Route;

/**
 * Class SwaggerBuilder
 * @package CatLab\RESTResource\Models
 */
class SwaggerBuilder implements DescriptionBuilder
{
    /**
     * @var string
     */
    private $host;

    /**
     * @var string
     */
    private $basePath;

    /**
     * @var mixed[]
     */
    private $paths;

    /**
     * @var mixed[]
     */
    private $schemas;

    /**
     * @var PrettyEntityNameLibrary
     */
    private $entityNameLibrary;

    /**
     * @var string
     */
    private $title;

    /**
     * @var string
     */
    private $description;

    /**
     * @var string
     */
    private $termsOfService;

    /**
     * @var string[]
     */
    private $contact;

    /**
     * @var string
     */
    private $license;

    /**
     * @var string
     */
    private $version;

    /**
     * SwaggerBuilder constructor.
     * @param string $host
     * @param string $basePath
     */
    public function __construct(string $host, string $basePath)
    {
        $this->paths = [];
        $this->schemas = [];
        $this->entityNameLibrary = new PrettyEntityNameLibrary();

        $this->host = $host;
        $this->basePath = $basePath;
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

        $method = $route->getMethod();
        if (isset($this->paths[$path][$method])) {
            throw new RouteAlreadyDefined('Route ' . $method . ' ' . $path . ' is already defined.');
        }

        $this->paths[$path][$method] = $route->toSwagger($this);

        return $this;
    }

    /**
     * @param ResourceDefinition $resourceDefinition
     * @param string $action
     * @return $this
     */
    public function addResourceDefinition(ResourceDefinition $resourceDefinition, string $action)
    {
        $name = $this->entityNameLibrary->toPretty($resourceDefinition->getEntityClassName()) . '_' . $action;
        if (!array_key_exists($name, $this->schemas)) {
            $this->schemas[$name] = null; // Set key to avoid circular references
            $this->schemas[$name] = $resourceDefinition->toSwagger($this, $action);
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

    /**
     * @param ResourceDefinition $resourceDefinition
     * @param string $action
     * @param string $cardinality
     * @return $this
     */
    public function getRelationshipSchema(ResourceDefinition $resourceDefinition, string $action, string $cardinality)
    {
        $refId = $this->addResourceDefinition($resourceDefinition, $action);

        if ($cardinality === Cardinality::ONE) {
            return [
                '$ref' => $refId
            ];
        } else {
            return [
                '$ref' => $this->addItemListDefinition(
                    $this->entityNameLibrary->toPretty($resourceDefinition->getEntityClassName()),
                    $refId,
                    $action
                )
            ];
        }
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
     * @param string $name
     * @param string $reference
     * @return mixed
     */
    private function addItemListDefinition(string $name, string $reference, string $action) : string
    {
        $name = $name . '_' . $action . '_items';
        if (!array_key_exists($name, $this->schemas)) {
            $this->schemas[$name] = [
                'type' => 'object',
                'properties' => [
                    ResourceTransformer::RELATIONSHIP_ITEMS => [
                        'type' => 'array',
                        'items' => [
                            '$ref' => $reference
                        ]
                    ]
                ]
            ];
        }
        return '#/definitions/' . $name;
    }

    /**
     *
     */
    public function build()
    {
        $out = [];

        $out['swagger'] = '2.0';
        $out['host'] = $this->host;
        $out['basePath'] = $this->basePath;
        $out['info'] = $this->getInfoObject();
        $out['paths'] = $this->paths;
        $out['definitions'] = $this->schemas;

        return $out;
    }
}