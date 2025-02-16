<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
class LdapLib extends TikiLib
{
    /**
     * Retrieve a specific field from a LDAP filter.
     *
     * @param str $dsn
     * @param str $filter
     * @param str $field
     * @param bool $all : return all records if true
     * @return str or array if $all = true
     */
    public function get_field($dsn, $filter, $field, $all = false)
    {
        // Force autoloading
        if (! class_exists('ADOConnection')) {
            return null;
        }

        // Try to connect
        $ldaplink = ADONewConnection($dsn);
        $return = null;

        if (! $ldaplink) {
            // Wrong DSN
            return $return;
        }

        $ldaplink->SetFetchMode(ADODB_FETCH_ASSOC);
        $rs = $ldaplink->Execute($filter);
        if ($rs) {
            while ($arr = $rs->FetchRow()) {
                if (isset($arr[$field])) {
                    // Retrieve field
                    $return[] = $arr[$field];
                    if ($all === false) {
                        break;
                    }
                }
            }
        }

        // Disconnect
        $ldaplink->Close();

        return ($all ? $return : array_shift($return)) ;
    }
}
