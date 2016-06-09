<?php

namespace CatLab\RESTResource\Tests;

use CatLab\Charon\Swagger\Authentication\OAuth2Authentication;
use CatLab\Charon\Swagger\SwaggerBuilder;
use CatLab\Charon\Transformers\ResourceTransformer;
use Tests\Petstore\Definitions\PetDefinition;
use CatLab\Charon\Enums\Action;
use CatLab\Charon\Models\Context;

use PHPUnit_Framework_TestCase;

/**
 * Class ValidatorTest
 * @package CatLab\RESTResource\Tests
 */
class DescriptionTest extends PHPUnit_Framework_TestCase
{
    /**
     *
     */
    public function testSwaggerDescription()
    {
        $routes = require 'Petstore/routes.php';

        $builder = new SwaggerBuilder('localhost', '/');

        $builder
            ->setTitle('Pet store API')
            ->setDescription('This pet store api allows you to buy pets')
            ->setContact('CatLab Interactive', 'https://www.catlab.eu/', 'info@catlab.eu')
            ->setVersion('1.0');

        $oauth = new OAuth2Authentication('oauth2');
        $oauth
            ->setAuthorizationUrl('oauth/authorize')
            ->setFlow('implicit')
            ->addScope('full', 'Full access')
        ;

        $builder->addAuthentication($oauth);

        foreach ($routes->getRoutes() as $route) {
            $builder->addRoute($route);
        }

        $expected = json_decode('
            {
               "swagger":"2.0",
               "host":"localhost",
               "basePath":"\/",
               "info":{
                  "title":"Pet store API",
                  "description":"This pet store api allows you to buy pets",
                  "contact":{
                     "name":"CatLab Interactive",
                     "url":"https:\/\/www.catlab.eu\/",
                     "email":"info@catlab.eu"
                  },
                  "version":"1.0"
               },
               "paths":{
                  "api\/v1\/description.{format}":{
                     "get":{
                        "summary":"Get swagger API description",
                        "parameters":[
                           {
                              "name":"format",
                              "type":"string",
                              "in":"path",
                              "required":false,
                              "description":"Output format",
                              "enum":[
                                 "json"
                              ],
                              "default":"json"
                           }
                        ],
                        "tags":[
                           "swagger"
                        ],
                        "responses":{
                           "403":{
                              "description":"Authentication error",
                              "headers":[
            
                              ]
                           },
                           "404":{
                              "description":"Entity not found",
                              "headers":[
            
                              ]
                           }
                        },
                        "security":{
                           "oauth2":[
                              "full"
                           ]
                        }
                     }
                  },
                  "api\/v1\/pets.{format}":{
                     "get":{
                        "summary":"Get all pet",
                        "parameters":[
                           {
                              "name":"name",
                              "type":"string",
                              "in":"query",
                              "required":false,
                              "description":"Find pets on name"
                           },
                           {
                              "name":"status",
                              "type":"string",
                              "in":"query",
                              "required":false,
                              "enum":[
                                 "available",
                                 "ending",
                                 "sold"
                              ]
                           },
                           {
                              "name":"format",
                              "type":"string",
                              "in":"path",
                              "required":false,
                              "description":"Output format",
                              "enum":[
                                 "json"
                              ],
                              "default":"json"
                           }
                        ],
                        "responses":{
                           "200":{
                              "schema":{
                                 "$ref":"#\/definitions\/Pet_view"
                              },
                              "headers":[
            
                              ]
                           },
                           "403":{
                              "description":"Authentication error",
                              "headers":[
            
                              ]
                           },
                           "404":{
                              "description":"Entity not found",
                              "headers":[
            
                              ]
                           }
                        },
                        "security":{
                           "oauth2":[
                              "full"
                           ]
                        }
                     }
                  },
                  "api\/v1\/pets\/{id}.{format}":{
                     "get":{
                        "summary":"Get a pet",
                        "parameters":[
                           {
                              "name":"id",
                              "type":"integer",
                              "in":"path",
                              "required":true
                           },
                           {
                              "name":"format",
                              "type":"string",
                              "in":"path",
                              "required":false,
                              "description":"Output format",
                              "enum":[
                                 "json"
                              ],
                              "default":"json"
                           }
                        ],
                        "responses":{
                           "200":{
                              "schema":{
                                 "$ref":"#\/definitions\/Pet_view"
                              },
                              "headers":[
            
                              ]
                           },
                           "403":{
                              "description":"Authentication error",
                              "headers":[
            
                              ]
                           },
                           "404":{
                              "description":"Entity not found",
                              "headers":[
            
                              ]
                           }
                        },
                        "security":{
                           "oauth2":[
                              "full"
                           ]
                        }
                     }
                  }
               },
               "definitions":{
                  "Pet_view":{
                     "type":"object",
                     "properties":{
                        "pet-id":{
                           "type":"integer"
                        },
                        "name":{
                           "type":"string"
                        },
                        "category":{
                           "type":"object",
                           "schema":{
                              "properties":{
                                 "link":{
                                    "type":"string"
                                 }
                              }
                           }
                        },
                        "photos":{
                           "type":"object",
                           "schema":{
                              "properties":{
                                 "link":{
                                    "type":"string"
                                 }
                              }
                           }
                        },
                        "tags":{
                           "type":"object",
                           "schema":{
                              "properties":{
                                 "link":{
                                    "type":"string"
                                 }
                              }
                           }
                        },
                        "status":{
                           "type":"string"
                        }
                     }
                  }
               },
               "securityDefinitions":{
                  "oauth2":{
                     "type":"oauth2",
                     "authorizationUrl":"oauth\/authorize",
                     "flow":"implicit",
                     "scopes":{
                        "full":"Full access"
                     }
                  }
               }
            }
        
        ', true);

        $this->assertEquals($expected, $builder->build());
    }
}