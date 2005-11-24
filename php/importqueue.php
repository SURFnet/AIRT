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
require_once 'config.plib';
require_once LIBDIR.'/airt.plib';
require_once LIBDIR.'/database.plib';
require_once LIBDIR.'/error.plib';
require_once LIBDIR.'/importqueue.plib';

if (array_key_exists('action', $_REQUEST)) {
   $action = $_REQUEST['action'];
} else {
   $action = 'list';
}

function showQueue() {
   pageHeader('AIRT Import queue');
   $out = "<form method=\"post\">";
   $out .= formatQueueOverview();
   $out .= "<p><input type=\"submit\" label=\"Commit all incidents as accept or reject\" name=\"action\" value=\"Process\"> ";
   $out .= "<input type=\"submit\" name=\"action\" label=\"Refresh the import queue. Any unprocessed changes will be lost.\" value=\"Refresh\"></p>\n";
   $out .= "</form>\n";
   print $out;
}

switch ($action) {
   //----------------------------------------------------------------
   case 'Process':
      $error = '';
      // no decision received. Process button pushed from empty queue?
      if (!array_key_exists('decision', $_POST)) {
         showQueue();
         break;
      }
      pageHeader('Processing import queue');

      // interpret all decision and take action if accept or reject
      foreach ($_POST['decision'] as $id=>$value) {
         $update = false;
         switch ($value) {
            case 'accept':
               $value = 'accepted';
               print t('Accepting queue element %id<br/>'."\n", array('%id'=>$id));
               flush();
               $update = true;
               if (isset($_POST['add'][$id]) && $_POST['add'][$id] == 'on') {
                  print t('Adding to existing incident<br/>'."\n");
                  $error = '';
                  if (queueAddLogging($id, $error)) {
                     print t('Error processing item: %error', array(
                        '%error'=>$error));
                     $update = false;
                  } else {
                     $update = true;
                  }
               } else if (queueToAIRT($id, $error)) {
                  $update = false;
                  airt_error('ERR_FUNC', 'importqueue.php:'.__LINE__, $error);
                  break;
               }
               break;
            case 'reject':
               print t('Rejecting queue element %id<br/>'."\n", array('%id'=>$id));
               flush();
               $value = 'rejected';
               $update = true;
               break;
            default:
               print t('Ignoring queue element %id<br/>'."\n", array('%id'=>$id));
               flush();
         }
         if ($update) {
            if (updateQueueItem($id, 'status', $value, $error)) {
               airt_error('ERR_QUERY', 'importqueue.php:'.__LINE__, $error);
               Header("Location: $_SERVER[PHP_SELF]");
               return;
            }
         }
      }

      // show updated queue;
      echo "<p/><a href=\"incident.php\">Done.</a>";
      break;

   // ----------------------------------------------------------------
   case "showdetails":
      if (!array_key_exists('id', $_GET)) {
         airt_error('PARAM_MISSING', 'importqueue.php:'.__LINE__);
         Header("Location: $_SERVER[PHP_SELF]");
         exit;
      }
      if (!is_numeric($_GET['id'])) {
         airt_error('PARAM_MISSING', 'importqueue.php:'.__LINE__);
         Header("Location: $_SERVER[PHP_SELF]");
         exit;
      }
      $item = queuePeekItem($_GET['id'], $error);
      if ($item == NULL) {
         airt_error('', 'importqueue.php:'.__LINE__, 'Error fetching queue item');
         Header("Location: $_SERVER[PHP_SELF]");
         exit;
      }
      pageHeader("Queue details for item $_GET[id]");
      $out = "<table>\n";
      $out .= "<tr>\n";
      $out .= "  <td>Status</td>\n";
      $out .= "  <td>$item[status]</td>\n";
      $out .= "</tr>\n";
      $out .= "<tr>\n";
      $out .= "  <td>Type</td>\n";
      $out .= "  <td>$item[type]</td>\n";
      $out .= "</tr>\n";
      $out .= "<tr>\n";
      $out .= "  <td>Summary</td>\n";
      $out .= "  <td>$item[summary]</td>\n";
      $out .= "</tr>\n";
      $out .= "<tr>\n";
      $out .= "  <td colspan=\"2\" align=\"left\" nowrap>Input queue data</td>\n";
      $out .= "</tr>\n";
      $out .= "<tr valign=\"top\">\n";
      $out .= t("  <td colspan=\"2\" align=\"left\"><pre>%xml</pre></td>",
         array('%xml'=>htmlentities($item['content'])));
      $out .= "</tr>\n";
      $out .= "</table>\n";

      print $out;
      pageFooter();
      break;
   // ----------------------------------------------------------------
   case 'Refresh':
   case 'list':
      showQueue();
      break;

   // ----------------------------------------------------------------
   default:
      airt_error('PARAM_INVALID', 'importqueue.php:'.__LINE__);
      Header("Location: $_SERVER[PHP_SELF]");
}
?>
