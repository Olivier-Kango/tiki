<?php
// (c) Copyright 2002-2016 by authors of the Tiki Wiki CMS Groupware Project
// 
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
// $Id$

class Services_Rating_Controller
{
	function action_vote($input)
	{
		$type = $input->type->text();
		$id = $input->id->id();

		$rating_value = $input->rating_value->asArray();
		$rating_prev = $input->rating_prev->asArray();

		$_REQUEST['rating_value'] = $rating_value;
		$_REQUEST['rating_prev'] = $rating_prev;

		return array(
			'type'  => $type,
			'id'    => $id
		);
	}
}