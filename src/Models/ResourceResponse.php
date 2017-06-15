<?php

namespace CatLab\Charon\Models;

use CatLab\Charon\Interfaces\SerializableResource;
use Symfony\Component\HttpFoundation\Response;

/**
 * Class ResourceResponse
 *
 * Helper method to allow passing resource responses through the laravel / symfony stack.
 *
 * @package CatLab\Charon\Laravel\Models
 */
class ResourceResponse extends Response
{
    /**
     * @var SerializableResource
     */
    private $resource;

    /**
     * @var string
     */
    private $jsonContent;

    /**
     * @var \CatLab\Charon\Interfaces\Context
     */
    private $context;

    /**
     * ResourceResponse constructor.
     * @param SerializableResource $resource
     * @param Context $context
     * @param int $status
     * @param array $headers
     */
    public function __construct(
        SerializableResource $resource,
        Context $context = null,
        $status = 200,
        $headers = []
    ) {
        parent::__construct('', $status, $headers);
        $this->resource = $resource;

        if (isset($context)) {
            $this->setContext($context);
        }
    }

    /**
     * @return SerializableResource
     */
    public function getResource()
    {
        return $this->resource;
    }

    /**
     * Sends content for the current web response.
     *
     * @return Response
     */
    public function sendContent()
    {
        header('Content-type: application/json');

        echo $this->getContent();
        return $this;
    }

    /**
     * Sends content for the current web response.
     *
     * @return Response
     */
    public function getContent()
    {
        if (!isset($this->jsonContent)) {
            $this->jsonContent = json_encode($this->resource->toArray());;
        }
        return $this->jsonContent;
    }

    /**
     * @return \CatLab\Charon\Interfaces\Context
     */
    public function getContext(): \CatLab\Charon\Interfaces\Context
    {
        return $this->context;
    }

    /**
     * @param \CatLab\Charon\Interfaces\Context $context
     * @return ResourceResponse
     */
    public function setContext(\CatLab\Charon\Interfaces\Context $context): ResourceResponse
    {
        $this->context = $context;
        return $this;
    }
}