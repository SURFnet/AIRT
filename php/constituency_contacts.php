<?php
/**
 * vim: syntax=php shiftwidth=3 tabstop=3
 * 
 * AIRT: APPLICATION FOR INCIDENT RESPONSE TEAMS
 * Copyright (C) 2004,2005,2006	Tilburg University, The Netherlands

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
 *
 * constituency_contacts.php -- manage constituency contacts
 */
require_once 'config.plib';
require_once LIBDIR.'/airt.plib';
require_once LIBDIR.'/database.plib';

$action = strip_tags(fetchFrom('REQUEST', 'action', '%s'));
defaultTo($action, 'list');

switch ($action) {
   //-----------------------------------------------------------------
   case "list":
      pageHeader(_('Constituency contacts'), array(
         'menu'=>'constituencies',
         'submenu'=>'contacts'));

      $res = db_query("
         SELECT u.lastname, u.firstname, u.email, u.phone, c.label, c.id as consid
         FROM   users u, constituencies c, constituency_contacts cc
         WHERE  u.id = cc.userid
         AND    c.id = cc.constituency
         ORDER  BY c.label, u.lastname, u.firstname, u.email")
      or die(_('Unable to execute query.'));
      print '<table class="horizontal">'.LF;
      print '<tr>'.LF;
      print '  <th>'._('Constituency').'</th>'.LF;
      print '  <th>'._('Last name').'</th>'.LF;
      print '  <th>'._('First name').'</th>'.LF;
      print '  <th>'._('Email').'</th>'.LF;
      print '  <th>'._('Phone').'</th>'.LF;
      print '  <th>'._('Action').'</th>'.LF;
      print '</tr>'.LF;
      while (($row = db_fetch_next($res)) !== false) {
          print '<tr>'.LF;
          print '<td>'.htmlentities($row['label']).'</td>'.LF;
          print '<td>'.htmlentities($row['lastname']).'</td>'.LF;
          print '<td>'.htmlentities($row['firstname']).'</td>'.LF;
          print '<td>'.htmlentities($row['email']).'</td>'.LF;
          print '<td>'.htmlentities($row['phone']).'</td>'.LF;
          print '<td><a href="?action=edit&consid=' . urlencode($row['consid']) . '">edit</a></td>'.LF;
          print '</tr>'.LF;
      }
      print '</table>'.LF;

      db_free_result($res);

      pageFooter();
      break;

   //-----------------------------------------------------------------
   case "edit":
      $consid = fetchFrom('GET', 'consid', '%d');
      if (empty($consid)) {
         die(_('Missing information in ').__LINE__);
      }
      if (!is_numeric($consid)) {
         die(_('Invalid format'));
      }
      pageHeader(_('Edit constituency assignments'), array(
		   'menu'=>'constituencies',
			'submenu'=>'contacts'));

      $res = db_query(
         "SELECT label, name
          FROM   constituencies
          WHERE  id=$consid")
      or die(_('Unable to execute query in ').__LINE__);

      if (db_num_rows($res) == 0) {
         die(_('Invalid constituency.'));
      }

      $row = db_fetch_next($res);
      $label = $row["label"];
      $name  = $row["name"];
      db_free_result($res);

      echo '<h3>'._('Current contacts of constituency ').
         strip_tags($label).'</H3>'.LF;

      $res = db_query(
         "SELECT u.id, login, lastname, firstname, email, phone
          FROM   constituency_contacts cc, users u
          WHERE  cc.constituency=$consid
          AND    cc.userid = u.id")
      or die(_('Unable to execute query(2).'));

      if (db_num_rows($res) == 0) {
         echo '<I>'._('No assigned users.').'</I>'.LF;
      } else {
         echo '<table class="horizontal">'.LF;
         while ($row = db_fetch_next($res)) {
            $login = $row['login'];
            $lastname = $row['lastname'];
            $firstname = $row['firstname'];
            $email = $row['email'];
            $phone = $row['phone'];
            $id = $row['id'];

            printf('
<tr>
    <td><a href="%s/users.php?action=edit&id=%d">edit</a></td>
    <td>%s (%s, %s)</td>
    <td><a href="mailto:%s">%s</a></td>
    <td>%s</td>
    <td><a href="%s?action=remove&cons=%d&user=%d">'._('Remove').'</a>
	</td>
</tr>',
            BASEURL, urlencode($id),
            strip_tags($login), strip_tags($lastname),
            strip_tags($firstname), strip_tags($email),
            strip_tags($email), strip_tags($phone),
            BASEURL.'/constituency_contacts.php', $consid, $id);
         }
         echo '</table>'.LF;
      }

      db_free_result($res);
      $res = db_query("SELECT  id, email
          FROM    users
          WHERE   NOT id IN (
             SELECT userid
             FROM   constituency_contacts
             WHERE  constituency=$consid
          )
          ORDER BY email")
      or die(_('Unable to execute query in ').__LINE__);

      if (db_num_rows($res) > 0) {
         print '<P>'.LF;
         print '<FORM action="'.BASEURL.'/constituency_contacts.php" method="POST">'.LF;
         print _('Assing user(s) to constituency:').LF;
         print '<SELECT name="userid">'.LF;
         while ($row = db_fetch_next($res)) {
            $email = strip_tags($row['email']);
            $id    = strip_tags($row['id']);

            printf("<option value=\"$id\">$email</option>\n");
         }
         print '</SELECT>'.LF;
         print '<input type="hidden" name="consid" value="'.$consid.'">'.LF;
         print '<input type="hidden" name="action" value="assignuser">'.LF;
         print '<input type="submit" value="Assign">'.LF;
         print '</FORM>'.LF;
      } else {
         echo '<P><I>'._('No unassigned users.').'</I>'.LF;
      }
      print '<P><HR>'.LF;
      print '<a href="'.BASEURL.'/constituency_contacts.php">'.
            _('Select another constituency').'</a> &nbsp;|&nbsp;'.
            '<a href="'.BASEURL.'/maintenance.php">'._('Settings').'</a>'.LF;
      pageFooter();
      break;

   //-----------------------------------------------------------------
   case "assignuser":
      $consid = fetchFrom('POST', 'consid', '%d');
      if (empty($consid)) {
         die(_('Missing information in ').__LINE__);
      }
      $userid = fetchFrom('POST', 'userid', '%d');
      if (empty($userid)) {
         die(_('Missing information in ').__LINE__);
      }
      if (!is_numeric($consid) || !is_numeric($userid)) {
         // should not happen
         die(_('Invalid data in ').__LINE__);
      }

      $res=db_query("
         INSERT INTO constituency_contacts
         (id, constituency, userid)
         VALUES
         (nextval('constituency_contacts_sequence'), $consid, $userid)")
      or die(_('Unable to execute query'));
      reload(BASEURL.'/constituency_contacts.php?action=edit&consid='.
         urlencode($consid));
      break;

   //-----------------------------------------------------------------
   case "remove":
      $cons = fetchFrom('GET', 'cons', '%d');
      if (empty($cons)) {
         die(_('Missing information in ').__LINE__);
      }
      $id = fetchFrom('GET', 'user', '%d');
      if (empty($id)) {
         die(_('Missing information in ').__LINE__);
      }
      if (!is_numeric($id) || !is_numeric($cons)) {
         die(_('Invalid format in ').__LINE__);
      }

      $res = db_query(
            "DELETE FROM constituency_contacts
             WHERE  userid=$id
             AND    constituency=$cons")
      or die(_('Unable to execute query'));
      reload(BASEURL.'/constituency_contacts.php?action=edit&consid='.
         urlencode($cons));

      break;

   //-----------------------------------------------------------------
   default:
      die(_('Unknown action: ').strip_tags($action));
} // switch
