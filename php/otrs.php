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
 * otrs.php -- frontend for otrs integration
 * $Id: incident.php 1016 2006-10-31 12:34:55Z kees $
 */

require_once 'config.plib';
require_once LIBDIR.'/airt.plib';
require_once LIBDIR.'/history.plib';


$action = fetchFrom('REQUEST','action');
defaultTo($action,'list');

switch ($action) {

  //--------------------------------------------------------------------
  case 'assign':
      $incidentid = fetchFrom('REQUEST', 'incidentnr');
		if (empty($incidentid)) {
		   print "Missing incidentnr";
			exit;
		}
		$TicketID = fetchFrom('REQUEST', 'TicketID');
		if (empty($TicketID)) {
		   print "Missing TicketID";
			exit;
		}
		print "incidentid: -$incidentid-<br/>";
		print "ticketid: -$TicketID-</br/>";
      break;

  //--------------------------------------------------------------------
  default:
      die(_('Unknown action'));
}
?>
