<?php

namespace App\Support;

class TlsCaBundle
{
    /**
     * Resolve a configured CA bundle without ever passing a missing file to Guzzle.
     *
     * Paths such as /vendor/... are commonly intended to be relative to the
     * Laravel application, but PHP interprets them from the filesystem root.
     */
    public static function resolve(mixed $configured, bool $default = true): bool|string
    {
        if (is_bool($configured)) {
            return $configured;
        }

        $path = trim((string) $configured);
        if ($path === '') {
            return $default;
        }

        $candidates = [$path, base_path(ltrim($path, '/\\'))];
        foreach (array_unique($candidates) as $candidate) {
            $resolved = realpath($candidate);
            if ($resolved !== false && is_file($resolved) && is_readable($resolved)) {
                return $resolved;
            }
        }

        // Let cURL/OpenSSL use the operating system trust store. Returning false
        // here would hide a deployment error by disabling certificate checks.
        return $default;
    }
}
