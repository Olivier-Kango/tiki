<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
class Search_ContentSource_WikiSource implements Search_ContentSource_Interface
{
    private $db;
    private $tikilib;
    private $flaggedrevisionlib;
    private $quantifylib;

    public function __construct()
    {
        global $prefs;

        $this->db = TikiDb::get();
        $this->tikilib = TikiLib::lib('tiki');

        if ($prefs['flaggedrev_approval'] == 'y') {
            $this->flaggedrevisionlib = TikiLib::lib('flaggedrevision');
        }

        if ($prefs['quantify_changes'] == 'y') {
            $this->quantifylib = TikiLib::lib('quantify');
        }
    }

    public function getDocuments()
    {
        return $this->db->table('tiki_pages')->fetchColumn('pageName', []);
    }

    public function getDocument($objectId, Search_Type_Factory_Interface $typeFactory): array|false
    {
        global $prefs;

        $wikilib = TikiLib::lib('wiki');
        $tikilib = TikiLib::lib('tiki');

        $info = $this->tikilib->get_page_info($objectId, true, true);

        if (! $info) {
            return false;
        }

        $contributors = $wikilib->get_contributors($objectId, $info['user']);
        if (! in_array($info['user'], $contributors)) {
            $contributors[] = $info['user'];
        }

        if ($info['is_html']) {
            // is_html flag does not get to the type handler, leaving HTML visible in the text provided
            $info['data'] = $tikilib->strip_tags($info['data']);
        }

        $data = [
            'title' => $typeFactory->sortable($info['pageName']),
            'language' => $typeFactory->identifier(empty($info['lang']) ? 'unknown' : $info['lang']),
            'creation_date' => $typeFactory->timestamp($info['created']),
            'modification_date' => $typeFactory->timestamp($info['lastModif']),
            'date' => $typeFactory->timestamp($info[$prefs['wiki_date_field']]),
            'description' => $typeFactory->plaintext($info['description']),
            'contributors' => $typeFactory->multivalue($contributors),

            'wiki_content' => $typeFactory->wikitext($info['data']),
            'wiki_keywords' => $typeFactory->plaintext($info['keywords']),
            'page_id' => $typeFactory->identifier($info['page_id']),

            'view_permission' => $typeFactory->identifier('tiki_p_view'),
            'url' => $typeFactory->identifier($wikilib->sefurl($info['pageName'])),
            'hash' => $typeFactory->identifier(''),
        ];

        if ($this->quantifylib) {
            $data['wiki_uptodateness'] = $typeFactory->sortable($this->quantifylib->getCompleteness($info['page_id']));
        }

        if ($this->flaggedrevisionlib) {
            $data['wiki_approval_state'] = $typeFactory->identifier('none');
        }

        $out = $data;

        if ($this->flaggedrevisionlib && $this->flaggedrevisionlib->page_requires_approval($info['pageName'])) {
            $out = [];

            // Will provide two documents: one approved and one latest
            $versionInfo = $this->flaggedrevisionlib->get_version_with($info['pageName'], 'moderation', 'OK');

            if (! $versionInfo || $versionInfo['version'] != $info['version']) {
                // No approved version or approved version differs, latest content marked as such
                $out[] = array_merge(
                    $data,
                    [
                        'hash' => $typeFactory->identifier('latest'),
                        'title' => $typeFactory->sortable(tr('%0 (latest)', $info['pageName'])),
                        'view_permission' => $typeFactory->identifier('tiki_p_wiki_view_latest'),
                        'wiki_approval_state' => $typeFactory->identifier('pending'),
                        'url' => $typeFactory->identifier(str_replace('&amp;', '&', $wikilib->sefurl($info['pageName'], true)) . 'latest'),
                        'approved_version' => $typeFactory->numeric((int) $versionInfo['version']),
                        'approved_user' => $typeFactory->identifier($versionInfo['user']),
                    ]
                );
            }

            if ($versionInfo) {
                // Approved version not latest, include approved version in index
                // Also applies when versions are equal, data would be the same
                $out[] = array_merge(
                    $data,
                    [
                        'wiki_content' => $typeFactory->wikitext($versionInfo['data']),
                        'wiki_approval_state' => $typeFactory->identifier('approved'),
                    ]
                );
            }
        }


        return $out;
    }

    public function getProvidedFields(): array
    {
        $fields = [
            'title',
            'language',
            'creation_date',
            'modification_date',
            'date',
            'description',
            'contributors',

            'wiki_content',
            'wiki_keywords',
            'page_id',

            'view_permission',
            'hash',
            'url',
        ];

        if ($this->quantifylib) {
            $fields[] = 'wiki_uptodateness';
        }

        if ($this->flaggedrevisionlib) {
            $fields[] = 'wiki_approval_state';
            $fields[] = 'approved_version';
            $fields[] = 'approved_user';
        }

        return $fields;
    }

    public function getProvidedFieldTypes(): array
    {
        $fields = [
            'title' => 'sortable',
            'language' => 'identifier',
            'creation_date' => 'timestamp',
            'modification_date' => 'timestamp',
            'date' => 'timestamp',
            'description' => 'plaintext',
            'contributors' => 'multivalue',

            'wiki_content' => 'wikitext',
            'wiki_keywords' => 'plaintext',
            'page_id' => 'identifier',

            'view_permission' => 'identifier',
            'hash' => 'identifier',
            'url' => 'identifier',
        ];

        if ($this->quantifylib) {
            $fields['wiki_uptodateness'] = 'sortable';
        }

        if ($this->flaggedrevisionlib) {
            $fields['wiki_approval_state'] = 'identifier';
            $fields['approved_version'] = 'numeric';
            $fields['approved_user'] = 'identifier';
        }

        return $fields;
    }

    public function getGlobalFields(): array
    {
        return [
            'title' => true,
            'description' => true,
            'date' => true,

            'wiki_content' => false,
            'wiki_keywords' => true,
        ];
    }
}
