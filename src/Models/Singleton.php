<?php

namespace CatLab\Charon\Models;

/**
 * Class Singleton
 */
abstract class Singleton
{
    protected function __construct()
    {
    }

    /**
     * @return mixed
     */
    final public static function instance()
    {
        static $instances = array();

        $calledClass = get_called_class();

        if (!isset($instances[$calledClass]))
        {
            $instances[$calledClass] = new $calledClass();
        }

        return $instances[$calledClass];
    }

    final private function __clone()
    {
    }
}