<?php

namespace Sterzik\DI;

use Composer\InstalledVersions;

class Path
{
    final public static function resolve(string $path): string
    {
        if ($path === "" || static::isAbsolute($path)) {
            return $path;
        }
        $rootDir = static::getRoot();
        return $rootDir . static::separator() . $path;
    }

    final public static function isAbsolute(string $path): bool
    {
        if (substr($path, 0, 1) === static::separator()) {
            return true;
        }
        if (static::isWindowsPlatform() && preg_match('/[A-Za-z]/', substr($path, 0, 1))) {
            $part = substr($path, 1, 2);
            if ($part === ":" . static::separator() || $part === ":/") {
                return true;
            }
        }
        return false;
    }

    public static function getRoot(): string
    {
        $path = InstalledVersions::getRootPackage()['install_path'];
        return realpath($path) ?: $path;
    }

    public static function isWindowsPlatform(): bool
    {
        return (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') ? true : false;
    }

    public static function separator(): string
    {
        return DIRECTORY_SEPARATOR;
    }
}
