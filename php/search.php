<?php
/* vim: syntax=php tabstop=3 shiftwidth=3
 * AIRT: APPLICATION FOR INCIDENT RESPONSE TEAMS
 * Copyright (C) 2004,2005	Tilburg University, The Netherlands

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
 * search.php - Search for additional information on host
 * 
 * $Id$
 */

require_once 'config.plib';
require_once LIBDIR.'/airt.plib';
require_once LIBDIR.'/search.plib';
require_once LIBDIR.'/incident.plib';
require_once LIBDIR.'/constituency.plib';
require_once LIBDIR.'/network.plib';

if (array_key_exists("action", $_REQUEST)) {
   $action=$_REQUEST["action"];
} else {
   $action = "none";
}

function showSearch($qtype='') {
   $hostchecked=$incidentchecked=$zoomchecked='';
   switch ($qtype) {
      case 'host':
         $hostchecked='CHECKED';
         break;
      case 'incident':
         $incidentchecked='CHECKED';
         break;
      case 'zoom':
         $zoomchecked='CHECKED';
         break;
      case 'email':
         $email='CHECKED';
         break;
      default:
         $hostchecked='CHECKED';
   }
   echo <<<EOF
<p><form>
Search for:<p/>
<input type="text" name="q" size="60"/>
<input type="submit" name="action" value="Search"/>
<br/>
<input type="radio" name="qtype" value="host" $hostchecked/>Hostname
<input type="radio" name="qtype" value="incident" $incidentchecked/>Incident
<input type="radio" name="qtype" value="zoom" $zoomchecked/>Mask
<input type="radio" name="qtype" value="email" $zoomchecked/>Mask
<p/>
</form>
EOF;
}


/** Find details about given hostname or IP address.
 * \param [in] $hostname  Hostname or IP address to search for.
 */
function search_host($hostname='') {
   // normalize to IP address
   $ip = @gethostbyname(trim($hostname));

   // get FQDN
   $hostname = @gethostbyaddr($ip);

   // call user-supplied categorization routine. Returns the id of the
   // constituency
   $networkid = categorize($ip);
   if (defined('CUSTOM_FUNCTIONS') && function_exists("custom_categorize")) {
      $networkid = custom_categorize($ip, $networkid);
   }

   // get addl info
   $networks = getNetworks();
   $constituencies = getConstituencies();

   $network = $networks[$networkid]["network"];
   $netmask = netmask2cidr($networks[$networkid]["netmask"]);
   $netname = $networks[$networkid]["label"];
   $consid  = $networks[$networkid]["constituency"];
   $conslabel = $constituencies[$consid]["label"];
   $consname  = $constituencies[$consid]["name"];

   // update active IP address
   $_SESSION["active_ip"] = $ip;
   $_SESSION["constituency_id"] = $consid;

   pageHeader("Detailed information for host $hostname", "search-info");

   echo <<<EOF
Search results for the following host:
<PRE>
 IP Address          : $ip
 Hostname            : $hostname
 Network             : $netname (<a href="$_SERVER[PHP_SELF]?q=$network/$netmask&action=Search&qtype=zoom">$network/$netmask</a>)
 Constituency        : $consname
</PRE>

<H2>Constituency Contacts</H2>
EOF;
   showConstituencyContacts($consid);

   // call user-defined search function. Must print in unformatted layout
   // additional info about hostname needed to make a decision.
   echo "<HR>";
   if (defined('CUSTOM_FUNCTIONS') && function_exists("search_info")) {
      search_info($ip, $networkid);
      echo "<HR>";
   }

   // include previous incidents
   echo <<<EOF
<h2>Previous incidents</h2>
EOF;
   $res = db_query("
      SELECT  i.id as incidentid,
            extract (epoch from a.added) as created,
            t.label as type,
            s.label as state,
            s2.label as status
      FROM  incidents i, 
            incident_addresses a,
            incident_types t,
            incident_status s2,
            incident_states s
      WHERE   i.id = a.incident
      AND     i.status = s2.id
      AND     i.state = s.id
      AND     i.type = t.id
      AND     a.ip = '$ip'

      ORDER BY incidentid")
   or die("Unable to query.");

   if (db_num_rows($res)) {
      echo <<<EOF
<table cellpadding="3">
<tr>
<th>Incident ID</th>
<th>Created</th>
<th>Type</th>
<th>State</th>
<th>Status</th>
</tr>
EOF;
      $count = 0;
      while ($row = db_fetch_next($res)) {
         printf("
<tr bgcolor=\"%s\">
   <td><a href=\"incident.php?action=details&incidentid=%s\">%s</a></td>
   <td>%s</td>
   <td>%s</td>
   <td>%s</td>
   <td>%s</td>
</tr>",
               ($count++ % 2 == 0 ? "#DDDDDD" : "#FFFFFF"),
               $row["incidentid"],
               normalize_incidentid($row["incidentid"]),
               Date("d M Y", $row["created"]),
               $row["type"],
               $row["state"],
               $row["status"]);
      }
      echo <<<EOF
</table>
EOF;
   } else {
      echo "<I>No previous incidents</I>";
   }
/*		
   echo <<<EOF
<h2>Link address to incident</h2>
EOF;
   // create new incident
   $count = showOpenIncidentSelection("incidentid");
   if ($count == 0) echo "<I>No previous incidents</I><P>";
*/		

   echo <<<EOF
<form action="incident.php" method="POST">
<input type="hidden" name="ip" value="ip">
EOF;
/*
   if ($count>0) {
      echo <<<EOF
<input type="submit" name="action" value="Link to incident">
EOF;
   }
*/
} // search_host()



/** Search for incidents by id
 */
function search_incident($incidentid) {
   pageHeader("Search results");
   $hits=array();
   if ((int)$incidentid > 0) {
      $incident = getIncident($incidentid);
      if ($incident) {
         $hits[] = $incident;
      }
   }
   if (sizeof($hits) == 0) {
      // check if we are looking for an internal one
      if (preg_match('/^'.INCIDENTID_PREFIX.'[0-9]+/', $incidentid) > 0) {
         $hits[] = getIncident(decode_incidentid($incidentid));
      } else {
         $q = q("SELECT incidentid from external_incidentids where externalid like '%%query%'", array('%query'=>db_escape_string($incidentid)));
         print ($q);
         $res = db_query($q);
         while ($row = db_fetch_next($res)) {
            $incident = getIncident($row['incidentid']);
            $hits[$incident['incidentid']] = $incident;
         }
      }
   }
   if (sizeof($hits) == 0) {
      print "No results matched your query.";
      return;
   }
   $out = "Research results for incident = '$incidentid':<p/>";
   $out .= '<table width="100%" cellpadding="2" border="0">';
   $out .= '<tr><td>Incidentid</td><td>Type</td><td>Status</td><td>State</td>'.
           '<td>IP Address</td><td>Hostname</td><td>Additional identifiers</td></tr>';
   $count = 0;
   foreach ($hits as $h) {
      $ip = $h['ips'][0];
      $out .= t('<tr valign="top" bgcolor=%c><td><a href="%url?action=details&incidentid=%id">%incidentid</a></td><td>%type</td><td>%status</td>'.
              '<td>%state</td><td>%ip</td><td>%host</td><td>%extids</td></tr>', array(
         '%url'=>'incident.php',
         '%id'=>$h['incidentid'],
         '%c'=>($c++ % 2 == 0) ? '#FFFFFF' : '#DDDDDD',
         '%incidentid'=>normalize_incidentid($h['incidentid']),
         '%type'=>getIncidentTypeDescr($h['type']),
         '%status'=>getIncidentStatusDescr($h['status']),
         '%state'=>getIncidentStateDescr($h['state']),
         '%ip'=> $ip['ip'],
         '%host'=>$ip['hostname'],
         '%extids'=>implode(',<br/>',getExternalIncidentIDs($h['incidentid']))));
   }
   $out .= '</table>';

   print $out;
} // search_incident


/** Returns TRUE or FALSE depending on whether the
 * IP range has been formulated correct or not
 * \param [in] $matches: Array containing the entire string, the four IP integers, and the mask.
 */ 

function mask_ok ($matches) {

   if (count($matches) == 6 and 
       0 <= $matches[1] and $matches[1] < 256 and
       0 <= $matches[2] and $matches[2] < 256 and
       0 <= $matches[3] and $matches[3] < 256 and
       0 <= $matches[4] and $matches[4] < 256 and
       0 <= $matches[5] and $matches[5] < 32) {
      return(TRUE);   
   } else {
     return(FALSE); 
   }
} //mask OK



/** Returns an array containing: 
 * at entry 0 the minimum value of the first affected byte 
 * at entry 1 the maximum value of the first affected byte
 * at entry 2 the invariant part of the ip range
 * at entry 3 '' or '.0' or '.0.0' or '.0.0.0' 
 * at entry 4 '' or '.255' or '.255.255' or '.255.255.255'
 * at entry 5 '' or '.%' or '.%.%' or '.%.%.%'
 *
 * The minimum IP value is constructed by concatenating the invariant part, the minimumvalue, and either '' or '.0' or '.0.0' or '.0.0.0'
 * The maximum IP value is constructed by concatenating the invariant part, the maximumvalue, and either '' or '.255' or '.255.255' or '.255.255.255'
 * The formatconstraint of the selected IP values is constructed by concatenating the invariant part, either '_' or '__' or '___', and either '' or '.%' or '.%.%' or '.%.%.%'
 * 
 * \param [in] $matches: Array containing the entire string, the four IP integers, and the mask.
 */

function mask_limits($matches) {

   if ($matches[5] < 9) { 

      $width      = 8-$matches[5];     
      $matches[1] = $matches[1] - ($matches[1] % pow(2,$width));

      $span       = pow(2,$width) - 1; 

      $min  = $matches[1];
      $max  = $min + $span;
      $pre  = "";
      $postmin = ".0.0.0";
      $postmax = ".255.255.255";
      $postlike = ".%.%.%";

   } else if ($matches[5] < 17) {

      $width      = 16-$matches[5];      
      $matches[2] = $matches[2] - ($matches[2] % pow(2,$width));

      $span       = pow(2,$width) - 1; 

      $min = $matches[2];
      $max = $min + $span;
      $pre = $matches[1] . ".";
      $postmin = ".0.0";
      $postmax = ".255.255";
      $postlike = ".%.%";
      
   } else if ($matches[5] < 25) {

      $width      = 24-$matches[5];
      $matches[3] = $matches[3] - ($matches[3] % pow(2,$width));

      $span       = pow(2,$width) - 1; 

      $min = $matches[3];
      $max = $min + $span;
      $pre = $matches[1] . "." . $matches[2] . ".";
      $postmin = ".0";
      $postmax = ".255";
      $postlike = ".%";

   } else {

      $width      = 32-$matches[5];      
      $matches[4] = $matches[4] - ($matches[4] % pow(2,$width));

      $span       = pow(2,$width) - 1; 

      $min = $matches[4];
      $max = $min + $span;
      $pre = $matches[1] . "." . $matches[2] . "." . $matches[3] . ".";
      $postmin = "";
      $postmax = "";
      $postlike = "";

   }
  
   return(array($min,$max,$pre,$postmin,$postmax,$postlike));

} //mask_limits


/** Find all incidents within an IP range
 * \param [in] $mask: IP range to search within.
 */
function search_zoom($mask) {
  
   preg_match("/(\d+)\.(\d+)\.(\d+)\.(\d+)\/(\d+)/",$mask,$matches);

   if (mask_ok($matches)) {

      $limits = mask_limits($matches);

      $min      = $limits[0];
      $max      = $limits[1];
      $pre      = $limits[2];
      $postmin  = $limits[3];
      $postmax  = $limits[4];
      $postlike = $limits[5];

      $where_clause = "";

      pageHeader("Search results from $pre$min$postmin to $pre$max$postmax");   

      if ($min < 10)
      {
         if ($max < 10)
         {
	    $where_clause  = "a.ip between '$pre$min$postmin' and '$pre$max$postmax' AND a.ip like '$pre"."_$postlike'\n";
	 }
         else if (9 < $max && $max < 100)
	 {
            $where_clause  = "((a.ip between '$pre$min$postmin' and '$pre"."9$postmax' AND a.ip like '$pre"."_$postlike')\n";
            $where_clause .= "OR (a.ip between '$pre"."10$postmin' and '$pre$max$postmax' AND a.ip like '$pre"."__$postlike'))\n";
	 }
         else if (99 < $max)
	 {
	    $where_clause  = "((a.ip between '$pre$min$postmin' and '$pre"."9$postmax' AND a.ip like '$pre"."_$postlike')\n";
            $where_clause .= "OR (a.ip between '$pre"."10$postmin' and '$pre"."99$postmax' AND a.ip like '$pre"."__$postlike')\n";
            $where_clause .= "OR (a.ip between '$pre"."100$postmin' and '$pre$max$postmax' AND a.ip like '$pre"."___$postlike'))\n";
	 }
      }
      else if (9 < $min && $min < 100)
      {
         if ($max < 100)
         {
            $where_clause  = "a.ip between '$pre$min$postmin' and '$pre$max$postmax' AND a.ip like '$pre"."__$postlike'\n";
         }
         else
	 {
            $where_clause  = "((a.ip between '$pre$min$postmin' and '$pre"."99$postmax' AND a.ip like '$pre"."__$postlike')\n";
            $where_clause .= "OR (a.ip between '$pre"."100$postmin' and '$pre$max$postmax' AND a.ip like '$pre"."___$postlike'))\n";
         } 
      }
      else
      {
	 $where_clause = "a.ip between '$pre$min$postmin' and '$pre$max$postmax' AND a.ip like '$pre"."___$postlike'\n";
      }

      $res = db_query("
         SELECT  i.id as incidentid,
                 extract (epoch from a.added) as created,
                 t.label as type,
                 s.label as state,
                 s2.label as status
         FROM  incidents i, 
               incident_addresses a,
               incident_types t,
               incident_status s2,
               incident_states s
         WHERE $where_clause
         AND     i.id = a.incident
         AND     i.status = s2.id
         AND     i.state = s.id
         AND     i.type = t.id
         ORDER BY incidentid")
         or die("Unable to query.");
    
      if (db_num_rows($res)) {
         echo <<<EOF
<table cellpadding="3">
<tr>
<th>Incident ID</th>
<th>Created</th>
<th>Type</th>
<th>State</th>
<th>Status</th>
</tr>
EOF;
         $count = 0;
         while ($row = db_fetch_next($res)) {
         printf("
<tr bgcolor=\"%s\">
   <td><a href=\"incident.php?action=details&incidentid=%s\">%s</a></td>
   <td>%s</td>
   <td>%s</td>
   <td>%s</td>
   <td>%s</td>
</tr>",
                ($count++ % 2 == 0 ? "#DDDDDD" : "#FFFFFF"),
                $row["incidentid"],
                normalize_incidentid($row["incidentid"]),
                Date("d M Y", $row["created"]),
                $row["type"],
                $row["state"],
                $row["status"]);
      }
      echo <<<EOF
</table>
EOF;
      } else {
      echo "<I>No incidents within this range</I>";
      }

   } else {
   echo "<I>$mask is not a correct netmask, 123.45.67.89/22 for instance is</I>";
   }
} //search_zoom


/** Search for an email address associated with an incident.
 * Only email addresses of users associated with an incident are searched.
 * Incident histories are left untouched.
 *
 * \param [in] $email Email address to search for
 * \param [out] $results An array containing incident IDs that match
 * \return 0 on success, 1 on failure
 */
function do_search_email($email='', &$results) {
   $results = array();
   if ($email == '') {
      return 1;
   }
   $userid = getUserByEmail(strtolower($email));
   $res = db_query(q('select incidentid from incident_users where userid=%userid', array('%userid'=>$userid)));
   if (!$res) {
      return 1;
   }
   foreach ($row as db_fetch_next($res)) {
      $results[] = $row['incidentid'];
   }
   return 0;
} // search_email


/** Show search results for email search.
 * \param [in] Array containing incident IDs
 * \return 0 on success, 1 on failure
 */
function show_search_email($incidentids) {
   if (!is_array($incidentids)) {
      return 1;
   }
   $out = '<table>';
   $out .= '<tr>';
   $out .= '  <th>Incident ID</th>';
   $out .= '  <th>Hostname</th>';
   $out .= '  <th>Type</th>';
   $out .= '  <th>Status</th>';
   $out .= '  <th>State</th>';
   $out .= '  <th>User</th>';
   $out .= '</tr>';

   foreach ($incidentids as $incidentid) {
      $out .= '<tr>';
      $out .= '   <td>'.normalize_incidentid($incidentid).'</td>';
      $incident = getIncident($incidentid);
      $out .= '   <td>'. implode($incident['ips'], '<br/>').'</td>';
      $out .= '   <td>'.getIncidentTypeLabel($incident['type']).'</td>';
      $out .= '   <td>'.getIncidentStatusLabel($incident['status']).'</td>';
      $out .= '   <td>'.getIncidentStateLabel($incident['state']).'</td>';
      $out .= '   <td>'. implode($incident['users'], '<br/>'.'</td>';
      $out .= '</tr>';
   }
   $out .= '</table>';
   printf($out);
} // show_search_email

















/***********************************************************************/
switch ($action) {
   case "none":
      pageHeader("Search", "search-search");
      showSearch();
      pageFooter();
      break;
   // ------------------------------------------------------------------
   case "Search":
   case "search":
		unset($_SESSION["current_name"]);
		unset($_SESSION["current_email"]);
      if (array_key_exists('qtype', $_REQUEST)) {
         $qtype = $_REQUEST['qtype'];
      } else {
         $qtype = 'host';
      }
      if (array_key_exists('q', $_REQUEST)) {
         $q = trim($_REQUEST['q']);
      } else {
         airt_error('PARAM_MISSING', 'search.php:'.__line__);
         Header($_SERVER['PHP_SELF']);
         exit;
      }
      switch ($qtype) {
         case 'host':
            search_host($q);
            break;
         case 'incident':
            search_incident($q);
            break;
         case 'zoom':
            search_zoom($q);
            break;

         default:
            echo 'Unknown query type';
      }

      echo <<<EOF
<input type="submit" name="action" value="New incident">
</form>
<P>
EOF;
      generateevent('searchoutput', array('q'=>$q));
      echo <<<EOF
<HR>
<H2>New Search</H2>
EOF;
      showSearch($qtype);
      pageFooter();

      break;
   default:
      die("Unknown action.");
} // switch
?>

