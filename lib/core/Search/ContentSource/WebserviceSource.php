<?php
// (c) Copyright 2002-2017 by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
// $Id$

class Search_ContentSource_WebserviceSource implements Search_ContentSource_Interface
{
	private $db;

	private $tiki_webservice_template;

	function __construct()
	{
		$this->db = TikiDb::get();
		$this->tiki_webservice_template = $this->db->table('tiki_webservice_template');
	}

	function getDocuments()
	{
		// get webservice templates using the index "engine"
		$rows = $this->tiki_webservice_template->fetchAll(['service', 'template', 'output',], ['engine' => 'index']);

		$out = [];

		foreach ($rows as $row) {
			if ($row['output'] === 'mindex') {	// multi-index
				$data = $this->getData($row['service'], $row['template']);
				if ($data) {
					foreach($data['mapping'] as $topObject => $topValue) {
						foreach($data['data'][$topObject] as $key => $val) {
							$out[] = $row['template'] . ':' . $key;
						}
					}
				}
			} else {
				$out[] = $row['template'];
			}
		}

		return $out;
	}

	/**
	 * Uses a JSON formatted "template" value set in the webservices interface
	 * example mapping for a single google place - use http://www.jsoneditoronline.org/ to check syntax
	 	{
	 	  "result": {
	 	    "name": {
	 	      "type": "sortable",
	 	      "field": "name"
	 	    },
	 	    "formatted_address": {
	 	      "type": "sortable",
	 	      "field": "address"
	 	    },
	 	    "formatted_phone_number": {
	 	      "type": "sortable",
	 	      "field": "phone"
	 	    },
	 	    "opening_hours": {
	 	      "weekday_text": {
	 	        "type": "multivalue",
	 	        "field": "opening_hours"
	 	      }
	 	    },
	 	    "types": {
	 	      "type": "multivalue",
	 	      "field": "google_types"
	 	    }
	 	  }
	 	}
	 *
	 *
	 * @param $templateName
	 * @param Search_Type_Factory_Interface $typeFactory
	 * @return array|bool
	 */
	function getDocument($templateName, Search_Type_Factory_Interface $typeFactory)
	{
		if (strpos($templateName, ':') !== false) {	// multi-index template from getDocuments

			list ($templateName, $index) = explode(':', $templateName);
			$serviceName = $this->tiki_webservice_template->fetchOne('service', ['template' => $templateName]);

		} else {

			$row = $this->tiki_webservice_template->fetchRow(['service', 'template', 'output',], ['template' => $templateName]);

			if ($row['output'] === 'mindex') {
				return [];							// TODO only works when reindexing
			} else {
				$serviceName = $row['service'];
				$index = false;
			}
		}

		if (! $serviceName) {
			return [];
		}

		$output = $this->getData($serviceName, $templateName);

		$data = [
			'title' => $typeFactory->sortable($templateName),
			'description' => $typeFactory->sortable(''),
			'modification_date' => $typeFactory->timestamp(TikiLib::lib('tiki')->now),

			// hmm, no perms?
			//'view_permission' => $typeFactory->identifier('tiki_p_view_sheet'),
		];

		$rows = [];

		foreach($output['mapping'] as $topObject => $topValue) {
			$dataObject = $output['data'][$topObject];
			foreach ($dataObject as $key => $val) {
				if (is_int($key) && $index !== false) {    		// array of objects
					$val = $dataObject[$index];
					if (! empty($val)) {	// we have the index # to get
						if (! $this->mapValue($val, $output['mapping'][$topObject][0], $typeFactory, $data)) {
							foreach ($val as $key2 => $val2) {
								if (! empty($val[$key2])) {
									$this->mapValue($val[$key2], $output['mapping'][$topObject][0][$key2], $typeFactory, $data);
								}
							}
						}
					}
					break;	// we just get this index item
				} else {
					if (! empty($dataObject[$key])) {
						if (! $this->mapValue($dataObject[$key], $output['mapping'][$topObject][$key], $typeFactory, $data)) {
							foreach ($val as $index => $val2) {
								if (! empty($dataObject[$key][$index])) {
									$this->mapValue($dataObject[$key][$index], $output['mapping'][$topObject][$key][$index], $typeFactory, $data);
								}
							}
						}
					}
				}
			}
		}

		if ($rows) {
			$data[! empty($topObject) ? $topObject : 'results'] = $typeFactory->nested($rows);
		}

		return $data;
	}

	/**
	 * Add the value form the webservice call to the array of data to return in getDocument
	 *
	 * @param string $value
	 * @param array $mapInfo
	 * @param Search_Type_Factory_Interface $typeFactory
	 * @param array $destinationArray
	 * @param bool $plain
	 * @return bool
	 */
	private function mapValue($value, $mapInfo, $typeFactory, & $destinationArray, $plain = false) {
		if (isset($mapInfo['type']) & isset($mapInfo['field'])) {
			$type = $mapInfo['type'];
			if (! $plain && is_callable([$typeFactory, $type])) {
				$destinationArray[$mapInfo['field']] = $typeFactory->$type($value);
				return true;
			} else {
				$destinationArray[$mapInfo['field']] = $value;
			}
		}
		return false;
	}

	/**
	 * @param $serviceName
	 * @param $templateName
	 * @return bool|mixed|string
	 */
	private function getData($serviceName, $templateName) {
		require_once 'lib/webservicelib.php';

		$webservice = \Tiki_Webservice::getService($serviceName);

		if (! $webservice) {
			return false;
		}

		$params = [];	// TODO get the params here somehow?
		$response = $webservice->performRequest($params);
		$template = $webservice->getTemplate($templateName);

		return $template->render($response, 'index');
	}

	function getProvidedFields()
	{
		return array(
			'title',
			'description',
			'modification_date',

			// TODO more
			//'view_permission',
		);
	}

	function getGlobalFields()
	{
		return array(
			'title' => true,
			'description' => true,
		);
	}
}

