<?php
/* vim: syntax=php tabstop=3 shiftwidth=3
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
 *
 * incident_states.php -- manage incident_states
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

function show_form($id="") {
   $label     = "";
   $desc      = "";
   $isdefault = 'f';
   $action    = "add";
   $submit    = _('Add!');

   if ($id != "") {
      $res = db_query("
         SELECT label, descr, isdefault
         FROM   incident_states
         WHERE  id = '$id'")
      or die(_('Unable to query database.'));

      if (db_num_rows($res) > 0) {
         $row = db_fetch_next($res);
         $action    = "update";
         $submit    = _('Update!');
         $label     = $row['label'];
         $desc      = $row['descr'];
         $isdefault = $row['isdefault'];
      }
   }
   if ($isdefault=='t') {
      $isdefault = 'CHECKED';
   } else {
      $isdefault = '';
   }
   echo '<form action="'.$_SERVER[PHP_SELF].'" method="POST">'.LF;
   echo '<input type="hidden" name="action" value="'.$action.'">'.LF;
   echo '<input type="hidden" name="id" value="'.$id.'">'.LF;
   echo '<table>'.LF;
   echo '<tr>'.LF;
   echo '<td>'._('Label').'</td>'.LF;
   echo '<td>'.LF;
   echo '<input type="text" size="30" name="label" value="'.$label.'">'.LF;
   echo '</td>'.LF;
   echo '</tr>'.LF;
   echo '<tr>'.LF;
   echo '<td>'._('Description').'</td>'.LF;
   echo '<td><input type="text" size="50" name="desc" value="'.$desc.'"></td>'.LF;
   echo '</tr>'.LF;
   echo '<tr>'.LF;
   echo '<td>'._('Entry is default').'</td>'.LF;
   echo '<td><input type="checkbox" name="isdefault" value="1" '.$isdefault.'></td>'.LF;
   echo '</tr>'.LF;
   echo '</table>'.LF;
   echo '<p>'.LF;
   echo '<input type="submit" value="'.$submit.'">'.LF;
   echo '</form>'.LF;
}

switch ($action) {
   // --------------------------------------------------------------
   case "list":
      pageHeader(_('Incident states'));
      $res = db_query(
         "SELECT   id, label, descr, isdefault
          FROM     incident_states
          ORDER BY label")
      or die(_('Unable to execute query 1'));

      echo '<table cellpadding="3">'.LF;
      echo '<tr>'.LF;
      echo '<td><B>'._('Label').'</B></td>'.LF;
      echo '<td><B>'._('Short description').'</B></td>'.LF;
      echo '<td><B>'._('Is default').'</B></td>'.LF;
      echo '<td><B>'._('Edit').'</B></td>'.LF;
      echo '<td><B>'._('Delete').'</B></td>'.LF;
      echo '</tr>'.LF;
      $count=0;
      while ($row = db_fetch_next($res)) {
         $label = $row["label"];
         $id    = $row["id"];
         $desc  = $row['descr'];
         $isdefault = $row['isdefault']=='t'? 'Yes':'';
         $color = ($count++%2==0?"#FFFFFF":"#DDDDDD");
         echo '<tr valign="top" bgcolor="'.$color.'">'.LF;
         echo '<td>'.$label.'</td>'.LF;
         echo '<td>'.$desc,'</td>'.LF;
         echo '<td>'.$isdefault.'</td>'.LF;
         echo '<td><a href="'.$_SERVER[PHP_SELF].
            '?action=edit&id='.$id.'">'._('edit').'</a></td>'.LF;
         echo '<td><a href="'.$_SERVER[PHP_SELF].
            '?action=delete&id='.$id.'">'._('delete').'</a></td>'.LF;
         echo '</tr>'.LF;
      } // while $row
      echo '</table>'.LF;

      db_free_result($res);

      echo '<h3>'._('New incident state').'</h3>'.LF;
      show_form('');

      break;

   //-----------------------------------------------------------------
   case "edit":
      if (array_key_exists("id", $_GET)) {
         $id=$_GET["id"];
      } else {
         die(_('Missing information.'));
      }

      pageHeader(_('Edit incident state'));
      show_form($id);
      pageFooter();
      break;

   //-----------------------------------------------------------------
   case "add":
   case "update":
      if (array_key_exists("id", $_POST)) {
         $id=$_POST["id"];
      } else {
         $id="";
      }
      if (array_key_exists("label", $_POST)) {
         $label=$_POST["label"];
      } else {
         die(_('Missing information (1).'));
      }
      if (array_key_exists("desc", $_POST)) {
         $desc=$_POST["desc"];
      } else {
         die(_('Missing information (2).'));
      }
      if (array_key_exists("isdefault", $_POST)) {
         $isdefault = 't';
      } else {
         $isdefault = 'f';
      }

      if ($isdefault=='t') {
         // The new/updated record is default, so all others are not.
         $q = "UPDATE incident_states
               SET isdefault = 'f'";
         $res = db_query($q) or die(_('Unable to execute query 4.'));
      }

      // Insert or update the current state record.
      if ($action=="add") {
         $res = db_query(sprintf("
            INSERT INTO incident_states
            (id, label, descr, isdefault)
            VALUES
            (nextval('incident_states_sequence'), %s, %s, %s)",
            db_masq_null($label),
            db_masq_null($desc),
            db_masq_null($isdefault)))
         or die(_('Unable to excute query 2.'));

         Header("Location: $_SERVER[PHP_SELF]");
      } else if ($action=="update") {
         if ($id=="") {
            die(_('Missing information (3).'));
         }
         $res = db_query(sprintf("
            UPDATE incident_states
            set  label=%s,
                 descr=%s,
                 isdefault=%s
            WHERE id=%s",
            db_masq_null($label),
            db_masq_null($desc),
            db_masq_null($isdefault),
            $id))
         or die(_('Unable to excute query 3.'));

         Header("Location: $_SERVER[PHP_SELF]");
      }
      break;

   //-----------------------------------------------------------------
   case "delete":
      if (array_key_exists("id", $_GET)) {
         $id=$_GET["id"];
      } else {
         die(_('Missing information.'));
      }

      $res = db_query("
         DELETE FROM incident_states
         WHERE  id='$id'")
      or die(_('Unable to execute query.'));

      Header("Location: $_SERVER[PHP_SELF]");

      break;
   //-----------------------------------------------------------------
   default:
      die(_('Unknown action: ').$action);
 } // switch

?>
