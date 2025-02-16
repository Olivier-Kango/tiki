<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
/**
 * Manage users preferences regarding periodic
 * reports of what have changed in Tiki.
 *
 * @package Tiki
 * @subpackage Reports
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
     * @param TikiDb $db
     * @return null
     */
    public function __construct(TikiDb $db, DateTime $dt)
    {
        $this->db = $db;
        $this->table = $db->table('tiki_user_reports');
        $this->dt = $dt;
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
            ['id', 'interval', 'view', 'type', 'always_email', 'last_report'],
            ['user' => $user]
        );
    }

    /**
     * Remove user preferences for reports.
     *
     * @param string $user
     * @return TikiDb_Pdo_Result|TikiDb_Adodb_Result
     */
    public function delete($user)
    {
        return $this->table->deleteMultiple(['user' => $user]);
    }

    /**
     * Add or update user preferences regarding receiving periodic
     * reports with changes in Tiki.
     *
     * @param string $user
     * @param string $interval     report interval (can be 'daily', 'weekly' and 'monthly')
     * @param string $view
     * @param string $type         whether the report should be send in plain text or html
     * @param int    $always_email if true the user will receive an e-mail even if there are no changes
     *
     * @return int|TikiDb_Pdo_Result|TikiDb_Adodb_Result
     */
    public function save($user, $interval, $view, $type, $always_email = 0)
    {
        if (! $this->get($user)) {
            $result = $this->table->insert(
                [
                    'user' => $user,
                    'interval' => $interval,
                    'view' => $view,
                    'type' => $type,
                    'always_email' => $always_email,
                    'last_report' => null,
                ]
            );
        } else {
            $result = $this->table->update(
                [
                    'interval' => $interval,
                    'view' => $view,
                    'type' => $type,
                    'always_email' => $always_email
                ],
                ['user' => $user]
            );
        }
        return $result;
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
        $user = isset($context['user']) ? $context['user'] : $context['object'];
        $this->save($user, 'daily', 'detailed', 'html', 0);
    }

    /**
     * Return a list of users that should receive the report.
     * @return array
     */
    public function getUsersForReport()
    {
        $users = $this->db->fetchAll('select `user`, `interval`, UNIX_TIMESTAMP(`last_report`) as last_report from tiki_user_reports');

        $ret = [];

        foreach ($users as $user) {
            if ($user['interval'] == "minute" && ($user['last_report'] + 60) <= $this->dt->format('U')) {
                $ret[] = $user['user'];
            }
            if ($user['interval'] == "hourly" && ($user['last_report'] + 3600) <= $this->dt->format('U')) {
                $ret[] = $user['user'];
            }
            if ($user['interval'] == "daily" && ($user['last_report'] + 86400) <= $this->dt->format('U')) {
                $ret[] = $user['user'];
            }
            if ($user['interval'] == "weekly" && ($user['last_report'] + 604800) <= $this->dt->format('U')) {
                $ret[] = $user['user'];
            }
            if ($user['interval'] == "monthly" && ($user['last_report'] + 2419200) <= $this->dt->format('U')) {
                $ret[] = $user['user'];
            }
        }

        return $ret;
    }

    /**
     * Return all users that are using periodic reports.
     * @return array a list of users names
     */
    public function getAllUsers()
    {
        return $this->table->fetchColumn('user', []);
    }

    /**
     * Update date and time of last report sent
     * to the user.
     * @param strin $user
     * @return null
     */
    public function updateLastReport($user)
    {
        $this->table->update(
            ['last_report' => $this->dt->format('Y-m-d H:i:s')],
            ['user' => $user]
        );
    }
}
