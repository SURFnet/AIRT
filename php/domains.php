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
require_once LIBDIR.'/domain.plib';

$action = strip_tags(fetchFrom('REQUEST', 'action', '%s'));
defaultTo($action, 'list');

function show_form($id="") {
   $label = "";
   $action = "add";
   $submit = _('Add!');
   $constituency = "";
   $domain = "";

   if (!empty($id)) {
      if (!is_numeric($id)) {
         die(_('Invalid parameter type in ').__LINE__);
      }
      $domains = getDomains();
      if (array_key_exists($id, $domains)) {
         $row = $domains["$id"];
         $action = "update";
         $submit = _('Update!');
         $domain = $row["domain"];
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
   print '   <td>'._('Domain').'</td>'.LF;
   print t('   <td><input type="text" size="30" maxlength="128" name="domain" required pattern="[a-z0-9]+[a-z0-9-]+\.[a-z]{2,}" value="%domain"></td>', array('%domain'=>$domain)).LF;
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
      pageHeader(_('Domains'), array(
         'menu'=>'constituencies',
         'submenu'=>'domains'));

      print '<p><a href="?action=listplain">list plain</a></p>'.LF;
      print '<table class="table horizontal">'.LF;
      print '<tr>'.LF;
      print '   <td><B>'._('Domain').'</B></td>'.LF;
      print '   <td><B>'._('Constituency').'</B></td>'.LF;
      print '   <td/'.LF;
      print '</tr>'.LF;

      $domainlist = getDomains();
      $constituencies = getConstituencies();

      $count = 0;
      foreach ($domainlist as $nid=>$data) {
         $id = $data["id"];
         $domain = $data["domain"];
         $constituency = $data["constituency"];
         $constituency_name  = $constituencies["$constituency"]["name"];
         $color = ($count++%2==0?"#FFFFFF":"#DDDDDD");
         print t('<tr>', array('%color'=>$color)).LF;

          print t('<td>%domain</td>', array('%domain'=>$domain)).LF;
         print t('<td><a href="constituencies.php?action=edit&cons=%constituency">%name</a></td>', array(
            '%constituency'=>$constituency,
            '%name'=>$constituency_name)).LF;
         print t('<td><a href="%url?action=edit&id=%id">'._('edit').'</a>',
         array('%url'=>BASEURL.'/domains.php', '%id'=>$id)).LF;
         print ' ';
         print t('<a href="%url?action=delete&id=%id">'._('delete').'</a></td>',
         array('%url'=>BASEURL.'/domains.php', '%id'=>$id)).LF;
         print '</tr>'.LF;
      }
      print '</table>'.LF;
      print '<h3>'._('Add domain').'</h3>'.LF;
      show_form("");
      break;

   //-----------------------------------------------------------------
   case "listplain":
      header('Content-Type: text/plain');
      $domainlist = getDomains();
      foreach ($domainlist as $nid=>$data) {
         print $data["domain"] . "\n";
      }
      break;

   //-----------------------------------------------------------------
   case "edit":
      $id = fetchFrom('GET', 'id', '%d');
      if (empty($id)) {
         airt_error(PARAM_MISSING, 'domains.php'.__LINE__);
         reload();
         break;
      }
      if (!is_numeric($id)) {
         die(_('Invalid parameter type in ').__LINE__);
      }
      pageHeader(_('Edit domain'), array(
		   'menu'=>'constituencies',
			'submenu'=>'domains'));
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

      $domain = strip_tags(fetchFrom('REQUEST', 'domain', '%s'));
      if (empty($domain)) {
          airt_msg(_('Domain cannot be empty.'));
          exit(reload());
      }

      $constituency = strip_tags(fetchFrom('REQUEST', 'constituency', '%d'));
      if (empty($constituency)) {
         airt_msg(_('Constituency may not be empty.'));
         exit(reload());

      }
      if ($action == "add") {
         if (addDomain(array(
            'domain'=>$domain,
            'constituency'=>$constituency
         ), $error) === false) {
            airt_msg(_('Unable to add domain in').' domains.php:'.__LINE__.
               ';'.$error);
         }
      } elseif ($action=="update") {
         if (empty($id)) {
            airt_error(PARAM_MISSING, 'domains.php'.__LINE__);
            reload();
            return;
         }
         if (updateDomain(array(
               'id'=>$id,
               'domain'=>$domain,
               'constituency'=>$constituency
         ), $error) === false) {
            airt_msg(_('Unable to update domain in').' domains.php:'.__LINE__.
               ';'.$error);
         }
      }
      reload();
      break;

   //-----------------------------------------------------------------
   case "delete":
      $id = fetchFrom('GET', 'id', '%d');
      if (empty($id)) {
         airt_error(PARAM_MISSING, 'domains.php'.__LINE__);
         reload();
         return;
      }
      if (!is_numeric($id)) {
         die(_('Invalid parameter type ').__LINE__);
      }
      $res = db_query(q('
         DELETE FROM domains
         WHERE  id=%id', array(
            '%id'=>$id)));
      if (!$res) {
         airt_error(DB_QUERY, 'domains.php'.__LINE__);
         reload();
         return;
      }
      reload();

      break;
   //-----------------------------------------------------------------
   default:
      die(_('Unknown action: ').strip_tags($action));
} // switch
