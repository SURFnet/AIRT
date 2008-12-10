<?php
/* $Id: certificatelogin.php 250 2005-02-24 12:05:07Z kees $
 *
 * AIRT: APPLICATION FOR INCIDENT RESPONSE TEAMS
 * Copyright (C) 2004	Tilburg University, The Netherlands

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
require_once LIBDIR.'/authentication.plib';
require_once LIBDIR.'/airt.plib';

if (!defined('X509CLIENT') || X509CLIENT === false) {
    exit;
}
/* CAUTION: This page expects the option
 * 	SSLOptions +ExportCertData
 * To be set in apache's mod_ssl config!
 */
if (!array_key_exists('SSL_CLIENT_CERT', $_SERVER)) {
    print _('Unable to authenticate using client certificate:').'<br/>'.LF;
    print _('Could not find SSL_CLIENT_CERT').'<br/>'.LF;
    print _('Hint: set SSLOptions +ExportCertData').'<br/>'.LF;
    exit;
}

$crt = openssl_x509_parse(openssl_x509_read($_SERVER['SSL_CLIENT_CERT']));
$q = q("SELECT id FROM users 
        WHERE x509name='%s'
        AND NOT login IS NULL
        AND NOT password IS NULL", array(
   '%s'=>trim($crt['name'])));
if (($res = db_query($q)) === false) {
    airt_msg(db_errormessage()._(' in ').'certificate.php:'.__LINE__);
    reload(BASEURL.'/login.php');
    exit;
}
if (db_num_rows($res) == 0) {
    airt_msg(t(_('%s is not a recognized certificate name.'), array(
       '%s'=>trim($crt['name']))));
    reload(BASEURL.'/login.php');
    exit;
}
if (db_num_rows($res) > 1) {
    airt_msg(_('Ambiguous certificate presented.'));
    reload(BASEURL.'/login.php');
    exit;
}
if (($row = db_fetch_next($res)) === false) {
    airt_msg(db_error_message()._(' in ').'certificate.php:'.__LINE__);
    reload(BASEURL.'/login.php');
    exit;
}
/* init! */
db_free_result($res);
airt_initSession($row['id']);
?>
