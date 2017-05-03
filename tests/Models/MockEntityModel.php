<?php

class MockEntityModel
{
    /**
     * @var int
     */
    private $id;

    /**
     * @var MockEntityModel[]
     */
    private $children;

    private static $_nextId;

    /**
     * @return int
     */
    private static function getNextId()
    {
        if (!isset(self::$_nextId)) {
            self::$_nextId = 0;
        }

        return ++self::$_nextId;
    }

    public static function clearNextId()
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
        $this->id = self::getNextId();
        $this->children = [];
    }

    /**
     *
     */
    public function addChildren()
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
    public function getName()
    {
        return 'Mock entity ' . $this->getId();
    }

    /**
     * @return string
     */
    public function getAlwaysVisibleField()
    {
        return 'wololo';
    }

    /**
     * @return string
     */
    public function getViewVisibleField()
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