<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
// $Id$

use Symfony\Component\Routing\Exception\ExceptionInterface;
use Symfony\Component\Routing\Exception\ResourceNotFoundException;
use Symfony\Component\Routing\Exception\RouteNotFoundException;
use Symfony\Component\Routing\Matcher\UrlMatcher;
use Symfony\Component\Routing\RequestContext;
use Symfony\Component\Routing\Route;
use Symfony\Component\Routing\RouteCollection;


class Services_ApiBridge
{
    protected $jitRequest;
    protected $routes;
    protected $context;

    public function __construct(JitFilter $jitRequest)
    {
        $this->jitRequest = $jitRequest;
        $this->routes = $this->prepareRoutes();
        $this->context = $this->prepareContext();
    }

    public function handle()
    {
        $route = $this->parseRoute();
        $request = $this->jitRequest->asArray();
        foreach ($route as $key => $value) {
            if (! in_array($key, ['controller', 'action', '_route']) && ! isset($request[$key])) {
                $request[$key] = $value;
                if ($key == 'confirmForm') {
                    $_POST[$key] = $value;
                }
            }
        }
        $this->jitRequest = new JitFilter($request);
        if ($route['_route'] == 'home') {
            $this->renderDocs();
        } elseif ($route['_route'] == 'docs') {
            $this->renderDocsYaml();
        } else {
            $broker = TikiLib::lib('service')->getBroker();
            $broker->process($route['controller'], $route['action'], $this->jitRequest);
        }
    }

    protected function parseRoute()
    {
        try {
            $route = $this->jitRequest->route->none();
            $matcher = new UrlMatcher($this->routes, $this->context);
            return $matcher->match('/'.$route);
        } catch (ResourceNotFoundException $e) {
            TikiLib::lib('access')->display_error('API', $e->getMessage(), 404);
        } catch (RouteNotFoundException $e) {
            TikiLib::lib('access')->display_error('API', $e->getMessage(), 404);
        } catch (ExceptionInterface $e) {
            TikiLib::lib('access')->display_error('API', $e->getMessage(), 400);
        }
    }

    protected function prepareContext()
    {
        global $base_uri, $base_host, $url_host, $url_scheme, $prefs;
        $path_info = str_replace($base_host, '', $base_uri);
        if (false !== $pos = strpos($path_info, '?')) {
            $path_info = substr($path_info, 0, $pos);
        }
        return new RequestContext($base_uri, $_SERVER['REQUEST_METHOD'], $url_host, $url_scheme, $prefs['http_port'] ? $prefs['http_port'] : 80, $prefs['https_port'] ? $prefs['https_port'] : 443, $path_info, http_build_query($_GET));
    }

    protected function prepareRoutes()
    {
        $routes = new RouteCollection();
        $routes->add('home', (new Route(''))->setMethods(['GET']));
        $routes->add('docs', (new Route('docs.yaml', ['_format' => 'yaml']))->setMethods(['GET']));
        $routes->add('trackers', (new Route('trackers', ['controller' => 'tracker', 'action' => 'list_trackers']))->setMethods(['GET']));
        $routes->add('trackers-create', (new Route('trackers', ['controller' => 'tracker', 'action' => 'replace', 'confirm' => 1]))->setMethods(['POST']));
        $routes->add('trackers-view', (new Route('trackers/{trackerId}', ['controller' => 'tracker', 'action' => 'list_items', 'offset' => -1, 'maxRecords' => -1]))->setMethods(['GET']));
        $routes->add('trackers-update', (new Route('trackers/{trackerId}', ['controller' => 'tracker', 'action' => 'replace', 'confirm' => 1]))->setMethods(['POST']));
        $routes->add('trackers-delete', (new Route('trackers/{trackerId}', ['controller' => 'tracker', 'action' => 'remove', 'confirm' => 1]))->setMethods(['DELETE']));
        $routes->add('trackers-clear', (new Route('trackers/{trackerId}/clear', ['controller' => 'tracker', 'action' => 'clear', 'confirmForm' => 'y']))->setMethods(['POST']));
        $routes->add('trackers-duplicate', (new Route('trackers/{trackerId}/duplicate', ['controller' => 'tracker', 'action' => 'duplicate', 'confirm' => 1]))->setMethods(['POST']));
        $routes->add('trackers-dump', (new Route('trackers/{trackerId}/dump', ['controller' => 'tracker', 'action' => 'dump_items']))->setMethods(['GET']));
        $routes->add('trackers-export', (new Route('trackers/{trackerId}/export', ['controller' => 'tracker', 'action' => 'export_profile']))->setMethods(['GET']));
        $routes->add('trackerfields', (new Route('trackers/{trackerId}/fields', ['controller' => 'tracker', 'action' => 'list_fields']))->setMethods(['GET']));
        $routes->add('trackerfields-create', (new Route('trackers/{trackerId}/fields', ['controller' => 'tracker', 'action' => 'add_field']))->setMethods(['POST']));
        $routes->add('trackerfields-update', (new Route('trackers/{trackerId}/fields/{fieldId}', ['controller' => 'tracker', 'action' => 'edit_field']))->setMethods(['POST']));
        $routes->add('trackerfields-delete', (new Route('trackers/{trackerId}/fields', ['controller' => 'tracker', 'action' => 'remove_fields', 'confirm' => 1]))->setMethods(['DELETE']));
        $routes->add('trackeritems-view', (new Route('trackers/{trackerId}/items/{itemId}', ['controller' => 'tracker', 'action' => 'view']))->setMethods(['GET']));
        $routes->add('trackeritems-clone', (new Route('trackers/{trackerId}/items/{itemId}/clone', ['controller' => 'tracker', 'action' => 'clone_item']))->setMethods(['POST']));
        $routes->add('trackeritems-create', (new Route('trackers/{trackerId}/items', ['controller' => 'tracker', 'action' => 'insert_item']))->setMethods(['POST']));
        $routes->add('trackeritems-update', (new Route('trackers/{trackerId}/items/{itemId}', ['controller' => 'tracker', 'action' => 'update_item']))->setMethods(['POST']));
        $routes->add('trackeritems-delete', (new Route('trackers/{trackerId}/items/{itemId}', ['controller' => 'tracker', 'action' => 'remove_item']))->setMethods(['DELETE']));
        $routes->add('trackeritems-status', (new Route('trackers/{trackerId}/items/{itemId}/status', ['controller' => 'tracker', 'action' => 'update_item_status', 'confirm' => 1]))->setMethods(['POST']));
        return $routes;
    }

    protected function renderDocs()
    {
        global $base_url;
        $smarty = TikiLib::lib('smarty');
        $smarty->assign('asset_path', $base_url . 'vendor_bundled/vendor/swagger-api/swagger-ui/dist/');
        echo $smarty->fetch('api/docs.tpl');
    }

    protected function renderDocsYaml()
    {
        global $base_url, $tikipath;
        $docs = file_get_contents($tikipath.'templates/api/docs.yaml');
        $docs = str_replace('{server-url}', $base_url.'api/', $docs);
        echo $docs;
    }
}
