<?php

declare(strict_types=1);

namespace CatLab\Charon;

use CatLab\Charon\Models\Singleton;
use CatLab\Charon\Transformers\ArrayTransformer;

// Optional library
use HTMLPurifier;
use HTMLPurifier_Config;

/**
 * Class CharonConfig
 * @package CatLab\Charon
 */
class CharonConfig extends Singleton
{
    /**
     * @var string
     */
    public $defaultArrayTransformer = ArrayTransformer::class;

    /**
     * @var HTMLPurifier
     */
    private $htmlPurifier;

    /**
     * @var callable
     */
    private $htmlPurifierFactory;

    private int $defaultRecordCount = 25;

    /**
     * @return string
     */
    public function getDefaultArrayTransformer()
    {
        return $this->defaultArrayTransformer;
    }

    /**
     * @param HTMLPurifier $purifier
     * @return $this
     */
    public function setHtmlPurifierFactory(callable $factory): static
    {
        $this->htmlPurifierFactory = $factory;
        return $this;
    }

    /**
     * @return HTMLPurifier
     */
    public function getHtmlPurifier()
    {
        if ($this->htmlPurifier === null) {

            if ($this->htmlPurifierFactory !== null) {
                $this->htmlPurifier = call_user_func($this->htmlPurifierFactory);
            } else {
                $config = HTMLPurifier_Config::createDefault();
                $this->htmlPurifier = new HTMLPurifier($config);
            }
        }

        return $this->htmlPurifier;
    }

    /**
     * @param int $records
     * @return $this
     */
    public function setDefaultRecordCount(int $records): static
    {
        $this->defaultRecordCount = $records;
        return $this;
    }

    /**
     * @return int
     */
    public function getDefaultRecordCount()
    {
        return $this->defaultRecordCount;
    }
}
