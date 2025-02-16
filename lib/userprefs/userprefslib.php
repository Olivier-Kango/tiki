<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
/**
 *
 */
class UserPrefsLib extends TikiLib
{
    /**
     * @param $user
     * @param $type
     * @param $avatarLibName
     * @param $avatarName
     * @param $avatarSize
     * @param $avatarType
     * @param $avatarData
     * @param bool $sendToIntertiki
     * @throws Exception
     */
    public function set_user_avatar($user, $type, $avatarLibName, $avatarName, $avatarSize, $avatarType, $avatarData, $sendToIntertiki = true)
    {
        global $prefs, $tikidomainslash;
        $userlib = TikiLib::lib('user');
        $query = "update `users_users` set `avatarType` = ?, `avatarLibName` = ?, `avatarName` = ?, `avatarSize` = ?, `avatarFileType` = ?, `avatarData` = ?  where `login`=?";
        $result = $this->query($query, [$type, $avatarLibName, $avatarName, ($avatarSize ? $avatarSize : null), $avatarType, $avatarData, $user]);
        if ($sendToIntertiki && $prefs['feature_intertiki'] == 'y' && ! empty($prefs['feature_intertiki_mymaster']) && $prefs['feature_intertiki_import_preferences'] == 'y') { //send to the master
            $userlib->interSendUserInfo($prefs['interlist'][$prefs['feature_intertiki_mymaster']], $user);
        }

        $image = 'temp/public/' . $tikidomainslash . 'avatar_' . md5($user) . '.*';
        foreach (glob($image) as $file) {
            unlink($file);
        }
    }

    /**
     * @param $user
     * @return bool
     */
    public function get_user_avatar_img($user)
    {
        $query = "select * from `users_users` where `login`=?";
        $result = $this->query($query, [$user]);
        if (! $result->numRows()) {
            return false;
        }
        $res = $result->fetchRow();
        return $res;
    }

    public function get_public_avatar_path($user): string
    {
        global $prefs, $tikidomainslash;
        $file = null;
        if ($user) {
            if ($prefs['users_serve_avatar_static'] == 'y') {
                $hash = md5($user);
                $files = glob("temp/public/{$tikidomainslash}avatar_$hash.{jpg,jpeg,gif,png}", GLOB_BRACE);

                if (! empty($files[0])) {
                    $file = $files[0];
                } else {
                    $file = $this->generate_avatar_file($user);
                }
                if ($lastmod = filemtime($file)) {
                    $file .= '?v=' . $lastmod;
                }
            } else {
                $info = $this->get_user_avatar_img($user);
                $content = $info["avatarData"];
                if (! empty($content)) {
                    $file = "tiki-show_user_avatar.php?user=" . urlencode($user);
                }
            }
        }
        if (! $file) {
                $file = 'img/noavatar.png';
            if ($lastmod = filemtime($file)) {
                $file .= '?v=' . $lastmod;
            }
        }
        if (TikiLib::lib('parser')->option['absolute_links']) {
            global $base_url;
            $file = $base_url . $file;
        }
        return $file;
    }

    private function generate_avatar_file($user)
    {
        global $tikidomainslash;

        $info = $this->get_user_avatar_img($user);
        if (empty($info)) {
            return 'img/noavatar.png';
        }

        $type = $info["avatarFileType"];
        $content = $info["avatarData"];

        if (empty($content)) {
            return 'img/noavatar.png';
        }

        global $mimetypes;
        require_once('lib/mime/mimetypes.php');

        if (! in_array($type, $mimetypes)) {
            return 'img/noavatar.png';
        }
        $filename = pathinfo($info['avatarName']);
        if (! empty($filename['extension']) && $mimetypes[strtolower($filename['extension'])] == $type) {
            $ext = $filename['extension'];
        } else {
            $ext = array_search($type, $mimetypes);
        }

        $hash = md5($user);
        $image = "temp/public/{$tikidomainslash}avatar_{$hash}.$ext";

        file_put_contents($image, $info['avatarData']);
        chmod($image, 0644);

        return $image;
    }

    /**
     * @param $u
     * @param $filename
     * @param $size
     * @param $type
     * @param $data
     * @return bool|int|null
     */
    public function set_file_gallery_image($u, $filename, $size, $type, $data)
    {
        global $prefs;
        $tikilib = TikiLib::lib('tiki');
        $filegallib = TikiLib::lib('filegal');
        if (! $prefs["user_picture_gallery_id"]) {
            return false;
        }
        if ($user_image_id = $tikilib->get_user_preference($u, 'user_fg_image_id')) {
            $file = Tiki\FileGallery\File::id($user_image_id);
            $file->setParam('user', $u);
            $user_image_id = $file->replace($data, $type, $u, $filename);
        } else {
            $file = new Tiki\FileGallery\File([
                'galleryId' => $prefs["user_picture_gallery_id"],
                'description' => $u,
                'user' => $u,
            ]);
            $user_image_id = $file->replace($data, $type, $u, $filename);
            $tikilib->set_user_preference($u, 'user_fg_image_id', $user_image_id);
        }
        return $user_image_id;
    }

    /**
     * @param $u
     * @return bool
     */
    public function remove_file_gallery_image($u)
    {
        $tikilib = TikiLib::lib('tiki');
        $filegallib = TikiLib::lib('filegal');
        if ($user_image_id = $tikilib->get_user_preference($u, 'user_fg_image_id')) {
            $file_info = $filegallib->get_file_info($user_image_id, false, false);
            $filegallib->remove_file($file_info, '', true);
            $tikilib->set_user_preference($u, 'user_fg_image_id', '');
            return true;
        } else {
            return false;
        }
    }

    /**
     * @param $u
     * @return null
     */
    public function get_user_picture_id($u)
    {
        $tikilib = TikiLib::lib('tiki');
        return $tikilib->get_user_preference($u, 'user_fg_image_id');
    }

    /**
     * @param $user
     * @return array
     */
    public function get_userprefs($user)
    {
        $query = "select * from `tiki_user_preferences` where `user`=?";
        $result = $this->query($query, [$user]);
        $ret = [];

        while ($res = $result->fetchRow()) {
            $ret[] = $res;
        }

        return $ret;
    }

    /**
     * @param $usersrc
     * @param $userdst
     * @return float|null
     */
    public function get_userdistance($usersrc, $userdst)
    {
        if ($usersrc == $userdst) {
            return null;
        }
        $user1 = $this->get_userprefs($usersrc);
        $user2 = $this->get_userprefs($userdst);

        for ($i = 0, $icount_user1 = count($user1); $i < $icount_user1; $i++) {
            if ($user1[$i]["prefName"] == "lat") {
                $u1lat = $user1[$i]["value"];
            }
            if ($user1[$i]["prefName"] == "lon") {
                $u1lon = $user1[$i]["value"];
            }
        }
        for ($i = 0, $icount_user2 = count($user2); $i < $icount_user2; $i++) {
            if ($user2[$i]["prefName"] == "lat") {
                $u2lat = $user2[$i]["value"];
            }
            if ($user2[$i]["prefName"] == "lon") {
                $u2lon = $user2[$i]["value"];
            }
        }
        if (isset($u1lat) && isset($u1lon) && isset($u2lat) && isset($u2lon)) {
            $distance = $this->distance($u1lat, $u1lon, $u2lat, $u2lon);
            return (round($distance, 2));
        } else {
            return(null);
        }
    }

    /**
     * @param $user
     * @return bool
     */
    public function get_user_clock_pref($user)
    {
        global $prefs;
        $tikilib = TikiLib::lib('tiki');
        $userclock = $tikilib->get_user_preference($user, 'display_12hr_clock');
        $use_24hr_clock = true;
        if ((isset($userclock) && $userclock == 'y') || (! isset($userclock) && $prefs['users_prefs_display_12hr_clock'] == 'y')) {
            $use_24hr_clock = false;
        }
        return $use_24hr_clock;
    }
}
