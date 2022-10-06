<?php
/* login.php - allows users to log in to this site.
 * vim: syntax=php tabstop=3 shiftwidth=3

 * AIRT: APPLICATION FOR INCIDENT RESPONSE TEAMS
 * Copyright (C) 2004,2005	Tilburg University, The Netherlands

 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.

 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.

 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
 */
$public=1;
require_once 'config.plib';
require_once LIBDIR."/authentication.plib";
require_once LIBDIR."/airt.plib";
require_once LIBDIR."/login.plib";

if (array_key_exists('action', $_REQUEST)) {
   $action=$_REQUEST['action'];
} else {
   $action = "none";
}

switch ($action) {
   case "none":
      airtLoginScreen();
      break;

    case "check":
       $login = fetchFrom('REQUEST', 'login');
       $password = fetchFrom('REQUEST', 'password');

       if (empty($login) || empty($password)) {
          airt_msg(_("Please enter a username and password to login."));
          reload();
          exit();
       }

       $userid = airt_authenticate($login, $password);
       if ($userid == -1) {
          pageHeader(_('Permission denied.'));
          print _('Username and/or password incorrect.');
          airt_invalidCredentials();
          pageFooter();
          exit();
       }

       airt_initSession($userid);
       break;

    default:
       die(_('Unknown action.'));
} // switch
