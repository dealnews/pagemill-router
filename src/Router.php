<?php

namespace PageMill\Router;

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
     */
    protected $routes;

    /**
     * Creates a new Router
     *
     * @param  string $app_base Directory of the app files
     * @param  array  $routes   Array of routes to map the request too
     *
     * @return object
     */
    public function __construct(array $routes = array()) {

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
    public function add($type, $pattern, $action, array $options = array()) {
        $options["action"] = $action;
        $route = $this->create_route($type, $pattern, $options);
        $this->routes[] = $route;
    }

    /**
     * Adds a route to the route list which contains a list of sub-routes
     * to be used to find the final matching route.
     *
     * @param string $type    Matching type. One of exact, regex, starts_with,
     *                        or default. There should be only one route with
     *                        the type default.
     * @param string $pattern Either a string or a regular expression
     * @param array  $routes  Array of routes
     * @param array  $options Array of additional options for the route matching
     */
    public function add_map($type, $pattern, array $routes, array $options = array()) {
        $sub_routes = array();
        foreach ($routes as $route) {
            if (isset($route["type"])) {
                $route_type = $route["type"];
                unset($route["type"]);
            } else {
                $route_type = $type;
            }
            if (isset($route["pattern"])) {
                $route_pattern = $route["pattern"];
                unset($route["pattern"]);
            } else {
                $route_pattern = $pattern;
            }
            $sub_routes[] = $this->create_route($route_type, $route_pattern, $route);
        }

        $options["routes"] = $sub_routes;
        $route = $this->create_route($type, $pattern, $options);
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
     */
    public function create_route($type, $pattern, array $options = array()) {
        $route = array(
            "type" => $type,
            "pattern" => $pattern
        );
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
    public function get_routes() {
        return $this->routes;
    }

    /**
     * Matches a request path to a route
     *
     * @param  string  $request_path  Request path to match
     * @param  array   $routes        Array of routes
     *
     * @return mixed  False on error. Route array on success
     */
    public function match($request_path = null, array $routes = null, array $server = null, array $headers = null) {

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
            if (function_exists("apache_request_headers")) {
                $headers = apache_request_headers();
            } else {
                $headers = array();
            }
        }

        $route = false;

        $default_route = false;

        foreach ($routes as $possible_route) {

            if (empty($possible_route["type"])) {
                throw new Exception\InvalidRoute("No type set for route");
            }

            if (!empty($possible_route["action"]) && !empty($possible_route["routes"])) {
                throw new Exception\InvalidRoute("Routes should include an action or routes, but not both");
            }

            if (empty($possible_route["action"]) && empty($possible_route["routes"])) {
                throw new Exception\InvalidRoute("Routes must include an action or routes");
            }

            if ($possible_route["type"] == "default") {
                if (!empty($default_route)) {
                    throw new Exception\InvalidRoute("Multiple default routes defined");
                }
                $default_route = $possible_route;
            } else{

                if (empty($possible_route["pattern"])) {
                    throw new Exception\InvalidRoute("No pattern set for route");
                }

                $matched_route = $this->match_route($possible_route, $request_path, $server, $headers);

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
     * @return mixed  False on error. Route array with tokens on success
     */
    public function match_route($route, $request_path, $server, $headers) {

        ($route = $this->match_path($route, $request_path)) &&
        ($route = $this->match_method($route, $server)) &&
        ($route = $this->match_headers($route, $headers)) &&
        ($route = $this->match_accept($route, $headers)) &&
        ($route = $this->match_host($route, $server));

        return $route;
    }

    /**
     * Determines if a route config matches the request method
     *
     * @param  array  $route  Route array
     * @param  array  $server Array of server variables. e.g. $_SERVER
     *
     * @return mixed  False if there is not match, the route array with the
     *                method value filled in if there is a match
     */
    public function match_method($route, $server) {
        if (!empty($route["method"])) {
            $result = $this->check_match(
                $route["method"],
                $server["REQUEST_METHOD"]
            );
            if ($result !== false) {
                $route["method"] = $server["REQUEST_METHOD"];
            } else {
                $route = false;
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
     * @return mixed  False if there is not match, the route array with the
     *                preferred mime typefilled in if there is a match
     */
    public function match_accept($route, $server) {
        if (!empty($route["accept"])) {

            if (is_string($route["accept"])) {
                $route["accept"] = [$route["accept"]];
            } elseif (!is_array($route["accept"])) {
                throw new Exception\InvalidMatchType("Invalid accept list. Must be a single mime type or an array of mime types.");
            }

            $chosen_mime_type = false;

            // RFC 2616 states that if there is no Accept header is provided
            // that any type should be returned
            if (!isset($server["HTTP_ACCEPT"])) {
                $server["HTTP_ACCEPT"] = "*/*";
            }

            // Parse the Accept header
            // see https://www.w3.org/Protocols/rfc2616/rfc2616-sec14.html
            $accept_mime_types = explode(",", $server["HTTP_ACCEPT"]);
            $mime_type_preference = [];
            foreach ($accept_mime_types as $mime_type) {
                $mime_type = trim($mime_type);
                $preference = 1.0;
                if (preg_match("/(.+?);q=(1|1.0|0.\d+)$/", $mime_type, $match)) {
                    $preference = (float)$match[2];
                    $mime_type = $match[1];
                }
                $mime_type_preference[$mime_type] = $preference;
            }

            // match the accept list against the Accept header
            $preferred_mime_types = [];
            foreach ($route["accept"] as $mime_type) {
                foreach ($mime_type_preference as $mime_type_pattern => $quality) {
                    $mime_type_pattern = strtolower($mime_type_pattern);
                    // if the pattern from the Accept header contains a *,
                    // convert this to a regex match
                    if (strpos($mime_type_pattern, "*") !== false) {
                        $mime_type_pattern = [
                            "type" => "regex",
                            "pattern" => '|^'.str_replace($mime_type_pattern, "*", ".+").'$|'
                        ];
                    }

                    $result = $this->check_match(
                        $mime_type_pattern,
                        strtolower($mime_type)
                    );
                    if ($result !== false) {
                        $preferred_mime_types[$mime_type] = $quality;
                    }
                }
            }

            if (!empty($preferred_mime_types)) {
                uasort($preferred_mime_types, function($a, $b) {
                    if ($a == $b) {
                        return 0;
                    }
                    return ($a > $b) ? -1 : 1;
                });
                $route["accept"] = key($preferred_mime_types);
            } else {
                $route = false;
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
     * @return mixed  False if there is not match, the route array with the
     *                host value filled in if there is a match
     */
    public function match_host($route, $server) {
        if (!empty($route["host"])) {
            $result = $this->check_match(
                $route["host"],
                $server["HTTP_HOST"]
            );
            if ($result !== false) {
                $route["host"] = $server["HTTP_HOST"];
            } else {
                $route = false;
            }
        }
        $this->host_matched = ($route !== false);
        return $route;
    }

    /**
     * Determines if a route config matches any number of headers
     *
     * @param  array  $route   Route array
     * @param  array  $headers Array of HTTP headers and values
     *
     * @return mixed  False if there is not match, the route array with the
     *                headers value filled in with the matching headers if
     *                there is a match
     */
    public function match_headers($route, $headers) {
        if (!empty($route["headers"])) {
            $resp = array();
            foreach ($route["headers"] as $header => $pattern) {
                $result = false;
                if (isset($headers[$header])) {
                    $result = $this->check_match(
                        $pattern,
                        $headers[$header]
                    );
                }
                if ($result !== false) {
                    $resp[$header] = $headers[$header];
                } else {
                    $route = false;
                    break;
                }
            }
            if ($route !== false) {
                $route["headers"] = $resp;
            }
        }
        $this->headers_matched = ($route !== false);
        return $route;
    }


    /**
     * Determines if a route matches the request path
     *
     * @param  array  $route        Route config array
     * @param  string $request_path Request path to match
     *
     * @return mixed  False on error. Route array with tokens on success
     */
    public function match_path($route, $request_path) {

        $tokens = $this->check_match($route, $request_path);

        if ($tokens !== false) {
            if (is_string($tokens)) {
                $tokens = trim($tokens, "/");
                $tokens = explode("/", $tokens);
            }
            if (!empty($route["tokens"])) {
                if (count($tokens) == count($route["tokens"])) {
                    $new_arr = array();
                    foreach ($route["tokens"] as $key => $name) {
                        $new_arr[$name] = $tokens[$key];
                    }
                    $route["tokens"] = $new_arr;
                } else {
                    $route = false;
                }
            } else {
                $route["tokens"] = $tokens;
            }
        } else {
            $route = false;
        }

        $this->path_matched = ($route !== false);

        return $route;
    }

    /**
     * Determines if a route matches the request path
     *
     * @param  array  $route        Route config array
     * @param  string $request_path Request path to match
     *
     * @return mixed  False on error. Route array with tokens on success
     */
    public function check_match($match_plan, $match_target) {

        $tokens = false;

        if (is_scalar($match_plan)) {
            if ($match_plan == $match_target) {
                $tokens = array();
            }
        } elseif (is_array($match_plan)) {
            $first_key = key($match_plan);
            if (is_numeric($first_key)) {
                if (in_array($match_target, $match_plan)) {
                    $tokens = array();
                }
            } elseif (!empty($match_plan["type"]) && !empty($match_plan["pattern"])) {
                switch ($match_plan["type"]) {
                    case "exact":
                        if ($match_plan["pattern"] == $match_target) {
                            $tokens = array();
                        }
                        break;
                    case "regex":
                        $result = preg_match($match_plan["pattern"], $match_target, $matches);
                        if ($result === false) {
                            throw new Exception\InvalidPattern("Invalid regex {$match_plan["pattern"]}");
                        } elseif ($result) {
                            if (!empty($matches[1])) {
                                unset($matches[0]);
                                $tokens = array_values($matches);
                            } else{
                                $tokens = array();
                            }
                        }
                        break;
                    case "starts_with":
                        if (strpos($match_target, $match_plan["pattern"]) === 0) {
                            if ($match_plan["pattern"] !== $match_target) {
                                $tokens = substr($match_target, strlen($match_plan["pattern"]));
                            } else {
                                $tokens = array();
                            }
                        }
                        break;
                    default:
                        throw new Exception\InvalidMatchType("Invalid type {$match_plan["type"]}");
                }
            } else {
                throw new Exception\InvalidMatchType("Invalid match plan");
            }
        } else {
            throw new Exception\InvalidMatchType("Invalid match plan");
        }

        return $tokens;
    }
}
