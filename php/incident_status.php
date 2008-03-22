<?php
/* vim: syntax=php tabstop=3 shiftwidth=3
 * TODO: codingstyle
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
 * incident_status.php -- manage incident_status
 * 
 * $Id$
 */
require_once 'config.plib';
require_once LIBDIR.'/airt.plib';
require_once LIBDIR.'/database.plib';

function show_form($id="") {
   $label     = '';
   $desc      = '';
   $isdefault = 'f';
   $action    = "add";
   $submit    = _('Add!');

   if ($id != "") {
      if (!is_numeric($id)) {
         die(_('Invalid parameter type in ').__LINE__);
      }
      $res = db_query("
         SELECT label, descr, isdefault
         FROM   incident_status
         WHERE  id = $id")
      or die("Unable to query database.");

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
   print '<form action="'.$_SERVER['PHP_SELF'].'" method="POST">'.LF;
   print '<input type="hidden" name="action" value="'.
      strip_tags($action).'">'.LF;
   print '<input type="hidden" name="id" value="'.
      strip_tags($id).'">'.LF;
   print '<table>'.LF;
   print '<tr>'.LF;
   print '   <td>Label</td>'.LF;
   print '   <td><input type="text" size="30" name="label" value="'.
      strip_tags($label).'"></td>'.LF;
   print '</tr>'.LF;
   print '<tr>'.LF;
   print '   <td>Description</td>'.LF;
   print '   <td><input type="text" size="50" name="desc" value="'.
      strip_tags($desc).'"></td>'.LF;
   print '</tr>'.LF;
   print '<tr>'.LF;
   print '   <td>Entry is default</td>'.LF;
   print '   <td><input type="checkbox" name="isdefault" value="1" '.
      strip_tags($isdefault).'></td>'.LF;
   print '</tr>'.LF;
   print '</table>'.LF;
   print '<p>'.LF;
   print '<input type="submit" value="'.
      strip_tags($submit).'">'.LF;
   print '</form>'.LF;
 }

$action = fetchFrom('REQUEST', 'action', '%s');
defaultTo($action, 'list');
switch ($action) {
   // --------------------------------------------------------------
   case "list":
      pageHeader(_('Incident status'));
      $res = db_query(
         "SELECT   id, label, descr, isdefault
          FROM     incident_status
          ORDER BY label")
      or die(_('Unable to execute query 1'));
      print '<table cellpadding="3">'.LF;
      print '<tr>'.LF;
      print '    <td><B>'._('Label').'</B></td>'.LF;
      print '    <td><B>'._('Description').'</B></td>'.LF;
      print '    <td><B>'._('Is default').'</B></td>'.LF;
      print '    <td><B>'._('Edit').'</B></td>'.LF;
      print '    <td><B>'._('Delete').'</B></td>'.LF;
      print ''.LF;
      print '</tr>'.LF;
      $count=0;
      while ($row = db_fetch_next($res)) {
         $label     = $row["label"];
         $id        = $row["id"];
         $desc      = $row['descr'];
         $isdefault = $row['isdefault']=='t'? 'Yes':'';
         $color = ($count++%2==0?"#FFFFFF":"#DDDDDD");
         print '<tr valign="top" bgcolor="'.strip_tags($color).'">'.LF;
         print '    <td>'.strip_tags($label).'</td>'.LF;
         print '    <td>'.strip_tags($desc).'</td>'.LF;
         print '    <td>'.strip_tags($isdefault).'</td>'.LF;
         print '    <td><a href="'.$_SERVER['PHP_SELF'].
            '?action=edit&id='.$id.'">'._('edit').'</a></td>'.LF;
         print '    <td><a href="'.$_SERVER['PHP_SELF'].
            '?action=delete&id='.$id.'">'._('delete').'</a></td>'.LF;
         print '</tr>'.LF;
        } // while $row
     print '</table>'.LF;

     db_free_result($res);

     print '<h3>'._('New incident status').'</h3>';
     show_form('');

     break;

   //-----------------------------------------------------------------
   case "edit":
      $id = fetchFrom('GET', 'id', '%d');
      if (empty($id)) {
         die(_('Missing information in ').__LINE__);
      }

      pageHeader(_('Edit incident status'));
      show_form($id);
      pageFooter();
      break;

   //-----------------------------------------------------------------
   case "add":
   case "update":
      $id = fetchFrom('POST', 'id', '%d');
      defaultTo($id, -1);
      $label = fetchFrom('POST', 'label', '%s');
      if (empty($label)) {
         die(_('Missing information in ').__LINE__);
      }
      $desc = fetchFrom('POST', 'desc', '%s');
      if (empty($desc)) {
         die(_('Missing information in ').__LINE__);
      }
      $isdefault = fetchFrom('POST', 'isdefault', '%s');
      defaultTo($isdefault, 'f');
      if ($isdefault!='f') {
         // The new/updated record is default, so all others are not.
         $q = "UPDATE incident_status
                SET isdefault = 'f'";
         $res = db_query($q) or die(_('Unable to execute query 4.'));
      }

      // Insert or update the current status record.
      if ($action=="add") {
         $res = db_query(sprintf("
            INSERT INTO incident_status
            (id, label, descr, isdefault)
            VALUES
            (nextval('incident_status_sequence'), %s, %s, %s)",
               db_masq_null($label),
               db_masq_null($desc),
               db_masq_null($isdefault)))
         or die(_('Unable to execute query in ').__LINE__);
         reload();
      } else if ($action=="update") {
         if ($id==-1) die(_('Missing information (3).'));
         if (!is_numeric($id)) {
            // should not happen
            die(_('Invalid parameter type in ').__LINE__);
         }
         $res = db_query(sprintf("
            UPDATE incident_status
            set label=%s,
                descr=%s,
                isdefault=%s
            WHERE id=%d",
               db_masq_null($label),
               db_masq_null($desc),
               db_masq_null($isdefault),
               $id))
         or die(_('Unable to execute query in ').__LINE__);
         reload();
        }
        break;

   //-----------------------------------------------------------------
   case "delete":
      $id = fetchFrom('GET', 'id', '%d');
      if (empty($id)) {
         die(_('Missing information in ').__LINE__);
      }
      if (!is_numeric($id)) {
         die(_('Invalid parameter type in ').__LINE__);
      }

      $res = db_query("
            DELETE FROM incident_status
            WHERE  id=$id")
      or die(_('Unable to execute query in ').__LINE__);

      reload();

      break;
    //-----------------------------------------------------------------
    default:
        die(_('Unknown action: ').strip_tags($action));
} // switch

?>
