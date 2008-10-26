<?php
/* vim: syntax=php tabstop=3 shiftwidth=3
 *
 * AIRT: APPLICATION FOR INCIDENT RESPONSE TEAMS
 * Copyright (C) 2004   Tilburg University, The Netherlands

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
 * constituencies.php -- manage constituency data
 *
 * $Id$
 */
require_once 'config.plib';
require_once LIBDIR.'/airt.plib';
require_once LIBDIR.'/database.plib';
require_once LIBDIR.'/constituency.plib';


/** GUI Component to show the update constituecy form. */
function formatConstituencyForm($id='') {
   $label = $description = '';
   $action = 'add';
   $submit = _('Add!');

   if (!empty($id)) {
      if (!is_numeric($id)) {
         die(_('Invalid parameter type ').__LINE__);
      }
      $constituencies = getConstituencies();

      if (array_key_exists($id, $constituencies)) {
         $row = $constituencies[$id];
         $label = $row['label'];
         $description = $row['name'];
         $action = 'update';
         $submit = _('Update!');
      }
   }
   $out = '<form action="'.$_SERVER['PHP_SELF'].'" method="POST">'.LF;
   $out .= '<input type="hidden" name="action" value="'.$action.'">'.LF;
   $out .= '<input type="hidden" name="consid" value="'.$id.'">'.LF;
   $out .= '<table>'.LF;
   $out .= '<tr>'.LF;
   $out .= '   <td>Label</td>'.LF;
   $out .= '   <td><input type="text" size="30" name="label" '.
           '       value="'.strip_tags($label).'"></td>'.LF;
   $out .= '</tr>'.LF;
   $out .= '<tr>'.LF;
   $out .= '   <td>Description</td>'.LF;
   $out .= '   <td><input type="text" size="30" name="description" '.
           '    value="'.strip_tags($description).'"></td>'.LF;
   $out .= '</tr>'.LF;
   $out .= '</table>'.LF;
   $out .= '<p>'.LF;
   $out .= '<input type="submit" value="'.$submit.'">'.LF;
   if ($action=="update") {
        $out .= '<input type="submit" name="action" value="Delete">'.LF;
   }
   $out .= '</form>'.LF;
   return $out;
}

$action = strip_tags(fetchFrom('REQUEST', 'action', '%s'));
defaultTo($action, 'list');

switch ($action) {
   // --------------------------------------------------------------
   case "list":
      pageHeader(_('Constituencies'));

      $out = '<table width="100%" cellpadding="3">'.LF;
      $out .= '<tr>'.LF;
      $out .= '<th>&nbsp;</th>'.LF;
      $out .= '<th>Label</th>'.LF;
      $out .= '<th>Description</th>'.LF;
      $out .= '<th>Netblocks</th>'.LF;
      $out .= '</tr>'.LF;
      $constituencies = getConstituencies();
      $networks = getNetworks();

      $count=0;
      foreach ($constituencies as $id => $row) {
         $consid = $id;
         $color = ($count++%2==0?"#FFFFFF":"#DDDDDD");
         $out .= '<tr valign="top" bgcolor="'.$color.'">'.LF;
         $out .= '<td>'.LF;
         $out .= '<a href="'.$_SERVER['PHP_SELF'].'?action=edit&cons='.
            $consid.'">'._('edit').'</a>'.LF;
         $out .= '</td>'.LF;
         $out .= '<td>'.strip_tags($row['label']).'</td>'.LF;
         $out .= '<td>'.strip_tags($row['name']).'</td>'.LF;
         $out .= '<td>'.LF;
         foreach ($networks as $id=>$row2) {
            if ($row2['constituency'] != $consid) {
               continue;
            }
            $out .= '- '.$row2['label'].'<BR><small>'.
               $row2['network'].' / '.$row2['netmask'].'</small><BR>'.LF;
         }
         $out .= '</td>'.LF;
         $out .= '</tr>'.LF;
      } // foreach
      $out .= '</table>';

      $out .= '<h3>'._('New constituency').'</h3>'.LF;
      $out .= formatConstituencyForm('');
      print $out;
      break;

   //-----------------------------------------------------------------
   case "edit":
      $cons = fetchFrom('GET', 'cons', '%d');
      if (empty($cons)) {
         die(_('Missing information in ').__LINE__);
      }

      pageHeader(_('Edit constituency'));
      print formatConstituencyForm($cons);
      pageFooter();
      break;

   //-----------------------------------------------------------------
   case "add":
   case "update":
      $consid = fetchFrom('POST', 'consid', '%d');
      defaultTo($consid, -1);

      $label = strip_tags(fetchFrom('POST', 'label', '%s'));
      if (empty($consid)) {
         die(_('Missing information in ').__LINE__);
      }
      $description = strip_tags(fetchFrom('POST', 'description', '%s'));
      if (empty($description)) {
         die(_('Missing information in ').__LINE__);
      }

      if ($action=="add") {
         if (addConstituency($label, $description, $error) === false) {
            airt_msg(_('Database error in ').'constituencies.plib:'.__LINE__);
            reload();
         }

         generateEvent("newconstituency", array(
            "label"=>$label,
            "name"=>$description
         ));
         reload();
      } else if ($action=="update") {
         if (empty($consid)) {
            airt_msg(_('Missing constituency in').' constituencies.php:'.
               __LINE__);
            reload();
            exit;
         }
         if (!is_numeric($consid)) {
            airt_msg(_('Invalid parameter type in').' constituencies.php:'.
               __LINE__);
            reload();
            exit;
         }
         if (updateConstituency($consid, $label, $description, $error) === false) {
            airt_msg(_('Database error:').$error);
            reload();
            exit;
         } 

         generateEvent("updateconstituency", array(
            "label"=>$label,
            "name"=>$description
         ));
         reload();
      }

      break;

   //-----------------------------------------------------------------
   case "Delete":
      $cons = fetchFrom('POST', 'consid', '%d');
      if (empty($cons)) {
         die(_('Missing information (1).'));
      }
      if (!is_numeric($cons)) {
         die(_('Invalid parameter type in ').__LINE__);
      }

      generateEvent("deleteconstituency", array(
         "constituencyid" => $cons
      ));

      $res = db_query("
         DELETE FROM constituencies
         WHERE  id=$cons")
      or die(_('Unable to execute query in ').__LINE__);

      reload();
      break;

   //-----------------------------------------------------------------
   default:
      die(_('Unknown action: ').$action);
} // switch
?>
