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
 * $Id$
 */
require_once 'config.plib';
require_once LIBDIR.'/airt.plib';
require_once LIBDIR.'/incident.plib';
require_once ETCDIR.'/otrs.cfg';

$action = strip_tags(strtolower(fetchFrom('REQUEST','action')));
defaultTo($action,'list');

switch ($action) {

   //--------------------------------------------------------------------
   case 'search':
	   $ticketno = strip_tags(fetchFrom('REQUEST', 'tn'));
      $ip = strip_tags(fetchFrom('REQUEST', 'ip'));
		if (!empty($ticketno)) {
		   $_SESSION['otrs_tn'] = $ticketno;
		}
		if (!empty($ip)) {
		   $_SESSION['active_ip'] = $ip;
		}
		Header('Location: '.BASEURL.'/search.php?q='.urlencode($ip).'&action=Search&qtype=host');
      break;

   //--------------------------------------------------------------------
   case _('link'):
      $incidentid_s = fetchFrom('REQUEST', 'incidentno_s');
      $incidentid_t = fetchFrom('REQUEST', 'incidentno_t');
		
      if (empty($incidentid_s) && empty($incidentid_t)) {
         print _('Missing incidentnr');
         exit;
      } else {
		   // incidentid_t take precendence
		   if (empty($incidentid_t)) {
			   $incidentid = $incidentid_s;
			} else {
			   $incidentid = $incidentid_t;
			}
			if ($incidentid != -1) {
				$incidentid=decode_incidentid($incidentid);
			} 
		}
      $tn = fetchFrom('REQUEST', 'tn');
      if (empty($tn)) {
         print _('Missing ticket number');
         exit;
      }
      if (in_array('_OTRS'.$tn, getExternalIncidentIDs($incidentid)) === false) {
         addExternalIncidentIDs($incidentid, '_OTRS'.$tn);
      }
      $cmd=sprintf('%s %s %s', LIBDIR.'/otrs/airt_otrs_moveto.pl',
         escapeshellcmd($tn),
         escapeshellcmd(OTRS_INCIDENT_QUEUE));
      exec($cmd);

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
					print 'ticketno="'.$ticketno.'" ';
               print 'status="'.getIncidentStatusLabelByID($incident['status']).'"/>'.LF;
            }
         }
			foreach (getOpenIncidents() as $o) {
            print '   <incident id="'.$o['incidentid'].'" ';
				print 'label="'.normalize_incidentid($o['incidentid']).'" ';
            print 'status="'.$o['status'].'"/>'.LF;
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
   case 'close':
      $ticketno = fetchFrom('REQUEST', 'tn');
		if (empty($ticketno)) {
		   die(_('Missing ticket number'));
		}

      $cmd=LIBDIR.'/otrs/airt_otrs_ticketclose.pl '.$ticketno;
      exec($cmd);

      Header('Location: '.$_SERVER['HTTP_REFERER']);
      break;
   //--------------------------------------------------------------------
   default:
      die(t(_('Unknown action (%action)'), array('%action'=>
         strip_tags($action))));
}
?>
