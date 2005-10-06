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

if (array_key_exists("action", $_REQUEST)) {
   $action=$_REQUEST["action"];
} else {
   $action = "list";
}

function show_form($id="") {
   $label = "";
   $action = "add";
   $submit = "Add!";
   $constituency = "";
   $netmask = "";
   $network = "";

   if ($id != "") {
      $networks = getNetworks();
      if (array_key_exists($id, $networks)) {
         $row = $networks["$id"];
         $action = "update";
         $submit = "Update!";
         $network = $row["network"];
         $netmask = $row["netmask"];
         $label   = $row["label"];
         $constituency = $row["constituency"];
      }
   }
   print t('<form method="POST">')."\n";
   print t('<input type="hidden" name="action" value="%action">', array(
      '%action'=>$action))."\n";
   print t('<input type="hidden" name="id" value="%id">', array(
      '%id'=>$id))."\n";
   print t('<table>')."\n";
   print t('<tr>')."\n";
   print t('   <td>Network Address</td>')."\n";
   print t('   <td><input type="text" size="30" name="network" value="%network"></td>', array('%network'=>$network))."\n";
   print t('</tr>')."\n";
   print t('<tr>')."\n";
   print t('   <td>Netmask or CIDR</td>')."\n";
   print t('   <td><input type="text" size="30" name="netmask" value="%netmask"></td>', array('%netmask'=>$netmask))."\n";
   print t('</tr>')."\n";
   print t('<tr>')."\n";
   print t('   <td>Label</td>')."\n";
   print t('   <td><input type="text" size="30" name="label" value="%label"></td>', array('%label'=>$label))."\n";
   print t('</tr>')."\n";
   print t('<tr>')."\n";
   print t('   <td>Constituency</td>'."\n");
   print t('   <td>%constituencies</td>'."\n", array('%constituencies'=>getConstituencySelection("constituency", $constituency)))."\n";
   print t('</tr>')."\n";
   print t('</table>')."\n";
   print t('<p/>')."\n";
   print t('<input type="submit" value="%submit">', array('%submit'=>$submit))."\n";
   print t('</form>')."\n";
}


switch ($action) {
   // --------------------------------------------------------------
   case "list":
      pageHeader("Networks");

      print t('<table cellpadding="3">')."\n";
      print t('<tr>')."\n";
      print t('   <td><B>Network</B></td>')."\n";
      print t('   <td><B>Label</B></td>')."\n";
      print t('   <td><B>Constituency</B></td>')."\n";
      print t('   <td><B>Edit</B></td>')."\n";
      print t('   <td><B>Delete</B></td>')."\n";
      print t('</tr>')."\n";

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
            '%color'=>$color))."\n";
         print t('<td>%network/%netmask</td>', array(
            '%network'=>$network,
            '%netmask'=>$netmask))."\n";
         print t('<td>%label</td>', array('%label'=>$label))."\n";
         print t('<td><a href="constituencies.php?action=edit&cons=%constituency">%name</a></td>', array(
            '%constituency'=>$constituency,
            '%name'=>$constituency_name))."\n";
         print t('<td><a href="%url?action=edit&id=%id"><small>edit</small></td>', array('%url'=>$_SERVER['PHP_SELF'], '%id'=>$id))."\n";
         print t('<td><a href="%url?action=delete&id=%id"><small>delete</small></td>', array('%url'=>$_SERVER['PHP_SELF'], '%id'=>$id))."\n";
         print t('</tr>')."\n";
      }
      print t('</table>')."\n";
      print t('<h3>New network</h3>')."\n";
      show_form("");
      break;

   //-----------------------------------------------------------------
   case "edit":
      if (array_key_exists("id", $_GET)) {
         $id=$_GET["id"];
      } else {
         airt_error(PARAM_MISSING, 'networks.php'.__LINE__);
         Header("Location: $_SERVER[PHP_SELF]");
         break;
      }
      pageHeader("Edit Network");
      show_form($id);
      pageFooter();
      break;

   //-----------------------------------------------------------------
   case "add":
   case "update":
      $missing = false;
      if (array_key_exists("id", $_POST)) {
         $id=$_POST["id"];
      } else {
         $id="";
      }
      if (array_key_exists("network", $_POST)) {
         $network=$_POST["network"];
      } else {
         $missing = true;
      }
      if (array_key_exists("netmask", $_POST)) {
         $netmask=$_POST["netmask"];
         $res = array();
         if (sscanf($netmask, "/%s", $res) == 1) {
            $netmask = cidr2netmask(substr($netmask, 1));
         }
      } else {
         $missing = true;
      }
      if (array_key_exists("label", $_POST)) {
         $label=$_POST["label"];
      } else {
         $missing = true;
      }
      if (array_key_exists("constituency", $_POST)) {
         $constituency=$_POST["constituency"];
      } else {
         $missing = true;
      }
      if ($missing) {
         airt_error(PARAM_MISSING, 'networks.php'.__LINE__);
         Header("Location: $_SERVER[PHP_SELF]");
         return;
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
            Header("Location: $_SERVER[PHP_SELF]");
            return;
         }

         Header("Location: $_SERVER[PHP_SELF]");
      } elseif ($action=="update") {
         if ($id=="") {
            airt_error(PARAM_MISSING, 'networks.php'.__LINE__);
            Header("Location: $_SERVER[PHP_SELF]");
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
         Header("Location: $_SERVER[PHP_SELF]");
      }
      break;

   //-----------------------------------------------------------------
   case "delete":
      if (array_key_exists("id", $_GET)) {
         $id=$_GET["id"];
      } else {
         airt_error(PARAM_MISSING, 'networks.php'.__LINE__);
         Header("Location: $_SERVER[PHP_SELF]");
         return;
      }
      $res = db_query(q('
         DELETE FROM networks
         WHERE  id=\'%id\'', array(
            '%id'=>$id)));
      if (!$res) {
         airt_error(DB_QUERY, 'networks.php'.__LINE__);
         Header("Location: $_SERVER[PHP_SELF]");
         return;
      }
      Header("Location: $_SERVER[PHP_SELF]");

      break;
   //-----------------------------------------------------------------
   default:
      die("Unknown action: $action");
} // switch
?>
