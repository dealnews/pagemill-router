<?php

namespace PageMill\Router;

class RouterTest extends \PHPUnit_Framework_TestCase {

    public function testConstructor() {
        $r = new Router(
            array(
                array(
                    "type" => "exact",
                    "pattern" => "/foo",
                    "action" => "Foo",
                    "tokens" => array()
                ),
                array(
                    "type" => "exact",
                    "pattern" => "/foo/bar",
                    "action" => "FooBar",
                    "tokens" => array()
                ),
            )
        );

        $route = $r->match("/foo");
        $this->assertEquals(
            array(
                "type" => "exact",
                "pattern" => "/foo",
                "action" => "Foo",
                "tokens" => array()
            ),
            $route
        );

        $route = $r->match("/foo/bar");
        $this->assertEquals(
            array(
                "type" => "exact",
                "pattern" => "/foo/bar",
                "action" => "FooBar",
                "tokens" => array()
            ),
            $route
        );

        $route = $r->match("/foo/bar/baz");
        $this->assertEquals(
            false,
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
        $routes = $r->get_routes();
        $this->assertEquals(
            array(
                array(
                    "type" => "exact",
                    "pattern" => "/foo",
                    "action" => "Foo"
                )
            ),
            $routes
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
            array(
                "type" => "exact",
                "pattern" => "/foo/bar",
                "action" => "FooBar",
                "tokens" => array()
            ),
            $route
        );

        $route = $r->match("/foo");
        $this->assertEquals(
            array(
                "type" => "exact",
                "pattern" => "/foo",
                "action" => "Foo",
                "tokens" => array()
            ),
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
            array(
                "type" => "default",
                "pattern" => "",
                "action" => "FooBar",
            ),
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
            false,
            $route
        );
    }

    public function testRouteMatch() {
        $r = new Router();
        $route = $r->match_route(
            array(
                "type" => "exact",
                "pattern" => "/foo/bar",
                "action" => "FooBar"
            ),
            "/foo/bar",
            array(),
            array()
        );
        $this->assertEquals(
            array(
                "type" => "exact",
                "pattern" => "/foo/bar",
                "action" => "FooBar",
                "tokens" => array()
            ),
            $route
        );
    }

    public function testSubRouteMatch() {
        $r = new Router();
        $route = $r->match(
            "/foo/bar",
            array(
                array(
                    "type" => "starts_with",
                    "pattern" => "/foo",
                    "routes" => array(
                        array(
                            "type" => "exact",
                            "pattern" => "/foo/bar",
                            "action" => "FooBar"
                        ),
                        array(
                            "type" => "exact",
                            "pattern" => "/foo/baz",
                            "action" => "FooBaz"
                        ),
                    )
                )
            )
        );
        $this->assertEquals(
            array(
                "type" => "exact",
                "pattern" => "/foo/bar",
                "action" => "FooBar",
                "tokens" => array()
            ),
            $route
        );
    }

    public function testSubRouteNoMatch() {
        $r = new Router();
        $route = $r->match(
            "/foo/ber",
            array(
                array(
                    "type" => "starts_with",
                    "pattern" => "/foo",
                    "routes" => array(
                        array(
                            "type" => "exact",
                            "pattern" => "/foo/bar",
                            "action" => "FooBar"
                        ),
                        array(
                            "type" => "exact",
                            "pattern" => "/foo/baz",
                            "action" => "FooBaz"
                        ),
                    )
                )
            )
        );
        $this->assertEquals(
            false,
            $route
        );
    }

    public function testRouteNoMatch() {
        $r = new Router();
        $route = $r->match_route(
            array(
                "type" => "exact",
                "pattern" => "/foo/bar",
                "action" => "FooBar"
            ),
            "/foo",
            array(),
            array()
        );
        $this->assertEquals(
            false,
            $route
        );
    }

    public function testPathMatch() {
        $r = new Router();
        $route = array(
            "type" => "exact",
            "pattern" => "/foo/bar",
            "action" => "FooBar",
            "method" => "GET"
        );

        $resp = $r->match_path(
            $route,
            "/foo/bar"
        );
        $this->assertEquals(
            array(
                "type" => "exact",
                "pattern" => "/foo/bar",
                "action" => "FooBar",
                "method" => "GET",
                "tokens" => array()
            ),
            $resp
        );
    }

    public function testPathNoMatch() {
        $r = new Router();
        $route = array(
            "type" => "exact",
            "pattern" => "/foo/bar",
            "action" => "FooBar",
            "method" => "GET"
        );

        $resp = $r->match_path(
            $route,
            "/foo"
        );
        $this->assertEquals(
            false,
            $resp
        );
    }

    public function testPathTokenStartsWithMatch() {
        $r = new Router();
        $route = array(
            "type" => "starts_with",
            "pattern" => "/foo/bar",
            "action" => "FooBar",
            "method" => "GET"
        );

        $resp = $r->match_path(
            $route,
            "/foo/bar/1/"
        );
        $this->assertEquals(
            array(
                "type" => "starts_with",
                "pattern" => "/foo/bar",
                "action" => "FooBar",
                "method" => "GET",
                "tokens" => array(
                    "1"
                )
            ),
            $resp
        );

        $resp = $r->match_path(
            $route,
            "/foo/bar/1/2/"
        );
        $this->assertEquals(
            array(
                "type" => "starts_with",
                "pattern" => "/foo/bar",
                "action" => "FooBar",
                "method" => "GET",
                "tokens" => array(
                    "1",
                    "2"
                )
            ),
            $resp
        );

        $route = array(
            "type" => "starts_with",
            "pattern" => "/foo/bar",
            "action" => "FooBar",
            "method" => "GET",
            "tokens" => array(
                "id"
            )
        );

        $resp = $r->match_path(
            $route,
            "/foo/bar/1/"
        );
        $this->assertEquals(
            array(
                "type" => "starts_with",
                "pattern" => "/foo/bar",
                "action" => "FooBar",
                "method" => "GET",
                "tokens" => array(
                    "id" => "1",
                )
            ),
            $resp
        );

        $resp = $r->match_path(
            $route,
            "/foo/bar/1/2/"
        );
        $this->assertEquals(
            false,
            $resp
        );
    }


    public function testPathTokenRegexMatch() {
        $r = new Router();
        $route = array(
            "type" => "regex",
            "pattern" => "!/foo/bar/(\d+)/!",
            "action" => "FooBar",
            "method" => "GET"
        );

        $resp = $r->match_path(
            $route,
            "/foo/bar/1/"
        );
        $this->assertEquals(
            array(
                "type" => "regex",
                "pattern" => "!/foo/bar/(\d+)/!",
                "action" => "FooBar",
                "method" => "GET",
                "tokens" => array(
                    "1"
                )
            ),
            $resp
        );

        $resp = $r->match_path(
            $route,
            "/foo/bar/1/2/"
        );
        $this->assertEquals(
            array(
                "type" => "regex",
                "pattern" => "!/foo/bar/(\d+)/!",
                "action" => "FooBar",
                "method" => "GET",
                "tokens" => array(
                    "1"
                )
            ),
            $resp
        );

        $route = array(
            "type" => "regex",
            "pattern" => "!/foo/bar/(\d+)/(\d+)/!",
            "action" => "FooBar",
            "method" => "GET"
        );

        $resp = $r->match_path(
            $route,
            "/foo/bar/1/2/"
        );
        $this->assertEquals(
            array(
                "type" => "regex",
                "pattern" => "!/foo/bar/(\d+)/(\d+)/!",
                "action" => "FooBar",
                "method" => "GET",
                "tokens" => array(
                    "1",
                    "2"
                )
            ),
            $resp
        );

        $resp = $r->match_path(
            $route,
            "/foo/bar/1/"
        );
        $this->assertEquals(
            false,
            $resp
        );

        $route = array(
            "type" => "regex",
            "pattern" => "!/foo/bar/(\d+)/(\d+)/!",
            "action" => "FooBar",
            "method" => "GET",
            "tokens" => array(
                "var1",
                "var2"
            )
        );
        $resp = $r->match_path(
            $route,
            "/foo/bar/1/2/"
        );
        $this->assertEquals(
            array(
                "type" => "regex",
                "pattern" => "!/foo/bar/(\d+)/(\d+)/!",
                "action" => "FooBar",
                "method" => "GET",
                "tokens" => array(
                    "var1" => "1",
                    "var2" => "2"
                )
            ),
            $resp
        );


    }

    public function testMethodMatch() {
        $r = new Router();
        $route = array(
            "type" => "exact",
            "pattern" => "/foo/bar",
            "action" => "FooBar",
            "method" => "GET"
        );

        $resp = $r->match_method(
            $route,
            array(
                "REQUEST_METHOD" => "GET"
            )
        );
        $this->assertEquals(
            "GET",
            $resp["method"]
        );
    }

    public function testMethodNoMatch() {
        $r = new Router();
        $route = array(
            "type" => "exact",
            "pattern" => "/foo/bar",
            "action" => "FooBar",
            "method" => "GET"
        );

        $resp = $r->match_method(
            $route,
            array(
                "REQUEST_METHOD" => "POST"
            )
        );
        $this->assertEquals(
            false,
            $resp
        );
    }

    public function testHostMatch() {
        $r = new Router();
        $route = array(
            "type" => "exact",
            "pattern" => "/foo/bar",
            "action" => "FooBar",
            "host" => "www.example.com"
        );

        $resp = $r->match_host(
            $route,
            array(
                "HTTP_HOST" => "www.example.com"
            )
        );
        $this->assertEquals(
            "www.example.com",
            $resp["host"]
        );

        $route = array(
            "type" => "exact",
            "pattern" => "/foo/bar",
            "action" => "FooBar",
            "host" => array(
                "type" => "regex",
                "pattern" => '/\.example\.com$/'
            )
        );

        $resp = $r->match_host(
            $route,
            array(
                "HTTP_HOST" => "www.example.com"
            )
        );
        $this->assertEquals(
            "www.example.com",
            $resp["host"]
        );

    }

    public function testHostNoMatch() {
        $r = new Router();
        $route = array(
            "type" => "exact",
            "pattern" => "/foo/bar",
            "action" => "FooBar",
            "host" => "www.example.com"
        );

        $resp = $r->match_host(
            $route,
            array(
                "HTTP_HOST" => "www2.example.com"
            )
        );
        $this->assertEquals(
            false,
            $resp
        );

        $route = array(
            "type" => "exact",
            "pattern" => "/foo/bar",
            "action" => "FooBar",
            "host" => array(
                "type" => "regex",
                "pattern" => '/\.example\.com$/'
            )
        );

        $resp = $r->match_host(
            $route,
            array(
                "HTTP_HOST" => "www2.example2.com"
            )
        );
        $this->assertEquals(
            false,
            $resp
        );

    }

    public function testHeaderMatch() {
        $r = new Router();
        $route = array(
            "type" => "exact",
            "pattern" => "/foo/bar",
            "action" => "FooBar",
            "headers" => array(
                "Host" => "www.example.com"
            )
        );

        $resp = $r->match_headers(
            $route,
            array(
                "Host" => "www.example.com"
            )
        );
        $this->assertEquals(
            "www.example.com",
            $resp["headers"]["Host"]
        );
    }

    public function testHeaderNoMatch() {
        $r = new Router();
        $route = array(
            "type" => "exact",
            "pattern" => "/foo/bar",
            "action" => "FooBar",
            "headers" => array(
                "Host" => "www.example.com"
            )
        );

        $resp = $r->match_headers(
            $route,
            array(
                "Host" => "www2.example.com"
            )
        );
        $this->assertEquals(
            false,
            $resp
        );
    }

    public function testHeadersOnlyReturnMatched() {
        $r = new Router();
        $route = array(
            "type" => "exact",
            "pattern" => "/foo/bar",
            "action" => "FooBar",
            "headers" => array(
                "Host" => "www.example.com"
            )
        );

        $resp = $r->match_headers(
            $route,
            array(
                "Host" => "www.example.com",
                "X-Foo" => "bar",
            )
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

    public function testCheckMatchString() {
        $r = new Router();
        $result = $r->check_match("foo", "foo");
        $this->assertEquals(
            array(),
            $result
        );
        $result = $r->check_match("foo", "bar");
        $this->assertEquals(
            false,
            $result
        );
    }

    public function testCheckMatchSimpleArray() {
        $r = new Router();
        $result = $r->check_match(
            array(
                "foo",
                "bar"
            ),
            "foo"
        );
        $this->assertEquals(
            array(),
            $result
        );
        $result = $r->check_match(
            array(
                "foo",
                "bar"
            ),
            "bar"
        );
        $this->assertEquals(
            array(),
            $result
        );
        $result = $r->check_match(
            array(
                "foo",
                "bar"
            ),
            "foz"
        );
        $this->assertEquals(
            false,
            $result
        );
    }

    public function testCheckMatchArrayExact() {
        $r = new Router();
        $result = $r->check_match(
            array(
                "type" => "exact",
                "pattern" => "foo"
            ),
            "foo"
        );
        $this->assertEquals(
            array(),
            $result
        );
        $result = $r->check_match(
            array(
                "type" => "exact",
                "pattern" => "foo"
            ),
            "foz"
        );
        $this->assertEquals(
            false,
            $result
        );
    }

    public function testCheckMatchArrayStartsWith() {
        $r = new Router();
        $result = $r->check_match(
            array(
                "type" => "starts_with",
                "pattern" => "/foo"
            ),
            "/foo"
        );
        $this->assertEquals(
            array(),
            $result
        );

        $result = $r->check_match(
            array(
                "type" => "starts_with",
                "pattern" => "/foo"
            ),
            "/foo/bar"
        );
        $this->assertEquals(
            "/bar",
            $result
        );

        $result = $r->check_match(
            array(
                "type" => "starts_with",
                "pattern" => "/foo"
            ),
            "/foz/bar"
        );
        $this->assertEquals(
            false,
            $result
        );
    }

    public function testCheckMatchRegex() {
        $r = new Router();
        $result = $r->check_match(
            array(
                "type" => "regex",
                "pattern" => "!^/foo!"
            ),
            "/foo"
        );
        $this->assertEquals(
            array(),
            $result
        );

        $result = $r->check_match(
            array(
                "type" => "regex",
                "pattern" => "!^/foo/(\d+)/!"
            ),
            "/foo/1/"
        );
        $this->assertEquals(
            array(
                1
            ),
            $result
        );
    }
}
