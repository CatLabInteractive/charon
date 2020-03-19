<?php

namespace App\Petstore\Controllers;
use CatLab\Charon\Collections\RouteCollection;
use CatLab\Charon\Enums\Action;
use CatLab\Charon\OpenApi\V2\OpenApiV2Builder;

/**
 * Class DescriptionController
 * @package App\Petstore\Controllers
 */
class DescriptionController extends AbstractResourceController
{
    public function index()
    {
        $builder = new OpenApiV2Builder('', '/');

        $builder
            ->setTitle('Petstore Example API')
            ->setDescription('Very simple Charon test api.')
            ->setContact('CatLab Interactive', 'https://www.catlab.eu/', 'support@catlab.be')
            ->setVersion('1.0');

        foreach ($this->getRouteCollection()->getRoutes() as $route) {
            $builder->addRoute($route);
        }

        $this->outputJson($builder->build($this->getContext(Action::VIEW)));
    }

    /**
     * @return RouteCollection
     */
    public function getRouteCollection() : RouteCollection
    {
        return include __DIR__ . '/../routes.php';
    }
}
