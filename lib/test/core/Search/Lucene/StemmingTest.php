<?php
// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
// $Id$

/**
 * @group unit
 */
class Search_Lucene_StemmingTest extends Search_Index_StemmingTest
{

	protected function setUp() : void
	{
		$dir = __DIR__ . '/test_index';
		$this->tearDown();

		$index = new Search_Lucene_Index($dir, 'en');
		$this->populate($index);

		$this->index = $index;
	}

	protected function tearDown() : void
	{
		if ($this->index) {
			$this->index->destroy();
		}
	}
}
