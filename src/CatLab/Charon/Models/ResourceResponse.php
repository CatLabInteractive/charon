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
     * ResourceResponse constructor.
     * @param SerializableResource $resource
     * @param int $status
     * @param array $headers
     */
    public function __construct(SerializableResource $resource, $status = 200, $headers = [])
    {
        parent::__construct('', $status, $headers);
        $this->resource = $resource;
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
}