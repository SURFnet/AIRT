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

function showQueue() {
   pageHeader('AIRT Import queue');
   $out = "<form method=\"post\">";
   $out .= formatQueueOverview();
   $out .= "<p><input type=\"submit\" name=\"action\" value=\"Update queue\"></p>\n";
   $out .= "</form>\n";
   print $out;
}

switch ($action) {
   #----------------------------------------------------------------
   case 'Update queue':
      $error = '';
      if (!array_key_exists('decision', $_POST)) {
         airt_error('PARAM_MISSING', 'queue.php:'.__LINE__);
         Header("Location: $_SERVER[PHP_SELF]");
         exit;
      }

      # interpret all decision and take action if accept or reject
      foreach ($_POST['decision'] as $id=>$value) {
         $update = false;
         switch ($value) {
            case 'accept':
               $value = 'accepted';
               $update = true;
               if (queueToAIRT($id, $error)) {
                  $update = false;
                  airt_error('ERR_FUNC', 'queue.php:'.__LINE__, $error);
                  break;
               }
               break;
            case 'reject':
               $value = 'rejected';
               $update = true;
               break;
         }
         if ($update) {
            if (updateQueueItem($id, 'status', $value, $error)) {
               airt_error('ERR_QUERY', 'queue.php:'.__LINE__, $error);
               Header("Location: $_SERVER[PHP_SELF]");
               return;
            }
         }
      }

      # show updated queue;
      showQueue();
      break;

   #----------------------------------------------------------------
   case 'list':
      showQueue();
      break;

   #----------------------------------------------------------------
   default:
      airt_error('PARAM_INVALID', 'queue.php:'.__LINE__);
      Header("Location: $_SERVER[PHP_SELF]");
}
?>
