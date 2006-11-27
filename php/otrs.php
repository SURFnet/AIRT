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
require_once LIBDIR.'/incident.plib';

$action = strtolower(fetchFrom('REQUEST','action'));
defaultTo($action,'list');

switch ($action) {

   //--------------------------------------------------------------------
   case 'search':
	   $ticketno = fetchFrom('REQUEST', 'tn');
      $ip = fetchFrom('REQUEST', 'ip');
		if (!empty($ticketno)) {
		   $_SESSION['otrs_tn'] = $ticketno;
		}
		if (!empty($ip)) {
		   $_SESSION['active_ip'] = $ip;
		}
		Header('Location: '.BASEURL.'/search.php?q='.urlencode($ip).'&action=Search&qtype=host');
      break;

   //--------------------------------------------------------------------
   case 'assign':
      $incidentid = fetchFrom('REQUEST', 'incidentnr');
      if (empty($incidentid)) {
         print _('Missing incidentnr');
         exit;
      }
      $tn = fetchFrom('REQUEST', 'tn');
      if (empty($tn)) {
         print _('Missing ticket number');
         exit;
      }
      if (in_array('_OTRS'.$tn, getExternalIncidentIDs($incidentid)) === false) {
         addExternalIncidentIDs($incidentid, '_OTRS'.$tn);
      }
      reload($_SERVER['HTTP_REFERER']);
      break;

   //--------------------------------------------------------------------
   case 'get':
      $ticketno = fetchFrom('REQUEST', 'tn');
      Header('Content-Type: text/xml');
      print '<?xml version="1.0" encoding="UTF-8" standalone="yes" ?>'.LF;
      print '<airt baseurl="'.BASEURL.'">'.LF;
      if ($ticketno != '') {
         $t = getIncidentIDsByExternalID('_OTRS'.$ticketno);
         if (is_array($t) && sizeof($t) > 0) {
            foreach ($t as $incidentid) {
               $incident = getIncident($incidentid);
               print '   <incident id="'.$incidentid.'" ';
               print 'label="'.normalize_incidentid($incidentid).'" ';
               print 'status="'.getIncidentStatusLabelByID($incident['status']).'"/>'.LF;
            }
         }
     }
     print '</airt>'.LF;

     break;

   //--------------------------------------------------------------------
	case 'new incident':
	   $ticketno = fetchFrom('REQUEST', 'tn');
		$ip = fetchFrom('REQUEST', 'ip');
		if (!empty($ticketno)) {
		   $_SESSION['otrs_tn'] = $ticketno;
		}
		if (!empty($ip)) {
		   $_SESSION['active_ip'] = $ip;
		}
		Header('Location: '.BASEURL.'/incident.php?action=new');
		break;

   //--------------------------------------------------------------------
   default:
      die(t(_('Unknown action (%action)'), array('%action'=>$action)));
}
?>
