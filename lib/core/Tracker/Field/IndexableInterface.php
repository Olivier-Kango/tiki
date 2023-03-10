<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
// $Id$
namespace Tracker\Field;

interface IndexableInterface extends \Tracker\Field\FieldInterface
{
    public function getDocumentPart(\Search_Type_Factory_Interface $typeFactory);

    public function getProvidedFields();

    public function getProvidedFieldTypes();

    public function getGlobalFields();

    public function getBaseKey();
}
