<?php

namespace CatLab\Charon\Interfaces;

/**
 * Interface DynamicContext
 *
 * If a resource definition implements DynamicContext, the transformContext method will be called to
 * determine which context to use in the parsing.
 *
 * @package CatLab\RESTResource\Interfaces
 */
interface DynamicContext
{
    /**
     * @param Context $context
     * @param $entity
     * @return Context
     */
    public function transformContext(Context $context, $entity);
}
