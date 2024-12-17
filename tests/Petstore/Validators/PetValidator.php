<?php

declare(strict_types=1);

namespace Tests\Petstore\Validators;

use CatLab\Charon\Models\RESTResource;
use CatLab\Requirements\Exceptions\RequirementValidationException;
use CatLab\Requirements\Exceptions\ValidatorValidationException;
use CatLab\Requirements\Interfaces\Validator;
use CatLab\Requirements\Models\Message;

class PetValidator implements Validator
{

    /**
     * @param $value
     * @return mixed
     * @throws RequirementValidationException
     */
    public function validate($value): void
    {
        /** @var RESTResource $value */
        // A pet must have at least one picture.

        $photos = $value->getProperties()->getFromName('photos')->getValue();

        if ($photos === null || count($photos) < 2) {
            throw ValidatorValidationException::make($this, $value);
        }
    }

    /**
     * @param ValidatorValidationException $exception
     * @return Message
     */
    public function getErrorMessage(ValidatorValidationException $exception) : Message
    {
        return new Message('Pets must have at least photos.');
    }
}
