<?php
// (c) Copyright 2002-2012 by authors of the Tiki Wiki CMS Groupware Project
// 
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
// $Id$

/**
 * @package Tiki
 * @subpackage Reports
 * 
 * Manage users preferences regarding periodic
 * reports of what have changed in Tiki.
 */
class Reports_Users
{
	/**
	 * @var TikiDb
	 */
	protected $db;
	
	protected $table;
	
	/**
	 * @var DateTime
	 */
	protected $dt;
	
	/**
	 * @var Reports_Cache
	 */
	protected $reportsCache;
	
	/**
	 * @param TikiDb $db
	 * @param Reports_Cache $reportsCache
	 * @return null
	 */
	public function __construct(TikiDb $db, DateTime $dt, Reports_Cache $reportsCache)
	{
		$this->db = $db;
		$this->table = $db->table('tiki_user_reports');
		$this->dt = $dt;
		$this->reportsCache = $reportsCache;
	}
	
	/**
	 * Return the preferences for receiving the reports
	 * for a given user.
	 * 
	 * @param string $user
	 * @return array
	 */
	public function get($user)
	{
		return $this->table->fetchRow(
			array('id', 'interval', 'view', 'type', 'always_email', 'last_report'),
			array('user' => $user)
		);
	}
	
	/**
	 * Remove user preferences for reports and
	 * the changes cache for this user.
	 * 
	 * @param $user
	 * @return null
	 */
	public function delete($user)
	{
		$this->table->deleteMultiple(array('user' => $user));
		$this->reportsCache->delete($user);
	}
	
	/**
	 * Add or update user preferences regarding receiving periodic
	 * reports with changes in Tiki.
	 *  
	 * @param string $user
	 * @param string $interval report interval (can be 'daily', 'weekly' and 'monthly')
	 * @param string $view
	 * @param string $type whether the report should be send in plain text or html
	 * @param bool $always_email if true the user will receive an e-mail even if there are no changes
	 * @return null
	 */
	public function save($user, $interval, $view, $type, $always_email = 0)
	{
		$this->db->query("set time_zone='+00:00'");
		if (!$this->get($user)) {
			$this->table->insert(array(
				'user' => $user, 'interval' => $interval, 'view' => $view, 'type' => $type,
				'always_email' => $always_email, 'last_report' => $this->dt->format('Y-m-d H:i:s')
			));
		} else {
			$this->table->update(
				array('interval' => $interval, 'view' => $view, 'type' => $type, 'always_email' => $always_email),
				array('user' => $user)
			);
		}	
	}
	
	/**
	 * Called by event tiki.user.create when feature
	 * dailyreports_enabled_for_new_users is enabled.
	 * 
	 * @param $context
	 * @return null
	 */
	public function addUserToDailyReports($context)
	{
		$this->save($context['user'], 'daily', 'detailed', 'html', 1);
	}
	
	/**
	 * Return a list of users that should receive the report.
	 * @return array
	 */
	public function getUsersForReport()
	{
		$users = $this->db->fetchAll('select `user`, `interval`, UNIX_TIMESTAMP(`last_report`) as last_report from tiki_user_reports');

		$ret = array();
		
		foreach ($users as $user) {
			if ($user['interval'] == "daily" && ($user['last_report'] + 86400) <= $this->dt->getTimestamp()) {
				$ret[] = $user['user'];
			}
			if ($user['interval'] == "weekly" && ($user['last_report'] + 604800) <= $this->dt->getTimestamp()) {
				$ret[] = $user['user'];
			}
			if ($user['interval'] == "monthly" && ($user['last_report'] + 2419200) <= $this->dt->getTimestamp()) {
				$ret[] = $user['user'];
			}
		}
		
		return $ret;
	}

	/**
	 * Update date and time of last report sent
	 * to the user.
	 * @param strin $user
	 * @return null
	 */
	function updateLastReport($user)
	{
		$this->table->update(
			array('last_report' => $this->dt->format('Y-m-d H:i:s')),
			array('user' => $user)
		);
	}
}