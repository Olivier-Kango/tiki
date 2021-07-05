<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
// $Id$

namespace Tiki\FileGallery\Manipulator;

use TikiLib;

class EmailParser extends Manipulator
{
	public function run($args = [])
	{
		global $prefs, $user;

		$file = $this->file;
		if ($file->filetype != 'message/rfc822') {
			return false;
		}

		$message_content = $file->getContents();
		try {
			$message = \ZBateson\MailMimeParser\Message::from($message_content);
		} catch (\Exception\RuntimeException $e) {
			Feedback::error(tr('Failed parsing file %0 as an email.', $file->fileId) . '<br />' . $e->getMessage());
			return false;
		}

		$result = [
			'message_id' => $message->getHeaderValue('Message-ID'),
			'subject' => $message->getHeaderValue('Subject'),
			'body' => $message->getContent(),
			'from' => $this->getRawAddress($message->getHeader('From')),
			'sender' => $this->getRawAddress($message->getHeader('Sender')),
			'recipient' => $this->getRawAddress($message->getHeader('To')),
			'date' => '',
			'content_type' => $message->getHeaderValue('Content-Type'),
			'plaintext' => $message->getTextContent(),
			'html' => $message->getHtmlContent(),
			'message_raw' => $message,
		];

		$date = $message->getHeader('Date');
		if ($date) {
			$result['date'] = $date->getDateTime()->getTimestamp();
		} else {
			$result['date'] = '';
		}

		return $result;
	}

	protected function getRawAddress($header)
    {
		if ($header) {
			if (function_exists('mb_decode_mimeheader')) {
				return mb_decode_mimeheader($header->getRawValue());
			} else {
				return $header->getRawValue();
			}
		} else {
			return '';
		}
	}
}
