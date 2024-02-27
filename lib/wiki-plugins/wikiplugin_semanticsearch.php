<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
function wikiplugin_semanticsearch_info()
{
    return [
        'name' => tra('Semantic Search'),
        'documentation' => 'PluginSemanticSearch',
        'description' => tra('Create a custom search using the https://github.com/Textualization/php-semantic-search'),
        'prefs' => ['wikiplugin_customsearch', 'feature_search'],
        'format' => 'html',
        'iconname' => 'search',
        'introduced' => 27,
        'tags' => ['advanced'],
        'params' => [
            'tpl' => [
                'required' => false,
                'name' => tra('Template file'),
                'description' => tra('Smarty template (.tpl) file where search user interface template is found'),
                'since' => '27',
                'default' => 'wiki-plugins/wikiplugin_semanticsearch.tpl',
                ],
            'indexer' => [
                'required' => false,
                'name' => tra('Textualization indexer'),
                'description' => tra('The textualization index class to instanciate.'),
                'filter' => 'string',
                'since' => '27',
                'default' => '\\Textualization\\SemanticSearch\\VectorIndex',
                ],
            'embedder' => [
                'required' => false,
                'name' => tra('Textualization embedder'),
                'description' => tra('The textualization embedder class to instanciate.  It must match the one used during server indexing'),
                'filter' => 'string',
                'since' => '27',
                'default' => '\\Textualization\\SemanticSearch\\SentenceTransphormerXLMEmbedder',
                ],
            'dblocation' => [
                'required' => true,
                'name' => tra('Template file'),
                'description' => tra('The filesystem location of the .db file to search into.'),
                'since' => '27'
                ],
        ],
    ];
}

function wikiplugin_semanticsearch($data, $params)
{
    global $prefs;
    $defaults = [];
    $plugininfo = wikiplugin_semanticsearch_info();
    foreach ($plugininfo['params'] as $key => $param) {
        $defaults["$key"] = $param['default'] ?? null;
    }
    $params = array_merge($defaults, $params);

    $id = 'semanticsearch';
    $html = '';
    $location = $params['dblocation'];
    if (! is_file($location)) {
        throw new InvalidArgumentException("$location isn't a file");
    }
    /** This is the configuration object for the SemanticSearch IndexFactory */
    $desc = [];
    $desc["location"] = $location;

    $desc['class'] = $params['indexer'];
    $desc['embedder'] = ['class' => $params['embedder']];
    $desc["max_docs"] = 20;
    $factory = "\Textualization\SemanticSearch\IndexFactory::make";
    if (! is_callable($factory)) {
        throw new Error("Unable to find $factory. You need to install the textualization/semantic-search composer package in your tiki for this plugin to work");
    }
    try {
        $index = call_user_func($factory, $desc);
    } catch (Exception $e) {
        $msg = "Unable to initialise the semantic search index.  Note that the search index requires permission to the index files, and access to ffo in php.ini.  The actual error is: ";
        return $msg . '<br/>' . $e;
    }

    $smarty = new Smarty_Tiki();
    $smarty->assign('id', $id);
    $query = $_REQUEST['query'];
    $smarty->assign('query', $query);
    $smarty->assign('facets', []);

    $smartyResults = [];
    if ($query) {
        $results = $index->search($query);
        foreach ($results as $result) {
            //$html .= print_r($result, true);
            $smartyResult = [];
            //This is just so the template will parse
            $smartyResult['object_type'] = 'wiki page' ;
            //This is just so the template will parse
            $smartyResult['object_id'] = 'dummyid';

            $smartyResult['url'] = $result->url;
            $smartyResult['title'] = $result->title;
            $smartyResult['score'] = sprintf('%.2f', $result->score);
            $chunkText = $index->fetch_document($result->url, $result->chunk_num)->text;
            $smartyResult['highlight'] = substr($chunkText, 0, strrpos(substr($chunkText, 0, 300), ' '));
            $smartyResults[] = $smartyResult;
        }
    }
    $smarty->assign('results', $smartyResults);
    $html .= $smarty->fetch($params['tpl']);
    return $html;
}
