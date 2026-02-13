<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
namespace Tiki\Sitemap\Type;

use Tiki\Sitemap\AbstractType;
use TikiLib;

/**
 * Generate Sitemap for Articles
 */
class Articles extends AbstractType
{
    /**
     * Generate Sitemap
     */
    public function generate(bool $standalone = false, ?array $context = null)
    {
        global $prefs;

        if (! $this->checkFeatureAndPermissions('feature_articles')) {
            return;
        }

        $articleLibrary = TikiLib::lib('art');

        $ranges = $this->getTypeDateRanges('Articles', $prefs['sitemap_split']);
        foreach ($ranges as $range) {
            // on auto mode only gen what requested for
            if ($context !== null && (int) $context['year'] !== (int) $range['year']) {
                continue;
            }

            [$min, $max] = $this->getRangeDateFilters($range);
            $listPages = $articleLibrary->list_articles(
                0,
                -1,
                'publishDate_desc',
                '',
                $min,
                $max,
                false,
                '',
                '',
                'y',
                '',
                '',
                '',
                '',
                '',
                '',
                '',
                false,
                'y'
            );

            $listPages['data'] = array_filter($listPages['data'], function ($article) {
                return ($article['disp_article'] === 'y');
            });

            // Build filename based on range
            $fileName = $range['prefix'] . '.xml';
            if (isset($range['year'])) {
                $fileName = $range['prefix'] . '-' . $range['year'] . '.xml';
                if (isset($range['month'])) {
                    $fileName = $range['prefix'] . '-' . $range['year'] . '-' . $range['month'] . '.xml';
                }
            }

            $this->addEntriesToSitemap($listPages, '/tiki-read_article.php?articleId=%s', 'articleId', 'article', $fileName);
        }
    }
}
