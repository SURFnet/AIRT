<?php
/* vim:syntax=php shiftwidth=3 tabstop=3
 * AIRT: APPLICATION FOR INCIDENT RESPONSE TEAMS
 * Copyright (C) 2004,2005	Kees Leune <kees@uvt.nl>
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
 */

require_once 'config.plib';
require_once ETCDIR.'/exportqueue.cfg';
require_once LIBDIR.'/airt.plib';
require_once LIBDIR.'/database.plib';
require_once LIBDIR.'/error.plib';
require_once LIBDIR.'/exportqueue.plib';


// The default action of this script is to simply list the export queue.
// All explicit actions are silently completed and end with a default action
// reload.

switch (strip_tags(fetchFrom('REQUEST','action'))) {
   case 'add':
      // Add new item to the queue.
      if (queueItemInsert(fetchFrom('REQUEST','task', '%d'),
                          strip_tags(fetchFrom('REQUEST','params')),
                          strip_tags(fetchFrom('REQUEST','scheduled')),
                          $error)) {
         airt_error('DB_QUERY','exportqueue.php:'.__LINE__,$error);
      }
      header('Location: exportqueue.php');
      exit;
   case 'remove':
      if (queueItemRemove(fetchFrom('REQUEST','taskid', '%d'),
                          $error)) {
         airt_error('DB_QUERY','exportqueue.php:'.__LINE__,$error);
      }
      header('Location: exportqueue.php');
      exit;
   default:
      // List the queue.
      pageHeader(_('AIRT export queue'));
      printf(formatQueueList());
      printf(formatQueueItemInsert());
      pageFooter();
}
