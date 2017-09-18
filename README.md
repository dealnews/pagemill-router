# PageMill Router

This library determines a route for a web request. It is built to be easy to use and fast.

## Basic Routing

For the most basic of routing needs, an array can simply be passed to the Router class constructor. Each route array should consist of a `type`, `pattern`,  and either an `action` or array of sub-routes called `routes`.  While `action` has no meaning to the router itself, its purpose is for use after matching.

```
$r = new PageMill\Router\Router(
    array(
        array(
            "type" => "exact",
            "pattern" => "/foo",
            "action" => "Foo"
        ),
        array(
            "type" => "exact",
            "pattern" => "/foo/bar",
            "action" => "FooBar"
        ),
    )
);

$route = $r->match("/foo");
```

## Adding with Methods

You can also add routes using the `add` method.

```
$r = new PageMill\Router\Router();
$r->add("exact", "/foo", "Foo");
$r->add("exact", "/foo/bar/", "FooBar");
$route = $r->match("/foo");
```

If performance is important, it is suggested to pass the routes into the constructor.

## Match Types

There are three matching types supported.

**exact** - The request path must match `pattern` exactly.

**starts_with** - Matches when the request path begins with the exact value of `pattern`.

**regex** - Uses a regular expression to match against the reqeust path.

## Matching Tokens

Optionally, a route can include an array of tokens that allow for using values from the request path to fill a named array in the return value of the `match` method. This is only valid for `starts_with` and `exact` match types.

For `starts_with` matches, the tokens will be filled in based on values that appear in the request path between slashes (`/`) that are not part of the pattern.

```
$r = new PageMill\Router\Router(
    array(
        array(
            "type" => "starts_with",
            "pattern" => "/foo/",
            "action" => "Foo",
            "tokens" = array(
                "group",
                "id"
            )
        )
    )
);

$route = $r->match("/foo/1/2/");
```

The value of `$route` would be:

```
array(
    "type" => "starts_with",
    "pattern" => "/foo/",
    "action" => "Foo",
    "tokens" = array(
        "group" => 1,
        "id" => 2
    )
)
```

For `regex` matches, the tokens will be filled based on back references used in the regular expression.

```
$r = new PageMill\Router\Router(
    array(
        array(
            "type" => "regex",
            "pattern" => "/foo/(\d+)/(\d+)/",
            "action" => "Foo",
            "tokens" = array(
                "group",
                "id"
            )
        )
    )
);

$route = $r->match("/foo/1/2/");
```

The value of `$route` would be:

```
array(
    "type" => "starts_with",
    "pattern" => "/foo/",
    "action" => "Foo",
    "tokens" = array(
        "group" => 1,
        "id" => 2
    )
)
```

## Matching Hostname, Request Method, Accept header, and Other HTTP Headers

It is also possible to match on hostname, request method, Accept header, and other HTTP headers by adding these settings to the route array.

`host` - Matches the HTTP Host header.
`method` - Matches the HTTP request method (GET, POST, etc.)
`accept` - Validates the Accept header contains one of a list of mime types.
`headers` - An array of headers and the patterns to match them.

To match on hostname, add `host` to the route config. To only match `GET` requests, add `method`.

```
$r = new PageMill\Router\Router(
    array(
        array(
            "type" => "regex",
            "pattern" => "/foo/(\d+)/(\d+)/",
            "action" => "Foo",
            "host" => "www.example.com",
            "method" => "GET"
        )
    )
);
$route = $r->match("/foo/1/2/");
```

The value of `$route` would be:

```
array(
    "type" => "starts_with",
    "pattern" => "/foo/",
    "action" => "Foo",
    "tokens" = array(
        "group" => 1,
        "id" => 2
    )
    "host" => "www.example.com",
    "method" => "GET"
)
```

By default, this is treated as an exact match. You can do more complex matching by providing an array with `type` and `pattern` set. This is true of `host`, `method` and the patterns in the `headers` array.

```
$r = new PageMill\Router\Router(
    array(
        array(
            "type" => "regex",
            "pattern" => "/foo/(\d+)/(\d+)/",
            "action" => "Foo",
            "host" => array(
                "type" => "regex",
                "pattern" => '/\.example\.com$/'
            ),
            "method" => "GET"
        )
    )
);
$route = $r->match("/foo/1/2/");
```

The matched values of these settings will be returned in the matched route array. The value of `$route` would be:

```
array(
    "type" => "starts_with",
    "pattern" => "/foo/",
    "action" => "Foo",
    "tokens" = array(
        "group" => 1,
        "id" => 2
    )
    "host" => "www.example.com",
    "method" => "GET"
)
```

To validate the Accept header against a list of mime types, this route config could be used. For this example, assume the Accept header from the client contains `text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8`.

NOTE: Unlike other options, the `accept` option only accepts a single string mime type or an array of mime types.

```
$r = new PageMill\Router\Router(
    array(
        array(
            "type" => "regex",
            "pattern" => "/foo/(\d+)/(\d+)/",
            "action" => "Foo",
            "accept" => array(
                "text/html"
            )
        )
    )
);
$route = $r->match("/foo/1/2/");
```

The value of `$route` would be:

```
array(
    "type" => "starts_with",
    "pattern" => "/foo/",
    "action" => "Foo",
    "tokens" = array(
        "group" => 1,
        "id" => 2
    )
    "accept" => "text/html"
)
```

Router will honor the clients quality scores from the Accept header. For an explination of the quality score used in HTTP client Accept headers, see https://www.w3.org/Protocols/rfc2616/rfc2616-sec14.html. If the quality scores for the matching mime types is equal, the order they are defined in the configuration will be honored.

```
$r = new PageMill\Router\Router(
    array(
        array(
            "type" => "regex",
            "pattern" => "/foo/(\d+)/(\d+)/",
            "action" => "Foo",
            "accept" => array(
                "application/json"
                "text/html"
            )
        )
    )
);
$route = $r->match("/foo/1/2/");
```

Because the quality score for text/html is not defined in the Accept header, it is assumed to be 1. The value of `$route` would be:

```
array(
    "type" => "starts_with",
    "pattern" => "/foo/",
    "action" => "Foo",
    "tokens" = array(
        "group" => 1,
        "id" => 2
    )
    "accept" => "text/html"
)
```

To match an arbitrary HTTP header, add `headers` to the route config.

This example will ensure that the `Authorization` header contains `12345678`.

```
$r = new PageMill\Router\Router(
    array(
        array(
            "type" => "regex",
            "pattern" => "/foo/(\d+)/(\d+)/",
            "action" => "Foo",
            "headers" => array(
                "Authorization" => array(
                    "type" => "exact",
                    "pattern" => '12345678'
                )
            )
        )
    )
);
$route = $r->match("/foo/1/2/");
```

The value of `$route` would be:

```
array(
    "type" => "starts_with",
    "pattern" => "/foo/",
    "action" => "Foo",
    "tokens" = array(
        "group" => 1,
        "id" => 2
    )
    "headers" => array(
        "Authorization" => "12345678"
    )
)
```

## Default Route

```
$r = new PageMill\Router\Router(
    array(
        array(
            "type" => "exact",
            "pattern" => "/foo/",
            "action" => "Foo",
        ),
        array(
            "type" => "default",
            "action" => "Default",
        )
    )
);
$route = $r->match("/foo/1/2/");
```

The value of `$route` would be:

```
array(
    "type" => "default",
    "action" => "Default",
)
```

Note: No tokens will be returned and no pattern matching is performed on the path for the default route.

Only one default route is allowed. An `InvalidRoute` exception will be thrown if more than one route is defined.

## Saving the Route List

If you would like to save the route list for reuse, you can call the `get_routes()` method.

```
$r = new PageMill\Router\Router(
    array(
        array(
            "type" => "exact",
            "pattern" => "/foo/",
            "action" => "Foo",
        ),
        array(
            "type" => "default",
            "action" => "Default",
        )
    )
);
$routes = $r->get_routes();
```

## Adding Sub Routes (aka Route Maps)

If you have several routes that all have the same prefix or match the same pattern, it can be beneficial to group those routes as sub-routes under a more general route. For example, if we have multiple routes that fall under the `/foo` path, we could configure our routes like this:

```
$r = new PageMill\Router\Router(
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
$route = $r->match("/foo/bar");
```

The value of `$route` would be:

```
array(
    "type" => "exact",
    "pattern" => "/foo/bar",
    "action" => "FooBar"
)
```
