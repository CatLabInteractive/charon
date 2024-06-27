<?php

declare(strict_types=1);

namespace Tests\Models;

class MockEntityModel
{
    /**
     * @var int
     */
    private $id;

    /**
     * @var MockEntityModel[]
     */
    private array $children = [];

    private static ?int $_nextId = null;

    /**
     * @return int
     */
    private function getNextId(): int|float
    {
        if (!isset(self::$_nextId)) {
            self::$_nextId = 0;
        }

        return ++self::$_nextId;
    }

    public static function clearNextId(): void
    {
        if (isset(self::$_nextId)) {
            self::$_nextId = 0;
        }
    }

    /**
     * MockEntityModel constructor.
     */
    public function __construct()
    {
        $this->id = $this->getNextId();
    }

    /**
     *
     */
    public function addChildren(): void
    {
        $this->children[] = new MockEntityModel($this->id + 1);
        $this->children[] = new MockEntityModel($this->id + 2);
        $this->children[] = new MockEntityModel($this->id + 3);
    }

    /**
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @return string
     */
    public function getName(): string
    {
        return 'Mock entity ' . $this->getId();
    }

    /**
     * @return string
     */
    public function getAlwaysVisibleField(): string
    {
        return 'wololo';
    }

    /**
     * @return string
     */
    public function getViewVisibleField(): string
    {
        return 'everything is awesome';
    }

    /**
     * @return MockEntityModel[]
     */
    public function getViewVisibleRelationship()
    {
        return $this->getChildren();
    }

    /**
     * @return MockEntityModel[]
     */
    public function getAlwaysVisibleRelationship()
    {
        return $this->getChildren();
    }

    /**
     * @return MockEntityModel[]
     */
    public function getChildren()
    {
        return $this->children;
    }

    public function getNthChild($number)
    {
        return $this->children[$number];
    }
}
