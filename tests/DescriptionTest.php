<?php

namespace CatLab\RESTResource\Tests;

use CatLab\Charon\Enums\Action;
use CatLab\Charon\Laravel\InputParsers\JsonBodyInputParser;
use CatLab\Charon\Laravel\InputParsers\PostInputParser;
use CatLab\Charon\Models\Context;
use CatLab\Charon\Swagger\Authentication\OAuth2Authentication;
use CatLab\Charon\Swagger\SwaggerBuilder;

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

        $context = new Context(Action::INDEX);
        $context->addInputParser(JsonBodyInputParser::class);
        $context->addInputParser(PostInputParser::class);

        $actual = $builder->build($context);

        $expected = json_decode('
            {
                "swagger": "2.0",
                "host": "localhost",
                "basePath": "\/",
                "info": {
                    "title": "Pet store API",
                    "description": "This pet store api allows you to buy pets",
                    "contact": {
                        "name": "CatLab Interactive",
                        "url": "https:\/\/www.catlab.eu\/",
                        "email": "info@catlab.eu"
                    },
                    "version": "1.0"
                },
                "paths": {
                    "api\/v1\/description.{format}": {
                        "get": {
                            "responses": {
                                "403": {
                                    "type": "string",
                                    "description": "Authentication error"
                                },
                                "404": {
                                    "type": "string",
                                    "description": "Entity not found"
                                }
                            },
                            "summary": "Get swagger API description",
                            "parameters": [
                                {
                                    "name": "format",
                                    "type": "string",
                                    "in": "path",
                                    "required": false,
                                    "description": "Output format",
                                    "default": "json",
                                    "enum": [
                                        "json"
                                    ]
                                }
                            ],
                            "tags": [
                                "swagger"
                            ],
                            "security": {
                                "oauth2": [
                                    "full"
                                ]
                            }
                        }
                    },
                    "api\/v1\/pets.{format}": {
                        "get": {
                            "responses": {
                                "200": {
                                    "schema": {
                                        "$ref": "#\/definitions\/Pet_index_items"
                                    }
                                },
                                "403": {
                                    "type": "string",
                                    "description": "Authentication error"
                                },
                                "404": {
                                    "type": "string",
                                    "description": "Entity not found"
                                }
                            },
                            "summary": "Get all pet",
                            "parameters": [
                                {
                                    "name": "name",
                                    "type": "string",
                                    "in": "query",
                                    "required": false,
                                    "description": "Find pets on name"
                                },
                                {
                                    "name": "status",
                                    "type": "string",
                                    "in": "query",
                                    "required": false,
                                    "enum": [
                                        "available",
                                        "ending",
                                        "sold"
                                    ]
                                },
                                {
                                    "name": "format",
                                    "type": "string",
                                    "in": "path",
                                    "required": false,
                                    "description": "Output format",
                                    "default": "json",
                                    "enum": [
                                        "json"
                                    ]
                                },
                                {
                                    "name": "pet-id",
                                    "type": "integer",
                                    "in": "query",
                                    "required": false,
                                    "description": "Filter results on pet-id"
                                },
                                {
                                    "name": "sort",
                                    "type": "array",
                                    "in": "query",
                                    "required": false,
                                    "description": "Define the sort parameter. Separate multiple values with comma.",
                                    "items": {
                                        "type": "string"
                                    },
                                    "enum": [
                                        "pet-id",
                                        "!pet-id"
                                    ]
                                },
                                {
                                    "name": "expand",
                                    "type": "array",
                                    "in": "query",
                                    "required": false,
                                    "description": "Expand relationships. Separate multiple values with comma. Values: category, photos, tags",
                                    "items": {
                                        "type": "string"
                                    },
                                    "enum": [
                                        "category",
                                        "photos",
                                        "tags"
                                    ]
                                },
                                {
                                    "name": "fields",
                                    "type": "array",
                                    "in": "query",
                                    "required": false,
                                    "description": "Define fields to return. Separate multiple values with comma. Values: *, name, category, category.*, category.name, category.category-description, photos, photos.*, photos.url, tags, tags.*, tags.name, status",
                                    "items": {
                                        "type": "string"
                                    },
                                    "enum": [
                                        "*",
                                        "name",
                                        "category",
                                        "category.*",
                                        "category.name",
                                        "category.category-description",
                                        "photos",
                                        "photos.*",
                                        "photos.url",
                                        "tags",
                                        "tags.*",
                                        "tags.name",
                                        "status"
                                    ]
                                }
                            ],
                            "security": {
                                "oauth2": [
                                    "full"
                                ]
                            }
                        }
                    },
                    "api\/v1\/pets\/{id}.{format}": {
                        "get": {
                            "responses": {
                                "200": {
                                    "schema": {
                                        "$ref": "#\/definitions\/Pet_view"
                                    }
                                },
                                "403": {
                                    "type": "string",
                                    "description": "Authentication error"
                                },
                                "404": {
                                    "type": "string",
                                    "description": "Entity not found"
                                }
                            },
                            "summary": "Get a pet",
                            "parameters": [
                                {
                                    "name": "id",
                                    "type": "integer",
                                    "in": "path",
                                    "required": true
                                },
                                {
                                    "name": "format",
                                    "type": "string",
                                    "in": "path",
                                    "required": false,
                                    "description": "Output format",
                                    "default": "json",
                                    "enum": [
                                        "json"
                                    ]
                                },
                                {
                                    "name": "expand",
                                    "type": "array",
                                    "in": "query",
                                    "required": false,
                                    "description": "Expand relationships. Separate multiple values with comma. Values: category, photos, tags",
                                    "items": {
                                        "type": "string"
                                    },
                                    "enum": [
                                        "category",
                                        "photos",
                                        "tags"
                                    ]
                                },
                                {
                                    "name": "fields",
                                    "type": "array",
                                    "in": "query",
                                    "required": false,
                                    "description": "Define fields to return. Separate multiple values with comma. Values: *, name, category, category.*, category.name, category.category-description, photos, photos.*, photos.url, tags, tags.*, tags.name, status",
                                    "items": {
                                        "type": "string"
                                    },
                                    "enum": [
                                        "*",
                                        "name",
                                        "category",
                                        "category.*",
                                        "category.name",
                                        "category.category-description",
                                        "photos",
                                        "photos.*",
                                        "photos.url",
                                        "tags",
                                        "tags.*",
                                        "tags.name",
                                        "status"
                                    ]
                                }
                            ],
                            "security": {
                                "oauth2": [
                                    "full"
                                ]
                            }
                        },
                        "put": {
                            "responses": {
                                "200": {
                                    "schema": {
                                        "$ref": "#\/definitions\/Pet_edit"
                                    }
                                },
                                "403": {
                                    "type": "string",
                                    "description": "Authentication error"
                                },
                                "404": {
                                    "type": "string",
                                    "description": "Entity not found"
                                }
                            },
                            "summary": "Get a pet",
                            "parameters": [
                                {
                                    "name": "id",
                                    "type": "integer",
                                    "in": "path",
                                    "required": true
                                },
                                {
                                    "name": "body",
                                    "in": "body",
                                    "required": false,
                                    "schema": {
                                        "$ref": "#\/definitions\/Pet_edit"
                                    }
                                },
                                {
                                    "name": "pet-id",
                                    "type": "integer",
                                    "in": "formData",
                                    "required": false
                                },
                                {
                                    "name": "name",
                                    "type": "string",
                                    "in": "formData",
                                    "required": false
                                },
                                {
                                    "name": "format",
                                    "type": "string",
                                    "in": "path",
                                    "required": false,
                                    "description": "Output format",
                                    "default": "json",
                                    "enum": [
                                        "json"
                                    ]
                                },
                                {
                                    "name": "expand",
                                    "type": "array",
                                    "in": "query",
                                    "required": false,
                                    "description": "Expand relationships. Separate multiple values with comma. Values: category, photos, tags",
                                    "items": {
                                        "type": "string"
                                    },
                                    "enum": [
                                        "category",
                                        "photos",
                                        "tags"
                                    ]
                                },
                                {
                                    "name": "fields",
                                    "type": "array",
                                    "in": "query",
                                    "required": false,
                                    "description": "Define fields to return. Separate multiple values with comma. Values: *, name, category, category.*, category.name, category.category-description, photos, photos.*, photos.url, tags, tags.*, tags.name, status",
                                    "items": {
                                        "type": "string"
                                    },
                                    "enum": [
                                        "*",
                                        "name",
                                        "category",
                                        "category.*",
                                        "category.name",
                                        "category.category-description",
                                        "photos",
                                        "photos.*",
                                        "photos.url",
                                        "tags",
                                        "tags.*",
                                        "tags.name",
                                        "status"
                                    ]
                                }
                            ],
                            "consumes": [
                                "application\/json",
                                "multipart\/form-data",
                                "application\/x-www-form-urlencoded"
                            ],
                            "security": {
                                "oauth2": [
                                    "full"
                                ]
                            }
                        }
                    }
                },
                "definitions": {
                    "Pet_index": {
                        "type": "object",
                        "properties": {
                            "pet-id": {
                                "type": "integer"
                            }
                        }
                    },
                    "Pet_index_items": {
                        "type": "object",
                        "properties": {
                            "items": {
                                "type": "array",
                                "items": {
                                    "$ref": "#\/definitions\/Pet_index"
                                }
                            }
                        }
                    },
                    "Pet_view": {
                        "type": "object",
                        "properties": {
                            "pet-id": {
                                "type": "integer"
                            },
                            "name": {
                                "type": "string"
                            },
                            "category": {
                                "type": "object",
                                "schema": {
                                    "properties": {
                                        "link": {
                                            "type": "string"
                                        }
                                    }
                                }
                            },
                            "photos": {
                                "type": "object",
                                "schema": {
                                    "properties": {
                                        "link": {
                                            "type": "string"
                                        }
                                    }
                                }
                            },
                            "tags": {
                                "type": "object",
                                "schema": {
                                    "properties": {
                                        "link": {
                                            "type": "string"
                                        }
                                    }
                                }
                            },
                            "status": {
                                "type": "string"
                            }
                        }
                    },
                    "Pet_edit": {
                        "type": "object",
                        "properties": {
                            "pet-id": {
                                "type": "integer"
                            },
                            "name": {
                                "type": "string"
                            },
                            "photos": {
                                "type": "object",
                                "schema": {
                                    "$ref": "#\/definitions\/Photo_create_items"
                                }
                            },
                            "tags": {
                                "type": "object",
                                "schema": {
                                    "$ref": "#\/definitions\/Tag_identifier_items"
                                }
                            }
                        }
                    },
                    "Photo_create": {
                        "type": "object",
                        "properties": {
                            "url": {
                                "type": "string"
                            }
                        }
                    },
                    "Photo_create_items": {
                        "type": "object",
                        "properties": {
                            "items": {
                                "type": "array",
                                "items": {
                                    "$ref": "#\/definitions\/Photo_create"
                                }
                            }
                        }
                    },
                    "Tag_identifier": {
                        "type": "object",
                        "properties": {
                            "tag-id": {
                                "type": "integer"
                            }
                        }
                    },
                    "Tag_identifier_items": {
                        "type": "object",
                        "properties": {
                            "items": {
                                "type": "array",
                                "items": {
                                    "$ref": "#\/definitions\/Tag_identifier"
                                }
                            }
                        }
                    }
                },
                "securityDefinitions": {
                    "oauth2": {
                        "type": "oauth2",
                        "authorizationUrl": "oauth\/authorize",
                        "flow": "implicit",
                        "scopes": {
                            "full": "Full access"
                        }
                    }
                }
            }
        
        ', true);

        /*
        echo json_encode($actual, JSON_PRETTY_PRINT);
        exit;
        */

        $this->assertEquals($expected, $actual);
    }
}