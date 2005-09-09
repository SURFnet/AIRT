<?php
/* vim:syntax=php shiftwidth=3 tabstop=3
 * $Id$ 

 * AIRT: APPLICATION FOR INCIDENT RESPONSE TEAMS
 * Copyright (C) 2004,2005	Kees Leune <kees@uvt.nl>

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
require_once '../config.plib';
require_once LIBDIR.'/airt.plib';
require_once LIBDIR.'/database.plib';
require_once LIBDIR.'/error.plib';
require_once LIBDIR.'/importqueue/importqueue.plib';

if (array_key_exists('action', $_REQUEST)) {
   $action = $_REQUEST['action'];
} else {
   $action = 'list';
}

switch ($action) {
   case 'list':
      pageHeader('AIRT Import queue');
      $res = db_query(q('SELECT id, created, status, sender, type, summary 
         FROM import_queue
         WHERE status = \'open\'
         ORDER BY created DESC'));
      if ($res == false) {
         airt_error('DB_QUERY', 'queue.php'.__LINE__);
         Header("Location: $_SERVER[PHP_SELF]");
         return;
      }
      $count = 0;
      $out = "<form method=\"post\">";
      $out .= "<table cellpadding=\"4\" class=\"queue\">\n";
      $out .= "<tr><th>Sender</th><th>Type</th><th>Created</th><th>Details</th><th>Decision</th></tr>\n";
      while ($row = db_fetch_next($res)) {
         $out .= t("<tr bgcolor=\"%color\">\n", array('%color'=>($count++ % 2 == 0) ? '#DDDDDD' : '#FFFFFF'));
         $out .= t("  <td>%sender</td>\n", array('%sender'=>$row['sender']));
         $out .= t("  <td>%type</td>\n", array('%type'=>$row['type']));
         $out .= t("  <td>%created</td>\n", array('%created'=>$row['created']));
         $out .= "  <td><a href=\"\">details</a></td>\n";
         $out .= "  <td><select name=\"decision[]\">\n";
         $out .= choice('Leave unchanged', 'leave', 'leave');
         $out .= choice('Accept', 'accept', 'leave');
         $out .= choice('Reject', 'reject', 'leave');
         $out .= "  </select>\n";
         $out .= "</td>\n";
         $out .= "</tr>\n";
      }
      $out .= "</table>\n";
      $out .= "<p><input type=\"submit\" name=\"action\" value=\"Update queue\"></p>\n";
      $out .= "</form>\n";
      $out .= t("<div class=\"queue_summary\">%count open incidents in queue.</div>", array('%count'=>$count));
      print $out;
      break;
   default:
      airt_error('PARAM_INVALID', 'queue.php');
      Header("Location: $_SERVER[PHP_SELF]");
}
?>
