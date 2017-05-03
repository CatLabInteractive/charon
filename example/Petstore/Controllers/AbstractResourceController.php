<?php

namespace App\Petstore\Controllers;

use CatLab\Charon\Collections\ResourceCollection;
use CatLab\Charon\Interfaces\RESTResource;
use CatLab\Charon\Models\Context;

/**
 * Class AbstractResourceController
 * @package App\Petstore\Controllers
 */
class AbstractResourceController
{
    /**
     * Output a list of resources
     * @param ResourceCollection $resources
     * @param $type
     */
    protected function outputResources(ResourceCollection $resources, $type)
    {
        switch ($type) {
            case 'json':
            default:
                $this->outputJson($resources->toArray());
                return;
        }
    }

    /**
     * Output a list of resources
     * @param RESTResource $resources
     * @param $type
     */
    protected function outputResource(RESTResource $resources, $type)
    {
        switch ($type) {
            case 'json':
            default:
                $this->outputJson($resources->toArray());
                return;
        }
    }

    /**
     * @param $action
     * @return Context
     */
    protected function getContext($action)
    {
        $context = new Context($action);

        if (isset($_GET['fields'])) {
            $context->showFields(array_map('trim', explode(',', $_GET['fields'])));
        }

        if (isset($_GET['expand'])) {
            $context->expandFields(array_map('trim', explode(',', $_GET['expand'])));
        }

        return $context;
    }

    /**
     * @param $data
     */
    protected function outputJson($data)
    {
        header('Content-type: application/json');
        echo json_encode($data);
    }

    /**
     * @param $entity
     * @param $id
     */
    protected function abortNotFound($entity, $id)
    {
        http_send_status(404);
        echo $entity . ' with id ' . $id . ' not found';
        exit;
    }
}