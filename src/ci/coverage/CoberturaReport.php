<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.

namespace TikiDevTools\Coverage;

use DOMDocument;
use DOMXPath;
use RuntimeException;

/** Validate artifact paths before the engine can silently omit measured files. */
final class CoberturaReport
{
    public static function normalize(string $input, string $output, string $root, array $tracked): void
    {
        if (! is_file($input)) {
            throw new RuntimeException('Coverage artifact is missing');
        }
        $xml = new DOMDocument();
        $xml->resolveExternals = false;
        $xml->substituteEntities = false;
        $previous = libxml_use_internal_errors(true);
        try {
            if (! $xml->load($input, LIBXML_NONET) || $xml->documentElement->tagName !== 'coverage') {
                throw new RuntimeException('Invalid Cobertura report');
            }
            if ($xml->doctype !== null) {
                $doctype = $xml->doctype;
                if (
                    $doctype->name !== 'coverage' || trim($doctype->internalSubset ?? '') !== ''
                    || ! in_array($doctype->systemId, [
                        'http://cobertura.sourceforge.net/xml/coverage-04.dtd',
                        'https://cobertura.sourceforge.net/xml/coverage-04.dtd',
                    ], true)
                ) {
                    throw new RuntimeException('Unsafe Cobertura document type');
                }
                $xml->removeChild($doctype);
            }
        } finally {
            libxml_clear_errors();
            libxml_use_internal_errors($previous);
        }
        $xpath = new DOMXPath($xml);
        $sources = [];
        foreach ($xpath->query('/coverage/sources/source') as $source) {
            $sources[] = rtrim(str_replace('\\', '/', trim($source->textContent)), '/');
        }
        $byBasename = [];
        foreach ($tracked as $path) {
            $byBasename[basename($path)][] = $path;
        }
        $classes = $xpath->query('//class');
        if ($classes->length === 0) {
            throw new RuntimeException('Coverage artifact contains no source files');
        }
        foreach ($classes as $class) {
            $filename = str_replace('\\', '/', $class->getAttribute('filename'));
            $candidates = [$filename];
            foreach ($sources as $source) {
                $candidates[] = $source . '/' . $filename;
            }
            $matches = [];
            foreach ($candidates as $candidate) {
                if (in_array('..', explode('/', $candidate), true)) {
                    continue;
                }
                if (str_starts_with($candidate, $root . '/')) {
                    $candidate = substr($candidate, strlen($root) + 1);
                }
                while (str_starts_with($candidate, './')) {
                    $candidate = substr($candidate, 2);
                }
                // Preserve repository-relative paths when reports were produced on another runner.
                // Require path context, not a basename guess, and reject multiple possible matches.
                foreach ($byBasename[basename($candidate)] ?? [] as $path) {
                    if ($candidate === $path || (str_starts_with($candidate, '/') && str_contains($path, '/') && str_ends_with($candidate, '/' . $path))) {
                        $matches[$path] = true;
                    }
                }
            }
            if (count($matches) !== 1) {
                throw new RuntimeException('Coverage source path is missing or ambiguous');
            }
            $class->setAttribute('filename', array_key_first($matches));
        }
        foreach ($xpath->query('/coverage/sources') as $source) {
            $source->parentNode->removeChild($source);
        }
        $sourcesNode = $xml->documentElement->insertBefore($xml->createElement('sources'), $xml->documentElement->firstChild);
        $sourcesNode->appendChild($xml->createElement('source'))->appendChild($xml->createTextNode($root));
        if ($xml->save($output) === false) {
            throw new RuntimeException('Could not write normalized coverage');
        }
    }
}
