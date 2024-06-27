<?php

declare(strict_types=1);

namespace CatLab\Charon\OpenApi\Authentication;

/**
 * Class OAuth2Authentication
 * @package CatLab\Charon\Swagger\Authentication
 */
class OAuth2Authentication extends Authentication
{
    public const FLOW_IMPLICIT = 'implicit';

    public const FLOW_CODE = 'code';

    /**
     * @var string
     */
    private $authorizationUrl;

    /**
     * @var string
     */
    private $flow;

    /**
     * @var string[]
     */
    private array $scopes = [];

    /**
     * OAuth2Authentication constructor.
     * @param string $name
     */
    public function __construct(string $name)
    {
        parent::__construct($name, 'oauth2');
    }

    /**
     * @param string $authorizationUrl
     * @return OAuth2Authentication
     */
    public function setAuthorizationUrl($authorizationUrl): static
    {
        $this->authorizationUrl = $authorizationUrl;
        return $this;
    }

    /**
     * @param string $flow
     * @return OAuth2Authentication
     */
    public function setFlow($flow): static
    {
        $this->flow = $flow;
        return $this;
    }

    /**
     * @return string
     */
    public function getAuthorizationUrl()
    {
        return $this->authorizationUrl;
    }

    /**
     * @return string
     */
    public function getFlow()
    {
        return $this->flow;
    }

    /**
     * @return \string[]
     */
    public function getScopes()
    {
        return $this->scopes;
    }

    /**
     * @param string $name
     * @param string $description
     * @return $this
     */
    public function addScope(string $name, string $description): static
    {
        $this->scopes[$name] = $description;
        return $this;
    }

    /**
     * @return mixed
     */
    public function toArray(): array
    {
        return [
            'type' => $this->getType(),
            'authorizationUrl' => $this->getAuthorizationUrl(),
            'flow' => $this->getFlow(),
            'scopes' => $this->getScopes()
        ];
    }
}
