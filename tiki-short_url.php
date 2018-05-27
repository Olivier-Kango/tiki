<?php
// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
// $Id$

use Tiki\CustomRoute\CustomRoute;

require_once('tiki-setup.php');

// Check if feature is enabled
$access->check_feature(['feature_sefurl_routes', 'sefurl_short_url']);

if (empty($_REQUEST['url']) || $tikilib->getMatchBaseUrlSchema($_REQUEST['url']) === null) {

	if ($_REQUEST['module'] == 'y') {
		echo json_encode(['error' => true, 'message' => tr('URL provided is empty or unsupported')]);
		return;
	}

	Feedback::error(tr('Unable to generate a short url for the requested resource.'), 'session');
	// Redirect to homepage
	$access->redirect();
	return;
}

$url = $_REQUEST['url'];
$description = tr("'%0' short url", substr($_REQUEST['title'], 0, 25));
$route = CustomRoute::getShortUrlRoute($url, $description);
$shortUrl = $route->getShortUrlLink();

if ($_REQUEST['module'] == 'y') {
	echo  json_encode(['url' => $shortUrl]);
	return;
}

Feedback::success(tr('Short URL:') . " <a href='{$shortUrl}'>{$shortUrl}</a>", 'session');
$access->redirect($url);
