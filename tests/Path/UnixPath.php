<?php

namespace Tests\Path;

use Sterzik\DI\Path;

class UnixPath extends Path
{
    public static function getRoot(): string
    {
        return '/root';
    }

    public static function isWindowsPlatform(): bool
    {
        return false;
    }

    public static function separator(): string
    {
        return '/';
    }
}
