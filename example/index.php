<?php

/**
 * This is a very much simplified example of a Charon driven api.
 * We only want to show the translation from entity to resources.
 * Charon works perfectly with Laravel of Symfony, but for this
 * example we just use a static model factory.
 *
 * For an example of Charon used in combination with laravel, check:
 * https://github.com/CatLabInteractive/laravel-petstore
 */

require '../vendor/autoload.php';

/** @var \CatLab\Charon\Collections\RouteCollection $routes */
$routes = include 'Petstore/routes.php';

$method = $_SERVER['REQUEST_METHOD'];
$path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);

// Find controller from
$route = $routes->findFromPath($path, $method);
if ($route) {
    if (class_exists($route->getControllerClass())) {
        $controllerClass = $route->getControllerClass();
        $controllerMethod = $route->getControllerAction();
        $parameters = $route->getParameters();
    } else {
        echo 'Controller not found: ' . $route->getAction();
        exit;
    }
} else {
    $controllerClass = \App\Petstore\Controllers\DescriptionController::class;
    $controllerMethod = 'index';
    $parameters = [];
}

// Dispatch method
$controller = new $controllerClass;
call_user_func_array([ $controller, $controllerMethod ], $parameters);