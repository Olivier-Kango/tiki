<?php
// (c) Copyright 2002-2016 by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
// $Id$
require_once 'lib/auth/tokens.php';
require_once dirname(__FILE__) . '/TikiXmppPrebind.php';

class XMPPLib extends TikiLib
{
	private $server_host = '';
	private $server_http_bind = '';

	/**
	 * @return string
	 */
	public function getServerHttpBind(): string
	{
		return $this->server_http_bind;
	}

	function __construct()
	{
		global $prefs;

		$this->server_host = $prefs['xmpp_server_host'];
		$this->server_http_bind = $prefs['xmpp_server_http_bind'];

		if (! class_exists('XmppPrebind')) {
			throw new Exception(
				"class 'XmppPrebind' does not exists."
				. " Install with `composer require candy-chat/xmpp-prebind-php:dev-master`.",
				1
			);
		}
	}

	function get_user_password($user)
	{
		$query  = "SELECT value FROM `tiki_user_preferences` WHERE `user`=?";
		$query .= " AND `prefName`='xmpp_password';";

		$result = $this->query($query, [$user]);
		$ret = $result->fetchRow();

		if (count($ret) === 1) {
			return $ret['value'];
		} else {
			return '';
		}
	}

	function get_user_jid($user)
	{
		return sprintf('%s@%s', $user, $this->server_host);
	}

	function check_token($givenUser, $givenToken)
	{
		global $prefs;

		$tokenlib = AuthTokens::build($prefs);
		$token = $tokenlib->getToken($givenToken);

		if (! $token || $token['entry'] !== 'openfireauthtoken') {
			return false;
		}
		// TODO: figure out how to delete token after n usages
		$tokenlib->deleteToken($token['tokenId']);

		$param = json_decode($token['parameters'], true);
		return is_array($param)
			&& ! empty($param['user'])
			&& $param['user'] === $givenUser;
	}

	function prebind($user)
	{
		global $prefs;

		$tokenlib = AuthTokens::build($prefs);

		if (empty($this->server_host) ||  empty($this->server_http_bind)) {
			return [];
		}

		$xmpp_username = $user;

		if (! empty($prefs['xmpp_openfire_use_token']) && $prefs['xmpp_openfire_use_token'] === 'y') {
			$token = $tokenlib->createToken(
				'openfireauthtoken',
				['user' => $user],	// parameters
				[], 				// groups
				[
					'timeout' => 300,
					'createUser' => 'n',
				]
			);
			$xmpp_password = "$token";
		} else {
			$xmpp_password = $this->get_user_password($user);
		}

		$xmppPrebind = new TikiXmppPrebind(
			$this->server_host,
			$this->server_http_bind,
			'tikiwiki',
			false,
			false
		);

		$xmppPrebind->connect($xmpp_username, $xmpp_password);

		try {
			$xmppPrebind->auth();
			$result = $xmppPrebind->getSessionInfo();
		} catch (XmppPrebindException $e) {
			throw new Exception($e->getMessage(), 401);
		}

		return $result;
	}

	/**
	 * Add css and js files and initialising js to the page
	 *
	 * @param array $params:
	 * 		view__mode => overlayed | fullscreen | mobile | embedded
	 *
	 * @throws Exception
	 */
	function addConverseJSToPage($params = []) {
		global $user, $prefs;

		static $instance = 0;
		$instance++;

		if ($instance > 1) {
			Feedback::error(tr('Only one instance of XMPP chat per page'));
			return '';
		}

		$headerlib = TikiLib::lib('header');

		$params = array_merge([
			'view_mode' => 'overlayed',
			'room' => '',
		], $params);

		switch ($params['view_mode']) {
			case 'fullscreen':
				$css_file = 'inverse.css';
				break;
			case 'embedded':
				$css_file = 'converse-muc-embedded.css';
				break;
			case 'mobile':
				$css_file = 'mobile.css';
				break;
			case 'overlayed':
			default:
				$css_file = 'converse.css';
		}

		$headerlib->add_cssfile('vendor_bundled/vendor/jcbrand/converse.js/css/' . $css_file . '');

		$xmpplib = TikiLib::lib('xmpp');

		$options = [
			'bosh_service_url' => $xmpplib->getServerHttpBind(),
			'jid' => $xmpplib->get_user_jid($user),
			'authentication' => 'prebind',
			'prebind_url' => TikiLib::lib('service')->getUrl([
				'controller' => 'xmpp',
				'action' => 'prebind',
			]),
			'whitelisted_plugins' => ['tiki'],
			'debug' => $prefs['xmpp_conversejs_debug'] === 'y',

		];

		if ($params['room']) {
			$options['auto_login'] = true;
			$options['auto_join_rooms'] = [$params['room']];
		}

		if (! empty($prefs['xmpp_conversejs_init_json'])) {
			$extraOptions = json_decode($prefs['xmpp_conversejs_init_json'], true);
			$options = array_merge($options, $extraOptions);
		}

		$optionString = json_encode($options, JSON_UNESCAPED_SLASHES);

		$js = '
(function () {
	converse.plugins.add("tiki", {
		"initialize": function () {
			var _converse = this._converse;
			_converse.api.listen.on("noResumeableSession", function (xhr) {
				feedback (tr("XMPP Module error") + ": " + xhr.statusText, "error", false);
				$("#conversejs").fadeOut("fast");
			});
		}
	});
	
	converse.initialize(' . $optionString . ');
})();
';
		$headerlib->add_jsfile('vendor_bundled/vendor/jcbrand/converse.js/dist/converse.js')
			->add_jq_onready($js);
	}
}
