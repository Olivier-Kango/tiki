<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
function prefs_unified_list()
{
    return [
        'unified_engine' => [
            'name' => tra('Unified search engine'),
            'description' => tra('Search engine used to index the content of this Tiki site. Some engines are more suitable for larger sites, but require additional software on the server.'),
            'type' => 'list',
            'options' => [
                'mysql' => tra('MySQL full-text search'),
                'elastic' => tra('Elasticsearch'),
                'manticore' => tra('Manticore Search'),
            ],
            'default' => 'mysql',
        ],
        'unified_search_default_operator' => [
            'name' => tra('Default Boolean Operator'),
            'description' => tra('Use OR or AND as the default search operator.'),
            'type' => 'list',
            'filter' => 'int',
            'default' => 1,
            'options' => [
                1 => tra('AND'),
                0 => tra('OR'),
            ],
        ],
        'unified_incremental_update' => [
            'name' => tra('Incremental Index Update'),
            'description' => tra('Update the index incrementally as the site content is modified.'),
            'type' => 'flag',
            'warning' => tra('This may lead to lower performance and accuracy than processing the index on a periodic basis.'),
            'default' => 'y',
        ],
        'unified_field_weight' => [
            'name' => tra('Field weights'),
            'description' => tra('Allow the field weights to be set that apply when ranking pages in the search results. The weight is applied only when the field is in the query. To nullify the value of a field, use an insignificant amount, but not 0, which may lead to unexpected behaviors such as stripping of results.') .
                '<br>(' . tr('Add these fields to the "Default content fields" preference below for it to have an effect in a global "content" search') . ')',
            'hint' => tra('One field per line, field_name__:__5.3'),
            'type' => 'textarea',
            'size' => 5,
            'filter' => 'text',
            'default' => "title:2.5\nallowed_groups:0.0001\ncategories:0.0001\ndeep_categories:0.0001",
        ],
        'unified_numeric_field_scroll' => [
            'name' => tra('Numeric field data modification via scroll'),
            'description' => tra('Allow the numeric field data to be changed on movement of mousepad or mouse scroll'),
            'type' => 'list',
            'options' => [
                'none' => tra('Disabled'),
                'browser' => tra('Use default behavior of the browser (browsers have different behaviors)'),
            ],
            'default' => 'none',
            'keywords' => 'wheel',
        ],
        'unified_default_content' => [
            'name' => tra('Default content fields'),
            'description' => tra('All of the content is aggregated in the contents field. For custom weighting to apply, the fields must be included in the query. This option allows other fields to be included in the default content search.'),
            'type' => 'text',
            'separator' => ',',
            'filter' => 'word',
            'default' => ['contents', 'title'],
            'size' => 80,
        ],
        'unified_tokenize_version_numbers' => [
            'name' => tra('Tokenize version numbers'),
            'description' => tra('Tokenize version number strings so that major versions are found when sub-versions are mentioned. For example, searching for 2.7 would return documents containing 2.7.4, but not 1.2.7.'),
            'type' => 'flag',
            'default' => 'n',
        ],
        'unified_user_cache' => [
            'name' => tra('Cache per user and query for Tiki built-in search'),
            'type' => 'text',
            'size' => '4',
            'filter' => 'digits',
            'description' => tra('Time in minutes a user has a same query cached applied to Tiki built-in search interface only.'),
            'units' => tra('minutes'),
            'default' => '0',
            'tags' => ['advanced'],
        ],
        'unified_forum_deepindexing' => [
            'name' => tra('Index forum replies together with initial post'),
            'description' => tra('Forum replies will be indexed together with the initial post as a single document instead of being indexed separately.'),
            'type' => 'flag',
            'default' => 'y',
        ],
        'unified_relation_object_indexing' => [
            'name' => tra('Relation types to index within object.'),
            'description' => tra('Comma-separated relation types for which objects should be indexed in their related objects.'),
            'type' => 'textarea',
            'hint' => tr('Elasticsearch needed'),
            'default' => '',
            'dependencies' => [
                'unified_elastic_index_current',
            ],
        ],
        'unified_cached_formatters' => [
            'name' => tra('Cache individual search formatters'),
            'description' => tra('List of search formatters whose output will be cached. This is separate to the result-specific formatted results cache.'),
            'type' => 'text',
            'separator' => ',',
            'default' => [],
            'tags' => ['advanced'],
        ],
        'unified_trackeritem_category_names' => [
            'name' => tra('Index Tracker Category names'),
            'description' => tra('Index the names and paths of category field values'),
            'hint' => tra('Requires reindexing'),
            'type' => 'flag',
            'default' => 'y',
            'dependencies' => [
                'feature_trackers',
                'feature_categories',
                'feature_search',
            ],
        ],
        'unified_cache_formatted_result' => [
            'name' => tra('Cache result-specific formatted results'),
            'description' => tr('Formatted search results such as the ones used in the List plugin will be cached to prevent process-intensive reformatting on each page load. The cache is result-specific.'),
            'warning' => tr('Every different result will generate a separate cache. This could quickly build up a large cache directory. It is recommended to clear Tiki caches often (e.g. once per week) via an automated job if you use this feature.'),
            'type' => 'flag',
            'default' => 'n',
            'tags' => ['advanced'],
        ],
        'unified_excluded_categories' => [
            'name' => tra('Excluded categories'),
            'description' => tra('List of category IDs to exclude from the search index'),
            'type' => 'text',
            'separator' => ',',
            'default' => [],
            'profile_reference' => 'category',
        ],
        'unified_excluded_plugins' => [
            'name' => tra('Excluded plugins'),
            'description' => tra('List of plugin names to exclude while indexing'),
            'type' => 'text',
            'filter' => 'word',
            'separator' => ',',
            'default' => [],
        ],
        'unified_included_plugins' => [
            'name' => tra('Additional plugins searchable by default'),
            'description' => tra('List of plugin names that are required to additionnaly include while indexing.') . ' ' . tra('Example: fancytable,list,trackerlist,trackerfilter'),
            'type' => 'text',
            'filter' => 'word',
            'separator' => ',',
            'default' => [
                "attach",
                "box",
                "code",
                "copyright",
                "div",
                "dl",
                "fancylist",
                "fancytable",
                "file",
                "files",
                "font",
                "footnote",
                "indent",
                "lang",
                "list",
                "markdown",
                "mono",
                "quote",
                "scroll",
                "sort",
                "split",
                "sub",
                "sup",
            ],
        ],
        'unified_exclude_nonsearchable_fields' => [
            'name' => tra('Don\'t index non searchable fields'),
            'description' => tra('Indexing will skip adding all tracker fields that are not marked as "searchable". This will free index space but also make it impossible to use those fields in search index queries.'),
            'type' => 'flag',
            'default' => 'n',
        ],
        'unified_exclude_nonsearchable_fields_from_facets' => [
            'name' => tra('Exclude non searchable fields from facets'),
            'description' => tra('Only tracker fields checked as "search" to generate facets on the default search page.'),
            'type' => 'flag',
            'filter' => 'alpha',
            'default' => 'n',
            'dependencies' => [
                'search_use_facets',
                'feature_trackers',
            ],
        ],
        'unified_elastic_url' => [
            'name' => tra('Elasticsearch URL'),
            'description' => tra('URL of any node in the cluster'),
            'type' => 'text',
            'filter' => 'url',
            'default' => 'http://localhost:9200',
            'size' => 40,
        ],
        'unified_elastic_auth' => [
            'name' => tra('Elasticsearch Authentication'),
            'description' => tra('When Elasticsearch security module is enabled, user authentication can be set up here.'),
            'type' => 'list',
            'options' => [
                '' => tra('No Authentication'),
                'basic' => tra('Basic Authentication'),
            ],
            'default' => '',
        ],
        'unified_elastic_user' => [
            'name' => tra('Elasticsearch User'),
            'description' => tra('HTTP basic authentication user to be sent with each request to Elasticsearch.'),
            'type' => 'text',
            'default' => '',
            'size' => 20,
            'dependencies' => [
                'unified_elastic_auth'
            ],
        ],
        'unified_elastic_pass' => [
            'name' => tra('Elasticsearch Password'),
            'description' => tra('HTTP basic authentication password to be sent with each request to Elasticsearch.'),
            'type' => 'password',
            'default' => '',
            'size' => 20,
            'dependencies' => [
                'unified_elastic_auth'
            ],
        ],
        'unified_elastic_index_prefix' => [
            'name' => tra('Elasticsearch index prefix'),
            'description' => tra('The prefix that is used for all indexes for this installation in Elasticsearch'),
            'type' => 'text',
            'filter' => 'word',
            'default' => 'tiki_',
            'size' => 10,
        ],
        'unified_elastic_index_current' => [
            'name' => tra('Elasticsearch current index'),
            'description' => tra('A new index is created upon rebuilding, and the old one is then destroyed. This setting enables seeing the currently active index.'),
            'hint' => tra('Do not change this value unless you know what you are doing.'),
            'type' => 'text',
            'filter' => 'word',
            'size' => '20',
            'default' => '',
        ],
        'unified_elastic_camel_case' => [
            'name' => tr('Tokenize CamelCase words'),
            'description' => tr('Consider the components of camel-case words as separate tokens, allowing them to be searched individually.'),
            'warning' => tr('Conflicts with Tokenize Version Numbers.'),
            'type' => 'flag',
            'default' => 'n',
        ],
        'unified_elastic_field_limit' => [
            'name' => tra('Elasticsearch field limit per index'),
            'units' => tra('fields'),
            'type' => 'text',
            'size' => '5',
            'filter' => 'digits',
            'description' => tra('The maximum number of fields per search index in Elasticsearch version 5.x and above'),
            'default' => '1000',
        ],
        'unified_elastic_mysql_search_fallback' => [
            'name' => tra('Use MySQL Full-Text Search (fallback)'),
            'type' => 'flag',
            'description' => tra('In case of Elasticsearch is active and unavailable, use MySQL Full-Text Search as fallback'),
            'default' => 'n',
        ],
        'unified_mysql_index_current' => [
            'name' => tra('MySQL full-text search current index'),
            'description' => tra('A new index is created upon rebuilding, and the old one is then destroyed. This setting enables seeing the currently active index.'),
            'hint' => tra('Do not change this value unless you know what you are doing.'),
            'type' => 'text',
            'filter' => 'word',
            'size' => '20',
            'default' => '',
        ],
        'unified_mysql_index_rebuilding' => [
            'name' => tra('The current MariaDB/MySQL index name that is being rebuilt (Internal)'),
            'description' => tra('This value helps to determine if there is a rebuild in progress, for incremental search.'),
            'type' => 'text',
            'filter' => 'word',
            'size' => '20',
            'default' => '',
        ],
        'unified_mysql_short_field_names' => [
            'name' => tra('MySQL use short field names'),
            'description' => tra('Due to frm file constraints, number of search fields that one index can hold is usually limited to about 1500. This can be exceeded if you have numerous tracker fields. Enabling this option will try to shorten the field names internally that should allow you to use 300-500 more fields. Switching this option requires full index rebuild.'),
            'type' => 'flag',
            'default' => 'n',
        ],
        'unified_mysql_restore_indexes' => [
            'name' => tra('Restore old MySQL indexes during reindex'),
            'description' => tra('If set, after the reindex is performed, old table MySQL indexes will be restored to the reindex related table.'),
            'type' => 'flag',
            'default' => 'n',
            'tags' => ['experimental'],
        ],
        'unified_manticore_url' => [
            'name' => tra('Manticore URL'),
            'description' => tra('URL of the Manticore search server'),
            'type' => 'text',
            'filter' => 'url',
            'default' => 'http://127.0.0.1',
            'size' => 40,
        ],
        'unified_manticore_http_port' => [
            'name' => tra('Manticore HTTP(S) Port'),
            'description' => tra('Port number for the HTTP(S) interface.'),
            'type' => 'text',
            'default' => '9308',
            'filter' => 'digits',
            'size' => 10,
        ],
        'unified_manticore_mysql_port' => [
            'name' => tra('Manticore MySQL Port'),
            'description' => tra('Port number for the MySQL interface.'),
            'type' => 'text',
            'default' => '9306',
            'filter' => 'digits',
            'size' => 10,
        ],
        'unified_manticore_index_prefix' => [
            'name' => tra('Manticore index prefix'),
            'description' => tra('The prefix that is used for all indexes for this installation in Manticore'),
            'type' => 'text',
            'filter' => 'word',
            'default' => 'tiki_',
            'size' => 10,
        ],
        'unified_manticore_index_current' => [
            'name' => tra('Manticore current index'),
            'description' => tra('A new set of indexes are created upon rebuilding, and the old ones are then destroyed. This setting enables seeing the currently active index prefix.'),
            'hint' => tra('Do not change this value unless you know what you are doing.'),
            'type' => 'text',
            'filter' => 'word',
            'size' => '20',
            'default' => '',
        ],
        'unified_manticore_index_rebuilding' => [
            'name' => tra('The current Manticore index name that is being rebuilt (Internal)'),
            'description' => tra('This value helps to determine if there is a rebuild in progress, for incremental search.'),
            'type' => 'text',
            'filter' => 'word',
            'size' => '20',
            'default' => '',
        ],
        'unified_manticore_morphology' => [
            'name' => tr('Morphology processing'),
            'description' => tr("Advanced morphology preprocessors to apply in the Manticore index, comma-separated.  For example libstemmer_en,libstemmer_fr. See Manticore manual for possible values."),
            'type' => 'text',
            'default' => '',
            'help' => 'https://manual.manticoresearch.com/Creating_an_index/NLP_and_tokenization/Morphology',
        ],
        'unified_manticore_always_index' => [
            'name' => tr('Manticore indexed full-text fields'),
            'description' => tr("Manticore has a hard-limit of 256 full-text indexed fields per index. If your installation has more, some will be indexed as string attributes and perform the slower regex search. You can add a comma-separated list of fields to always index as full-text here."),
            'type' => 'textarea',
            'default' => 'title,contents',
        ],
        'unified_identifier_fields' => [
            'name' => tr('Unified index identifier fields (Internal)'),
            'description' => tr('Used to store the fields to be considered as identifiers. This is overwritten after each index rebuilding.'),
            'type' => 'text',
            'hint' => tra('Do not change this value unless you know what you are doing.'),
            'separator' => ',',
            'default' => [],
            'filter' => 'word',
        ],
        'unified_add_to_categ_search' => [
            'name' => tra('Use unified search in category admin'),
            'description' => tra('Use unified search to find objects to add to categories. This limits the types of objects available to those included in the unified index.'),
            'type' => 'flag',
            'default' => 'n',
            'dependencies' => [
                'feature_search',
            ],
        ],
        'unified_stopwords' => [
            'name' => tr('Stop Word List'),
            'description' => tr('Words excluded from the search index, because they can be too frequent and produce unwanted results.'),
            'type' => 'text',
            'default' => ["a", "an", "and", "are", "as", "at", "be", "but", "by", "for", "if", "in", "into", "is", "it", "not", "of", "on", "or", "s", "such", "t", "that", "the", "their", "then", "there", "these", "they", "this", "to", "was", "will", "with"],
            'separator' => ',',
            'hint' => tr('MySQL full-text search has its own list of stop words configured in the server.'),
        ],
        'unified_trim_sorted_search' => [
            'name' => tra('Automatically trim Elasticsearch results on date-sorted query'),
            'description' => tra('Automatically trim Elasticsearch results in unified search if the query is sorted by modification or creation date.'),
            'type' => 'flag',
            'default' => 'n',
            'dependencies' => [
                'feature_search',
            ],
        ],
        'unified_highlight_results' => [
            'name' => tra('Highlight results pages'),
            'description' => tra('Highlight words on the result pages based on the search query.'),
            'type' => 'flag',
            'default' => 'y',
            'tags' => ['basic'],
        ],
        'unified_search_textarea_admin' => [
            'name' => tra('Plugins tab of the textarea control panel loads with an empty list'),
            'description' => tra('Improve the performance of the textarea control panel by avoiding the loading of all plugins initially'),
            'type' => 'flag',
            'default' => 'n',
            'dependencies' => [
                'feature_search',
            ],
            'tags' => ['experimental'], // See warning
            'warning' => tra('Some plugins may not appear. When using the MySQL engine, can have problems with short plugin names (for MyISAM, those under "ft_min_word_len").'), // See ticket #6313
        ],
        'unified_elastic_possessive_stemmer' => [
            'name' => tr('Possessive Stemmer'),
            'description' => tr("The possessive stemmer removes possessives (trailing \"'s\") from words before indexing them."),
            'type' => 'flag',
            'default' => 'y',
        ],
        'unified_list_cache_default_on' => [
            'name' => tra('LIST plugin cache default on'),
            'description' => tra('If selected, LIST plugins will be cached by default unless turned off at plugin level.'),
            'type' => 'flag',
            'default' => 'n',
            'tags' => ['advanced'],
            'help' => 'PluginList',
        ],
        'unified_list_cache_default_expiry' => [
            'name' => tra('LIST plugin cache default expiry'),
            'description' => tra('Default number of minutes for LIST plugin cache expiry.'),
            'type' => 'text',
            'default' => '30',
            'tags' => ['advanced'],
            'help' => 'PluginList',
        ],
        'unified_last_rebuild_stats' => [
            'name' => tra('Last rebuild statistics'),
            'description' => tra('Record of last rebuild object counts and timings.'),
            'hint' => tra('Do not change this value unless you know what you are doing.'),
            'type' => 'text',
            'default' => [],
            'tags' => ['advanced'],
        ],
    ];
}
