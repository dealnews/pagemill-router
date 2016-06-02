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

## Matching Hostname Request Method and Other HTTP Headers

It is also possible to match on hostname, request method and other HTTP headers by adding these settings to the route array.

`host` - Matches the HTTP Host header.
`method` - Matches the HTTP request method (GET, POST, etc.)
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

To match an arbitrary HTTP header, add `headers` to the route config.

This example will require that the `Accept` header contains `text/html`. Assume that the `Accept` header contains `text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8` for the request.

```
$r = new PageMill\Router\Router(
    array(
        array(
            "type" => "regex",
            "pattern" => "/foo/(\d+)/(\d+)/",
            "action" => "Foo",
            "headers" => array(
                "Accept" => array(
                    "type" => "regex",
                    "pattern" => '/text\/html/'
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
        "Accept" => "text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8"
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

TODO

## Adding Route Maps

TODO


