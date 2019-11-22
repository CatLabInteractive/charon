<?php

namespace CatLab\RESTResource\Tests;

use CatLab\Charon\ResourceTransformer;
use MockEntityModel;
use CatLab\Charon\Enums\Action;
use CatLab\Charon\Models\Context;

use MockResourceDefinitionDepthFour;
use MockResourceDefinitionDepthOne;
use MockResourceDefinitionDepthThree;
use MockResourceDefinitionDepthTwo;
use PHPUnit_Framework_TestCase;

require_once 'ResourceDefinitionDepths/MockResourceDefinitionDepthOne.php';
require_once 'ResourceDefinitionDepths/MockResourceDefinitionDepthTwo.php';
require_once 'ResourceDefinitionDepths/MockResourceDefinitionDepthThree.php';
require_once 'ResourceDefinitionDepths/MockResourceDefinitionDepthFour.php';

/**
 * Class MaxDepthTest
 *
 * Tests the max depth parameter for recursive relationship fields.
 *
 * @package CatLab\RESTResource\Tests
 */
class MaxDepthTest extends BaseTest
{
    /**
     * @return MockEntityModel
     */
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
     * @return mixed
     */
    private function getDeepArray()
    {
        return json_decode('
            {
               "id":1,
               "children":{
                  "items":[
                     {
                        "id":2,
                        "children":{
                           "items":[
                              {
                                 "id":5,
                                 "children":{
                                    "items":[
                                       {
                                          "id":8,
                                          "children":{
                                             "items":[
            
                                             ]
                                          }
                                       },
                                       {
                                          "id":9,
                                          "children":{
                                             "items":[
            
                                             ]
                                          }
                                       },
                                       {
                                          "id":10,
                                          "children":{
                                             "items":[
            
                                             ]
                                          }
                                       }
                                    ]
                                 }
                              },
                              {
                                 "id":6,
                                 "children":{
                                    "items":[
                                       {
                                          "id":11,
                                          "children":{
                                             "items":[
            
                                             ]
                                          }
                                       },
                                       {
                                          "id":12,
                                          "children":{
                                             "items":[
            
                                             ]
                                          }
                                       },
                                       {
                                          "id":13,
                                          "children":{
                                             "items":[
            
                                             ]
                                          }
                                       }
                                    ]
                                 }
                              },
                              {
                                 "id":7,
                                 "children":{
                                    "items":[
                                       {
                                          "id":14,
                                          "children":{
                                             "items":[
            
                                             ]
                                          }
                                       },
                                       {
                                          "id":15,
                                          "children":{
                                             "items":[
            
                                             ]
                                          }
                                       },
                                       {
                                          "id":16,
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
                        "id":3,
                        "children":{
                           "items":[
                              {
                                 "id":17,
                                 "children":{
                                    "items":[
                                       {
                                          "id":20,
                                          "children":{
                                             "items":[
            
                                             ]
                                          }
                                       },
                                       {
                                          "id":21,
                                          "children":{
                                             "items":[
            
                                             ]
                                          }
                                       },
                                       {
                                          "id":22,
                                          "children":{
                                             "items":[
            
                                             ]
                                          }
                                       }
                                    ]
                                 }
                              },
                              {
                                 "id":18,
                                 "children":{
                                    "items":[
                                       {
                                          "id":23,
                                          "children":{
                                             "items":[
            
                                             ]
                                          }
                                       },
                                       {
                                          "id":24,
                                          "children":{
                                             "items":[
            
                                             ]
                                          }
                                       },
                                       {
                                          "id":25,
                                          "children":{
                                             "items":[
            
                                             ]
                                          }
                                       }
                                    ]
                                 }
                              },
                              {
                                 "id":19,
                                 "children":{
                                    "items":[
                                       {
                                          "id":26,
                                          "children":{
                                             "items":[
            
                                             ]
                                          }
                                       },
                                       {
                                          "id":27,
                                          "children":{
                                             "items":[
            
                                             ]
                                          }
                                       },
                                       {
                                          "id":28,
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
                        "id":4,
                        "children":{
                           "items":[
                              {
                                 "id":29,
                                 "children":{
                                    "items":[
                                       {
                                          "id":32,
                                          "children":{
                                             "items":[
            
                                             ]
                                          }
                                       },
                                       {
                                          "id":33,
                                          "children":{
                                             "items":[
            
                                             ]
                                          }
                                       },
                                       {
                                          "id":34,
                                          "children":{
                                             "items":[
            
                                             ]
                                          }
                                       }
                                    ]
                                 }
                              },
                              {
                                 "id":30,
                                 "children":{
                                    "items":[
                                       {
                                          "id":35,
                                          "children":{
                                             "items":[
            
                                             ]
                                          }
                                       },
                                       {
                                          "id":36,
                                          "children":{
                                             "items":[
            
                                             ]
                                          }
                                       },
                                       {
                                          "id":37,
                                          "children":{
                                             "items":[
            
                                             ]
                                          }
                                       }
                                    ]
                                 }
                              },
                              {
                                 "id":31,
                                 "children":{
                                    "items":[
                                       {
                                          "id":38,
                                          "children":{
                                             "items":[
            
                                             ]
                                          }
                                       },
                                       {
                                          "id":39,
                                          "children":{
                                             "items":[
            
                                             ]
                                          }
                                       },
                                       {
                                          "id":40,
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
    }

    /**
     * @return mixed
     */
    private function getDeepData3()
    {
        return json_decode('
            {
               "id":1,
               "children":{
                  "items":[
                     {
                        "id":2,
                        "children":{
                           "items":[
                              {
                                 "id":5,
                                 "children":{
                                    "items":[
                                       {
                                          "id":8
                                       },
                                       {
                                          "id":9
                                       },
                                       {
                                          "id":10
                                       }
                                    ]
                                 }
                              },
                              {
                                 "id":6,
                                 "children":{
                                    "items":[
                                       {
                                          "id":11
                                       },
                                       {
                                          "id":12
                                       },
                                       {
                                          "id":13
                                       }
                                    ]
                                 }
                              },
                              {
                                 "id":7,
                                 "children":{
                                    "items":[
                                       {
                                          "id":14
                                       },
                                       {
                                          "id":15
                                       },
                                       {
                                          "id":16
                                       }
                                    ]
                                 }
                              }
                           ]
                        }
                     },
                     {
                        "id":3,
                        "children":{
                           "items":[
                              {
                                 "id":17,
                                 "children":{
                                    "items":[
                                       {
                                          "id":20
                                       },
                                       {
                                          "id":21
                                       },
                                       {
                                          "id":22
                                       }
                                    ]
                                 }
                              },
                              {
                                 "id":18,
                                 "children":{
                                    "items":[
                                       {
                                          "id":23
                                       },
                                       {
                                          "id":24
                                       },
                                       {
                                          "id":25
                                       }
                                    ]
                                 }
                              },
                              {
                                 "id":19,
                                 "children":{
                                    "items":[
                                       {
                                          "id":26
                                       },
                                       {
                                          "id":27
                                       },
                                       {
                                          "id":28
                                       }
                                    ]
                                 }
                              }
                           ]
                        }
                     },
                     {
                        "id":4,
                        "children":{
                           "items":[
                              {
                                 "id":29,
                                 "children":{
                                    "items":[
                                       {
                                          "id":32
                                       },
                                       {
                                          "id":33
                                       },
                                       {
                                          "id":34
                                       }
                                    ]
                                 }
                              },
                              {
                                 "id":30,
                                 "children":{
                                    "items":[
                                       {
                                          "id":35
                                       },
                                       {
                                          "id":36
                                       },
                                       {
                                          "id":37
                                       }
                                    ]
                                 }
                              },
                              {
                                 "id":31,
                                 "children":{
                                    "items":[
                                       {
                                          "id":38
                                       },
                                       {
                                          "id":39
                                       },
                                       {
                                          "id":40
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
    }

    /**
     * @return mixed
     */
    private function getDeepData2()
    {
        return json_decode('
            {
               "id":1,
               "children":{
                  "items":[
                     {
                        "id":2,
                        "children":{
                           "items":[
                              {
                                 "id":5
                              },
                              {
                                 "id":6
                              },
                              {
                                 "id":7
                              }
                           ]
                        }
                     },
                     {
                        "id":3,
                        "children":{
                           "items":[
                              {
                                 "id":17
                              },
                              {
                                 "id":18
                              },
                              {
                                 "id":19
                              }
                           ]
                        }
                     },
                     {
                        "id":4,
                        "children":{
                           "items":[
                              {
                                 "id":29
                              },
                              {
                                 "id":30
                              },
                              {
                                 "id":31
                              }
                           ]
                        }
                     }
                  ]
               }
            }
        ', true);
    }

    /**
     * @return mixed
     */
    private function getDeepData1()
    {
        return json_decode('
            {
               "id":1,
               "children":{
                  "items":[
                     {
                        "id":2
                     },
                     {
                        "id":3
                     },
                     {
                        "id":4
                     }
                  ]
               }
            }
        ', true);
    }


    /**
     *
     */
    public function testOutputMaxDepthOne()
    {
        $transformer = $this->getResourceTransformer();

        $mockEntity = $this->getDeepChildren();
        $resource = $transformer->toResource(\MockResourceDefinitionDepthOne::class, $mockEntity, new Context(Action::VIEW));

        $expected = $this->getDeepData1();

        $this->assertEquals($expected, $resource->toArray());
    }

    /**
     *
     */
    public function testOutputMaxDepthTwo()
    {
        $transformer = $this->getResourceTransformer();

        $mockEntity = $this->getDeepChildren();
        $resource = $transformer->toResource(\MockResourceDefinitionDepthTwo::class, $mockEntity, new Context(Action::VIEW));

        $expected = $this->getDeepData2();

        $this->assertEquals($expected, $resource->toArray());
    }

    /**
     *
     */
    public function testOutputMaxDepthThree()
    {
        $transformer = $this->getResourceTransformer();

        $mockEntity = $this->getDeepChildren();
        $resource = $transformer->toResource(\MockResourceDefinitionDepthThree::class, $mockEntity, new Context(Action::VIEW));

        $expected = $this->getDeepData3();

        $this->assertEquals($expected, $resource->toArray());
    }

    /**
     *
     */
    public function testOutputMaxDepthFour()
    {
        $transformer = $this->getResourceTransformer();

        $mockEntity = $this->getDeepChildren();
        $resource = $transformer->toResource(MockResourceDefinitionDepthFour::class, $mockEntity, new Context(Action::VIEW));

        $expected = $this->getDeepArray();

        $this->assertEquals($expected, $resource->toArray());
    }

    /**
     *
     */
    public function testInputDepthOne()
    {
        $transformer = $this->getResourceTransformer();

        $input = $this->getDeepArray();

        $resource = $transformer->fromArray(
            MockResourceDefinitionDepthOne::class,
            $input,
            new Context(Action::CREATE)
        );

        $this->assertEquals($this->getDeepData1(), $resource->toArray());
    }

    /**
     *
     */
    public function testInputDepthTwo()
    {
        $transformer = $this->getResourceTransformer();

        $input = $this->getDeepArray();

        $resource = $transformer->fromArray(
            MockResourceDefinitionDepthTwo::class,
            $input,
            new Context(Action::CREATE)
        );

        $this->assertEquals($this->getDeepData2(), $resource->toArray());
    }

    /**
     *
     */
    public function testInputDepthThree()
    {
        $transformer = $this->getResourceTransformer();

        $input = $this->getDeepArray();

        $resource = $transformer->fromArray(
            MockResourceDefinitionDepthThree::class,
            $input,
            new Context(Action::CREATE)
        );

        $this->assertEquals($this->getDeepData3(), $resource->toArray());
    }
}
