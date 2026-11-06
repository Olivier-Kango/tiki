<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
namespace Tiki\Filesystem;

/**
 * Canonical filesystem allowlist. Deny-by-default when no roots are configured.
 */
class PathAllowlist
{
    /** @var list<string> Canonical absolute roots */
    private array $allowedRoots;

    /**
     * @param mixed $allowedPathsPreference Raw preference value (string CSV, serialized array, or array)
     */
    public function __construct(mixed $allowedPathsPreference = null)
    {
        $this->allowedRoots = $this->buildAllowedRoots($allowedPathsPreference);
    }

    public function isConfigured(): bool
    {
        return $this->allowedRoots !== [];
    }

    /**
     * @return list<string>
     */
    public function getAllowedRoots(): array
    {
        return $this->allowedRoots;
    }

    /**
     * @return string|false Canonical path when allowed, false otherwise
     */
    public function resolvePath(string $requestedPath): string|false
    {
        if ($this->allowedRoots === [] || $requestedPath === '' || str_contains($requestedPath, "\0")) {
            return false;
        }

        $resolvedPath = realpath($requestedPath);
        if ($resolvedPath === false || ! $this->isWithinAllowedRoots($resolvedPath)) {
            return false;
        }

        return $resolvedPath;
    }

    /**
     * @param list<string> $candidates
     * @return string|false
     */
    public function resolvePathFromCandidates(array $candidates): string|false
    {
        if ($this->allowedRoots === []) {
            return false;
        }

        foreach ($candidates as $candidate) {
            if (! is_string($candidate) || $candidate === '') {
                continue;
            }

            $resolved = $this->resolvePath($candidate);
            if ($resolved !== false) {
                return $resolved;
            }
        }

        return false;
    }

    public function isWithinAllowedRoots(string $path): bool
    {
        $path = rtrim($path, '/\\');
        foreach ($this->allowedRoots as $root) {
            $rootWithSep = $root . DIRECTORY_SEPARATOR;
            if ($path === $root || str_starts_with($path, $rootWithSep)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Walk each path segment so a symlink cannot escape $root, including when the
     * final file does not exist yet (new writes).
     */
    public static function isResolvedPathWithinRoot(string $root, string $relativePath): bool
    {
        $root = rtrim($root, '/\\');
        if ($root === '' || $relativePath === '' || str_contains($relativePath, "\0")) {
            return false;
        }

        $normalized = str_replace(['\\', '/'], DIRECTORY_SEPARATOR, $relativePath);
        $segments = array_values(array_filter(explode(DIRECTORY_SEPARATOR, $normalized), fn ($s) => $s !== ''));
        $current = $root;

        foreach ($segments as $segment) {
            if ($segment === '.') {
                continue;
            }
            if ($segment === '..') {
                return false;
            }

            $current .= DIRECTORY_SEPARATOR . $segment;
            if (is_link($current)) {
                $resolved = realpath($current);
                if ($resolved === false || ! self::isPathInsideRoot($root, $resolved)) {
                    return false;
                }
                $current = $resolved;
            }
        }

        if (file_exists($current)) {
            $resolved = realpath($current);
            return $resolved !== false && self::isPathInsideRoot($root, $resolved);
        }

        return self::isPathInsideRoot($root, $current);
    }

    private static function isPathInsideRoot(string $root, string $path): bool
    {
        $root = rtrim($root, '/\\');
        $path = rtrim($path, '/\\');
        return $path === $root || str_starts_with($path, $root . DIRECTORY_SEPARATOR);
    }

    /**
     * @return list<string>
     */
    private function parsePreferenceValue(mixed $allowedPathsPreference): array
    {
        if (is_array($allowedPathsPreference)) {
            return $allowedPathsPreference;
        }

        if (! is_string($allowedPathsPreference) || $allowedPathsPreference === '') {
            return [];
        }

        $unserialized = @unserialize($allowedPathsPreference, ['allowed_classes' => false]);
        if (is_array($unserialized)) {
            return $unserialized;
        }

        return preg_split('/\s*,\s*/', $allowedPathsPreference, -1, PREG_SPLIT_NO_EMPTY);
    }

    /**
     * @return list<string>
     */
    private function buildAllowedRoots(mixed $allowedPathsPreference): array
    {
        $allowedRoots = [];
        foreach ($this->parsePreferenceValue($allowedPathsPreference) as $root) {
            if (! is_string($root) || $root === '') {
                continue;
            }

            $resolvedRoot = realpath($root);
            if ($resolvedRoot !== false) {
                $allowedRoots[] = rtrim($resolvedRoot, '/\\');
            }
        }

        return array_values(array_unique($allowedRoots));
    }
}
