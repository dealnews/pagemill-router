<?php

namespace PageMill\Router;

use \PageMill\Pattern\Pattern;
use \PageMill\Accept\Accept;

/**
 * Router Class
 *
 * Decides what route we are answering and creates a controller
 * to handle the request.
 *
 */

class Router {

    /**
     * Array of possible routes
     *
     * @var array
     */
    protected $routes = [];

    /**
     * Creates a new Router
     *
     * @param  string $app_base Directory of the app files
     * @param  array  $routes   Array of routes to map the request too
     *
     * @return object
     */
    public function __construct(array $routes = []) {

        if (!empty($routes)) {
            $this->routes = $routes;
        }

    }

    /**
     * Adds a route to the route list
     *
     * @param string $type    Matching type. One of exact, regex, starts_with,
     *                        or default. There should be only one route with
     *                        the type default.
     * @param string $pattern Either a string or a regular expression
     * @param mixed  $action  The action to be taken if the route is matched.
     *                        The action has no meaning to the router. It is
     *                        to be used externally to answer the request.
     * @param array  $options Array of additional options for the route matching
     */
    public function add(string $type, string $pattern, $action, array $options = []) {
        $options["action"] = $action;
        $route = $this->createRoute($type, $pattern, $options);
        $this->routes[] = $route;
    }

    /**
     * Creates a route entry with validation of the options
     *
     * @param string $type    Matching type. One of exact, regex, starts_with,
     *                        or default. There should be only one route with
     *                        the type default.
     * @param string $pattern Either a string or a regular expression
     * @param array  $options Array of additional options for the route matching
     *                        as well as any action or list of sub-routes
     *
     * @return array
     */
    public function createRoute(string $type, string $pattern, array $options = []): array {
        $route = [
            "type" => $type,
            "pattern" => $pattern
        ];
        if (!empty($options)) {
            foreach ($options as $opt => $value) {
                switch ($opt) {
                    case "method":
                    case "tokens":
                    case "host":
                    case "headers":
                    case "action":
                    case "routes":
                    case "accept":
                        $route[$opt] = $value;
                        break;
                    default:
                        throw new Exception\InvalidRoute("Invalid option $opt for pattern $pattern");
                }
            }
        }
        return $route;
    }

    /**
     * Returns the current route list. Useful for backing up a dynamicly
     * built route list that is perhaps kept in a database. This could be used
     * to cache the route list.
     *
     * @return array
     */
    public function getRoutes(): array {
        return $this->routes;
    }

    /**
     * Matches a request path to a route
     *
     * @param  string  $request_path  Request path to match
     * @param  array   $routes        Array of routes
     *
     * @return array  Empty array if no matching route is found. Route array on success.
     */
    public function match(?string $request_path = null, ?array $routes = null, ?array $server = null, ?array $headers = null): array {

        if ($request_path === null) {
            $request_path = parse_url($_SERVER["REQUEST_URI"], PHP_URL_PATH);
        }

        if ($routes === null) {
            $routes = $this->routes;
        }

        if ($server === null) {
            $server = $_SERVER;
        }

        if ($headers === null) {
            $headers = $this->getHeaders($server);
        }

        $route = [];

        $default_route = false;

        foreach ($routes as $possible_route) {

            if (empty($possible_route["type"])) {
                throw new Exception\InvalidRoute("No type set for route", 1);
            }

            if (!empty($possible_route["action"]) && !empty($possible_route["routes"])) {
                throw new Exception\InvalidRoute("Routes should include an action or routes, but not both", 2);
            }

            if (empty($possible_route["action"]) && empty($possible_route["routes"])) {
                throw new Exception\InvalidRoute("Routes must include an action or routes", 3);
            }

            if ($possible_route["type"] == "default") {
                if (!empty($default_route)) {
                    throw new Exception\InvalidRoute("Multiple default routes defined", 4);
                }
                $default_route = $possible_route;
            } else{

                if (empty($possible_route["pattern"])) {
                    throw new Exception\InvalidRoute("No pattern set for route", 5);
                }

                $matched_route = $this->matchRoute($possible_route, $request_path, $server, $headers);

                if ($matched_route) {

                    // If we have sub routes, use them to match
                    if (!empty($matched_route["routes"])) {
                        $matched_route = $this->match(
                            $request_path,
                            $matched_route["routes"],
                            $server,
                            $headers
                        );
                    }

                    if ($matched_route) {
                        $route = $matched_route;
                        break;
                    }
                }
            }
        }

        if (empty($route) && !empty($default_route)) {
            $route = $default_route;
        }

        return $route;
    }

    /**
     * Determines if a route matches the request
     *
     * @param  array  $route        Route config array
     * @param  string $request_path Request path to match
     *
     * @return array  Empty array if there is no match, the route array
     *                if there is a match.
     */
    public function matchRoute($route, $request_path, $server, $headers) {

        ($route = $this->matchPath($route, $request_path)) &&
        ($route = $this->matchMethod($route, $server)) &&
        ($route = $this->matchHeaders($route, $headers)) &&
        ($route = $this->matchAccept($route, $headers)) &&
        ($route = $this->matchHost($route, $server));

        return $route;
    }

    /**
     * Determines if a route config matches the request method
     *
     * @param  array  $route  Route array
     * @param  array  $server Array of server variables. e.g. $_SERVER
     *
     * @return array  Empty array if there is no match, the route array with the
     *                method value filled in if there is a match
     */
    public function matchMethod(array $route, array $server): array {
        if (!empty($route["method"])) {
            $result = $this->checkMatch(
                $route["method"],
                $server["REQUEST_METHOD"]
            );
            if ($result !== false) {
                $route["method"] = $server["REQUEST_METHOD"];
            } else {
                $route = [];
            }
        } elseif (!empty($server["REQUEST_METHOD"])) {
            $route["method"] = $server["REQUEST_METHOD"];
        }
        return $route;
    }

    /**
     * Determines if a route config matches the request's Accept header
     *
     * @param  array  $route  Route array
     * @param  array  $server Array of server variables. e.g. $_SERVER
     *
     * @return array  Empty array if there is no match, the route array with the
     *                preferred mime typefilled in if there is a match
     */
    public function matchAccept(array $route, array $server): array {

        static $accept;

        if (!empty($route["accept"])) {

            if (is_string($route["accept"])) {
                $route["accept"] = [$route["accept"]];
            } elseif (!is_array($route["accept"])) {
                throw new Exception\InvalidMatchType("Invalid accept list. Must be a single mime type or an array of mime types.", 10);
            }

            if (empty($accept)) {
                $accept = new Accept();
            }

            $chosen_mime_type = $accept->determine($route["accept"], $server);

            if ($chosen_mime_type !== false) {
                $route["accept"] = $chosen_mime_type;
            } else {
                $route = [];
            }
        }
        return $route;
    }

    /**
     * Determines if a route config matches the request host
     *
     * @param  array  $route  Route array
     * @param  array  $server Array of server variables. e.g. $_SERVER
     *
     * @return array  Empty array if there is no match, the route array with the
     *                host value filled in if there is a match
     */
    public function matchHost(array $route, array $server): array {
        if (!empty($route["host"])) {
            $result = $this->checkMatch(
                $route["host"],
                $server["HTTP_HOST"]
            );
            if ($result !== false) {
                $route["host"] = $server["HTTP_HOST"];
            } else {
                $route = [];
            }
        }

        return $route;
    }

    /**
     * Determines if a route config matches any number of headers
     *
     * @param  array  $route   Route array
     * @param  array  $headers Array of HTTP headers and values
     *
     * @return array  Empty array if there is no match, the route array with the
     *                headers value filled in with the matching headers if
     *                there is a match
     */
    public function matchHeaders(array $route, array $headers): array {
        if (!empty($route["headers"])) {
            $resp = [];

            // RFC 2616 states that headers are case insensitive
            foreach ($headers as $header => $value) {
                $lower_header = strtolower($header);
                if (!array_key_exists($lower_header, $headers)) {
                    $headers[$lower_header] = $value;
                    unset($headers[$header]);
                }
            }

            foreach ($route["headers"] as $header => $pattern) {
                $result = false;
                $lower_header = strtolower($header);
                if (isset($headers[$lower_header])) {
                    $result = $this->checkMatch(
                        $pattern,
                        $headers[$lower_header]
                    );
                }
                if ($result !== false) {
                    $resp[$header] = $headers[$lower_header];
                } else {
                    $route = [];
                    break;
                }
            }
            if ($route !== []) {
                $route["headers"] = $resp;
            }
        }

        return $route;
    }


    /**
     * Determines if a route matches the request path
     *
     * @param  array  $route        Route config array
     * @param  string $request_path Request path to match
     *
     * @return array  Empty array on error. Route array with tokens on success
     */
    public function matchPath(array $route, string $request_path): array {

        $tokens = $this->checkMatch($route, $request_path);

        if ($tokens !== false) {
            if (is_string($tokens)) {
                $tokens = trim($tokens, "/");
                $tokens = explode("/", $tokens);
            }
            if (!empty($route["tokens"])) {
                if (count($tokens) == count($route["tokens"])) {
                    $new_arr = [];
                    foreach ($route["tokens"] as $key => $name) {
                        $new_arr[$name] = $tokens[$key];
                    }
                    $route["tokens"] = $new_arr;
                } else {
                    $route = [];
                }
            } else {
                $route["tokens"] = $tokens;
            }
        } else {
            $route = [];
        }

        return $route;
    }

    /**
     * Determines if a route matches the request path
     *
     * @param  mixed  $match_plan   Array with a type and pattern
     * @param  string $match_target Value to match the plain against
     *
     * @return mixed  False if it does not match. Array on success
     */
    public function checkMatch($match_plan, string $match_target) {

        static $pattern;

        $tokens = false;

        if (is_scalar($match_plan)) {
            if ($match_plan == $match_target) {
                $tokens = [];
            }
        } elseif (is_array($match_plan)) {
            $first_key = key($match_plan);
            if (is_numeric($first_key)) {
                if (in_array($match_target, $match_plan)) {
                    $tokens = [];
                }
            } elseif (!empty($match_plan["type"]) && !empty($match_plan["pattern"])) {
                if (empty($pattern)) {
                    $pattern = new Pattern();
                }
                try {
                    $tokens = $pattern->match(
                        $match_plan["type"],
                        [$match_plan["pattern"]],
                        $match_target
                    );
                    if ($tokens === true) {
                        $tokens = [];
                    }
                } catch (\PageMill\Pattern\Exception\InvalidType $e) {
                    throw new Exception\InvalidMatchType("Invalid match plan");
                } catch (\PageMill\Pattern\Exception\InvalidPattern $e) {
                    throw new Exception\InvalidPattern("Invalid regex {$match_plan["pattern"]}");
                }
            } else {
                throw new Exception\InvalidMatchType("Invalid match plan");
            }
        } else {
            throw new Exception\InvalidMatchType("Invalid match plan");
        }

        return $tokens;
    }

    /**
     * Returns the headers from the request
     *
     * @param  array $server Array such as the $_SERVER array
     *
     * @return array         Array of headers and values
     */
    public function getHeaders(array $server): array {
        $headers = [];
        if (function_exists("apache_request_headers")) {
            $headers = apache_request_headers();
        } else {
            // try and parse the headers from the server var. PHP creates
            // some headers as variables in this array prefixed with HTTP_.
            foreach ($server as $var => $value) {
                if (strpos($var, "HTTP_") === 0) {
                    $header = substr($var, 5);
                    $headers[$header] = $value;
                }
            }
        }
        return $headers;
    }
}
