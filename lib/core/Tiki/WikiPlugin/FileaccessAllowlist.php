<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
namespace Tiki\WikiPlugin;

use Tiki\Filesystem\PathAllowlist;

/**
 * Allowlist enforcement for filesystem wiki plugins (LSDIR, LOCALFILES).
 *
 * Initialized from preference wikiplugin_fileaccess_allowed_paths (deny-by-default).
 */
class FileaccessAllowlist
{
    private PathAllowlist $allowlist;

    /**
     * @param mixed $allowedPathsPreference Raw preference value (string CSV, serialized array, or array)
     */
    public function __construct(mixed $allowedPathsPreference = null)
    {
        $this->allowlist = new PathAllowlist($allowedPathsPreference);
    }

    public static function fromPreference(): self
    {
        return new self(\TikiLib::lib('tiki')->get_preference('wikiplugin_fileaccess_allowed_paths', ''));
    }

    public function isConfigured(): bool
    {
        return $this->allowlist->isConfigured();
    }

    /**
     * @param string $reason no_roots|outside_path|outside_dir
     */
    public function getDeniedHtml(string $reason): string
    {
        $message = match ($reason) {
            'no_roots' => tra('Access denied: no allowed roots are configured. Ask your server administrator to set preference wikiplugin_fileaccess_allowed_paths.'),
            'outside_path' => tra('Access denied: path is outside allowed roots. Ask your server administrator to update preference wikiplugin_fileaccess_allowed_paths.'),
            'outside_dir' => tra('Access denied: directory is outside allowed roots. Ask your server administrator to update preference wikiplugin_fileaccess_allowed_paths.'),
            default => tra('Access denied.'),
        };

        return "<span class='attention'>" . $message . '</span>';
    }

    /**
     * @return string|false Canonical path when allowed, false otherwise
     */
    public function resolvePath(string $requestedPath): string|false
    {
        return $this->allowlist->resolvePath($requestedPath);
    }

    /**
     * @param list<string> $candidates Paths to try (e.g. relative and DOCUMENT_ROOT-prefixed)
     * @return string|false Canonical path when allowed, false otherwise
     */
    public function resolvePathFromCandidates(array $candidates): string|false
    {
        return $this->allowlist->resolvePathFromCandidates($candidates);
    }
}
