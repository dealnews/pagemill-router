<?php

namespace PageMill\Router;

class RouterTest extends \PHPUnit\Framework\TestCase {

    public function testConstructor() {
        $r = new Router(
            [
                [
                    "type" => "exact",
                    "pattern" => "/foo",
                    "action" => "Foo",
                    "tokens" => []
                ],
                [
                    "type" => "exact",
                    "pattern" => "/foo/bar",
                    "action" => "FooBar",
                    "tokens" => []
                ],
            ]
        );

        $route = $r->match("/foo");
        $this->assertEquals(
            [
                "type" => "exact",
                "pattern" => "/foo",
                "action" => "Foo",
                "tokens" => []
            ],
            $route
        );

        $route = $r->match("/foo/bar");
        $this->assertEquals(
            [
                "type" => "exact",
                "pattern" => "/foo/bar",
                "action" => "FooBar",
                "tokens" => []
            ],
            $route
        );

        $route = $r->match("/foo/bar/baz");
        $this->assertEquals(
            [],
            $route
        );
    }


    public function testAdd() {
        $r = new Router();
        $r->add(
            "exact",
            "/foo",
            "Foo"
        );
        $routes = $r->getRoutes();
        $this->assertEquals(
            [
                [
                    "type" => "exact",
                    "pattern" => "/foo",
                    "action" => "Foo"
                ]
            ],
            $routes
        );
    }

    public function testCreateRoute() {
        $r = new Router();
        $route = $r->createRoute(
            "exact",
            "/foo",
            [
                "accept"  => "text/html",
                "action"  => "Foo",
                "headers" => [
                    "X-Foo"
                ],
                "host"    => "www.example.com",
                "method"  => "GET",
                "tokens"  => [
                    "foo"
                ],
            ]
        );

        $this->assertEquals(
            [
                "type" => "exact",
                "pattern" => "/foo",
                "accept"  => "text/html",
                "action"  => "Foo",
                "headers" => [
                    "X-Foo"
                ],
                "host"    => "www.example.com",
                "method"  => "GET",
                "tokens"  => [
                    "foo"
                ],
            ],
            $route
        );

        $this->expectException(\PageMill\Router\Exception\InvalidRoute::class);

        $route = $r->createRoute(
            "exact",
            "/foo",
            [
                "bad-value" => true,
            ]
        );

    }

    public function testMatch() {
        $r = new Router();
        $r->add(
            "exact",
            "/foo",
            "Foo"
        );
        $r->add(
            "exact",
            "/foo/bar",
            "FooBar"
        );

        $route = $r->match("/foo/bar");
        $this->assertEquals(
            [
                "type" => "exact",
                "pattern" => "/foo/bar",
                "action" => "FooBar",
                "tokens" => []
            ],
            $route
        );

        $route = $r->match("/foo");
        $this->assertEquals(
            [
                "type" => "exact",
                "pattern" => "/foo",
                "action" => "Foo",
                "tokens" => []
            ],
            $route
        );

    }

    public function testDefaultRoute() {
        $r = new Router();
        $r->add(
            "exact",
            "/foo",
            "Foo"
        );
        $r->add(
            "default",
            "",
            "FooBar"
        );

        $route = $r->match("/foo/bar");
        $this->assertEquals(
            [
                "type" => "default",
                "pattern" => "",
                "action" => "FooBar",
            ],
            $route
        );
    }

    public function testNoMatch() {
        $r = new Router();
        $r->add(
            "exact",
            "/foo",
            "Foo"
        );
        $r->add(
            "exact",
            "/foo/bar",
            "FooBar"
        );
        $route = $r->match("/foo/bar/baz");
        $this->assertEquals(
            [],
            $route
        );
    }

    public function testRouteMatch() {
        $r = new Router();
        $route = $r->matchRoute(
            [
                "type" => "exact",
                "pattern" => "/foo/bar",
                "action" => "FooBar"
            ],
            "/foo/bar",
            [],
            []
        );
        $this->assertEquals(
            [
                "type" => "exact",
                "pattern" => "/foo/bar",
                "action" => "FooBar",
                "tokens" => []
            ],
            $route
        );
    }

    public function testSubRouteMatch() {
        $r = new Router();
        $route = $r->match(
            "/foo/bar",
            [
                [
                    "type" => "starts_with",
                    "pattern" => "/foo",
                    "routes" => [
                        [
                            "type" => "exact",
                            "pattern" => "/foo/bar",
                            "action" => "FooBar"
                        ],
                        [
                            "type" => "exact",
                            "pattern" => "/foo/baz",
                            "action" => "FooBaz"
                        ],
                    ]
                ]
            ]
        );
        $this->assertEquals(
            [
                "type" => "exact",
                "pattern" => "/foo/bar",
                "action" => "FooBar",
                "tokens" => []
            ],
            $route
        );
    }

    public function testSubRouteNoMatch() {
        $r = new Router();
        $route = $r->match(
            "/foo/ber",
            [
                [
                    "type" => "starts_with",
                    "pattern" => "/foo",
                    "routes" => [
                        [
                            "type" => "exact",
                            "pattern" => "/foo/bar",
                            "action" => "FooBar"
                        ],
                        [
                            "type" => "exact",
                            "pattern" => "/foo/baz",
                            "action" => "FooBaz"
                        ],
                    ]
                ]
            ]
        );
        $this->assertEquals(
            [],
            $route
        );
    }

    public function testRouteNoMatch() {
        $r = new Router();
        $route = $r->matchRoute(
            [
                "type" => "exact",
                "pattern" => "/foo/bar",
                "action" => "FooBar"
            ],
            "/foo",
            [],
            []
        );
        $this->assertEquals(
            [],
            $route
        );
    }

    public function testPathMatch() {
        $r = new Router();
        $route = [
            "type" => "exact",
            "pattern" => "/foo/bar",
            "action" => "FooBar",
            "method" => "GET"
        ];

        $resp = $r->matchPath(
            $route,
            "/foo/bar"
        );
        $this->assertEquals(
            [
                "type" => "exact",
                "pattern" => "/foo/bar",
                "action" => "FooBar",
                "method" => "GET",
                "tokens" => []
            ],
            $resp
        );
    }

    public function testPathNoMatch() {
        $r = new Router();
        $route = [
            "type" => "exact",
            "pattern" => "/foo/bar",
            "action" => "FooBar",
            "method" => "GET"
        ];

        $resp = $r->matchPath(
            $route,
            "/foo"
        );
        $this->assertEquals(
            [],
            $resp
        );
    }

    public function testPathTokenStartsWithMatch() {
        $r = new Router();
        $route = [
            "type" => "starts_with",
            "pattern" => "/foo/bar",
            "action" => "FooBar",
            "method" => "GET"
        ];

        $resp = $r->matchPath(
            $route,
            "/foo/bar/1/"
        );
        $this->assertEquals(
            [
                "type" => "starts_with",
                "pattern" => "/foo/bar",
                "action" => "FooBar",
                "method" => "GET",
                "tokens" => [
                    "1"
                ]
            ],
            $resp
        );

        $resp = $r->matchPath(
            $route,
            "/foo/bar/1/2/"
        );
        $this->assertEquals(
            [
                "type" => "starts_with",
                "pattern" => "/foo/bar",
                "action" => "FooBar",
                "method" => "GET",
                "tokens" => [
                    "1",
                    "2"
                ]
            ],
            $resp
        );

        $route = [
            "type" => "starts_with",
            "pattern" => "/foo/bar",
            "action" => "FooBar",
            "method" => "GET",
            "tokens" => [
                "id"
            ]
        ];

        $resp = $r->matchPath(
            $route,
            "/foo/bar/1/"
        );
        $this->assertEquals(
            [
                "type" => "starts_with",
                "pattern" => "/foo/bar",
                "action" => "FooBar",
                "method" => "GET",
                "tokens" => [
                    "id" => "1",
                ]
            ],
            $resp
        );

        $resp = $r->matchPath(
            $route,
            "/foo/bar/1/2/"
        );
        $this->assertEquals(
            [],
            $resp
        );
    }


    public function testPathTokenRegexMatch() {
        $r = new Router();
        $route = [
            "type" => "regex",
            "pattern" => "!/foo/bar/(\d+)/!",
            "action" => "FooBar",
            "method" => "GET"
        ];

        $resp = $r->matchPath(
            $route,
            "/foo/bar/1/"
        );
        $this->assertEquals(
            [
                "type" => "regex",
                "pattern" => "!/foo/bar/(\d+)/!",
                "action" => "FooBar",
                "method" => "GET",
                "tokens" => [
                    "1"
                ]
            ],
            $resp
        );

        $resp = $r->matchPath(
            $route,
            "/foo/bar/1/2/"
        );
        $this->assertEquals(
            [
                "type" => "regex",
                "pattern" => "!/foo/bar/(\d+)/!",
                "action" => "FooBar",
                "method" => "GET",
                "tokens" => [
                    "1"
                ]
            ],
            $resp
        );

        $route = [
            "type" => "regex",
            "pattern" => "!/foo/bar/(\d+)/(\d+)/!",
            "action" => "FooBar",
            "method" => "GET"
        ];

        $resp = $r->matchPath(
            $route,
            "/foo/bar/1/2/"
        );
        $this->assertEquals(
            [
                "type" => "regex",
                "pattern" => "!/foo/bar/(\d+)/(\d+)/!",
                "action" => "FooBar",
                "method" => "GET",
                "tokens" => [
                    "1",
                    "2"
                ]
            ],
            $resp
        );

        $resp = $r->matchPath(
            $route,
            "/foo/bar/1/"
        );
        $this->assertEquals(
            [],
            $resp
        );

        $route = [
            "type" => "regex",
            "pattern" => "!/foo/bar/(\d+)/(\d+)/!",
            "action" => "FooBar",
            "method" => "GET",
            "tokens" => [
                "var1",
                "var2"
            ]
        ];
        $resp = $r->matchPath(
            $route,
            "/foo/bar/1/2/"
        );
        $this->assertEquals(
            [
                "type" => "regex",
                "pattern" => "!/foo/bar/(\d+)/(\d+)/!",
                "action" => "FooBar",
                "method" => "GET",
                "tokens" => [
                    "var1" => "1",
                    "var2" => "2"
                ]
            ],
            $resp
        );


    }

    public function testMethodMatch() {
        $r = new Router();
        $route = [
            "type" => "exact",
            "pattern" => "/foo/bar",
            "action" => "FooBar",
            "method" => "GET"
        ];

        $resp = $r->matchMethod(
            $route,
            [
                "REQUEST_METHOD" => "GET"
            ]
        );
        $this->assertEquals(
            "GET",
            $resp["method"]
        );
    }

    public function testMethodNoMatch() {
        $r = new Router();
        $route = [
            "type" => "exact",
            "pattern" => "/foo/bar",
            "action" => "FooBar",
            "method" => "GET"
        ];

        $resp = $r->matchMethod(
            $route,
            [
                "REQUEST_METHOD" => "POST"
            ]
        );
        $this->assertEquals(
            [],
            $resp
        );
    }

    public function testHostMatch() {
        $r = new Router();
        $route = [
            "type" => "exact",
            "pattern" => "/foo/bar",
            "action" => "FooBar",
            "host" => "www.example.com"
        ];

        $resp = $r->matchHost(
            $route,
            [
                "HTTP_HOST" => "www.example.com"
            ]
        );
        $this->assertEquals(
            "www.example.com",
            $resp["host"]
        );

        $route = [
            "type" => "exact",
            "pattern" => "/foo/bar",
            "action" => "FooBar",
            "host" => [
                "type" => "regex",
                "pattern" => '/\.example\.com$/'
            ]
        ];

        $resp = $r->matchHost(
            $route,
            [
                "HTTP_HOST" => "www.example.com"
            ]
        );
        $this->assertEquals(
            "www.example.com",
            $resp["host"]
        );

    }

    public function testHostNoMatch() {
        $r = new Router();
        $route = [
            "type" => "exact",
            "pattern" => "/foo/bar",
            "action" => "FooBar",
            "host" => "www.example.com"
        ];

        $resp = $r->matchHost(
            $route,
            [
                "HTTP_HOST" => "www2.example.com"
            ]
        );
        $this->assertEquals(
            [],
            $resp
        );

        $route = [
            "type" => "exact",
            "pattern" => "/foo/bar",
            "action" => "FooBar",
            "host" => [
                "type" => "regex",
                "pattern" => '/\.example\.com$/'
            ]
        ];

        $resp = $r->matchHost(
            $route,
            [
                "HTTP_HOST" => "www2.example2.com"
            ]
        );
        $this->assertEquals(
            [],
            $resp
        );

    }

    public function testHeaderMatch() {
        $r = new Router();
        $route = [
            "type" => "exact",
            "pattern" => "/foo/bar",
            "action" => "FooBar",
            "headers" => [
                "Host" => "www.example.com"
            ]
        ];

        $resp = $r->matchHeaders(
            $route,
            [
                "Host" => "www.example.com"
            ]
        );
        $this->assertEquals(
            "www.example.com",
            $resp["headers"]["Host"]
        );
    }

    public function testHeaderNoMatch() {
        $r = new Router();
        $route = [
            "type" => "exact",
            "pattern" => "/foo/bar",
            "action" => "FooBar",
            "headers" => [
                "Host" => "www.example.com"
            ]
        ];

        $resp = $r->matchHeaders(
            $route,
            [
                "Host" => "www2.example.com"
            ]
        );
        $this->assertEquals(
            [],
            $resp
        );
    }

    public function testHeadersOnlyReturnMatched() {
        $r = new Router();
        $route = [
            "type" => "exact",
            "pattern" => "/foo/bar",
            "action" => "FooBar",
            "headers" => [
                "Host" => "www.example.com"
            ]
        ];

        $resp = $r->matchHeaders(
            $route,
            [
                "Host" => "www.example.com",
                "X-Foo" => "bar",
            ]
        );
        $this->assertEquals(
            1,
            count($resp["headers"])
        );
        $this->assertEquals(
            "www.example.com",
            $resp["headers"]["Host"]
        );
    }

    public function testHeaderMatchCase() {
        $r = new Router();
        $route = [
            "type" => "exact",
            "pattern" => "/foo/bar",
            "action" => "FooBar",
            "headers" => [
                "host" => "www.example.com"
            ]
        ];

        $resp = $r->matchHeaders(
            $route,
            [
                "Host" => "www.example.com"
            ]
        );
        $this->assertEquals(
            "www.example.com",
            $resp["headers"]["host"]
        );
    }

    public function testGetHeaders() {
        $r = new Router();

        $resp = $r->getHeaders(
            [
                "HTTP_HOST" => "www.example.com"
            ]
        );
        $this->assertEquals(
            [
                "HOST" => "www.example.com"
            ],
            $resp
        );
    }

    public function testAcceptMatch() {
        $r = new Router();

        $route = [
            "type" => "exact",
            "pattern" => "/foo/bar",
            "action" => "FooBar",
            "accept" => "text/html"
        ];

        $resp = $r->matchAccept(
            $route,
            [
                "HTTP_ACCEPT" => "text/html;q=0.1"
            ]
        );
        $this->assertEquals(
            "text/html",
            $resp["accept"]
        );

        $route = [
            "type" => "exact",
            "pattern" => "/foo/bar",
            "action" => "FooBar",
            "accept" => "text/plain"
        ];

        $resp = $r->matchAccept(
            $route,
            [
                "HTTP_ACCEPT" => "text/html;q=0.1"
            ]
        );
        $this->assertEquals(
            [],
            $resp
        );

        $this->expectException(\PageMill\Router\Exception\InvalidMatchType::class);
        $this->expectExceptionCode(10);

        $resp = $r->matchAccept(
            [
                "type"    => "exact",
                "pattern" => "/foo/bar",
                "action"  => "FooBar",
                "accept"  => true
            ],
            [
                "HTTP_ACCEPT" => "text/html;q=0.1"
            ]
        );
    }

    public function testCheckMatchString() {
        $r = new Router();
        $result = $r->checkMatch("foo", "foo");
        $this->assertEquals(
            [],
            $result
        );
        $result = $r->checkMatch("foo", "bar");
        $this->assertEquals(
            false,
            $result
        );
    }

    public function testCheckMatchSimpleArray() {
        $r = new Router();
        $result = $r->checkMatch(
            [
                "foo",
                "bar"
            ],
            "foo"
        );
        $this->assertEquals(
            [],
            $result
        );
        $result = $r->checkMatch(
            [
                "foo",
                "bar"
            ],
            "bar"
        );
        $this->assertEquals(
            [],
            $result
        );
        $result = $r->checkMatch(
            [
                "foo",
                "bar"
            ],
            "foz"
        );
        $this->assertEquals(
            false,
            $result
        );
    }

    public function testCheckMatchArrayExact() {
        $r = new Router();
        $result = $r->checkMatch(
            [
                "type" => "exact",
                "pattern" => "foo"
            ],
            "foo"
        );
        $this->assertEquals(
            [],
            $result
        );
        $result = $r->checkMatch(
            [
                "type" => "exact",
                "pattern" => "foo"
            ],
            "foz"
        );
        $this->assertEquals(
            false,
            $result
        );
    }

    public function testCheckMatchArrayStartsWith() {
        $r = new Router();
        $result = $r->checkMatch(
            [
                "type" => "starts_with",
                "pattern" => "/foo"
            ],
            "/foo"
        );
        $this->assertEquals(
            [],
            $result
        );

        $result = $r->checkMatch(
            [
                "type" => "starts_with",
                "pattern" => "/foo"
            ],
            "/foo/bar"
        );
        $this->assertEquals(
            "/bar",
            $result
        );

        $result = $r->checkMatch(
            [
                "type" => "starts_with",
                "pattern" => "/foo"
            ],
            "/foz/bar"
        );
        $this->assertEquals(
            false,
            $result
        );
    }

    public function testCheckMatchRegex() {
        $r = new Router();
        $result = $r->checkMatch(
            [
                "type" => "regex",
                "pattern" => "!^/foo!"
            ],
            "/foo"
        );
        $this->assertEquals(
            [],
            $result
        );

        $result = $r->checkMatch(
            [
                "type" => "regex",
                "pattern" => "!^/foo/(\d+)/!"
            ],
            "/foo/1/"
        );
        $this->assertEquals(
            [
                1
            ],
            $result
        );
    }

    /**
     * @dataProvider badRoutes
     */
    public function testBadRoutes($routes, $expectCode) {

        $this->expectException(\PageMill\Router\Exception\InvalidRoute::class);
        $this->expectExceptionCode($expectCode);

        $r = new Router($routes);
        $r->match("/");
    }

    public function badRoutes() {
        return [
            "No type set for route" => [
                [
                    [
                        "pattern" => "/",
                        "action" => "Foo",
                    ]
                ],
                1
            ],

            "Routes should include an action or routes, but not both" => [
                [
                    [
                        "type" => "exact",
                        "pattern" => "/",
                        "action" => "Foo",
                        "routes" => [
                            "type" => "exact",
                            "pattern" => "/",
                            "action" => "Foo",
                        ]
                    ]
                ],
                2
            ],

            "Routes must include an action or routes" => [
                [
                    [
                        "type" => "exact",
                        "pattern" => "/",
                    ]
                ],
                3
            ],

            "Multiple default routes defined" => [
                [
                    [
                        "type" => "default",
                        "action" => "Foo",
                    ],
                    [
                        "type" => "default",
                        "action" => "Bar",
                    ]
                ],
                4
            ],

            "No pattern set for route" => [
                [
                    [
                        "type" => "exact",
                        "action" => "Foo",
                    ]
                ],
                5
            ],
        ];
    }
}
