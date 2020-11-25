<?php

namespace CatLab\Charon\Validation\Requirements;

use CatLab\Requirements\Exists;

/**
 * Class RelationshipExists
 * @package CatLab\Charon\Validation
 */
class RelationshipExists extends Exists
{
    /**
     * @return string
     */
    function getTemplate() : string
    {
        return 'The %s is a required relationship.';
    }
}
