<?php
/* vim: syntax=php tabstop=3 shiftwidth=3
 * AIRT: APPLICATION FOR INCIDENT RESPONSE TEAMS
 * Copyright (C) 2004,2005   Tilburg University, The Netherlands
 * TODO Codingstyle

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
 * netblocks.php -- manage net blocks
 * 
 * $Id$
 */
require_once 'config.plib';
require_once LIBDIR.'/airt.plib';
require_once LIBDIR.'/database.plib';
require_once LIBDIR.'/constituency.plib';
require_once LIBDIR.'/network.plib';

$action = fetchFrom('REQUEST', 'action', '%s');
defaultTo($action, 'list');

function show_form($id="") {
   $label = "";
   $action = "add";
   $submit = _('Add!');
   $constituency = "";
   $netmask = "";
   $network = "";

   if (!empty($id)) {
      if (!is_numeric($id)) {
         die(_('Invalid parameter type in ').__LINE__);
      }
      $networks = getNetworks();
      if (array_key_exists($id, $networks)) {
         $row = $networks["$id"];
         $action = "update";
         $submit = _('Update!');
         $network = $row["network"];
         $netmask = $row["netmask"];
         $label   = $row["label"];
         $constituency = $row["constituency"];
      }
   }
   print '<form method="POST">'.LF;
   print t('<input type="hidden" name="action" value="%action">', array(
      '%action'=>strip_tags($action))).LF;
   print t('<input type="hidden" name="id" value="%id">', array(
      '%id'=>$id)).LF;
   print '<table>'.LF;
   print '<tr>'.LF;
   print '   <td>'._('Network Address').'</td>'.LF;
   print t('   <td><input type="text" size="30" name="network" value="%network"></td>', array('%network'=>$network)).LF;
   print '</tr>'.LF;
   print '<tr>'.LF;
   print '   <td>'._('Netmask or CIDR').'</td>'.LF;
   print t('   <td><input type="text" size="30" name="netmask" value="%netmask"></td>', array('%netmask'=>$netmask)).LF;
   print '</tr>'.LF;
   print '<tr>'.LF;
   print '   <td>'._('Label').'</td>'.LF;
   print t('   <td><input type="text" size="30" name="label" value="%label"></td>', array('%label'=>$label)).LF;
   print '</tr>'.LF;
   print '<tr>'.LF;
   print '   <td>'._('Constituency').'</td>'.LF;
   print t('   <td>%constituencies</td>'.LF, array('%constituencies'=>getConstituencySelection("constituency", $constituency))).LF;
   print '</tr>'.LF;
   print '</table>'.LF;
   print '<p/>'.LF;
   print t('<input type="submit" value="%submit">', array('%submit'=>$submit)).LF;
   print '</form>'.LF;
}


switch ($action) {
   // --------------------------------------------------------------
   case "list":
      pageHeader(_('Networks'));

      print '<table cellpadding="3">'.LF;
      print '<tr>'.LF;
      print '   <td><B>'._('Network').'</B></td>'.LF;
      print '   <td><B>'._('Label').'</B></td>'.LF;
      print '   <td><B>'._('Constituency').'</B></td>'.LF;
      print '   <td><B>'._('Edit').'</B></td>'.LF;
      print '   <td><B>'._('Delete').'</B></td>'.LF;
      print '</tr>'.LF;

      $networklist = getNetworks();
      usort($networklist, "airt_netsort");
      $constituencies = getConstituencies();

      $count=0;
      foreach ($networklist as $nid=>$data) {
         $id = $data["id"];
         $network      = $data["network"];
         $netmask      = netmask2cidr($data["netmask"]);
         $label        = $data["label"];
         $constituency = $data["constituency"];
         $constituency_name  = $constituencies["$constituency"]["name"];
         $color = ($count++%2==0?"#FFFFFF":"#DDDDDD");
         print t('<tr valign="top" bgcolor="%color">', array(
            '%color'=>$color)).LF;
         print t('<td>%network/%netmask</td>', array(
            '%network'=>$network,
            '%netmask'=>$netmask)).LF;
         print t('<td>%label</td>', array('%label'=>$label)).LF;
         print t('<td><a href="constituencies.php?action=edit&cons=%constituency">%name</a></td>', array(
            '%constituency'=>$constituency,
            '%name'=>$constituency_name)).LF;
         print t('<td><a href="%url?action=edit&id=%id"><small>'._('edit').'</small></td>', array('%url'=>$_SERVER['PHP_SELF'], '%id'=>$id)).LF;
         print t('<td><a href="%url?action=delete&id=%id"><small>'._('delete').'</small></td>', array('%url'=>$_SERVER['PHP_SELF'], '%id'=>$id)).LF;
         print '</tr>'.LF;
      }
      print '</table>'.LF;
      print '<h3>'._('New network').'</h3>'.LF;
      show_form("");
      break;

   //-----------------------------------------------------------------
   case "edit":
      $id = fetchFrom('GET', 'id', '%d');
      if (empty($id)) {
         airt_error(PARAM_MISSING, 'networks.php'.__LINE__);
         reload();
         break;
      }
      if (!is_numeric($id)) {
         die(_('Invalid parameter type in ').__LINE__);
      }
      pageHeader(_('Edit Network'));
      show_form($id);
      pageFooter();
      break;

   //-----------------------------------------------------------------
   // XXX
   case "add":
   case "update":
      $id = fetchFrom('POST', 'id', '%d');
      defaultTo($id, -1);
      if (!is_numeric($id)) {
         die(_('Invalid parameter type ').__LINE__);
      }
      $network = fetchFrom('POST', 'network', '%s');
      if (empty($network)) {
         die(_('Missing parameter value in ').__LINE__);
      }
      $netmask = fetchFrom('POST', 'netmask', '%s');
      if (empty($netmask)) {
         die(_('Missing parameter value in ').__LINE__);
      }
      $res = array();
      if (sscanf($netmask, "/%s", $res) == 1) {
         $netmask = cidr2netmask(substr($netmask, 1));
      }
      $label = fetchFrom('POST', 'label', '%s');
      if (empty($label)) {
         die(_('Missing parameter value in ').__LINE__);
      }
      $constituency = fetchFrom('POST', 'constituency', '%s');
      if (empty($constituency)) {
         die(_('Missing parameter value in ').__LINE__);
      }
      if (!is_numeric($constituency)) {
         die(_('Invalid parameter type ').__LINE__);
      }
      if ($action=="add") {
         $res = db_query(q('
            INSERT INTO networks
            (id, network, netmask, label, constituency)
            VALUES
           (nextval(\'networks_sequence\'), %network, %netmask, %label, %cons)',
            array("%network"=>db_masq_null($network),
               '%netmask'=>db_masq_null($netmask),
               '%label'=>db_masq_null($label),
               '%cons'=>$constituency)));
         if (!$res) {
            airt_error(DB_QUERY, 'networks.php'.__LINE__);
            reload();
            return;
         }
         reload();
      } elseif ($action=="update") {
         if (empty($id)) {
            airt_error(PARAM_MISSING, 'networks.php'.__LINE__);
            reload();
            return;
         }
         $res = db_query(q('
            UPDATE networks
            SET    network=%network,
                   netmask=%netmask,
                   label=%label,
                   constituency=%cons
            WHERE id=%id', array(
               '%network'=>db_masq_null($network),
               '%netmask'=>db_masq_null($netmask),
               '%label'=>db_masq_null($label),
               '%cons'=>$constituency,
               '%id'=>$id)));
         if (!$res) {
            airt_error(DB_QUERY, 'networks.php'.__LINE__);
            Header("Location: $_SERVER[PHP_SELF]");
            return;
         }
         reload();
      }
      break;

   //-----------------------------------------------------------------
   case "delete":
      $id = fetchFrom('GET', 'id', '%d');
      if (empty($id)) {
         airt_error(PARAM_MISSING, 'networks.php'.__LINE__);
         reload();
         return;
      }
      if (!is_numeric($id)) {
         die(_('Invalid parameter type ').__LINE__);
      }
      $res = db_query(q('
         DELETE FROM networks
         WHERE  id=%id', array(
            '%id'=>$id)));
      if (!$res) {
         airt_error(DB_QUERY, 'networks.php'.__LINE__);
         reload();
         return;
      }
      reload();

      break;
   //-----------------------------------------------------------------
   default:
      die(_('Unknown action: ').strip_tags($action));
} // switch
?>
