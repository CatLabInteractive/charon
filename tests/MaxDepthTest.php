<?php

namespace CatLab\RESTResource\Tests;

use CatLab\Charon\Transformers\ResourceTransformer;
use MockEntityModel;
use CatLab\Charon\Enums\Action;
use CatLab\Charon\Models\Context;

use PHPUnit_Framework_TestCase;

/**
 * Class MaxDepthTest
 *
 * Tests the max depth parameter for recursive relationship fields.
 *
 * @package CatLab\RESTResource\Tests
 */
class MaxDepthTest extends PHPUnit_Framework_TestCase
{
    private function getDeepChildren()
    {
        MockEntityModel::clearNextId();
        $mockEntity = new MockEntityModel();

        $mockEntity->addChildren();

        // Add children for all the children
        foreach ($mockEntity->getChildren() as $child) {
            $child->addChildren();
            foreach ($child->getChildren() as $grandchild) {
                $grandchild->addChildren();
            }
        }

        return $mockEntity;
    }

    /**
     *
     */
    public function testOutputMaxDepthOne()
    {
        $transformer = new ResourceTransformer();

        $mockEntity = $this->getDeepChildren();
        $resource = $transformer->toResource(MockResourceDefinitionDepthOne::class, $mockEntity, new Context(Action::VIEW));

        $expected = json_decode('
            {
               "name":1,
               "children":{
                  "items":[
                     {
                        "name":2
                     },
                     {
                        "name":3
                     },
                     {
                        "name":4
                     }
                  ]
               }
            }
        ', true);

        $this->assertEquals($expected, $resource->toArray());
    }

    /**
     *
     */
    public function testOutputMaxDepthTwo()
    {
        $transformer = new ResourceTransformer();

        $mockEntity = $this->getDeepChildren();
        $resource = $transformer->toResource(MockResourceDefinitionDepthTwo::class, $mockEntity, new Context(Action::VIEW));

        $expected = json_decode('
            {
               "name":1,
               "children":{
                  "items":[
                     {
                        "name":2,
                        "children":{
                           "items":[
                              {
                                 "name":5
                              },
                              {
                                 "name":6
                              },
                              {
                                 "name":7
                              }
                           ]
                        }
                     },
                     {
                        "name":3,
                        "children":{
                           "items":[
                              {
                                 "name":17
                              },
                              {
                                 "name":18
                              },
                              {
                                 "name":19
                              }
                           ]
                        }
                     },
                     {
                        "name":4,
                        "children":{
                           "items":[
                              {
                                 "name":29
                              },
                              {
                                 "name":30
                              },
                              {
                                 "name":31
                              }
                           ]
                        }
                     }
                  ]
               }
            }
        ', true);

        $this->assertEquals($expected, $resource->toArray());
    }

    /**
     *
     */
    public function testOutputMaxDepthThree()
    {
        $transformer = new ResourceTransformer();

        $mockEntity = $this->getDeepChildren();
        $resource = $transformer->toResource(MockResourceDefinitionDepthThree::class, $mockEntity, new Context(Action::VIEW));

        $expected = json_decode('
            {
               "name":1,
               "children":{
                  "items":[
                     {
                        "name":2,
                        "children":{
                           "items":[
                              {
                                 "name":5,
                                 "children":{
                                    "items":[
                                       {
                                          "name":8
                                       },
                                       {
                                          "name":9
                                       },
                                       {
                                          "name":10
                                       }
                                    ]
                                 }
                              },
                              {
                                 "name":6,
                                 "children":{
                                    "items":[
                                       {
                                          "name":11
                                       },
                                       {
                                          "name":12
                                       },
                                       {
                                          "name":13
                                       }
                                    ]
                                 }
                              },
                              {
                                 "name":7,
                                 "children":{
                                    "items":[
                                       {
                                          "name":14
                                       },
                                       {
                                          "name":15
                                       },
                                       {
                                          "name":16
                                       }
                                    ]
                                 }
                              }
                           ]
                        }
                     },
                     {
                        "name":3,
                        "children":{
                           "items":[
                              {
                                 "name":17,
                                 "children":{
                                    "items":[
                                       {
                                          "name":20
                                       },
                                       {
                                          "name":21
                                       },
                                       {
                                          "name":22
                                       }
                                    ]
                                 }
                              },
                              {
                                 "name":18,
                                 "children":{
                                    "items":[
                                       {
                                          "name":23
                                       },
                                       {
                                          "name":24
                                       },
                                       {
                                          "name":25
                                       }
                                    ]
                                 }
                              },
                              {
                                 "name":19,
                                 "children":{
                                    "items":[
                                       {
                                          "name":26
                                       },
                                       {
                                          "name":27
                                       },
                                       {
                                          "name":28
                                       }
                                    ]
                                 }
                              }
                           ]
                        }
                     },
                     {
                        "name":4,
                        "children":{
                           "items":[
                              {
                                 "name":29,
                                 "children":{
                                    "items":[
                                       {
                                          "name":32
                                       },
                                       {
                                          "name":33
                                       },
                                       {
                                          "name":34
                                       }
                                    ]
                                 }
                              },
                              {
                                 "name":30,
                                 "children":{
                                    "items":[
                                       {
                                          "name":35
                                       },
                                       {
                                          "name":36
                                       },
                                       {
                                          "name":37
                                       }
                                    ]
                                 }
                              },
                              {
                                 "name":31,
                                 "children":{
                                    "items":[
                                       {
                                          "name":38
                                       },
                                       {
                                          "name":39
                                       },
                                       {
                                          "name":40
                                       }
                                    ]
                                 }
                              }
                           ]
                        }
                     }
                  ]
               }
            }
        ', true);

        $this->assertEquals($expected, $resource->toArray());
    }

    /**
     *
     */
    public function testOutputMaxDepthFour()
    {
        $transformer = new ResourceTransformer();

        $mockEntity = $this->getDeepChildren();
        $resource = $transformer->toResource(MockResourceDefinitionDepthFour::class, $mockEntity, new Context(Action::VIEW));

        $expected = json_decode('
            {
               "name":1,
               "children":{
                  "items":[
                     {
                        "name":2,
                        "children":{
                           "items":[
                              {
                                 "name":5,
                                 "children":{
                                    "items":[
                                       {
                                          "name":8,
                                          "children":{
                                             "items":[
            
                                             ]
                                          }
                                       },
                                       {
                                          "name":9,
                                          "children":{
                                             "items":[
            
                                             ]
                                          }
                                       },
                                       {
                                          "name":10,
                                          "children":{
                                             "items":[
            
                                             ]
                                          }
                                       }
                                    ]
                                 }
                              },
                              {
                                 "name":6,
                                 "children":{
                                    "items":[
                                       {
                                          "name":11,
                                          "children":{
                                             "items":[
            
                                             ]
                                          }
                                       },
                                       {
                                          "name":12,
                                          "children":{
                                             "items":[
            
                                             ]
                                          }
                                       },
                                       {
                                          "name":13,
                                          "children":{
                                             "items":[
            
                                             ]
                                          }
                                       }
                                    ]
                                 }
                              },
                              {
                                 "name":7,
                                 "children":{
                                    "items":[
                                       {
                                          "name":14,
                                          "children":{
                                             "items":[
            
                                             ]
                                          }
                                       },
                                       {
                                          "name":15,
                                          "children":{
                                             "items":[
            
                                             ]
                                          }
                                       },
                                       {
                                          "name":16,
                                          "children":{
                                             "items":[
            
                                             ]
                                          }
                                       }
                                    ]
                                 }
                              }
                           ]
                        }
                     },
                     {
                        "name":3,
                        "children":{
                           "items":[
                              {
                                 "name":17,
                                 "children":{
                                    "items":[
                                       {
                                          "name":20,
                                          "children":{
                                             "items":[
            
                                             ]
                                          }
                                       },
                                       {
                                          "name":21,
                                          "children":{
                                             "items":[
            
                                             ]
                                          }
                                       },
                                       {
                                          "name":22,
                                          "children":{
                                             "items":[
            
                                             ]
                                          }
                                       }
                                    ]
                                 }
                              },
                              {
                                 "name":18,
                                 "children":{
                                    "items":[
                                       {
                                          "name":23,
                                          "children":{
                                             "items":[
            
                                             ]
                                          }
                                       },
                                       {
                                          "name":24,
                                          "children":{
                                             "items":[
            
                                             ]
                                          }
                                       },
                                       {
                                          "name":25,
                                          "children":{
                                             "items":[
            
                                             ]
                                          }
                                       }
                                    ]
                                 }
                              },
                              {
                                 "name":19,
                                 "children":{
                                    "items":[
                                       {
                                          "name":26,
                                          "children":{
                                             "items":[
            
                                             ]
                                          }
                                       },
                                       {
                                          "name":27,
                                          "children":{
                                             "items":[
            
                                             ]
                                          }
                                       },
                                       {
                                          "name":28,
                                          "children":{
                                             "items":[
            
                                             ]
                                          }
                                       }
                                    ]
                                 }
                              }
                           ]
                        }
                     },
                     {
                        "name":4,
                        "children":{
                           "items":[
                              {
                                 "name":29,
                                 "children":{
                                    "items":[
                                       {
                                          "name":32,
                                          "children":{
                                             "items":[
            
                                             ]
                                          }
                                       },
                                       {
                                          "name":33,
                                          "children":{
                                             "items":[
            
                                             ]
                                          }
                                       },
                                       {
                                          "name":34,
                                          "children":{
                                             "items":[
            
                                             ]
                                          }
                                       }
                                    ]
                                 }
                              },
                              {
                                 "name":30,
                                 "children":{
                                    "items":[
                                       {
                                          "name":35,
                                          "children":{
                                             "items":[
            
                                             ]
                                          }
                                       },
                                       {
                                          "name":36,
                                          "children":{
                                             "items":[
            
                                             ]
                                          }
                                       },
                                       {
                                          "name":37,
                                          "children":{
                                             "items":[
            
                                             ]
                                          }
                                       }
                                    ]
                                 }
                              },
                              {
                                 "name":31,
                                 "children":{
                                    "items":[
                                       {
                                          "name":38,
                                          "children":{
                                             "items":[
            
                                             ]
                                          }
                                       },
                                       {
                                          "name":39,
                                          "children":{
                                             "items":[
            
                                             ]
                                          }
                                       },
                                       {
                                          "name":40,
                                          "children":{
                                             "items":[
            
                                             ]
                                          }
                                       }
                                    ]
                                 }
                              }
                           ]
                        }
                     }
                  ]
               }
            }
        ', true);

        $this->assertEquals($expected, $resource->toArray());
    }
}


class MockResourceDefinitionDepthOne extends \CatLab\Charon\Models\ResourceDefinition
{
    public function __construct()
    {
        parent::__construct(MockEntityModel::class);

        $this
            ->field('id')
            ->display('name')
            ->visible(true)

            ->relationship('children', MockResourceDefinitionDepthOne::class)
            ->expanded()
            ->visible()
            ->many()
        ;
    }
}

class MockResourceDefinitionDepthTwo extends \CatLab\Charon\Models\ResourceDefinition
{
    public function __construct()
    {
        parent::__construct(MockEntityModel::class);

        $this
            ->field('id')
            ->display('name')
            ->visible(true)

            ->relationship('children', MockResourceDefinitionDepthTwo::class)
            ->expanded()
            ->visible()
            ->many()
            ->maxDepth(2)
        ;
    }
}


class MockResourceDefinitionDepthThree extends \CatLab\Charon\Models\ResourceDefinition
{
    public function __construct()
    {
        parent::__construct(MockEntityModel::class);

        $this
            ->field('id')
            ->display('name')
            ->visible(true)

            ->relationship('children', MockResourceDefinitionDepthThree::class)
            ->expanded()
            ->visible()
            ->many()
            ->maxDepth(3)
        ;
    }
}

class MockResourceDefinitionDepthFour extends \CatLab\Charon\Models\ResourceDefinition
{
    public function __construct()
    {
        parent::__construct(MockEntityModel::class);

        $this
            ->field('id')
            ->display('name')
            ->visible(true)

            ->relationship('children', MockResourceDefinitionDepthFour::class)
            ->expanded()
            ->visible()
            ->many()
            ->maxDepth(4)
        ;
    }
}