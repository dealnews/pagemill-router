<?php

namespace PageMill\Router;

class NormalizeTest extends \PHPUnit\Framework\TestCase {

    public function testPathDirectoryIndex() {
        $request_path = "/foo/bar/index.html";
        $request_path = Normalize::directory_index(
            $request_path,
            array(
                "index.html"
            )
        );
        $this->assertEquals(
            "/foo/bar/",
            $request_path
        );
    }

    public function testPathPrefix() {
        $request_path = "/foo/bar/index.html";
        $request_path = Normalize::prefix(
            $request_path,
            array(
                "/foo"
            )
        );
        $this->assertEquals(
            "/bar/index.html",
            $request_path
        );

        $request_path = "/bar/foo/index.html";
        $request_path = Normalize::prefix(
            $request_path,
            array(
                "/foo"
            )
        );
        $this->assertEquals(
            "/bar/foo/index.html",
            $request_path
        );
    }

    public function testEndingSlash() {
        $request_path = "/foo/bar";
        $request_path = Normalize::ending_slash(
            $request_path
        );
        $this->assertEquals(
            "/foo/bar/",
            $request_path
        );
    }

    public function testEndingSlashExcludes() {
        $request_path = "/foo/bar/baz";
        $request_path = Normalize::ending_slash(
            $request_path,
            array(
                "!^/foo/bar/baz!"
            )
        );
        $this->assertEquals(
            "/foo/bar/baz",
            $request_path
        );
    }
}
