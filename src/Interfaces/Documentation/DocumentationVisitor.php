<?php


namespace CatLab\Charon\Interfaces\Documentation;

use CatLab\Charon\Models\Properties\Base\Field;
use CatLab\Charon\Models\Routing\Route;
use CatLab\Charon\OpenApi\OpenApiException;

/**
 * Interface DocumentationVisitor
 * @package CatLab\Charon\Interfaces\Documentation
 */
interface DocumentationVisitor
{
    /**
     * @param Field $field
     * @throws OpenApiException
     * @return mixed
     */
    public function visitField(Field $field, $action);

    public function visitRoute(Route $route);
}
