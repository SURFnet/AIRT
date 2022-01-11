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
 */
require_once 'config.plib';
require_once LIBDIR.'/airt.plib';
require_once LIBDIR.'/database.plib';
require_once LIBDIR.'/constituency.plib';
require_once LIBDIR.'/network.plib';

$action = strip_tags(fetchFrom('REQUEST', 'action', '%s'));
defaultTo($action, 'list');

switch ($action) {
   // --------------------------------------------------------------
   case "list":
      pageHeader(_('Constituencies'), array(
         'menu'=>'constituencies',
         'submenu'=>'constituencies'));

      $out = '<table class="horizontal">'.LF;
      $out .= '<tr>'.LF;
      $out .= '<th>'._('Name').'</th>'.LF;
      $out .= '<th>'._('Type').'</th>'.LF;
      $out .= '<th>'._('Description').'</th>'.LF;
      $out .= '<th>&nbsp;</th>'.LF;
      $out .= '</tr>'.LF;
      $constituencies = getConstituencies();

      $count=0;
      foreach ($constituencies as $id => $row) {
         $consid = $id;
         $out .= '<tr>'.LF;
         $out .= '<td>'.strip_tags($row['label']).'</td>'.LF;
         $out .= '<td>'.substr(strip_tags($row['ctype']),0,15).'</td>'.LF;
         $out .= '<td>'.strip_tags($row['name']).'</td>'.LF;
         $out .= '<td>'.LF;
         $out .= '<a href="'.BASEURL.'/constituencies.php?action=edit&cons='.
            $consid.'">'._('edit').'</a>'.LF;
         $out .= '<a href="'.BASEURL.'/constituencies.php?action=Delete&consid='.
            $consid.'">'._('delete').'</a>'.LF;
         $out .= '</td>'.LF;
         $out .= '</tr>'.LF;
      } // foreach
      $out .= '</table>';

      $out .= '<div>'.LF;
      $out .= '<h3>'._('New constituency').'</h3>'.LF;
      $out .= t('<form action="%u/constituencies.php">', array(
         '%u'=>BASEURL));
      $out .= '<input type="hidden" name="action" value="add"/>'.LF;
      $out .= '<table>'.LF;
      $out .= '<tr>'.LF;
      $out .= '<td>'._('Name').'</td>'.LF;
      $out .= '<td><input type="text" size="30" name="label"/></td>'.LF;
      $out .= '</tr>'.LF;
      $out .= '<tr>'.LF;
      $out .= '<td>'._('Description').'</td>'.LF;
      $out .= '<td><input type="text" size="30" name="description"/></td>'.LF;
      $out .= '</tr>'.LF;
      $out .= '<tr>'.LF;
      $out .= '<td/>'.LF;
      $out .= '<td><input type="submit" value="'._('Add').'"/></td>'.LF;
      $out .= '</tr>'.LF;
      $out .= '</table>'.LF;
      $out .= '</form>'.LF;
      $out .= '</div>'.LF;
      print $out;
      break;

   //-----------------------------------------------------------------
   case "edit":
      constituencyDetails();
      break;

   //-----------------------------------------------------------------
   case "add":
   case "update":
      $consid = fetchFrom('REQUEST', 'consid', '%d');
      defaultTo($consid, -1);

      $label = strip_tags(fetchFrom('REQUEST', 'label', '%s'));
      if (empty($label)) {
         die(_('Missing information in ').__LINE__);
      }
      $description = strip_tags(fetchFrom('REQUEST', 'description', '%s'));
      if (empty($description)) {
         die(_('Missing information in ').__LINE__);
      }

      $contacts = strip_tags(fetchFrom('REQUEST', 'contacts', '%s'));
      if (empty($contacts)) {
          $contacts = array();
      } else {
          $contacts = split("\r\n", $contacts);
      }
      if (!is_array($contacts)) {
          $contacts = array();
      }
      foreach ($contacts as $key=>$value) {
          $contacts[$key] = strip_tags($value);
      }

      $notes = htmlentities(fetchFrom('REQUEST', 'notes', '%s'));
      defaultTo($notes, '');
      

      if ($action=="add") {
         if (($c = addConstituency($label, $description, $error)) === false) {
            airt_msg($error);
            reload();
            exit();
         }

         generateEvent("newconstituency", array(
            "label"=>$label,
            "name"=>$description
         ));

         foreach ($contacts as $contact) {
             if (trim($contact) == '') {
                 continue;
             }
             if (addConstituencyContact($c, array('email'=>$contact), $error) === false) {
                 airt_msg($error);
             }
         }
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
         if (updateConstituency($consid, $label, $description, $notes, $error) === false) {
            airt_msg(_('Database error:').$error);
            reload();
            exit;
         } 

         $c = getConstituencyContacts($consid);
         // add contacts that do not yet exist
         /*
         foreach ($contacts as $contact) {
             if (trim($contact) == '') {
                 continue;
             }
             if (($u = getUserByEmail($contact)) == false) {
                 addUser(array('email'=>$contact));
                 airt_msg(t(_('Added user %u.'), array(
                    '%u'=>htmlentities($contact))));
                 $u = getUserByEmail($contact);
             } 
             if (!array_key_exists($u['id'], $c)) {
                if (addConstituencyContact($consid, 
                   array('userid'=>$u['id']), $error) === false) {
                     airt_msg($error);
                     reload();
                     exit();
                 } else {
                    airt_msg(t(_('Added user %u to constituency.'), array(
                       '%u'=>htmlentities($contact))));
                 }
             }
             unset($c[$u['id']]);
         }
         foreach ($c as $uid=>$data) {
             if ((removeConstituencyContact($consid, $uid, $error)) === false) {
                airt_msg($error);
                reload();
                exit();
             } else {
                airt_msg(t(_('Removed user %u from constituency.'), array(
                  '%u'=>htmlentities($data['email']))));
             }
         }
         */

         generateEvent("updateconstituency", array(
            "label"=>$label,
            "name"=>$description
         ));
         reload();
      }

      break;

   case 'rmcontact':
      removeConstituencyContactFrontend();
      break;

   case 'addcontact':
      addConstituencyContactFrontend();
      break;

   //-----------------------------------------------------------------
   case "Delete":
      $cons = fetchFrom('REQUEST', 'consid', '%d');
      defaultTo($cons, 'a');
      if (!is_numeric($cons)) {
         airt_msg(_('Invalid parameter type or missing information in ').__LINE__);
         reload();
         exit();
      }

      generateEvent("deleteconstituency", array(
         "constituencyid" => $cons
      ));

      if (db_query("DELETE FROM constituencies WHERE id=$cons") === false) {
          airt_msg(_('Unable to delete constituency: ').db_errormessage());
      }

      reload(BASEURL.'/constituencies.php');
      break;

   //-----------------------------------------------------------------
   default:
      die(_('Unknown action: ').$action);
} // switch
