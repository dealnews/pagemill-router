# PageMill Router

This library determines a route for a web request. It is built to be easy to use
and fast.

## Basic Routing

For the most basic of routing needs, an array can simply be passed to the Router
class constructor. Each route array should consist of a `type`, `pattern`,  and
`action`.  While `action` has no meaning to the router itself, its purpose is
for use after matching.

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

If performance is important, it is suggested to pass the routes into
the constructor.

## Match Types

There are three matching types supported.

*exact* - The request path must match `pattern` exactly.
*starts_with* - Matches when the request path begins with the exact value of `pattern`.
*regex* - Uses a regular expression to match against the reqeust path.

## Matching Tokens

Optionally, a route can include an array of tokens that allow for using values
from the request path to fill a named array in the return value of the `match`
method. This is only valid for `starts_with` and `exact` match types.

For `starts_with` matches, the tokens will be filled in based on values that
appear in the request path between /'s.

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

For `regex` matches, the tokens will be filled based on back references used in
the regular expression.

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

## Matching Headers

TODO

## Default Route

TODO

## Saving the Route List

TODO

