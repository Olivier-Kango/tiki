<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
use Services_OAuthServer_JsonResponse as JsonResponse;

class Services_OAuthServer_Controller
{
    public $utilities;
    public function setUp()
    {
        $this->utilities = new Services_OAuthServer_Utilities();
    }

    /*
     * OAuth protocol actions
     */

    /**
     * It return an access_token in Implicit Grant or Client Credentials Grant flows
     * or an authorization code for other grants. The authorization code can be
     * exchanged for an access token using action_access_token method.
     *
     * On AuthCode grant method, it may throw an exception in case
     * of wrong CSRF token.
     *
     * Other flows different than ImplicitGrant, AuthCodeGrant, RefreshTokenGrant,
     * and ClientCredentialsGrant are not supported and will raise an exception.
     *
     * @param JitFilter $request
     * @return void
     */
    public function action_authorize($request)
    {
        global $user;

        $accesslib = TikiLib::lib('access');
        $oauthserverlib = TikiLib::lib('oauthserver');
        $servicelib = TikiLib::lib('service');
        $params = $request->getStored();

        if ($params['response_type'] === 'code') {
            if (empty($user)) {
                $params['action'] = 'consent';
                $params['controller'] = 'oauthserver';
                TikiLib::setExternalContext(true);
                $consent_url = $servicelib->getUrl($params);
                $accesslib->redirect($consent_url);
                exit;
            }

            // this should throw exception on failure
            // TODO If this is a POST then second parameter should be false
            $accesslib->checkCsrf(null, true, 'ticket', null, null, 'services');
        }

        $oauthserverlib->determineServerGrant($params['skip_keypair'] ?? false);
        $server = $oauthserverlib->getServer($params['skip_keypair'] ?? false);
        $userEntity = $oauthserverlib->getUserEntity();

        // The oauth library give the default for "not set" info
        foreach ($request as $key => $value) {
            if (empty($value)) {
                unset($request[$key]);
            }
        }

        $params = $request->getStored();
        $request = $this->utilities->tiki2Psr7Request($request);

        $authRequest = $server->validateAuthorizationRequest($request);
        $authRequest->setUser($userEntity);

        $authRequest->setAuthorizationApproved(true);

        $response = new JsonResponse();
        $response = $server->completeAuthorizationRequest($authRequest, $response);
        $headers = $response->getHeaders();
        if (isset($headers['Location'][0])) {
            $headers['Location'][0] = html_entity_decode($headers['Location'][0]);
        }
        $response = new JsonResponse($response->getStatusCode(), $headers, $response->getBody(), $response->getProtocolVersion(), $response->getReasonPhrase());

        $this->utilities->processPsr7Response($response);
    }

    public function action_access_token($request)
    {
        $accesslib = TikiLib::lib('access');
        $oauthserverlib = TikiLib::lib('oauthserver');
        $oauthserverlib->determineServerGrant();

        $request = $this->utilities->tiki2Psr7Request($request);
        $response = new JsonResponse();

        $server = $oauthserverlib->getServer();
        $server->respondToAccessTokenRequest($request, $response);
        $this->utilities->processPsr7Response($response);
    }

    public function action_consent($request)
    {
        global $user;

        $params = $request->getQueryParams();
        /** @var OAuthServerLib $oauthserverlib */
        $oauthserverlib = TikiLib::lib('oauthserver');
        $accesslib = TikiLib::lib('access');
        $servicelib = TikiLib::lib('service');
        $form = [];

        if (empty($user)) {
            unset($_SESSION['loginfrom']);
            $_SESSION['loginfrom'] = $servicelib->getUrl($params);
            $accesslib->redirect('tiki-login_scr.php');
            exit;
        }

        if (empty($params['response_type'])) {
            header('400 Bad Request');
            throw new Services_Exception_NotAvailable(tr('Missing %0 parameter', 'response_type'));
        }
        $form['response_type'] = $params['response_type'];

        if (empty($params['client_id'])) {
            header('400 Bad Request');
            throw new Services_Exception_NotAvailable(tr('Missing %0 parameter', 'client_id'));
        }
        $client = $oauthserverlib->getClient($params['client_id']);

        if (empty($client)) {
            header('400 Bad Request');
            throw new Services_Exception_NotAvailable(tr('Not Found'));
        }

        $form['redirect_uri'] = $client->getRedirectUri();
        if (! empty('redirect_uri')) {
            $form['redirect_uri'] = $params['redirect_uri'];
        }

        $form['scope'] = '';
        if (! empty('scope')) {
            $form['scope'] = $params['scope'];
        }

        $form = array_map('htmlspecialchars', $form);

        $smarty = TikiLib::lib('smarty');
        $smarty->assign('metatag_robots', 'NOINDEX, NOFOLLOW');
        $smarty->assign('authorize_url', $servicelib->getUrl([
            'action' => 'authorize',
            'controller' => 'oauthserver',
            'response_type' => 'code'
        ]));
        $smarty->assign('response_type', $form['response_type']);
        $smarty->assign('client', $client);
        $smarty->assign('redirect_uri', $form['redirect_uri']);
        $smarty->assign('scope', $form['scope']);

        $smarty->assign('mid', 'oauthserver/consent.tpl');
        $smarty->display("tiki.tpl");
        exit;
    }

    public function action_client_modify($request)
    {
        $access = TikiLib::lib('access');
        $request = $this->utilities->tiki2Psr7Request($request);
        $access->check_permission('tiki_p_admin');
        $params = ['delete' => null];

        if ($request->getMethod() !== 'POST') {
            $response = new JsonResponse(405, [], '');
            return $this->utilities->processPsr7Response($response);
        }

        $oauthserverlib = TikiLib::lib('oauthserver');
        $repo = $oauthserverlib->getClientRepository();
        $params = array_merge($params, $request->getQueryParams());
        $client = ClientEntity::build($params);

        $response_content = null;
        $response_code = null;
        $validation_errors = $repo->validate($client);

        if ($client->getId()) {
            if ($repo->exists($client)) {
                if ($params['delete'] === '1') {
                    $repo->delete($client);
                    $response_code = 200;
                    $response_content = true;
                } elseif (empty($validation_errors)) {
                    $repo->update($client);
                    $response_code = 200;
                    $response_content = $client->toArray();
                } else {
                    $response_code = 400;
                    $response_content = $validation_errors;
                }
            } else {
                $response_code = 404;
                $response_content = ['error' => 'Client not found'];
            }
        } elseif ($params['delete'] !== '1' && empty($validation_errors)) {
            $client->setClientId($repo::generateSecret(32));
            $client->setClientSecret($repo::generateSecret(64));
            $repo->create($client);
            $response_content = $client->toArray();
            $response_code = 201;
        } else {
            $response_code = 400;
            $response_content = $validation_errors;
        }

        $response = new JsonResponse($response_code, [], $response_content);
        return $this->utilities->processPsr7Response($response);
    }

    public function action_check($request)
    {
        $request = $this->utilities->tiki2Psr7Request($request);
        $params = $request->getQueryParams();
        $oauthserverlib = TikiLib::lib('oauthserver');

        if ($request->getMethod() !== 'GET') {
            $response = new JsonResponse(405, [], '');
            return $this->utilities->processPsr7Response($response);
        }

        $authorization = $request->getHeaderLine('Authorization') ?: '';
        $authorization = preg_split('/  */', $authorization);

        $valid = ! empty($params['auth_token'])
            && count($authorization) === 2
            && strcasecmp($authorization[0], 'Basic') === 0
            && ! empty($authorization = base64_decode($authorization[1]));

        if (! $valid) {
            $response = new JsonResponse(400, [], 'Missing content');
            return $this->utilities->processPsr7Response($response);
        }

        list($client_id, $client_secret) = explode(':', $authorization);
        $repo = $oauthserverlib->getClientRepository();
        $client = $repo->get($client_id);

        if (! $client || $client->getClientSecret() !== trim($client_secret)) {
            $response = new JsonResponse(403, [], 'Invalid client');
            return $this->utilities->processPsr7Response($response);
        }

        $repo = $oauthserverlib->getAccessTokenRepository();
        $token = $repo->get($params['auth_token']);

        $valid = ! empty($token);
        $valid = $valid
            && $token->getClient()->getIdentifier() == $client->getIdentifier();

        if (! $valid) {
            $response = new JsonResponse(403, [], 'Invalid token');
            return $this->utilities->processPsr7Response($response);
        }

        $response = new JsonResponse(200, [], 'ok');
        return $this->utilities->processPsr7Response($response);
    }

    public function action_public_key()
    {
        global $prefs;
        return ['key' => TikiLib::lib('oauthserver')->getPublicKey()];
    }
}
