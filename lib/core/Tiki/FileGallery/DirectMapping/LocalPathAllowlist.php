<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
namespace Tiki\FileGallery\DirectMapping;

use Tiki\Filesystem\PathAllowlist;

/**
 * Local direct-mapping roots. Deny-by-default unless fgal_direct_mapping_allowed_paths is set.
 */
class LocalPathAllowlist
{
    private PathAllowlist $allowlist;

    /**
     * @param mixed $allowedPathsPreference Raw preference value (string CSV, serialized array, or array)
     */
    public function __construct(mixed $allowedPathsPreference = null)
    {
        $this->allowlist = new PathAllowlist($allowedPathsPreference);
    }

    public static function fromPreferences(): self
    {
        global $prefs;

        $value = $prefs['fgal_direct_mapping_allowed_paths'] ?? null;
        if ($value === null) {
            $value = \TikiLib::lib('tiki')->get_preference('fgal_direct_mapping_allowed_paths', '');
        }

        return new self($value);
    }

    /**
     * @return string Canonical absolute directory path
     */
    public function resolveLocalRoot(string $requestedPath): string
    {
        if ($requestedPath === '' || str_contains($requestedPath, "\0")) {
            throw new InvalidLocalPathException(tr('Invalid local direct mapping path.'));
        }

        if ($requestedPath[0] !== '/' && ! preg_match('#^[A-Za-z]:[\\\\/]#', $requestedPath)) {
            throw new InvalidLocalPathException(tr(
                'Local direct mapping path must be an absolute filesystem path: %0',
                $requestedPath
            ));
        }

        if (! $this->allowlist->isConfigured()) {
            throw new InvalidLocalPathException(tr(
                'Local direct mapping is not allowed: no allowed roots are configured. Ask your server administrator to set preference fgal_direct_mapping_allowed_paths via system configuration.'
            ));
        }

        $resolvedPath = $this->allowlist->resolvePath($requestedPath);
        if ($resolvedPath === false || ! is_dir($resolvedPath)) {
            throw new InvalidLocalPathException(tr(
                'Local direct mapping path is outside allowed roots or is not a directory. Ask your server administrator to update preference fgal_direct_mapping_allowed_paths.'
            ));
        }

        return rtrim($resolvedPath, '/\\');
    }

    public function assertResolvedPathWithinRoot(string $root, string $relativePath): void
    {
        if (! PathAllowlist::isResolvedPathWithinRoot($root, $relativePath)) {
            throw new InvalidLocalPathException(tr('Access denied: path resolves outside the direct mapping root.'));
        }
    }

    /**
     * @param array<string, mixed> $directConfig
     */
    public function validateDirectConfig(array $directConfig): void
    {
        if (($directConfig['adapter'] ?? '') !== 'local') {
            return;
        }

        $path = $directConfig['path'] ?? '';
        if (! is_string($path) || $path === '') {
            throw new InvalidLocalPathException(tr('Local filesystem path is required for direct mapping.'));
        }

        $this->resolveLocalRoot($path);
    }
}
