<?php
/* vim: syntax=php tabstop=3 shiftwidth=3
 * TODO: Codingstyle
 *
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
 *
 * links.php -- manage links
 * 
 * $Id$
 */
require_once 'config.plib';
require_once LIBDIR.'/airt.plib';
require_once LIBDIR.'/database.plib';

function format_position_select($current_value, $max) {
   $out = choice(_('Do not display'), '', $current_value);
   for ($i=1; $i <= $max; $i++) {
      $out .= choice($i, $i, $current_value);
   }
   return $out;
}

$action = strip_tags(fetchFrom('REQUEST', 'action', '%s'));
defaultTo($action, 'list');

switch ($action) {
   // --------------------------------------------------------------
   case "list":
      $res = db_query(q(" SELECT count(id) FROM urls"));
      $row = db_fetch_next($res);
      $n = $row['count'];
      db_free_result($res);

      $res = db_query(q("
         SELECT id, url, label, navbar_position, menu_position
         FROM   urls
         ORDER BY menu_position"));
      if (!$res) {
         airt_error('DB_QUERY', 'links.php:'.__LINE__);
         reload('index.php');
         return;
      }
      $out = '<p><strong>'._('Main menu links').'</strong></p>'.LF;
      $out .= '<form method="POST">'.LF;
      $out .= '<table width="100%">'.LF;
      $out .= '<tr>'.LF;
      $out .= '  <td><strong>'._('Menu item').'</strong></td>'.LF;
      $out .= '  <td><strong>'._('Position').'</strong></td>'.LF;
      $out .= '  <td colspan="2">&nbsp;</td>'.LF;
      $out .= '</tr>'.LF;

      $count=0;
      while ($row = db_fetch_next($res)) {
         $out .= t("<tr bgcolor=\"%color\">\n", array(
            '%color' => ($count++%2==0) ? "#DDDDDD" : "#FFFFFF"));
         $out .= "<td>\n";
         $out .= t(   '<a href="%url">%label</a>', array(
            '%url'=>$row["url"], '%label'=>strip_tags($row["label"])));
         $out .= t("</td>\n");
         $out .= "<td>\n";
         $out .= t("  <select name=\"menu_pos[%id]\">\n", array(
            '%id'=>$row['id']));
         $out .= format_position_select($row['menu_position'], $n);
         $out .= "  </select>\n";
         $out .= "</td>\n";
         $out .= t('<td><a href="%url?action=edit&id=%id">'._('edit').'</a></td>',
            array('%url'=>$_SERVER['PHP_SELF'],
                  '%id'=>urlencode($row["id"])));
         $out .= t('<td><a href="%url?action=delete&id=%id">'._('delete').'</a></td>',
            array('%url'=>$_SERVER['PHP_SELF'],
                  '%id'=>urlencode($row["id"])));
         $out .= "</tr>\n";
      }
      $out .= "</table>\n";
      $out .= "<p><input type=\"submit\" name=\"action\" value=\""._('Update main menu')."\"></p>";
      $out .= "</form>\n";
      $out .= "<hr/><br/>\n";

      db_free_result($res);
      $out .= "<p/>";
      $res = db_query(q("
         SELECT id, url, label, navbar_position, menu_position
         FROM   urls
         ORDER BY navbar_position"));
      if (!$res) {
         airt_error('DB_QUERY', 'links.php:'.__LINE__);
         Header("Location: index.php");
         return;
      }
      $out .= t("<p><strong>"._('Navbar links')."</strong></p>\n");
      $out .= t('<form method="POST">');
      $out .= t("<table width=\"100%\">");
      $out .= "<tr>\n";
      $out .= "  <td><strong>"._('Menu item')."</strong></td>\n";
      $out .= "  <td><strong>"._('Position')."</strong></td>\n";
      $out .= "  <td colspan=\"2\">&nbsp;</td>\n";
      $out .= "</tr>\n";

      $count=0;
      while ($row = db_fetch_next($res)) {
         $out .= t("<tr bgcolor=\"%color\">\n", array(
            '%color' => ($count++%2==0) ? "#DDDDDD" : "#FFFFFF"));
         $out .= "<td>\n";
         $out .= t(   '<a href="%url">%label</a>', array(
            '%url'=>$row["url"], '%label'=>$row["label"]));
         $out .= t("</td>\n");
         $out .= "<td>\n";
         $out .= t("  <select name=\"menu_pos[%id]\">\n", array(
            '%id'=>$row['id']));
         $out .= format_position_select($row['navbar_position'], $n);
         $out .= "  </select>\n";
         $out .= "</td>\n";
         $out .= t('<td><a href="%url?action=edit&id=%id">'._('edit').'</a></td>',
            array('%url'=>$_SERVER['PHP_SELF'],
                  '%id'=>urlencode($row["id"])));
         $out .= t('<td><a href="%url?action=delete&id=%id">'._('delete').'</a></td>',
            array('%url'=>$_SERVER['PHP_SELF'],
                  '%id'=>urlencode($row["id"])));
         $out .= "</tr>\n";
      }
      $out .= "</table>\n";
      $out .= "<p><input type=\"submit\" name=\"action\" value=\"".
         _('Update navigation bar')."\"></p>";
      $out .= "</form>\n";
      $out .= "<hr/><br/>\n";


      $out .= "<strong>"._('Add new menu item')."</strong><BR/>\n";
      $out .= "<form method=\"POST\">\n";
      $out .= "<input type=\"hidden\" name=\"action\" value=\"add\">\n";
      $out .= "<table>\n";
      $out .= "<tr>\n";
      $out .= "   <td>"._('URL')."</td>\n";
      $out .= "   <td><input type=\"text\" name=\"url\" size=\"50\"></td>\n";
      $out .= "</tr>\n";
      $out .= "<tr>\n";
      $out .= "   <td>"._('Description')."</td>\n";
      $out .= "   <td><input type=\"text\" name=\"description\" size=\"50\"></td>\n";
      $out .= "</tr>\n";
      $out .= "</table>\n";
      $out .= "<input type=\"submit\" value=\""._('Add')."\">\n";
      $out .= "</form>\n";

      pageHeader(_("Links"));
      print $out;
      pageFooter();
      break;

   // --------------------------------------------------------------
   case "add":
        $url = strip_tags(fetchFrom('REQUEST', 'url'));
        if (empty($url)) {
           die(_('Missing information ').__LINE__);
        }
        $description = strip_tags(fetchFrom('REQUEST', 'description'));
        if (empty($description)) {
           die(_('Missing information ').__LINE__);
        }
        $now = Date("Y-m-d H:i:s");
        $res = db_query(sprintf("
            INSERT INTO urls
            (id, url, label, created, createdby)
            VALUES
            (nextval('urls_sequence'), %s, %s, '%s', %s)",
            db_masq_null($url),
            db_masq_null($description),
            $now,
            $_SESSION["userid"]))
        or die(_("Unable to insert URL"));

        reload();
        break;

    // --------------------------------------------------------------
    case "delete":
       $id = fetchFrom('REQUEST', 'id', '%d');
       if (empty($id)) {
          die(_('Missing information in ').__LINE__);
       }
       if (!is_numeric($id)) {
          // should not happen
          die(_('Invalid parameter type ').__LINE__);
       }

       $res = db_query(sprintf("
            DELETE FROM urls
            WHERE ID=%d", $id))
       or die(_("Unable to delete URL"));

       reload();
       break;

    // --------------------------------------------------------------
    case "edit":
       $id = fetchFrom('REQUEST', 'id', '%d');
       if (empty($id)) {
          die(_("Missing information in ").__LINE__);
       }
       if (!is_numeric($id)) {
           // should not happen
           die(_('Invalid parameter type ').__LINE__);
       }

       $res = db_query(sprintf("
            SELECT url, label
            FROM   urls
            WHERE  id=%d", $id))
       or die(_("Unable to retrieve URL"));

       if (db_num_rows($res) == 0) die(_("Incorrect row id"));

       pageHeader("Edit link");
       $row = db_fetch_next($res);

        print '<form action="'.$_SERVER['PHP_SELF'].'" method="POST">'.LF;
        print '<input type="hidden" name="action" value="update">'.LF;
        print '<input type="hidden" name="id" value="'.$id.'">'.LF;
        print '<table>'.LF;
        print '<tr>'.LF;
        print '    <td>URL</td>'.LF;
        print '    <td><input type="text" name="url" size="50" value="'.
           strip_tags($row['url']).'"></td>'.LF;
        print '</tr>'.LF;
        print '<tr>'.LF;
        print '    <td>Description</td>'.LF;
        print '    <td><input type="text" name="description" size="50"'.LF;
        print '         value="'.strip_tags($row['label']).'"></td>'.LF;
        print '</tr>'.LF;
        print '</table>'.LF;
        print '<input type="submit" value="Update">'.LF;
        print '</form>'.LF;
        break;

    // --------------------------------------------------------------
    case "update":
       $url = strip_tags(fetchFrom('REQUEST', 'url'));
       if (empty($url)) {
          die(_("Missing information ").__LINE__);
       }
       $description = strip_tags(fetchFrom('REQUEST', 'description'));
       if (empty($description)) {
          die(_("Missing information ").__LINE__);
       }
       $id = fetchFrom('REQUEST', 'id', '%d');
       if (empty($id)) {
          die(_("Missing information ").__LINE__);
       }
       if (!is_numeric($id)) {
          die(_('Invalid parameter type ').__LINE__);
       }

       $res = db_query(sprintf("
            UPDATE URLs
            SET    label=%s,
                   url=%s
            WHERE  id=%d", 
            db_masq_null($description),
            db_masq_null($url),
            $id))
        or die(_("Unable to update URL"));

        reload();
        break;

    // --------------------------------------------------------------
    case _("Update main menu"):
      $menu_pos = fetchFrom('POST', 'menu_pos');
      if (empty($menu_pos)) {
         airt_error('PARAM_MISSING', 'links.php:'.__LINE__);
         reload();
         break;
      }
      foreach ($menu_pos as $id=>$pos) {
        if (!empty($id) && !is_numeric($id)) {
           airt_msg(_('Invalid parameter type in ').__LINE__);
           reload();
        }
        if (!empty($pos) && !is_numeric($pos)) {
           airt_msg(_('Invalid parameter type in ').__LINE__);
           reload();
        }
        $res = db_query(q("UPDATE urls SET menu_position=%pos WHERE id=%id", 
            array('%pos'=>($pos=='')?'NULL':sprintf("%d", $pos), '%id'=>$id)));
        if (!$res) {
            airt_error('DB_QUERY', 'links.php:'.__LINE__);
            reload();
            break;
        }
      }
      airt_msg(_("Menu updated."));
      reload();
      break;

    // --------------------------------------------------------------
    case _("Update navigation bar"):
      $menu_pos = fetchFrom('POST', 'menu_pos');
      if (empty($menu_pos)) {
         airt_error('PARAM_MISSING', 'links.php:'.__LINE__);
         reload();
         break;
      }
      foreach ($menu_pos as $id=>$pos) {
        if (!empty($id) && !is_numeric($id)) {
           airt_msg(_('Invalid parameter type in ').__LINE__);
           reload();
        }
        if (!empty($pos) && !is_numeric($pos)) {
           airt_msg(_('Invalid parameter type in ').__LINE__);
           reload();
        }
        $res = db_query(q("UPDATE urls SET navbar_position=%pos WHERE id=%id", 
            array('%pos'=>($pos=='')?'NULL':sprintf("%d",$pos), '%id'=>$id)));
        if (!$res) {
            airt_error('DB_QUERY', 'links.php:'.__LINE__);
            reload();
            break;
         }
      }
      airt_msg(_("Navigation bar updated."));
      reload();
      break;
   // --------------------------------------------------------------
    default:
        die(_("Unknown action: ").strip_tags($action));
} // switch
?>
