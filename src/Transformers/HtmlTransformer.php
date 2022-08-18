<?php

namespace CatLab\Charon\Transformers;

use CatLab\Charon\CharonConfig;
use CatLab\Charon\Exceptions\NotImplementedException;
use CatLab\Charon\Interfaces\Context;
use CatLab\Charon\Interfaces\Transformer;

/**
 *
 */
class HtmlTransformer implements Transformer
{
    /**
     * @param $value
     * @param Context $context
     * @return mixed|void
     */
    public function toResourceValue($value, Context $context)
    {
        return $value;
    }

    /**
     * @param $value
     * @param Context $context
     * @return mixed|void
     */
    public function toEntityValue($value, Context $context)
    {
        // Is this plain text input?
        if (strip_tags($value) === $value) {
            // Wrap around a paragraph
            $value = '<p>' . nl2br($value) . '</p>';
        }

        $purifier = CharonConfig::instance()->getHtmlPurifier();
        return $purifier->purify($value);
    }

    /**
     * @param $value
     * @return mixed|void
     * @throws \CatLab\Charon\Exceptions\CharonException
     */
    public function toParameterValue($value)
    {
        throw NotImplementedException::makeTranslatable('HTML parameters are not supported');
    }
}
