<?php
// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
// $Id$

class Tracker_Field_EmailFolder extends Tracker_Field_Files implements Tracker_Field_Exportable
{
	public static function getTypes()
	{
		global $prefs;

		$options = [
			'EF' => [
				'name' => tr('Email Folder'),
				'description' => tr('Associate email messages with tracker items.'),
				'prefs' => ['trackerfield_email_folder', 'feature_file_galleries'],
				'tags' => ['advanced'],
				'help' => 'Email Folder Tracker Field',
				'default' => 'y',
				'params' => [
					'galleryId' => [
						'name' => tr('Gallery ID'),
						'description' => tr('File gallery to upload new emails into.'),
						'filter' => 'int',
						'legacy_index' => 0,
						'profile_reference' => 'file_gallery',
					],
				],
			],
		];
		return $options;
	}

	function getFieldData(array $requestData = [])
	{
		global $prefs;
		$filegallib = TikiLib::lib('filegal');

		$galleryId = (int) $this->getOption('galleryId');
		$galinfo = $filegallib->get_file_gallery($galleryId);
		if (!$galinfo) {
			Feedback::error(tr('Files field: Gallery #%0 not found', $galleryId));
			return [];
		}

		$value = '';
		$ins_id = $this->getInsertId();
		if (isset($requestData[$ins_id])) {
			// Incoming data from form
			$value = $requestData[$ins_id];
		} else {
			$value = $this->getValue();
		}

		// Obtain the information from the database for display
		$fileIds = array_filter(explode(',', $value));
		$emails = [];
		foreach ($fileIds as $fileId) {
			$file_object = Tiki\FileGallery\File::id($fileId);
			$parsed_fields = (new Tiki\FileGallery\Manipulator\EmailParser($file_object))->run();
			$parsed_fields['fileId'] = $fileId;
			$parsed_fields['trackerId'] = $this->getTrackerDefinition()->getConfiguration('trackerId');
			$parsed_fields['itemId'] = $this->getItemId();
			$parsed_fields['fieldId'] = $this->getConfiguration('fieldId');
			$emails[] = $parsed_fields;
		}

		return [
			'galleryId' => $galleryId,
			'emails' => $emails,
			'value' => $value,
		];
	}

	function renderInput($context = [])
	{
		return $this->renderOutput($context);
	}

	function renderOutput($context = [])
	{
		if (! isset($context['list_mode'])) {
			$context['list_mode'] = 'n';
		}

		$value = $this->getValue();

		if ($context['list_mode'] === 'csv') {
			return $value;
		}

		$emails = $this->getConfiguration('emails');

		if ($context['list_mode'] === 'text') {
			return implode(
				"\n",
				array_map(
					function ($email) {
						return $email['subject'];
					},
					$emails
				)
			);
		}

		return $this->renderTemplate('trackeroutput/email_folder.tpl', $context, [
			'emails' => $emails
		]);
	}

	function handleSave($value, $oldValue)
	{
		$filegallib = TikiLib::lib('filegal');
		if (isset($value['new'])) {
			$galleryId = (int) $this->getOption('galleryId');
			$galinfo = $filegallib->get_file_gallery($galleryId);
			$fileId = $filegallib->upload_single_file($galinfo, $value['new']['name'], $value['new']['size'], $value['new']['type'], $value['new']['content']);
			$value = explode(',', $oldValue);
			if ($fileId) {
				$value[] = $fileId;
			}
		} elseif (isset($value['delete'])) {
			$fileId = $value['delete'];
			$existing = explode(',', $oldValue);
			if (($key = array_search($fileId, $existing)) !== false) {
				unset($existing[$key]);
				$value = $existing;
			}
			$info = $filegallib->get_file_info($fileId);
			if ($info) {
				$filegallib->remove_file($info);
			}
		}
		return parent::handleSave(implode(',', $value), $oldValue);
	}

	function getDocumentPart(Search_Type_Factory_Interface $typeFactory)
	{
		$value = $this->getValue();
		$baseKey = $this->getBaseKey();
		$emails = $this->getConfiguration('emails');

		$subjects = [];
		$dates = [];
		$senders = [];
		$recipients = [];
		foreach ($emails as $email) {
			$subjects[] = $email['subject'];
			$dates[] = $email['date'];
			$senders[] = $email['sender'];
			$recipients[] = $email['recipient'];
		}

		$out = [
			$baseKey => $typeFactory->identifier($value),
			"{$baseKey}_subjects" => $typeFactory->multivalue($subjects),
			"{$baseKey}_dates" => $typeFactory->multivalue($dates),
			"{$baseKey}_senders" => $typeFactory->multivalue($senders),
			"{$baseKey}_recipients" => $typeFactory->multivalue($recipients),
		];
		return $out;
	}

	function getProvidedFields()
	{
		$baseKey = $this->getBaseKey();
		$fields = [
			$baseKey,
			"{$baseKey}_subjects",
			"{$baseKey}_dates",
			"{$baseKey}_senders",
			"{$baseKey}_recipients",
		];
		return $fields;
	}

	function getGlobalFields()
	{
		$baseKey = $this->getBaseKey();
		return [$baseKey => true];
	}

	function getTabularSchema()
	{
		$schema = new Tracker\Tabular\Schema($this->getTrackerDefinition());

		$permName = $this->getConfiguration('permName');
		$name = $this->getConfiguration('name');

		$schema->addNew($permName, 'default')
			->setLabel($name)
			->setRenderTransform(function ($value) {
				return $value;
			})
			->setParseIntoTransform(function (& $info, $value) use ($permName) {
				$info['fields'][$permName] = $value;
			});

		return $schema;
	}
}
