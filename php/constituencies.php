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

if (array_key_exists("action", $_REQUEST)) {
   $action=$_REQUEST["action"];
} else {
   $action = "list";
}

/** GUI Component to show the update constituecy form. */
function formatConstituencyForm($id="") {
   $label = $description = '';
   $action = 'add';
   $submit = _('Add!');

   if ($id != '') {
      $constituencies = getConstituencies();

      if (array_key_exists($id, $constituencies)) {
         $row = $constituencies[$id];
         $action = 'update';
         $submit = _('Update!');
         $label = $row['label'];
         $description = $row['name'];
      }
   }
   $out = '<form action="'.$_SERVER['PHP_SELF'].'" method="POST">'.LF;
   $out .= '<input type="hidden" name="action" value="'.$action.'">'.LF;
   $out .= '<input type="hidden" name="consid" value="'.$id.'">'.LF;
   $out .= '<table>'.LF;
   $out .= '<tr>'.LF;
   $out .= '   <td>Label</td>'.LF;
   $out .= '   <td><input type="text" size="30" name="label" '.
           '       value="'.$label.'"></td>'.LF;
   $out .= '</tr>'.LF;
   $out .= '<tr>'.LF;
   $out .= '   <td>Description</td>'.LF;
   $out .= '   <td><input type="text" size="30" name="description" '.
           '    value="'.$description.'"></td>'.LF;
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
         $label = $row["label"];
         $name  = $row["name"];
         $consid = $id;
         $color = ($count++%2==0?"#FFFFFF":"#DDDDDD");
         $out .= '<tr valign="top" bgcolor="'.$color.'">'.LF;
         $out .= '<td>'.LF;
         $out .= '<a href="'.$_SERVER['PHP_SELF'].'?action=edit&cons='.$consid.
                 '">'._('edit').'</a>'.LF;
         $out .= '</td>'.LF;
         $out .= '<td>'.$label.'</td>'.LF;
         $out .= '<td>'.$name.'</td>'.LF;
         $out .= '<td>'.LF;
         foreach ($networks as $id=>$row2) {
            if ($row2["constituency"] != $consid) {
               continue;
            }
            $label   = $row2["label"];
            $network = $row2["network"];
            $netmask = $row2["netmask"];

            $out .= '- '.$label.'<BR>  <small>'.$network .' / '. $netmask.
                    '</small><BR>'.LF;
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
      if (array_key_exists("cons", $_GET)) {
         $cons=$_GET["cons"];
      } else {
         die(_('Missing information.'));
      }

      pageHeader(_('Edit constituency'));
      print formatConstituencyForm($cons);
      pageFooter();
      break;

   //-----------------------------------------------------------------
   case "add":
   case "update":
      if (array_key_exists("consid", $_POST)) {
         $consid=$_POST["consid"];
      } else {
         $consid="";
      }
      if (array_key_exists("label", $_POST)) {
         $label=$_POST["label"];
      } else {
         die(_('Missing information (1).'));
      }
      if (array_key_exists("description", $_POST)) {
            $description=$_POST["description"];
      } else {
         die(_('Missing information (2).'));
      }
      if ($action=="add") {
         $res = db_query(sprintf("
            INSERT INTO constituencies
            (id, label, name)
            VALUES
            (nextval('constituencies_sequence'), %s, %s)",
            db_masq_null($label),
            db_masq_null($description)))
         or die(_('Unable to excute query.'));

         generateEvent("newconstituency", array(
            "label"=>$label,
            "name"=>$description
         ));
         Header("Location: $_SERVER[PHP_SELF]");
      } else if ($action=="update") {
         if ($consid=="") {
            die(_('Missing information (3).'));
         }

         $res = db_query(sprintf("
            UPDATE constituencies
            SET  label=%s,
                 name=%s
            WHERE id=%s",
            db_masq_null($label),
            db_masq_null($description),
            $consid))
         or die(_('Unable to excute query.'));

         generateEvent("updateconstituency", array(
            "label"=>$label,
            "name"=>$description
         ));
         Header("Location: $_SERVER[PHP_SELF]");
   }

   break;

   //-----------------------------------------------------------------
   case "Delete":
      if (array_key_exists("consid", $_POST)) {
         $cons=$_POST["consid"];
      } else {
         die(_('Missing information (1).'));
      }

      generateEvent("deleteconstituency", array(
         "constituencyid" => $cons
      ));

      $res = db_query("
         DELETE FROM constituencies
         WHERE  id='$cons'")
      or die(_('Unable to execute query.'));

      Header("Location: $_SERVER[PHP_SELF]");

      break;

   //-----------------------------------------------------------------
   default:
      die(_('Unknown action: ').$action);
 } // switch

?>
