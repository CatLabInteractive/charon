<?php

namespace CatLab\Charon\Laravel;

use CatLab\Charon\Laravel\Resolvers\PropertyResolver;
use CatLab\Charon\Laravel\Resolvers\PropertySetter;
use CatLab\Charon\Laravel\Transformers\ResourceTransformer;
use Illuminate\Support\ServiceProvider;

/**
 * Class RESTResourceProvider
 * @package CatLab\RESTResource\Laravel
 */
class RESTResourceProvider extends ServiceProvider
{
    /**
     * Register the Resource Transformer singleton
     *
     * @return void
     */
    public function register()
    {
        $this->registerResourceTransformer();
    }

    /**
     *
     */
    protected function registerResourceTransformer()
    {
        $parent = $this;

        // Our own custom gatekeeper
        $this->app->singleton(\CatLab\Charon\Interfaces\ResourceTransformer::class, function() use ($parent) {
            return $parent->createResourceTransformer();
        });
    }

    /**
     * @return ResourceTransformer
     */
    protected function createResourceTransformer()
    {
        return new ResourceTransformer(
            new PropertyResolver(),
            new PropertySetter()
        );
    }
}