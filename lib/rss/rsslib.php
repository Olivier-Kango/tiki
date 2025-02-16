<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
class RSSLib extends TikiDb_Bridge
{
    private $items;
    private $feeds;
    private $modules;
    private bool $updateArticles = true;

    public function __construct()
    {
        $this->items = $this->table('tiki_rss_items');
        $this->feeds = $this->table('tiki_rss_feeds');
        $this->modules = $this->table('tiki_rss_modules');
    }

    public function enableUpdateArticles()
    {
        $this->updateArticles = true;
    }

    public function disableUpdateArticles()
    {
        $this->updateArticles = false;
    }

    // ------------------------------------
    // functions for rss feeds we syndicate
    // ------------------------------------

    /**
     * Return the feed format name
     *
     * @return string feed format name
     */
    public function get_current_feed_format_name()
    {
        $ver = $this->get_current_feed_format();

        if ($ver == '2') {
            $name = 'rss';
        } elseif ($ver == '5') {
            $name = 'atom';
        }

        return $name;
    }

    /**
     * Return the feed format code (2 for rss and 5 for atom)
     * we currently use (user param or default value)
     * if the user parameter is other than 2 or 5, we return the default value.
     *
     * @return int $ver
     */
    public function get_current_feed_format()
    {
        global $prefs;

        if (isset($_REQUEST['ver']) && ($_REQUEST['ver'] == 2 || $_REQUEST['ver'] == 5)) {
            $ver = $_REQUEST['ver'];
        } else {
            $ver = $prefs['feed_default_version'];
        }

        return $ver;
    }

    /* check for cached rss feed data */
    public function get_from_cache($uniqueid, $rss_version = "9")
    {
        global $tikilib, $user, $prefs;

        $rss_version = $this->get_current_feed_format();

        $output = [];
        $output["content-type"] = "application/xml";
        $output["encoding"] = "UTF-8";

        $output["data"] = "EMPTY";

        // caching rss data for anonymous users only
        if (isset($user) && $user <> "") {
            return $output;
        }

        $res = $this->feeds->fetchFullRow(['name' => $uniqueid, 'rssVer' => $rss_version]);
        if (! $res) {
            // nothing found, then insert empty row for this feed+rss_ver
            $this->feeds->insert(
                [
                    'name' => $uniqueid,
                    'rssVer' => $rss_version,
                    'refresh' => (int) $prefs['feed_cache_time'],
                    'lastUpdated' => 1,
                    'cache' => '-',
                ]
            );
        } else {
            // entry found in db:
            $output["data"] = $res["cache"];
            // $refresh = $res["refresh"]; // global cache time currently
            $refresh = $prefs['feed_cache_time']; // global cache time currently
            $lastUpdated = $res["lastUpdated"];
            // up to date? if not, then set trigger to reload data:
            if ($tikilib->now - $lastUpdated >= $refresh) {
                $output["data"] = "EMPTY";
            }
        }
        $output['content-type'] = 'application/xml';
        return $output;
    }

    /* put to cache */
    public function put_to_cache($uniqueid, $rss_version, $output)
    {
        global $user, $tikilib;
        // caching rss data for anonymous users only
        if (isset($user) && $user <> "") {
            return;
        }
        if ($output == "" || $output == "EMPTY") {
            return;
        }

        $rss_version = $rss_version ?? $this->get_current_feed_format();

        // update cache with new generated data if data not empty

        $this->feeds->update(
            [
                'cache' => $output,
                'lastUpdated' => $tikilib->now,
            ],
            [
                'name' => $uniqueid,
                'rssVer' => $rss_version,
            ]
        );
    }

    /**
     * Generate a feed (ATOM 1.0 or RSS 2.0 using Laminas\Feed\Writer\Feed
     *
     * @param string $section Tiki feature the feed is related to
     * @param string $uniqueid
     * @param string $feed_version DEPRECATED
     * @param array $changes the content that will be used to generate the feed
     * @param string $itemurl base url for items (e.g. "tiki-view_blog_post.php?postId=%s")
     * @param string $urlparam
     * @param string $id name of the id field used to identify each feed item (e.g. "postId")
     * @param string $title title for the feed
     * @param string $titleId name of the key in the $changes array with the title of each item
     * @param string $desc description for the feed
     * @param string $descId name of the key in the $changes array with the description of each item
     * @param string $dateId name of the key in the $changes array with the date of each item
     * @param string $authorId name of the key in the $changes array with the author of each item
     * @param bool $fromcache if true recover the feed from cache
     *
     * @return array the generated feed
     */
    public function generate_feed($section, $uniqueid, $feed_version, $changes, $itemurl, $urlparam, $id, $title, $titleId, $desc, $descId, $dateId, $authorId, $fromcache = false)
    {
        global $tiki_p_admin, $prefs;
        $userlib = TikiLib::lib('user');
        $tikilib = TikiLib::lib('tiki');
        $smarty = TikiLib::lib('smarty');

        // both title and description fields cannot be null
        if (empty($title) || empty($desc)) {
            $msg = tra('The title and description must be entered, to generate a feed.');
            if ($tiki_p_admin) {
                $msg .= ' ' . tra('To fix this error go to Admin -> Feeds.');
            } else {
                $msg .= ' ' . tra('Please contact the site administrator and request this error to be corrected');
            }
            $smarty->assign('msg', $msg);
            $smarty->display('error.tpl');
            die;
        }

        $feed_format = $this->get_current_feed_format();
        $feed_format_name = $this->get_current_feed_format_name();

        if ($prefs['feed_cache_time'] < 1) {
            $fromcache = false;
        }

        // only get cache data if rss cache is enabled
        if ($fromcache) {
            $output = $this->get_from_cache($uniqueid, $feed_format);
            if ($output['data'] != 'EMPTY') {
                return $output;
            }
        }

        $urlarray = parse_url($_SERVER["REQUEST_URI"]);
        $rawPath = str_replace('\\', '/', dirname($urlarray["path"]));
        $URLPrefix = $tikilib->httpPrefix() . $rawPath;
        if ($rawPath != "/") {
            $URLPrefix .= "/"; // Append a slash unless Tiki is in the document root. dirname() removes a slash except in that case.
        }

        if (isset($prefs['feed_' . $section . '_index']) && $prefs['feed_' . $section . '_index'] != '') {
            $feedLink = $prefs['feed_' . $section . '_index'];
        } else {
            $feedLink = htmlspecialchars($tikilib->httpPrefix() . $_SERVER["REQUEST_URI"]);
        }

        $img = htmlspecialchars($URLPrefix . $prefs['feed_img']);

        $title = htmlspecialchars($title);
        $desc = htmlspecialchars($desc);
        $read = $URLPrefix . $itemurl;

        $feed = new Laminas\Feed\Writer\Feed();
        $feed->setTitle($title);
        $feed->setDescription($desc);

        if (! empty($prefs['feed_language'])) {
            $feed->setLanguage($prefs['feed_language']);
        }

        $feed->setLink($tikilib->tikiUrl(''));
        $feed->setFeedLink($feedLink, $feed_format_name);
        $feed->setDateModified($tikilib->now);

        if ($feed_format_name == 'atom') {
            $author = [];

            if (! empty($prefs['feed_atom_author_name'])) {
                $author['name'] = $prefs['feed_atom_author_name'];
            }
            if (! empty($prefs['feed_atom_author_email'])) {
                $author['email'] = $prefs['feed_atom_author_email'];
            }
            if (! empty($prefs['feed_atom_author_url'])) {
                $author['url'] = $prefs['feed_atom_author_url'];
            }

            if (! empty($author)) {
                if (empty($author['name'])) {
                    $msg = tra('If you set feed author email or URL, you must set feed author name.');
                    $smarty->assign('msg', $msg);
                    $smarty->display('error.tpl');
                    die;
                }
                $feed->addAuthor($author);
            }
        } else {
            $authors = [];

            if (! empty($prefs['feed_rss_editor_email'])) {
                $authors['name'] = $prefs['feed_rss_editor_email'];
            }
            if (! empty($prefs['feed_rss_webmaster_email'])) {
                $authors['name'] = $prefs['feed_rss_webmaster_email'];
            }

            if (! empty($authors)) {
                $feed->addAuthors([$authors]);
            }
        }

        if (! empty($prefs['feed_img'])) {
            $image = [];
            $image['uri'] = $tikilib->tikiUrl($prefs['feed_img']);
            $image['title'] = tra('Feed logo');
            $image['link'] = $tikilib->tikiUrl('');
            $feed->setImage($image);
        }

        foreach ($changes["data"] as $data) {
            $item = $feed->createEntry();

            if (empty($data[$titleId]) || ! is_string($data[$titleId])) {
                $data[$titleId] = tra('No title specified');
            }

            $item->setTitle($data[$titleId]);

            if (isset($data['sefurl'])) {
                $item->setLink($URLPrefix . urlencode($data['sefurl']));
            } elseif ($urlparam != '') {            // 2 parameters to replace
                $item->setlink(sprintf($read, urlencode($data["$id"]), urlencode($data["$urlparam"])));
            } else {
                $item->setLink(sprintf($read, urlencode($data["$id"])));
            }

            if (isset($data[$descId]) && $data[$descId] != '') {
                $item->setDescription($data[$descId]);
            }

            $item->setDateCreated((int) $data[$dateId]);
            $item->setDateModified((int) $data[$dateId]);

            if ($authorId != '' && array_key_exists('feed_' . $section . '_showAuthor', $prefs) && $prefs['feed_' . $section . '_showAuthor'] == 'y') {
                $author = $this->process_item_author($data[$authorId]);

                if (array_key_exists('name', $author) && ! empty($author['name']) && is_string($author['name'])) {
                    $item->addAuthor($author);
                }
            }

            $feed->addEntry($item);
        }

        $data = $feed->export($feed_format_name);
        $this->put_to_cache($uniqueid, $feed_format, $data);

        $output = [];
        $output["data"] = $data;
        $output["content-type"] = 'application/xml';

        return $output;
    }

    /**
     * Return information about the user acording to its preferences
     *
     * @param string $login
     * @return array author data (can be the login name or the realName if set and email if public)
     */
    public function process_item_author($login)
    {
        $userlib = TikiLib::lib('user');
        $tikilib = TikiLib::lib('tiki');

        $author = [];

        if ($userlib->user_exists($login) && $tikilib->get_user_preference($login, 'user_information', 'private') == 'public') {
            // if realName is not set use $login
            $author['name'] = $tikilib->get_user_preference($login, 'realName', $login);

            if ($tikilib->get_user_preference($login, 'email is public', 'n') != 'n') {
                $res = $userlib->get_user_info($login, false);
                $author['email'] = TikiLib::scrambleEmail($res['email']);
            }
        } else {
            $author['name'] = $login;
        }

        return $author;
    }

    // --------------------------------------------
    // functions for rss feeds syndicated by others
    // --------------------------------------------

    /* get (a part of) the list of existing rss feeds from db */
    public function list_rss_modules($offset = 0, $maxRecords = null, $sort_mode = 'name_asc', $find = '')
    {
        global $prefs;

        $conditions = [];
        if ($maxRecords === null) {
            $maxRecords = $prefs['maxRecords'];
        }
        if ($find) {
            $conditions['search'] = $this->modules->expr('(`name` LIKE ? OR `description` LIKE ?)', ["%$find%", "%$find%"]);
        }

        $ret = $this->modules->fetchAll($this->modules->all(), $conditions, $maxRecords, $offset, $this->modules->sortMode($sort_mode));

        foreach ($ret as & $res) {
            $res["minutes"] = $res["refresh"] / 60;
        }

        return [
            'data' => $ret,
            'cant' => $this->modules->fetchCount($conditions),
        ];
    }

    /* replace rss feed in db */
    public function replace_rss_module($rssId, $name, $description, $url, $refresh, $showTitle, $showPubDate, $noUpdate = false)
    {
        //if ($this->rss_module_name_exists($name)) return false; // TODO: Check the name
        $refresh = 60 * $refresh;

        $data = [
                'name' => $name,
                'description' => $description,
                'refresh' => $refresh,
                'url' => $url,
                'showTitle' => $showTitle,
                'showPubDate' => $showPubDate,
                ];

        if ($rssId) {
            $this->modules->update($data, ['rssId' => (int) $rssId,]);
        } else {
            $data['lastUpdated'] = 1;
            $rssId = $this->modules->insert($data);
        }

        if (! $noUpdate) {
            // Updating is normally required, except for cases where we know it will be updated later (e.g. after article generation is set, so that articles are created immediately)
            $this->refresh_rss_module($rssId);
        }
        return $rssId;
    }

    /* remove rss feed from db */
    public function remove_rss_module($rssId)
    {
        $resultModule = $this->modules->delete(['rssId' => $rssId,]);
        $resultItems = $this->items->deleteMultiple(['rssId' => $rssId,]);
        return ['feed' => $resultModule, 'items' => $resultItems];
    }

    /* read rss feed data from db */
    public function get_rss_module($rssId)
    {
        return $this->modules->fetchFullRow(['rssId' => $rssId]);
    }

    /**
     * @param $rssId
     *
     * @return mixed
     */
    public function refresh_rss_module($rssId)
    {
        return $this->update_feeds([ $rssId ], true);
    }

    /**
     * @return array
     * @throws Exception
     */
    public function refresh_all_rss_modules()
    {
        $mods = $this->list_rss_modules(0, -1);
        $feeds = [];
        foreach ($mods['data'] as $mod) {
            $feeds[] = $mod['rssId'];
        }
        return $this->update_feeds($feeds, true);
    }

    /**
     * @param int $rssId     feed id
     * @param int $olderThan publication date more than than this number of seconds ago
     *
     * @return TikiDb_Adodb_Result|TikiDb_Pdo_Result
     */
    public function clear_rss_cache($rssId, $olderThan = 0)
    {
        $conditions = ['rssId' => (int)$rssId];

        if ($olderThan) {
            $conditions['publication_date'] = $this->items->lesserThan(time() - $olderThan);
        }

        return $this->items->deleteMultiple($conditions);
    }

    /* check if an rss feed name already exists */
    public function rss_module_name_exists($name)
    {
        return $this->modules->fetchCount(['name' => $name]);
    }

    /* get rss feed id by name */
    public function get_rss_module_id($name)
    {
        return $this->modules->fetchOne('rssId', ['name' => $name]);
    }

    /* check if 'showTitle' for an rss feed is enabled */
    public function get_rss_showTitle($rssId)
    {
        return $this->modules->fetchOne('showTitle', ['rssId' => $rssId]);
    }

    /* check if 'showPubdate' for an rss feed is enabled */
    public function get_rss_showPubDate($rssId)
    {
        return $this->modules->fetchOne('showPubDate', ['rssId' => $rssId]);
    }

    public function get_feed_items($feeds, $count = 10, $sortBy = 'publication_date', $sortOrder = 'DESC')
    {
        $feeds = (array) $feeds;

        $this->update_feeds($feeds);

        return $this->items->fetchAll(
            $this->items->all(),
            ['rssId' => $this->items->in($feeds),],
            $count,
            0,
            [$sortBy => $sortOrder]
        );
    }

    /**
     * @param      $feeds
     * @param bool $force
     * @param bool $updateArticles
     *
     * @return array
     * @throws Exception
     */
    private function update_feeds($feeds, $force = false)
    {
        global $tikilib;

        $conditions = ['rssId' => $this->modules->in($feeds),];

        if (! $force) {
            $conditions['date'] = $this->modules->expr('`lastUpdated` < ? - `refresh`', [$tikilib->now]);
        }

        $result = $this->modules->fetchAll(['rssId', 'url', 'actions'], $conditions);
        $feedResult['feeds'] = count($result);
        $entryReturn = ['feed' => 0, 'articles' => 0, 'feedData' => []];
        foreach ($result as $row) {
            $entryResult = $this->update_feed($row['rssId'], $row['url'], $row['actions']);
            if (! empty($entryResult['feed'])) {
                $entryReturn['feed'] += $entryResult['feed'];
            }
            if ($this->updateArticles && ! empty($entryResult['articles'])) {
                $entryReturn['articles'] += $entryResult['articles'];
            }
            if (! empty($entryResult['feedData'])) {
                $entryReturn['feedData'] = array_merge($entryReturn['feedData'], $entryResult['feedData']);
            }
        }
        $feedResult['entries'] = $entryReturn;
        return $feedResult;
    }

    /**
     * @param $rssId
     * @param $url
     * @param $actions
     *
     * @return array
     * @throws Exception
     */
    private function update_feed($rssId, $url, $actions)
    {
        global $tikilib;

        $filter = new DeclFilter();
        $filter->addStaticKeyFilters(
            [
                'url' => 'url',
                'title' => 'striptags',
                'author' => 'striptags',
                'description' => 'striptags',
                'content' => 'purifier',
            ]
        );

        $guidFilter = TikiFilter::get('url');
        $success = ['feed' => 0, 'articles' => 0, 'feedData' => []];
        $feed = null;
        try {
            $content = $tikilib->httprequest($url);
            if ($content) {
                $feed = Laminas\Feed\Reader\Reader::importString($content);
            }
            if (! $feed) {
                throw new Laminas\Feed\Exception\RuntimeException('Unreadable feed.');
            }
        } catch (Laminas\Feed\Exception\ExceptionInterface $e) {
            $this->modules->update(
                [
                    'lastUpdated' => $tikilib->now,
                    'sitetitle' => 'N/A',
                    'siteurl' => '#',
                    ],
                ['rssId' => $rssId,]
            );
            return $success;
        }
        $siteTitle = TikiFilter::get('striptags')->filter($feed->getTitle());
        $siteUrl = TikiFilter::get('url')->filter($feed->getLink());

        $this->modules->update(
            [
                'lastUpdated' => $tikilib->now,
                'sitetitle' => $siteTitle,
                'siteurl' => $siteUrl,
                ],
            ['rssId' => $rssId,]
        );
        $DOM = new DOMDocument();
        foreach ($feed as $entry) { // TODO: optimize. Atom entries have an 'updated' element which can be used to only update updated entries
            $guid = $guidFilter->filter($entry->getId());

            $authors = $entry->getAuthors();

            $categories = $entry->getCategories();

            $link = $entry->getLink();
            if (! $link) {
                $link = '';
            }
            $description = $entry->getDescription();
            if (! $description) {
                $description = '';
            }
            $data = $filter->filter(
                [
                    'title' => $entry->getTitle(),
                    'url' => $link,
                    'description' => $description,
                    'content' => $entry->getContent(),
                    'author' => $authors ? implode(', ', $authors->getValues()) : '',
                    'categories' => $categories ? json_encode($categories->getValues()) : json_encode([]),
                    'language' => TikiFilter::get('striptags')->filter($feed->getLanguage()),
                ]
            );

            $data['guid'] = $guid;
            if (method_exists($entry, 'getDateCreated') && $createdDate = $entry->getDateCreated()) {
                $data['publication_date'] = $createdDate->getTimestamp();
            } else {
                global $tikilib;
                $data['publication_date'] = $tikilib->now;
            }

            $htmlContent = $entry->getContent();
            if (! empty($htmlContent)) {
                @$DOM->loadHTML($htmlContent);
                $rows = $DOM->getElementsByTagName('tr');
                foreach ($rows as $row) {
                    $cols = $row->getElementsByTagName('td');
                    if (isset($cols[0]) && $cols[1]) {
                        $data[str_replace(' ', '', $cols[0]->textContent)] = $cols[1]->textContent;
                    }
                }
            }

            array_push($success['feedData'], $data);

            if (! $this->updateArticles) {
                continue;
            }

            $count = $this->items->fetchCount(['rssId' => $rssId, 'guid' => $guid]);
            if (0 == $count) {
                $result = $this->insert_item($rssId, $data, $actions);
                if (! empty($result['feed'])) {
                    $success['feed']++;
                }
                if (! empty($result['articles'])) {
                    $success['articles']++;
                }
            } else {
                $result = $this->update_item($rssId, $data['guid'], $data);
                if ($result && $result->numrows()) {
                    $success['feed']++;
                }
            }
        }
        return $success;
    }

    public function get_feed_source_categories($rssId)
    {
        $feeds = $this->items->fetchAll(['categories'], ['rssId' => $rssId]);
        $categories = [];
        foreach ($feeds as $feed) {
            if (isset($feed['categories'])) {
                foreach (json_decode($feed['categories']) as $sourcecat) {
                    $categories[$sourcecat] = [];
                }
            }
        }
        $custom_info = $this->get_article_custom_info($rssId);
        $categories = array_merge($categories, $custom_info);
        ksort($categories, SORT_NATURAL);
        return $categories;
    }

    public function get_article_custom_info($rssId)
    {
        $result = $this->modules->fetchOne('actions', ['rssId' => $rssId]);
        $actions = [];
        if ($result !== false && isset($result['actions'])) {
            $actions = json_decode($result['actions'], true);
            if (json_last_error() !== JSON_ERROR_NONE) {
                throw new InvalidArgumentException('Invalid JSON string: ' . json_last_error_msg());
            }
        } else {
            trigger_error("No actions found for rssId: $rssId", E_USER_WARNING);
        }
        $categories = [];
        $customFields = [
            'custom_atype' => 'atype',
            'custom_topic' => 'topic',
            'custom_rating' => 'rating',
            'custom_priority' => 'priority'
        ];
        foreach ($actions as $action) {
            foreach ($customFields as $fieldName => $categoryKey) {
                if (isset($action[$fieldName])) {
                    foreach ($action[$fieldName] as $source_category => $value) {
                        $categories[$source_category][$categoryKey] = $value;
                    }
                }
            }
        }
        return $categories;
    }

    /**
     * @param $rssId
     * @param $data
     * @param $actions
     *
     * @return array|bool|mixed
     * @throws Exception
     */
    private function insert_item($rssId, $data, $actions)
    {
        $result = $this->items->insert(
            [
                'rssId' => $rssId,
                'guid' => $data['guid'],
                'url' => $data['url'],
                'publication_date' => $data['publication_date'],
                'title' => $data['title'],
                'author' => $data['author'],
                'description' => $data['description'],
                'content' => $data['content'],
                'categories' => $data['categories'],
            ]
        );

        if (is_string($actions) && ! empty($actions)) {
            $actions = json_decode($actions, true);
            if (json_last_error() !== JSON_ERROR_NONE) {
                throw new InvalidArgumentException('Invalid JSON string in actions: ' . json_last_error_msg());
            }
        } else {
            $actions = [];
        }

        //currently handles creation of articles through process_action_article()
        $actionCount = 0;
        if (! empty($actions)) {
            $pagecontentlib = TikiLib::lib('pagecontent');
            $data = $pagecontentlib->augmentInformation($data);
            foreach ($actions as $action) {
                $method = 'process_action_' . $action['type'];
                unset($action['type']);

                if ($action['active']) {
                    $actionId = $this->$method($action, $data, $rssId);
                    if (! empty($actionId) && is_numeric($actionId)) {
                        $actionCount++;
                    }
                }
            }
        }
        return ['feed' => $result, 'articles' => $actionCount];
    }

    private function update_item($rssId, $guid, $data)
    {
        // A feed may contain several entries with the same GUID... see http://framework.zend.com/issues/browse/ZF-10954. Assuming a single record would actually cause issues, see r37318.
        return $this->items->updateMultiple(
            ['rssId' => $rssId, 'guid' => $guid,],
            [
                'url' => $data['url'],
                'publication_date' => $data['publication_date'],
                'title' => $data['title'],
                'author' => $data['author'],
                'description' => $data['description'],
                'content' => $data['content'],
            ]
        );
    }

    private function process_action_article($configuration, $data, $rssId)
    {
        $tikilib = TikiLib::lib('tiki');
        $artlib = TikiLib::lib('art');
        $publication = $data['publication_date'];

        // First override with custom settings for source categories if any
        if (isset($data['categories'])) {
            $source_categories = json_decode($data['categories'], true);
        }
        if (! empty($source_categories)) {
            $custominfo = $this->get_article_custom_info($rssId);

            $oldcats = array_keys($custominfo);

            if ($newcats = array_diff($source_categories, $oldcats)) {
                // send a notification if there are new categories
                $nots = $tikilib->get_event_watches('article_submitted', '*');
                if (count($nots)) {
                    $title = $this->modules->fetchOne('name', ['rssId' => $rssId]);
                    include_once('lib/notifications/notificationemaillib.php');
                    $smarty = TikiLib::lib('smarty');
                    $smarty->assign('mail_site', $_SERVER['SERVER_NAME']);
                    $smarty->assign('rssId', $rssId);
                    $smarty->assign('title', $title);
                    $smarty->assign('newcats', $newcats);
                    sendEmailNotification($nots, 'watch', 'rss_new_source_category_subject.tpl', $_SERVER['SERVER_NAME'], 'rss_new_source_category.tpl');
                }
            }

            $current_priority = 0;
            foreach ($custominfo as $source_category => $settings) {
                if (in_array($source_category, $source_categories)) {
                    if (isset($settings['priority']) && $settings['priority'] < $current_priority) {
                        continue;
                    }
                    if (! empty($settings['atype'])) {
                        $configuration['atype'] = $settings['atype'];
                    }
                    if (isset($settings['topic']) && $settings['topic'] > '') {
                        // need to match 0
                        $configuration['topic'] = $settings['topic'];
                    }
                    if (isset($settings['rating']) && $settings['rating'] > '') {
                        // need to match 0
                        $configuration['rating'] = $settings['rating'];
                    }
                    $current_priority = isset($settings['priority']) ? $settings['priority'] : 0;
                }
            }
        }

        if ($configuration['future_publish'] > 0) {
            $publication = $tikilib->now + $configuration['future_publish'] * 60;
        }

        $expire = $publication + 3600 * 24 * $configuration['expiry'];

        if (strpos($data['content'], trim($data['description'])) === 0 && strlen($data['description']) < 1024) {
            $data['content'] = substr($data['content'], strlen(trim($data['description'])));
        }
        $data['content'] = trim($data['content']) == '' ? $data['content'] : '~np~' . $data['content'] . '~/np~';

        if ($configuration['submission'] == true) {
            $subid = $this->table('tiki_submissions')->fetchOne('subId', [
                'linkto' => $data['url'],
                'topicId' => $configuration['topic'],
            ]);
            if (! $subid) {
                $subid = 0;
            }
            $subid = $artlib->replace_submission(
                $data['title'],
                $data['author'],
                $configuration['topic'],
                'n',
                '',
                0,
                '',
                '',
                $data['description'],
                $data['content'],
                $publication,
                $expire,
                'admin',
                $subid,
                0,
                0,
                $configuration['atype'],
                '',
                '',
                $data['url'],
                '',
                $configuration['a_lang'],
                $configuration['rating']
            );

            if (count($configuration['categories'])) {
                $categlib = TikiLib::lib('categ');
                $objectId = $categlib->add_categorized_object('submission', $subid, $data['title'], $data['title'], 'tiki-edit_submission.php?subId=' . $subid);

                foreach ($configuration['categories'] as $categId) {
                    $categlib->categorize($objectId, $categId);
                }
            }
            return $subid;
        } else {
            $id = $this->table('tiki_articles')->fetchOne('articleId', [
                'linkto' => $data['url'],
                'topicId' => $configuration['topic'],
            ]);
            if (! $id) {
                $id = 0;
            }
            $id = $artlib->replace_article(
                $data['title'],
                $data['author'],
                $configuration['topic'],
                'n',
                '',
                0,
                '',
                '',
                $data['description'],
                $data['content'],
                $publication,
                $expire,
                'admin',
                $id,
                0,
                0,
                $configuration['atype'],
                '',
                '',
                $data['url'],
                '',
                $configuration['a_lang'],
                $configuration['rating']
            );

            if (count($configuration['categories'])) {
                $categlib = TikiLib::lib('categ');
                $objectId = $categlib->add_categorized_object('article', $id, $data['title'], $data['title'], 'tiki-read_article.php?articleId=' . $id);

                foreach ($configuration['categories'] as $categId) {
                    $categlib->categorize($objectId, $categId);
                }
            }

            TikiLib::lib('relation')->add_relation('tiki.rss.source', 'article', $id, 'rss', $rssId);
            require_once('lib/search/refresh-functions.php');
            refresh_index('articles', $id);
            $related_items = TikiLib::lib('relation')->get_relations_to('article', $id, 'tiki.article.attach');
            foreach ($related_items as $item) {
                refresh_index($item['type'], $item['itemId']);
            }
            return $id;
        }
    }

    /**
     * @param $rssId
     * @param $configuration
     *
     * @return TikiDb_Adodb_Result|TikiDb_Pdo_Result
     */
    public function set_article_generator($rssId, $configuration)
    {
        $configuration['type'] = 'article';

        $module = $this->get_rss_module($rssId);

        if ($module['actions']) {
            $actions = json_decode($module['actions'], true);
        } else {
            $actions = [];
        }

        $out = [];
        foreach ($actions as $action) {
            if ($action['type'] != 'article') {
                $out[] = $action;
            }
        }

        $out[] = $configuration;

        return $this->modules->update(
            ['actions' => json_encode($out),],
            ['rssId' => $rssId,]
        );
    }

    public function get_article_generator($rssId)
    {
        $module = $this->get_rss_module($rssId);

        if ($module['actions']) {
            $actions = json_decode($module['actions'], true);
        } else {
            $actions = [];
        }

        $default = [
                'active' => false,
                'expiry' => 365,
                'atype' => 'Article',
                'topic' => 0,
                'future_publish' => -1,
                'categories' => [],
                'rating' => 5,
                'feed_name' => $module['name'],
                ];

        foreach ($actions as $action) {
            if ($action['type'] == 'article') {
                unset($action['type']);
                return array_merge($default, $action);
            }
        }

        return $default;
    }

    public function generate_feed_from_data($data, $feed_descriptor)
    {
        require_once 'lib/smarty_tiki/modifier.sefurl.php';

        $tikilib = TikiLib::lib('tiki');
        $writer = new Laminas\Feed\Writer\Feed();
        $writer->setTitle($feed_descriptor['feedTitle']);
        $writer->setDescription($feed_descriptor['feedDescription']);
        $writer->setLink($tikilib->tikiUrl(''));
        $writer->setDateModified(time());

        foreach ($data as $row) {
            $titleKey = $feed_descriptor['entryTitleKey'];
            $url = $row[$feed_descriptor['entryUrlKey']];
            $title = $row[$titleKey];

            if (isset($feed_descriptor['entryObjectDescriptors'])) {
                list($typeKey, $objectKey) = $feed_descriptor['entryObjectDescriptors'];
                $object = $row[$objectKey];
                $type = $row[$typeKey];

                if (empty($url)) {
                    $url = smarty_modifier_sefurl($object, $type);
                }

                if (empty($title)) {
                    $title = TikiLib::lib('object')->get_title($type, $object);
                }
            }

            $entry = $writer->createEntry();
            $entry->setTitle($title ? $title : tra('Unspecified'));
            $entry->setLink($tikilib->tikiUrl($url));


            if (! empty($row[$feed_descriptor['entryModificationKey']])) {
                $date = $row[$feed_descriptor['entryModificationKey']];
                if (! is_int($date)) {
                    $date = strtotime($date);
                }
                $entry->setDateModified($date);
            }

            $writer->addEntry($entry);
        }

        return $writer;
    }
}
