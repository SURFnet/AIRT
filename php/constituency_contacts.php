<?php
/* $Id$
 * $URL$
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
 * 
 * $Id$
 */
require_once 'config.plib';
require_once LIBDIR.'/airt.plib';
require_once LIBDIR.'/database.plib';

if (array_key_exists("action", $_REQUEST)) {
   $action=$_REQUEST["action"];
} else {
   $action = "list";
}

switch ($action) {
   //-----------------------------------------------------------------
   case "list":
      pageHeader(_('Constituency contacts'));

      $res = db_query("SELECT   id, label, name
             FROM     constituencies
             ORDER BY label")
      or die(_('Unable to execute query.'));

      echo _('Please select a constituency to edit assign contacts:').'<P>'.LF;
      while($row=db_fetch_next($res)) {
         $id = $row["id"];
         $label = $row["label"];
         $name = $row["name"];
         echo "<a href=\"$_SERVER[PHP_SELF]?action=edit&consid=$id\">$label - $name</a><P>";
      }
      db_free_result($res);

      pageFooter();
      break;

   //-----------------------------------------------------------------
   case "edit":
      if (array_key_exists("consid", $_GET)) {
         $consid=$_GET["consid"];
      } else {
         die(_('Missing information.'));
      }
      if (!is_numeric($consid)) {
         die(_('Invalid format'));
      }
      pageHeader(_('Edit constituency assignments'));

      $res = db_query(
         "SELECT label, name
          FROM   constituencies
          WHERE  id=$consid")
      or die(_('Unable to execute query 1.'));

      if (db_num_rows($res) == 0) {
         die(_('Invalid constituency.'));
      }

      $row = db_fetch_next($res);
      $label = $row["label"];
      $name  = $row["name"];
      db_free_result($res);

      echo '<h3>'._('Current contacts of constituency ').$label.'</H3>'.LF;

      $res = db_query(
         "SELECT u.id, login, lastname, firstname, email, phone
          FROM   constituency_contacts cc, users u
          WHERE  cc.constituency=$consid
          AND    cc.userid = u.id")
      or die(_('Unable to execute query(2).'));

      if (db_num_rows($res) == 0) {
         echo '<I>'._('No assigned users.').'</I>'.LF;
      } else {
         echo '<table border="1" cellpadding="4">'.LF;
         while ($row = db_fetch_next($res)) {
            $login = $row['login'];
            $lastname = $row['lastname'];
            $firstname = $row['firstname'];
            $email = $row['email'];
            $phone = $row['phone'];
            $id = $row['id'];

            printf('
<tr>
    <td>%s (%s, %s)</td>
    <td><a href="mailto:%s">%s</a></td>
    <td>%s</td>
    <td><a href="%s?action=remove&cons=%s&user=%s">'._('Remove').'</a>
	</td>
</tr>',
            $login, $lastname, $firstname,
            $email, $email,
            $phone, $_SERVER['PHP_SELF'], $consid, $id);
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
      or die(_('Unable to execute query(3).'));

      if (db_num_rows($res) > 0) {
         print '<P>'.LF;
         print '<FORM action="'.$_SERVER[PHP_SELF].'" method="POST">'.LF;
         print _('Assing user(s) to constituency:').LF;
         print '<SELECT name="userid">'.LF;
         while ($row = db_fetch_next($res)) {
            $email = $row['email'];
            $id    = $row["id"];

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
      print '<a href="'.$_SERVER[PHP_SELF].'">'.
            _('Select another constituency').'</a> &nbsp;|&nbsp;'.
            '<a href="maintenance.php">'._('Settings').'</a>'.LF;
      pageFooter();
      break;

   //-----------------------------------------------------------------
   case "assignuser":
      if (array_key_exists("consid", $_POST)) {
         $consid=$_POST["consid"];
      } else {
         die(_('Missing information (1).'));
      }
      if (array_key_exists("userid", $_POST)) {
         $userid=$_POST["userid"];
      } else {
         die(_('Missing information (2).'));
      }
      if (!is_numeric($consid) || !is_numeric($userid)) {
         die(_('Invalid data.'));
      }

      $res=db_query("
         INSERT INTO constituency_contacts
         (id, constituency, userid)
         VALUES
         (nextval('constituency_contacts_sequence'), $consid, $userid)")
      or die(_('Unable to execute query'));
      Header("Location: $_SERVER[PHP_SELF]?action=edit&consid=$consid");
      break;

   //-----------------------------------------------------------------
   case "remove":
      if (array_key_exists("cons", $_GET)) {
         $cons=$_GET["cons"];
      } else {
         die(_('Missing information (1).'));
      }
      if (array_key_exists("user", $_GET)) {
         $id=$_GET["user"];
      } else {
         die(_('Missing information (2).'));
      }
      if (!is_numeric($id) || !is_numeric($cons)) {
         die(_('Invalid format'));
      }

      $res = db_query(
            "DELETE FROM constituency_contacts
             WHERE  userid=$id
             AND    constituency=$cons")
      or die(_('Unable to execute query'));
      Header("Location: $_SERVER[PHP_SELF]?action=edit&consid=$cons");

      break;

   //-----------------------------------------------------------------
   default:
      die(_('Unknown action: ').$action);
} // switch

?>
