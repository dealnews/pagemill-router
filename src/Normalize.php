<?php

namespace PageMill\Router;

/**
 * Request Path Normalization
 *
 * This class provides several helper functions for normalizing request
 * paths before attempting to match the route.
 *
 * These can be important for SEO reasons. Search engines see example.com/foo
 * and example.com/foo/ as two different URLs and may penalize them. It can
 * also make other analytics work harder.
 *
 * If the normalized path is not the same as the original path, a redirect
 * could be issued. An alternative would be to use a canonical URL in the
 * response via either HTTP headers or an HTML link tag.
 */

class Normalize {

    /**
     * Ensures the request path has an ending slash if it appears to be a directory
     * request.
     *
     * @param  string $request_path  URL path.
     * @param  array  $excludes      Array of paths to exclude because sometimes
     *                               we do weird stuff.
     *
     * @return void
     */
    public static function ending_slash($request_path, array $excludes = array()) {

        foreach ($excludes as $regex) {
            if (preg_match($regex, $request_path)) {
                return $request_path;
            }
        }

        $base = basename($request_path);

        if (substr($request_path, -1) != "/" && strpos($base, ".") === false && substr($base, -1) != "/") {
            $request_path .= "/";
        }

        return $request_path;
    }


    /**
     * Normalizes the path. This can be used to remove file names like
     * index.html or add them. Also, any prefixes of the URL path that should
     * be removed such as a sub-directory common to all paths.
     *
     * @param  string $request_path     URL path.
     * @param  array  $directory_index  Array of file names to be removed from
     *                                  the end of paths leaving only a / at
     *                                  the end of the path.
     *
     * @return string
     */
    public static function directory_index($request_path, array $directory_index) {

        if (!empty($directory_index)) {
            foreach ($directory_index as $path) {
                $len = strlen($path);
                if (substr($request_path, -1 * ($len+1)) == "/".$path) {
                    $request_path = substr($request_path, 0, -1 * $len);
                    break;
                }
            }
        }

        return $request_path;
    }

    /**
     * Normalizes the path. This can be used to remove file names like
     * index.html or add them. Also, any prefixes of the URL path that should
     * be removed such as a sub-directory common to all paths.
     *
     * @param  string $request_path     URL path.
     * @param  array  $prefixes         Array of strings to be removed from the
     *                                  beginning of the request path. Useful
     *                                  for removing parts of the path common
     *                                  to all routes.
     *
     * @return string
     */
    public static function prefix($request_path, array $prefixes) {

        if (!empty($prefixes)) {
            foreach ($prefixes as $path) {
                $len = strlen($path);
                if (substr($request_path, 0, $len) == $path) {
                    $request_path = substr($request_path, $len);
                    break;
                }
            }
        }

        return $request_path;
    }
}
