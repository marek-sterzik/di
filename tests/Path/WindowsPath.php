<?php

namespace Tests\Path;

use Sterzik\DI\Path;

class WindowsPath extends Path
{
    public static function getRoot(): string
    {
        return 'C:\\root';
    }

    public static function isWindowsPlatform(): bool
    {
        return true;
    }

    public static function separator(): string
    {
        return '\\';
    }
}
