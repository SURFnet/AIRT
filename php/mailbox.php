<?php
/* vim:syntax=php shiftwidth=3 tabstop=3
 *
 * AIRT: APPLICATION FOR INCIDENT RESPONSE TEAMS
 * Copyright (C) 2006   Tilburg University, The Netherlands

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
 * Incidents.php - incident management interface
 * $Id$
 */

require_once 'config.plib';
require_once LIBDIR.'/airt.plib';
require_once LIBDIR.'/database.plib';
require_once LIBDIR.'/history.plib';
require_once LIBDIR.'/mailbox.plib';

switch (strtolower(fetchFrom('REQUEST', 'action'))) {
    case 'list':
       listMailbox();
       break;
    case 'view':
       viewMessage();
       break;
    case 'processqueue':
	    processQueue();
	    break;
    case 'attachment';
       viewAttachment();
       break;
    case 'link':
       linkMessage();
       break;
    case 'unlink':
       unlinkMessage();
       break;
    case 'nav':
       navigate();
       break;
    default:
       if (listMailbox($error) == false) {
          print $error;
       }
}
?>
