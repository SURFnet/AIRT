<?php
/* vim: syntax=php tabstop=3 shiftwidth=3
 * AIRT: APPLICATION FOR INCIDENT RESPONSE TEAMS
 * Copyright (C) 2004-2008   Tilburg University, The Netherlands

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
 */
require_once 'config.plib';
require_once LIBDIR.'/airt.plib';
require_once LIBDIR.'/search.plib';
require_once LIBDIR.'/incident.plib';
require_once LIBDIR.'/constituency.plib';
require_once LIBDIR.'/network.plib';
require_once LIBDIR.'/user.plib';

$action = fetchFrom('REQUEST', 'action');
defaultTo($action, 'none');

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
   print '<div class="search-searchbox">'.LF;
   print _('Search for:').'<p/>'.LF;
   print '<table>'.LF;
   print '<tr>'.LF;
   print '<td>'._('Hostname:').'</td>'.LF;
   print '<td>'.LF;
   print '<form method="GET">'.LF;
   print '<input type="text" name="q" size="60"/>'.LF;
   print '<input name="qtype" value="host" type="hidden">'.LF;
   print '<input type="submit" name="action" value="'._('Search').'"/>'.LF;
   print '</form>'.LF;
   print '</td>'.LF;
   print '</tr>'.LF;

   print '<tr>'.LF;
   print '<td>'._('Incident ID:').'</td>'.LF;
   print '<td>'.LF;
   print '<form method="GET">'.LF;
   print '<input type="text" name="q" size="60"/>'.LF;
   print '<input name="qtype" value="incident" type="hidden">'.LF;
   print '<input type="submit" name="action" value="'._('Search').'"/>'.LF;
   print '</form>'.LF;
   print '</td>'.LF;
   print '</tr>'.LF;

   print '<tr>'.LF;
   print '<td>'._('Network/Netmask:').'</td>'.LF;
   print '<td>'.LF;
   print '<form method="GET">'.LF;
   print '<input type="text" name="q" size="60"/>'.LF;
   print '<input name="qtype" value="zoom" type="hidden">'.LF;
   print '<input type="submit" name="action" value="'._('Search').'"/>'.LF;
   print '</form>'.LF;
   print '</td>'.LF;
   print '</tr>'.LF;

   print '<tr>'.LF;
   print '<td>'._('Email address:').'</td>'.LF;
   print '<td>'.LF;
   print '<form method="GET">'.LF;
   print '<input type="text" name="q" size="60"/>'.LF;
   print '<input name="qtype" value="email" type="hidden">'.LF;
   print '<input type="submit" name="action" value="'._('Search').'"/>'.LF;
   print '</form>'.LF;
   print '</td>'.LF;
   print '</tr>'.LF;

   print '</table>'.LF;
   print '</div>'.LF;
}


/** Find details about given hostname or IP address.
 * \param [in] $hostname  Hostname or IP address to search for.
 */
function search_host($hostname='') {
   global $qtype;

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

   $netmask = $networks[$networkid]["netmask"];
   $netname = $networks[$networkid]["label"];
   $consid  = $networks[$networkid]["constituency"];
   $conslabel = $constituencies[$consid]["label"];
   $consname  = $constituencies[$consid]["name"];

   // update active IP address
   $_SESSION["active_ip"] = $ip;
   $_SESSION["constituency_id"] = $consid;

   pageHeader(_('Detailed information for host ').$hostname);

   print '<div class="search-output-basic">'.LF;
   print _('Search results for the following host:');
   print '<table>';
   print '<tr><td>' . _('IP Address').':</td><td><kbd>'.$ip.'</kbd></td></tr>'.LF;
   print '<tr><td>' . _('Hostname').':</td><td><kbd>'.$hostname.'</kbd></td></tr>'.LF;
   print '<tr><td>' . _('Network').':</td><td>'.$netname.' (<a href="'.
      BASEURL.'/search.php?q='.$network.'/'.$netmask.'&action=Search&qtype=zoom">'.prettyNetwork($network,$netmask).'</a>)'.'</td></tr>'.LF;
   print '<tr><td>' . _('Constituency').':</td><td>'.$consname.'</td></tr>' . LF;
   print '</table>'.LF;
   print '</div>'.LF;

   print '<div class="search-constituency">'.LF;
   print '<H2>'._('Constituency Contacts').'</H2>'.LF;
   showConstituencyContacts($consid);
   print '</div>'.LF;

   // include previous incidents
   print '<div class="search-previous">'.LF;
   print '<h2>'._('Previous incidents').'</h2>'.LF;
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
      AND     a.ip = '".db_escape_string($ip)."'

      ORDER BY incidentid")
   or die(_('Unable to query.'));

   if (db_num_rows($res)) {
      print '<table class="horizontal">'.LF;
      print '<tr>'.LF;
      print '   <th>'._('Incident ID').'</th>'.LF;
      print '   <th>'._('Created').'</th>'.LF;
      print '   <th>'._('Type').'</th>'.LF;
      print '   <th>'._('State').'</th>'.LF;
      print '   <th>'._('Status').'</th>'.LF;
      print '</tr>'.LF;
      $count = 0;
      while ($row = db_fetch_next($res)) {
         printf("
<tr>
   <td><a href=\"incident.php?action=details&incidentid=%s\">%s</a></td>
   <td>%s</td>
   <td>%s</td>
   <td>%s</td>
   <td>%s</td>
</tr>",
               $row["incidentid"],
               normalize_incidentid($row["incidentid"]),
               Date("d M Y", $row["created"]),
               $row["type"],
               $row["state"],
               $row["status"]);
      }
      print '</table>'.LF;
   } else {
      echo "<I>"._('No previous incidents')."</I>";
   }
   print '</div>'.LF;


   // call user-defined search function. Must print in unformatted layout
   // additional info about hostname needed to make a decision.
   echo "<HR>";
   if (defined('CUSTOM_FUNCTIONS') && function_exists("search_info")) {
      search_info($ip, $networkid);
      echo "<HR>";
   }

   print '<form action="'.BASEURL.'/incident.php">'.LF;
   print t('<input type="hidden" name="ip" value="%ip">'.LF, array(
      '%ip'=>strip_tags($ip)));
} // search_host()



/** Search for incidents by id
 */
function search_incident($incidentid) {
   pageHeader(_('Search results'));
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
         $res = db_query($q);
         while ($row = db_fetch_next($res)) {
            $incident = getIncident($row['incidentid']);
            $hits[$incident['incidentid']] = $incident;
         }
      }
   }
   if (sizeof($hits) == 0) {
      print _('No results matched your query.');
      return;
   }
   $out = _('Research results for incident = ').$incidentid.':<p/>';
   $out .= '<table class="horizontal">';
   $out .= '<tr>'.LF;
   $out .= '<td>'._('Incidentid').'</td>'.LF;
   $out .= '<td>'._('Type').'</td>'.LF;
   $out .= '<td>'._('Status').'</td>'.LF;
   $out .= '<td>'._('State').'</td>'.LF;
   $out .= '<td>'._('IP Address').'</td>'.LF;
   $out .= '<td>'._('Hostname').'</td>'.LF;
   $out .= '<td>'._('Additional identifiers').'</td>'.LF;
   $out .= '</tr>'.LF;
   $count = 0;
   foreach ($hits as $h) {
      $ip = $h['ips'][0];
      $out .= t('<tr valign="top"><td><a href="%url?action=details&incidentid=%id">%incidentid</a></td><td>%type</td><td>%status</td>'.
              '<td>%state</td><td>%ip</td><td>%host</td><td>%extids</td></tr>', array(
         '%url'=>'incident.php',
         '%id'=>$h['incidentid'],
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


/** Returns an array containing:
 *
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


/** Find all incidents within an IP range IPV4/IPV6
 * \param [in] $mask: IP range to search within.
 */
function search_zoom($mask) {
   $mask = airt_searchhelper($mask);
   if($mask !== null) {

      pageHeader(_("Search results for $mask"));

      $where_clause = "a.ip::inet <<= inet '".db_escape_string($mask)."' \n";

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
         print '<table class="horizontal">'.LF;
         print '<tr>'.LF;
         print '<th>'._('Incident ID').'</th>'.LF;
         print '<th>'._('Created').'</th>'.LF;
         print '<th>'._('Type').'</th>'.LF;
         print '<th>'._('State').'</th>'.LF;
         print '<th>'._('Status').'</th>'.LF;
         print '</tr>'.LF;
         $count = 0;
         while ($row = db_fetch_next($res)) {
             $count++;
            printf("
<tr>
   <td><a href=\"incident.php?action=details&incidentid=%s\">%s</a></td>
   <td>%s</td>
   <td>%s</td>
   <td>%s</td>
   <td>%s</td>
</tr>",
                $row["incidentid"],
                normalize_incidentid($row["incidentid"]),
                Date("d M Y", $row["created"]),
                $row["type"],
                $row["state"],
                $row["status"]);
         }
         print '</table>'.LF;
         print '<p>'.t(_('%n incident(s) found.'), array('%n'=>$count)).'</p>'.LF;
      } else {
         echo "<I>"._('No incidents within this range')."</I>";
      }
    } else {
      echo "<I>$mask "._('is not a correct netmask, 123.45.67.89/22 for instance is')."</I>";
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
   $userids = array();
   if (empty($email)) {
      return 1;
   }
   /* find all matching user ids */
   $res = db_query(q('select id from users where email like \'%%email%\'',
      array('%email'=>db_escape_string($email))));
   if (!$res) {
      return 1;
   }
   if (db_num_rows($res) == 0) {
      return 0;
   }
   while ($row = db_fetch_next($res)) {
      $userids[] = $row['id'];
   }
   db_free_result($res);

   // stop processing of no users were found
   if (sizeof($userids) == 0) {
      return 0;
   }
   // fetch corresponding incidentids
   $res = db_query(q('select incidentid from incident_users where userid in (%userids) order by incidentid', array('%userids'=>implode($userids, ','))));
   if ($res === false) {
      return 1;
   }
   while ($row = db_fetch_next($res)) {
      $results[] = $row['incidentid'];
   }
   return 0;
} // search_email


/** Show search results for email search.
 * \param [in] $incidentids Array containing incident IDs
 * \return 0 on success, 1 on failure
 */
function show_search_email($incidentids) {
   if (!is_array($incidentids)) {
      return 1;
   }
   pageHeader("Search output");
   $out = '<table class="horizontal">';
   $out .= '<tr>';
   $out .= '  <th>'._('Incident ID').'</th>';
   $out .= '  <th>'._('Hostname').'</th>';
   $out .= '  <th>'._('Constituency').'</th>';
   $out .= '  <th>'._('Type').'</th>';
   $out .= '  <th>'._('Status').'</th>';
   $out .= '  <th>'._('State').'</th>';
   $out .= '  <th>'._('User').'</th>';
   $out .= '</tr>';

   $constituencies = getConstituencies();
   $count=0;
   foreach ($incidentids as $incidentid) {
      $out .= '<tr>'.LF;
      $out .= t('   <td><a href="incident.php?action=details&incidentid=%id">%iid</a></td>', array('%id'=>$incidentid, '%iid'=>normalize_incidentid($incidentid)));
      $incident = getIncident($incidentid);
      foreach ($incident['ips'] as $node) {
         $out .= '   <td>'.$node['hostname'].'</td>';
         $out .= '   <td>'.$constituencies[$node['constituency']]['label'].
                     '</td>';
      }
      $out .= '   <td>'.getIncidentTypeLabelByID($incident['type']).'</td>';
      $out .= '   <td>'.getIncidentStatusLabelByID($incident['status']).'</td>';
      $out .= '   <td>'.getIncidentStateLabelByID($incident['state']).'</td>';
      $out .= '   <td>';
      foreach ($incident['users'] as $u) {
         $u = getUserByUserID($u);
         $out .= $u['email']."<br/>";
      }
      $out .= '   </td>';
      $out .= '</tr>';
   }
   $out .= '</table>';
   print $out;
} // show_search_email


/***********************************************************************/
switch ($action) {
   case "none":
      pageHeader(_('Search'));
      showSearch();
      pageFooter();
      break;
   // ------------------------------------------------------------------
   case 'query':
      $query = trim(fetchFrom('REQUEST', 'query'));
      if (empty($query)) {
          reload();
          exit;
      }

      if (strstr($query, '@') !== false) {
          reload(t(BASEURL.'/search.php?q=%q&qtype=email&action=Search', array(
             '%q'=>urlencode($query))));
          exit;
      }
      if(strstr($query,':') !== false || strstr($query,'::') !== false ) {
        reload(t(BASEURL.'/search.php?q=%q&qtype=host&action=Search', array(
           '%q'=>urlencode($query))));
        exit;
      }
      if (preg_match('@^([0-9]+\.[0-9]+\.[0-9]+\.[0-9]+/[0-9.]+)$@', $query, $match) > 0) {
          reload(t(BASEURL.'/search.php?q=%q&qtype=zoom&action=Search', array(
             '%q'=>urlencode($query))));
          exit;
      }

      if (preg_match('/^('.INCIDENTID_PREFIX.')?([0-9]+)$/', $query, $match) > 0) {
          if (getIncident($match[2]) !== false) {
              reload(BASEURL.'/incident.php?action=details&incidentid='.
                 urlencode($match[2]));
              exit;
          }
      }
      if ((preg_match('/^([0-9]+\.[0-9]+\.[0-9]+\.[0-9]+)$/', $query, $match) > 0) ||
          (preg_match('/.+\.[^.]+\.[^.]+.+/', $query, $match) > 0)) {
          reload(t(BASEURL.'/search.php?q=%q&qtype=host&action=Search', array(
             '%q'=>urlencode($query))));
          exit;
      }

      reload(t(BASEURL.'/search.php?q=%q&qtype=incident&action=Search', array(
         '%q'=>$query)));
      print "No match: $query";
      break;

   // ------------------------------------------------------------------
   case _("Search"):
   case "search":
      unset($_SESSION["current_name"]);
      unset($_SESSION["current_email"]);
      if (array_key_exists('qtype', $_REQUEST)) {
         $qtype = $_REQUEST['qtype'];
      } else {
         $qtype = 'host';
      }
      if (array_key_exists('q', $_REQUEST)) {
         $q = strip_tags(trim($_REQUEST['q']));
      } else {
         airt_error('PARAM_MISSING', 'search.php:'.__line__);
         reload();
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
         case 'email':
            do_search_email($q, $res);
            show_search_email($res);
            break;

         default:
            echo _('Unknown query type');
      }

      print '<input type="submit" name="action" value="'._('New incident').'">'.LF;
      print '</form>'.LF;
      print '<P>'.LF;
      print '<H2>'._('New Search').'</H2>'.LF;
      showSearch($qtype);
      generateevent('searchoutput', array('q'=>$q, 'qtype'=>$qtype));
      pageFooter();

      break;
   default:
      die(_('Unknown action.'));
} // switch
