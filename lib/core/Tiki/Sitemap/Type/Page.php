<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
namespace Tiki\Sitemap\Type;

use TikiLib;
use Tiki\Sitemap\AbstractType;

/**
 * Generate Sitemap for Pages
 */
class Page extends AbstractType
{
    /**
     * Generate Sitemap
     */
    public function generate()
    {
        global $tikilib, $prefs;
        $wikilib = TikiLib::lib('wiki');
        $priority = [];

        if (! $this->checkFeatureAndPermissions('feature_wiki')) {
            return;
        }

        /** @var \TikiLib $tikilib */
        $listPages = $tikilib->list_pages();
        $attributes = TikiLib::lib('attribute')->getAllAttributes("tiki.object.sitemap");
        $listPages['data'] = array_filter($listPages['data'], function ($page) use ($attributes) {
            if ($attributes[$page['pageName']] !== 'n') {
                return ($page);
            }
        });
        $priority = array_count_values(array_column($wikilib->getAllBacklinks(), 'toPage'));
        foreach ($priority as $key => $values) {
            $priority[$key] = '0.8';
        }
        $priority[$prefs['wikiHomePage']] = '1.0';
        $this->addEntriesToSitemap($listPages, '/tiki-index.php?page=%s', 'pageSlug', null, 'sitemap.xml', '', 'lastModif', $priority);
    }
}
