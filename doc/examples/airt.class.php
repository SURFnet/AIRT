<?php
/** Authentication plugin to be used with Dokuwiki.
 * Drop this file in dokuwiki's auth folder and set
 * $conf['authtype'] to 'airt';
 *
 * Do not forget to update the location of your config.plib (see below)
 *
 */
require_once '/opt/airt/php/config.plib';
require_once LIBDIR.'/authentication.plib';
require_once LIBDIR.'/user.plib';

class auth_airt extends auth_basic {
    var $cando = array (
       'addUser' => false,
       'deluser' => false,
       'modLogin' => false,
       'modPass' => false,
       'modName' => false,
       'modMail' => false,
       'modGroups' => false,
       'getUsers' => false,
       'getUserCount' => false,
       'getGroups' => false,
       'external' => false,
       'logoff' => false,
    );
    var $success = true;

    /* check the username/password. Return true if it is a match, and
     * return false if it is not.
     */
    function checkpass($user, $pass) {
        $uid = airt_authenticate($user, $pass);
        if ($uid > 0) {
            return true;
        } else {
            return false;
        }
    }

    /* return some information about the user. Note: this plugin does not
     * support groups; all authenticated users will be made member of
     * dokuwiki's users group.
     *
     * $user contains the login of the authenticated user;
     * Function returns an array that must contain the keys 'name', 'mail', 
     * and 'grps'. Name and mail are strings, 'grps' is an array of strings.
     */
    function getUserData($user) {
        $u = getUserByLogin($user);
        return array(
            'name' => $u['firstname'].' '.$u['lastname'],
            'mail' => $u['email'],
            'grps' => array('users'),
        );
    }
}
?>
