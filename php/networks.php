<?php
/* vim: syntax=php tabstop=3 shiftwidth=3
 * AIRT: APPLICATION FOR INCIDENT RESPONSE TEAMS
 * Copyright (C) 2004,2005   Tilburg University, The Netherlands

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
require_once 'config.plib';
require_once LIBDIR.'/airt.plib';
require_once LIBDIR.'/database.plib';
require_once LIBDIR.'/constituency.plib';
require_once LIBDIR.'/network.plib';

$action = strip_tags(fetchFrom('REQUEST', 'action', '%s'));
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
         $datasource = $row["datasource"];
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
   print t('   <td><input type="text" required size="30" minlength="4" pattern="^\d[\da-fA-F.:]+$" name="network" value="%network"></td>', array('%network'=>$network)).LF;
   print '</tr>'.LF;
   print '<tr>'.LF;
   print '   <td>'._('Netmask/Prefix or CIDR for IPv6').'</td>'.LF;
   print t('   <td><input type="text" required minlength="1" maxlength="15" size="30" pattern="^\d[\da-fA-F.:]+$" name="netmask" value="%netmask"></td>', array('%netmask'=>$netmask)).LF;
   print '</tr>'.LF;
   print '<tr>'.LF;
   print '   <td>'._('Label').'</td>'.LF;
   print t('   <td><input type="text" required size="30" name="label" value="%label"></td>', array('%label'=>$label)).LF;
   print '</tr>'.LF;
   print '<tr>'.LF;
   print '   <td>'._('Constituency').'</td>'.LF;
   print t('   <td>%constituencies</td>'.LF, array('%constituencies'=>getConstituencySelection("constituency", $constituency))).LF;
   print '</tr>'.LF;
   print '<tr>'.LF;
   print '   <td>'._('Datasource').'</td>'.LF;
   print t('   <td><input type="text" disabled size="30" value="%datasource"></td>', array('%datasource'=>$datasource)).LF;
   print '</tr>'.LF;
   print '</table>'.LF;
   print '<p/>'.LF;
   print t('<input type="submit" value="%submit">', array('%submit'=>$submit)).LF;
   print '</form>'.LF;
}


switch ($action) {
   // --------------------------------------------------------------
   case "list":
      pageHeader(_('Networks'), array(
         'menu'=>'constituencies',
         'submenu'=>'networks'));

      print '<table class="table horizontal">'.LF;
      print '<tr>'.LF;
      print '   <td><B>'._('Network').'</B></td>'.LF;
      print '   <td><B>'._('Label').'</B></td>'.LF;
      print '   <td><B>'._('Constituency').'</B></td>'.LF;
      print '   <td/'.LF;
      print '</tr>'.LF;

      $networklist = getNetworks();
      usort($networklist, "airt_netsort");
      $constituencies = getConstituencies();

      $count = 0;
      foreach ($networklist as $nid=>$data) {
         $id = $data["id"];
         $label        = $data["label"];
         $constituency = $data["constituency"];
         $constituency_name  = $constituencies["$constituency"]["name"];
         $color = ($count++%2==0?"#FFFFFF":"#DDDDDD");
         print t('<tr>', array('%color'=>$color)).LF;

         print t('<td>%network</td>', [
             '%network'=>prettyNetwork($data['network'],$data['netmask'])]).LF;
         print t('<td>%label</td>', array('%label'=>$label)).LF;
         print t('<td><a href="constituencies.php?action=edit&cons=%constituency">%name</a></td>', array(
            '%constituency'=>$constituency,
            '%name'=>$constituency_name)).LF;
         print t('<td><a href="%url?action=edit&id=%id">'._('edit').'</a>',
         array('%url'=>BASEURL.'/networks.php', '%id'=>$id)).LF;
         print ' ';
         print t('<a href="%url?action=delete&id=%id">'._('delete').'</a></td>',
         array('%url'=>BASEURL.'/networks.php', '%id'=>$id)).LF;
         print '</tr>'.LF;
      }
      print '</table>'.LF;
      print '<h3>'._('New network').'</h3>'.LF;
      print '<p>Enter either as IPv4 + netmask, or IPv6 + cidr length.</p>'.LF;
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
      pageHeader(_('Edit Network'), array(
		   'menu'=>'constituencies',
			'submenu'=>'networks'));
      print '<div class="right">'.LF;
      print t('<a class="button" href="?action=delete&id=%i">%l</a>', [
         '%i'=>$id,
         '%l'=>_('Delete network')]);
      print '</div>'.LF;

      show_form($id);
      pageFooter();
      break;

   //-----------------------------------------------------------------
   case "add":
   case "update":
      $id = fetchFrom('POST', 'id', '%d');
      defaultTo($id, -1);
      if (!is_numeric($id)) {
         die(_('Invalid parameter type ').__LINE__);
      }

      $network = strip_tags(fetchFrom('REQUEST', 'network', '%s'));
      if (empty($network)) {
          airt_msg(_('Network cannot be empty. Must contain a valid IPv4/IPv6 address.'));
          exit(reload());
      } elseif (validateIPV4($network)) {
        $netmask = strip_tags(fetchFrom('REQUEST', 'netmask', '%s'));
        if (validateIPV4mask($netmask) == false) {
           airt_msg(_('Invalid format for netmask. Valid format is dotted quad subnet mask, '.
              'e.g 255.255.255.0'));
           exit(reload());
        }
      } elseif(validateIPV6($network)) {
        $netmask = strip_tags(fetchFrom('REQUEST', 'netmask', '%s'));
        $network = expandIPV6($network);
        if (validateIPV6CIDR($netmask) == false) {
           airt_msg(_('Invalid format for netmask. Valid formats is cidr length, '.
              'e.g 32'));
           exit(reload());
        }
      } else {
        airt_msg(_('Must contain a valid IPv4/IPv6 address.'));
        exit(reload());
      }

      $label = strip_tags(fetchFrom('REQUEST', 'label', '%s'));
      if (empty($label)) {
         airt_msg(_('Label may not be empty.'));
         exit(reload());
      }
      $constituency = strip_tags(fetchFrom('REQUEST', 'constituency', '%d'));
      if (empty($constituency)) {
         airt_msg(_('Constituency may not be empty.'));
         exit(reload());

      }
      if ($action == "add") {
         if (addNetwork(array(
            'network'=>$network,
            'netmask'=>$netmask,
            'label'=>$label,
            'constituency'=>$constituency
         ), $error) === false) {
            airt_msg(_('Unable to add network in').' networks.php:'.__LINE__.
               ';'.$error);
         }
      } elseif ($action=="update") {
         if (empty($id)) {
            airt_error(PARAM_MISSING, 'networks.php'.__LINE__);
            reload();
            return;
         }
         if (updateNetwork(array(
               'id'=>$id,
               'network'=>$network,
               'netmask'=>$netmask,
               'label'=>$label,
               'constituency'=>$constituency
         ), $error) === false) {
            airt_msg(_('Unable to update network in').' network.php:'.__LINE__.
               ';'.$error);
         }
      }
      reload();
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
      airt_msg(t(_('Network %u deleted.'), ['%u'=>$id]));
      reload();

      break;
   //-----------------------------------------------------------------
   default:
      die(_('Unknown action: ').strip_tags($action));
} // switch
