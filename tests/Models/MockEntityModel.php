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

    /**
     * MockEntityModel constructor.
     * @param $id
     */
    public function __construct($id)
    {
        $this->id = $id;
        $this->children = [];
    }

    /**
     *
     */
    public function addChildren()
    {
        $this->children[] = new MockEntityModel(2);
        $this->children[] = new MockEntityModel(3);
        $this->children[] = new MockEntityModel(4);
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