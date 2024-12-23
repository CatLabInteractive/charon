<?php

declare(strict_types=1);

namespace CatLab\Charon\Models\Routing;

use CatLab\Base\Interfaces\Database\OrderParameter;
use CatLab\Charon\Collections\HeaderCollection;
use CatLab\Charon\Collections\ParameterCollection;
use CatLab\Charon\Enums\Cardinality;
use CatLab\Charon\Interfaces\RouteMutator;
use CatLab\Charon\Enums\Action;
use CatLab\Charon\Models\StaticResourceDefinitionFactory;
use CatLab\Requirements\Enums\PropertyType;

/**
 * Class ReturnValue
 * @package CatLab\RESTResource\Models\Routing
 */
class ReturnValue implements RouteMutator
{
    /**
     * @var Route
     */
    private \CatLab\Charon\Interfaces\RouteMutator $parent;

    /**
     * @var string
     */
    private $type;

    private ?array $types = null;

    private string $cardinality = Cardinality::ONE;

    /**
     * @var string
     */
    private $context;

    private int $statusCode;

    private ?string $description = null;

    private \CatLab\Charon\Collections\HeaderCollection $headers;

    /**
     * ReturnValue constructor.
     * @param RouteMutator $route
     * @param string $type
     * @param string $context
     * @throws \CatLab\Charon\Exceptions\InvalidContextAction
     */
    public function __construct(RouteMutator $route, $type = null, $context = null)
    {
        $this->parent = $route;

        if (isset($type)) {
            $this->type = $type;
        }

        $this->statusCode = 200;
        $this->headers = new HeaderCollection();
    }

    /**
     * @param string $type
     * @param null $context
     * @return ReturnValue
     * @throws \CatLab\Charon\Exceptions\InvalidContextAction
     */
    public function one($type = null, $context = null) : ReturnValue
    {
        $this->cardinality = Cardinality::ONE;
        if (isset($type)) {
            $this->type = $type;
        }

        if (isset($context)) {
            Action::checkValid($context);
            $this->context = $context;
        }

        return $this;
    }

    /**
     * @param string $type
     * @param null $context
     * @return ReturnValue
     * @throws \CatLab\Charon\Exceptions\InvalidContextAction
     */
    public function many($type = null, $context = null) : ReturnValue
    {
        $this->cardinality = Cardinality::MANY;
        if (isset($type)) {
            $this->type = $type;
        }

        if (isset($context)) {
            Action::checkValid($context);
            $this->context = $context;
        }

        return $this;
    }

    /**
     * @param array $types
     * @return $this
     * @throws \CatLab\Charon\Exceptions\InvalidContextAction
     */
    public function oneOf(array $types): static
    {
        $this->one();
        $this->types = $types;
        return $this;
    }

    /**
     * @param array $types
     * @return $this
     * @throws \CatLab\Charon\Exceptions\InvalidContextAction
     */
    public function anyOf(array $types): static
    {
        $this->many();
        $this->types = $types;
        return $this;
    }

    /**
     * @return string
     */
    public function getCardinality() : string
    {
        return $this->cardinality;
    }

    /**
     * @return null|string
     */
    public function getType()
    {
        return $this->type ?? PropertyType::STRING;
    }

    /**
     * @return array
     */
    public function getTypes()
    {
        if ($this->types !== null) {
            return $this->type;
        }

        return [ $this->getType() ];
    }

    /**
     * @return string
     */
    public function getContext() : string
    {
        if ($this->cardinality === null) {
            $this->cardinality = Cardinality::ONE;
        }

        return $this->context ?? Action::getReadAction($this->cardinality);
    }

    /**
     * @param string $type
     * @param string $action
     * @return ReturnValue
     * @throws \CatLab\Charon\Exceptions\InvalidContextAction
     */
    public function returns($type = null, string $action = null) : ReturnValue
    {
        return $this->parent->returns($type, $action);
    }

    /**
     * @param string $tag
     * @return RouteMutator
     */
    public function tag(string $tag) : RouteMutator
    {
        return $this->parent->tag($tag);
    }

    /**
     * @return ParameterCollection
     */
    public function parameters() : ParameterCollection
    {
        return $this->parent->parameters();
    }

    /**
     * @param string $summary
     * @return RouteMutator
     */
    public function summary($summary) : RouteMutator
    {
        return $this->parent->summary($summary);
    }

    /**
     * @param $status
     * @return $this
     */
    public function statusCode(int $status): static
    {
        $this->statusCode = $status;
        return $this;
    }

    /**
     * @return int
     */
    public function getStatusCode(): int
    {
        return $this->statusCode;
    }

    /**
     * @param string $description
     * @return $this
     */
    public function describe(string $description): static
    {
        $this->description = $description;
        return $this;
    }

    /**
     * @return HeaderCollection
     */
    public function headers(): \CatLab\Charon\Collections\HeaderCollection
    {
        return $this->headers;
    }

    /**
     * @return \CatLab\Charon\Interfaces\ResourceDefinition|null
     * @throws \CatLab\Charon\Exceptions\InvalidResourceDefinition
     */
    public function getResourceDefinition(): ?\CatLab\Charon\Interfaces\ResourceDefinition
    {
        $definition = $this->getResourceDefinitions();
        if ($definition !== []) {
            return $definition[0];
        }

        return null;
    }

    /**
     * @return \CatLab\Charon\Interfaces\ResourceDefinition[]
     * @throws \CatLab\Charon\Exceptions\InvalidResourceDefinition
     */
    public function getResourceDefinitions(): array
    {
        $types = $this->getType();
        if (!is_array($types)) {
            $types = [ $types ];
        }

        $out = [];
        foreach ($types as $v) {
            if (!PropertyType::isNative($v)) {
                $factory = StaticResourceDefinitionFactory::getFactoryOrDefaultFactory($v);
                $r = $factory->getDefault();
                if ($r) {
                    $out[] = $r;
                }
            }
        }

        return $out;
    }

    /**
     * @return string
     * @throws \CatLab\Charon\Exceptions\InvalidResourceDefinition
     */
    public function getDescriptionFromType(): string
    {
        if (!$this->getType()) {
            return 'No description set.';
        }

        if (PropertyType::isNative($this->getType())) {
            return 'Returns ' . $this->getType();
        }

        $types = $this->getTypes();
        $classNames = array_map(function($type) {

            $factory = StaticResourceDefinitionFactory::getFactoryOrDefaultFactory($type);
            $type = $factory->getDefault();

            return $type->getEntityClassName();
        }, $types);
        return 'Returns ' . $this->getCardinality() . ' ' . implode(', ', $classNames);
    }

    /**
     * @param string $mimetype
     * @return RouteMutator
     */
    public function consumes(string $mimetype) : RouteMutator
    {
        return $this->parent->consumes(...func_get_args());
    }

    /**
     * @param string $order
     * @param string $direction
     * @return RouteMutator
     */
    public function defaultOrder(string $order, $direction = OrderParameter::ASC) : RouteMutator
    {
        return $this->parent->defaultOrder($order, $direction);
    }

    /**
     * @return string
     */
    public function getDescription(): ?string
    {
        return $this->description;
    }

    /**
     * @param int $maxDepth
     * @return RouteMutator
     */
    public function maxExpandDepth(int $maxDepth) : RouteMutator
    {
        return $this->parent->maxExpandDepth($maxDepth);
    }
}
