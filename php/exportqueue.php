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
require_once LIBDIR.'/exportqueue.plib';

if (array_key_exists('action', $_REQUEST)) {
   $action = $_REQUEST['action'];
} else {
   $action = 'list';
}

pageHeader('AIRT export queue');
echo "<em>Note: this is experimental demo code only</em><p>\n";

switch ($action) {
   case 'add':
      // Sanitize incoming stuff.
      if (queueItemInsert($_REQUEST['task'],
                          '',
                          $_REQUEST['scheduled'],
                          $output)) {
         // Oops.
         echo('<p><strong>'.
              t('ERROR: %err',
                array('%err'=>$output)).
              '</strong></p>'.LF);
      }
   case 'list':
      printf(formatQueueList());
      printf(formatQueueItemInsert());
      break;
   default:
      // Could also be an error message, consider.
      printf(formatQueueList());
}

pageFooter();

?>
