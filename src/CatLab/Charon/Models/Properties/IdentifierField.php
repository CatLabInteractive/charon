<?php

namespace CatLab\Charon\Models\Properties;

use CatLab\Charon\Enums\Action;
use CatLab\Charon\Interfaces\Context;
use CatLab\Charon\Exceptions\NotImplementedException;
use CatLab\Charon\Models\ResourceDefinition;
use CatLab\Charon\Models\ValidationBuilder;
use CatLab\Charon\Interfaces\ResourceDefinition as ResourceDefinitionContract;

/**
 * Class IdentifierField
 * @package app\Models\ResourceDefinition
 */
class IdentifierField extends ResourceField
{
    /**
     * IdentifierField constructor.
     * @param ResourceDefinition $resourceDefinition
     * @param string $fieldName
     */
    public function __construct(ResourceDefinition $resourceDefinition, $fieldName)
    {
        parent::__construct($resourceDefinition, $fieldName);

        $this
            ->sortable(true)
            ->filterable(true);
    }

    /**
     * @param Context $context
     * @param array $currentPath
     * @return bool
     */
    public function shouldInclude(Context $context, array $currentPath)
    {
        return true;
    }

    /**
     * @param string $action
     * @return bool
     */
    public function hasAction($action)
    {
        // Only on create this is not required.
        if ($action === Action::CREATE) {
            return false;
        }

        return true;
    }

    /**
     * @return bool
     */
    public function canSetProperty()
    {
        return false;
    }
}