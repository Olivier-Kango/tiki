<?php
// (c) Copyright 2002-2016 by authors of the Tiki Wiki CMS Groupware Project
// 
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
// $Id$

function prefs_unified_list()
{
	return array(
		'unified_engine' => array(
			'name' => tra('Unified search engine'),
			'description' => tra('Search engine used to index the content of your Tiki. Some engines are more suitable for larger sites, but require additional software on the server.'),
			'type' => 'list',
			'options' => array(
				'lucene' => tra('Lucene (PHP implementation)'),
				'mysql' => tra('MySQL full-text search'),
				'elastic' => tra('Elasticsearch'),
			),
			'default' => 'mysql',
		),
		'unified_lucene_location' => array(
			'name' => tra('Lucene index location'),
			'description' => tra('Path to the location of the Lucene search index. The index must be on a local filesystem with enough space to contain the volume of the database.'),
			'type' => 'text',
			'size' => 35,
			'default' => 'temp/unified-index',
		),
		'unified_lucene_highlight' => array(
			'name' => tra('Highlight results snippets'),
			'description' => tra('Highlight the result snippet based on the search query. Enabling this option will impact performance, but improve user experience.'),
			'type' => 'flag',
			'default' => 'n',
			'tags' => array('basic'),
		),
		'unified_lucene_max_result' => array(
			'name' => tra('Lucene maximum results'),
			'description' => tra('Maximum number of results to produce. Results beyond these will need a more refined query to be reached.'),
			'type' => 'text',
			'filter' => 'int',
			'units' => tra('results'),
			'default' => 200,
			'size' => 6,
		),
		'unified_lucene_max_resultset_limit' => array(
			'name' => tra('Lucene maximum result-set limit'),
			'hint' => tra('Maximum size of result set to consider. Default 1000, 0 = unlimited.'),
			'description' => tra('This is used when calculating result scores and sort order which can lead to out of memory errors on large data sets. The default of 1000 is safe with the PHP memory_limit set to 128M'),
			'type' => 'text',
			'filter' => 'int',
			'units' => tra('result sets'),
			'default' => 1000,
			'size' => 6,
		),
		'unified_lucene_terms_limit' => array(
			'name' => tra('Lucene terms per query limit'),
			'description' => tra('Maximum number of terms to be generated. Try increasing this value if you get errors saying "Terms per query limit is reached" espescially with wildcard, range and fuzzy searches.'),
			'type' => 'text',
			'filter' => 'int',
			'units' => tra('terms'),
			'default' => 1024,
			'size' => 6,
		),
		'unified_lucene_max_buffered_docs' => array(
			'name' => tra('Lucene maximum number of buffered documents'),
			'description' => tra('Number of documents required before the buffered in-memory documents are written into a new segment.'),
			'hint' => tra(''),
			'type' => 'text',
			'filter' => 'int',
			'units' => tra('documents'),
			'default' => 10,
			'size' => 6,
		),
		'unified_lucene_max_merge_docs' => array(
			'name' => tra('Lucene maximum number of merge documents'),
			'description' => tra('Largest number of documents merged by addDocument(). Small values (for example, less than 10,000) are best for interactive indexing, as this limits the length of pauses while indexing to a few seconds. Larger values are best for batched indexing and speedier searches.'),
			'hint' => tra('Small values (for example, less than 10,000) are best for interactive indexing. Use 0 for the Lucene default, which is practically infinite.'),
			'type' => 'text',
			'filter' => 'int',
			'units' => tra('merge documents'),
			'default' => 0,
			'size' => 8,
		),
		'unified_lucene_default_operator' => array(
			'name' => tra('Default Boolean Operator'),
			'description' => tra('Use OR (default) or AND as the default search operator.'),
			'type' => 'list',
			'filter' => 'int',
			'default' => ZendSearch\Lucene\Search\QueryParser::B_OR,
			'options' => array(
				0 => tra('OR'),
				1 => tra('AND'),
			),
		),
		'unified_lucene_merge_factor' => array(
			'name' => tra('Lucene merge factor'),
			'description' => tra('How often segment indices are merged by addDocument(). With smaller values, less RAM is used while indexing, and searches on unoptimized indices are faster, but indexing speed is slower. With larger values, more RAM is used during indexing, and while searches on unoptimized indices are slower, indexing is faster. Thus larger values (> 10) are best for batch index creation, and smaller values (< 10) for indices that are interactively maintained.'),
			'hint' => tra('Large values (greater than 10) are best for batch index creation, and smaller values (less than 10) for indices that are interactively maintained.'),
			'type' => 'text',
			'filter' => 'int',
			'default' => 10,
			'size' => 6,
		),
		'unified_incremental_update' => array(
			'name' => tra('Incremental Index Update'),
			'description' => tra('Update the index incrementally as the site content is modified. This may lead to lower performance and accuracy than processing the index on a periodic basis.'),
			'type' => 'flag',
			'default' => 'y',
		),
		'unified_field_weight' => array(
			'name' => tra('Field weights'),
			'description' => tra('Allows the field weights to be set that apply when ranking the pages for search listing. The weight only applies when the field is in the query. To nullify the value of a field, use an insignificant amount, not 0, which may lead to unexpected behaviors, such as stripping results.'),
			'hint' => tra('One field per line, field_name__:__5.3'),
			'type' => 'textarea',
			'size' => 5,
			'filter' => 'text',
			'default' => "title:2.5\nallowed_groups:0.0001\ncategories:0.0001\ndeep_categories:0.0001",
		),
		'unified_default_content' => array(
			'name' => tra('Default content fields'),
			'description' => tra('All of the content is aggregated in the contents field. For custom weighting to apply, the fields must be included in the query. This option allows other fields to be included in the default content search.'),
			'type' => 'text',
			'separator' => ',',
			'filter' => 'word',
			'default' => array('contents', 'title'),
			'size' => 80,
		),
		'unified_tokenize_version_numbers' => array(
			'name' => tra('Tokenize version numbers'),
			'description' => tra('Tokenize version number strings so that major versions are found when sub-versions are mentionned. For example, searching for 2.7 would return documents containing 2.7.4, but not 1.2.7.'),
			'type' => 'flag',
			'default' => 'n',
		),
		'unified_user_cache' => array(
			'name' => tra('Cache per user and query'),
			'type' => 'text',
			'size' => '4',
			'filter' => 'digits',
			'description' => tra('Time in minutes a user has a same query cached '),
			'units' => tra('minutes'),
			'default' => '0',
			'tags' => array('advanced'),
		),
		'unified_forum_deepindexing' => array(
			'name' => tra('Index forum replies together with initial post'),
			'description' => tra('If enabled, forum replies will be indexed together with the initial post as a single document instead of being indexed separately'),
			'type' => 'flag',
			'default' => 'y',
		),
		'unified_relation_object_indexing' => array(
			'name' => tra('Relation types to index within object.'),
			'description' => tra('Comma-separated relation types for which objects should be indexed in their related objects. (Elasticsearch needed)'),
			'type' => 'textarea',
			'default' => '',
			'dependencies' => array(
				'unified_elastic_index_current',
			),
		),
		'unified_cached_formatters' => array(
			'name' => tra('Search formatters to cache'),
			'description' => tra('Search formatters to cache the output of'),
			'type' => 'text',
			'separator' => ',',
			'default' => array('categorylist'),
		),
		'unified_trackerfield_keys' => array(
			'name' => tra('Format to use for tracker field keys'),
			'description' => tra('Choose between field IDs and permanent names for the tracker indexing'),
			'type' => 'list',
			'default' => 'permName',
			'options' => array(
				'permName' =>tr('Permanent name'),
				'fieldId' => tr('Field ID (backward compatibility mode with Tiki 7 and 8)'),
			),
		),
		'unified_parse_results' => array(
			'name' => tra('Parse the results'),
			'description' => tra('Parse the results. May impact performance'),
			'type' => 'flag',
			'default' => 'n',
		),
		'unified_excluded_categories' => array(
			'name' => tra('Excluded categories'),
			'description' => tra('List of category IDs to exclude from the search index.'),
			'type' => 'text',
			'separator' => ',',
			'default' => array(),
			'profile_reference' => 'category',
		),
		'unified_excluded_plugins' => array(
			'name' => tra('Excluded plugins'),
			'description' => tra('List of plugin names to exclude while indexing.'),
			'type' => 'text',
			'filter' => 'word',
			'separator' => ',',
			'default' => array(),
		),
		'unified_exclude_all_plugins' => array(
			'name' => tra('Exclude all plugins'),
			'description' => tra('If enabled, indexing will exclude all plugins.'),
			'type' => 'flag',
			'default' => 'y',
		),
		'unified_included_plugins' => array(
			'name' => tra('Except included plugins'),
			'description' => tra('List of plugin names that are required to be included while indexing, when excluding all.'). ' ' . tra('Example: fancytable,list,trackerlist,trackerfilter .'),
			'type' => 'text',
			'filter' => 'word',
			'separator' => ',',
			'dependencies' => array(
				'unified_exclude_all_plugins',
			),
			'default' => array(),
		),
		'unified_elastic_url' => array(
			'name' => tra('Elasticsearch URL'),
			'description' => tra('URL of any node in the cluster.'),
			'type' => 'text',
			'filter' => 'url',
			'default' => 'http://localhost:9200',
			'size' => 40,
		),
		'unified_elastic_index_prefix' => array(
			'name' => tra('Elasticsearch index prefix'),
			'description' => tra('Prefix used for all indexes for this installation in Elasticsearch.'),
			'type' => 'text',
			'filter' => 'word',
			'default' => 'tiki_',
			'size' => 10,
		),
		'unified_elastic_index_current' => array(
			'name' => tra('Elasticsearch current index'),
			'description' => tra('A new index is created upon rebuild and the old one is then destroyed. This setting allows you to see the currently active one.'),
			'hint' => tra('Do not change this value unless you know what you are doing.'),
			'type' => 'text',
			'filter' => 'word',
			'size' => '20',
			'default' => '',
		),
		'unified_elastic_camel_case' => array(
			'name' => tr('Tokenize CamelCase words'),
			'description' => tr('Consider the components of camel-case words as separate tokens, allowing them to be searched individually.'),
			'warning' => tr('Conflicts with Tokenize Version Numbers.'),
			'hint' => tr('Elasticsearch only'),
			'type' => 'flag',
			'default' => 'n',
		),
		'unified_elastic_field_limit' => array(
			'name' => tra('Elasticsearch field limit per index'),
			'type' => 'text',
			'size' => '5',
			'filter' => 'digits',
			'description' => tra('Maximum number of fields per search index in Elasticsearch version 5.x and above.'),
			'default' => '1000',
		),
		'unified_mysql_index_current' => array(
			'name' => tra('MySQL full-text search current index'),
			'description' => tra('A new index is created upon rebuild and the old one is then destroyed. This setting allows you to see the currently active one.'),
			'hint' => tra('Do not change this value unless you know what you are doing.'),
			'type' => 'text',
			'filter' => 'word',
			'size' => '20',
			'default' => '',
		),
		'unified_identifier_fields' => array(
			'name' => tr('Unified index identifier fields (Internal)'),
			'description' => tr('Used to store the fields to be considered as identifiers. Overwritten after each index rebuild.'),
			'type' => 'text',
			'hint' => tra('Do not change this value unless you know what you are doing.'),
			'separator' => ',',
			'default' => array(),
			'filter' => 'word',
		),
		'unified_add_to_categ_search' => array(
			'name' => tra('Use unified search in category admin'),
			'description' => tra('Use unfied search to find objects to add to categories. Limits types of objects available to those included in the unified index.'),
			'type' => 'flag',
			'default' => 'n',
			'dependencies' => array(
				'feature_search',
			),
		),
		'unified_stopwords' => array(
			'name' => tr('Stop Word List'),
			'description' => tr('Words excluded from the search index as they can be too frequent and cause noise.'),
			'type' => 'text',
			'default' => ["a", "an", "and", "are", "as", "at", "be", "but", "by", "for", "if", "in", "into", "is", "it", "not", "of", "on", "or", "s", "such", "t", "that", "the", "their", "then", "there", "these", "they", "this", "to", "was", "will", "with"],
			'separator' => ',',
			'hint' => tr('MySQL full-text search has its own list of stop words configured in the server.'),
		),
		'unified_trim_sorted_search' => array(
			'name' => tra('Automatically trim Elasticsearch results on date-sorted query'),
			'description' => tra('Automatically trim Elastic Search results in unified search if the query is sorted by modification or creation date.'),
			'type' => 'flag',
			'default' => 'n',
			'dependencies' => array(
				'feature_search',
			),
		),
		'unified_highlight_results' => array(
			'name' => tra('Highlight results pages'),
			'description' => tra('Highlight words on the result pages based on the search query.'),
			'type' => 'flag',
			'default' => 'y',
			'tags' => array('basic'),
		),
		'unified_search_textarea_admin' => array(
			'name' => tra('Plugins tab of the textarea control panel loads with an empty list'),
			'description' => tra('Increase performance of the textarea control panel by avoiding to load all plugins initially'),
			'type' => 'flag',
			'default' => 'n',
			'dependencies' => array(
				'feature_search',
			),
			'tags' => array('experimental'), // See warning
			'warning' => tra('Some plugins may not appear. When using the MySQL engine, can have problems with short plugin names (for MyISAM, those under "ft_min_word_len").'), // See ticket #6313
		),
	);
}

