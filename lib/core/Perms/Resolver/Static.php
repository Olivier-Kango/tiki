<?php
// (c) Copyright 2002-2016 by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
// $Id$

/**
 * Resolver containing the list of permissions for each group as a
 * static list. The resolvers are generated by factories and apply
 * for a specific context.
 */
class Perms_Resolver_Static implements Perms_Resolver
{
	private $known = [];
	private $from = '';

	/*
	 * Convert array $known into an internal structure where the permission name is the key.
	 * I.e. $this->known['customers']['add_object'] == true
	 * @param array $known - array[groupname] = array(perms)
	 * @param string $from -type of object the permissons belongs to : i.e 'object', 'category'
	 */
	function __construct(array $known, $from = '')
	{
		foreach ($known as $group => $perms) {
			$this->known[$group] = array_fill_keys($perms, true);
		}
		$this->from = $from;
	}

	/*
	 * Check if a specific permission like 'add_object' exist in any of the groups
	 * @param string $name  - permission name
	 * @param array $groups - all groups available
     * @return bool $success - true if permission was found
	 */
	function check($name, array $groups)
	{
		foreach ($groups as $groupName) {
			if (isset($this->known[$groupName])) {
				if (isset($this->known[$groupName][$name])) {
					return true;
				}
			}
		}

		return false;
	}



	/*
	 * Get name of the object type the permissons to check belong to : i.e 'object', 'category'
	 * @return $string name of object type
	 */
	function from()
	{
		return $this->from;
	}


	/*
	 * Get array of applicable groups.
     * @return array $ applicableGroups
	 */
	function applicableGroups()
	{
		return array_keys($this->known);
	}
}
