<?php

namespace Tests\Suite;

use Tests\Helper;
use Tests\Path\UnixPath;
use Tests\Path\WindowsPath;

class PathTest extends AbstractTestCase
{
    public function testAbsolutePathUnix()
    {
        $this->assertSame("/root/test/x", UnixPath::resolve("test/x"));
        $this->assertSame("/test/x", UnixPath::resolve("/test/x"));
    }

    public function testAbsolutePathWindows()
    {
        $this->assertSame("C:\\root\\test/x", WindowsPath::resolve("test/x"));
        $this->assertSame("\\test/x", WindowsPath::resolve("\\test/x"));
        $this->assertSame("c:\\test/x", WindowsPath::resolve("c:\\test/x"));
    }
}
