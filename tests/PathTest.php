<?php

namespace Tests\Suite;

use Sterzik\DI\Path;
use Tests\Helper;
use Tests\Path\UnixPath;
use Tests\Path\WindowsPath;

class PathTest extends AbstractTestCase
{
    public function testAbsolutePathUnix(): void
    {
        $this->assertSame("/root/test/x", UnixPath::resolve("test/x"));
        $this->assertSame("/test/x", UnixPath::resolve("/test/x"));
    }

    public function testAbsolutePathWindows(): void
    {
        $this->assertSame("C:\\root\\test/x", WindowsPath::resolve("test/x"));
        $this->assertSame("\\test/x", WindowsPath::resolve("\\test/x"));
        $this->assertSame("c:\\test/x", WindowsPath::resolve("c:\\test/x"));
    }

    public function testRootPathDetection(): void
    {
        $this->assertSame(dirname(__DIR__), Path::getRoot());
    }
}
