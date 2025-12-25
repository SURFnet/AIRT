<?php
/* vim: syntax=php tabstop=3 shiftwidth=3

 * AIRT: APPLICATION FOR INCIDENT RESPONSE TEAMS
 * Copyright (C) 2004,2005,2006      Tilburg University, The Netherlands

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
require_once LIBDIR.'/database.plib';
require_once LIBDIR.'/airt.plib';
require_once LIBDIR.'/constituency.plib';
require_once LIBDIR.'/incident.plib';
require_once LIBDIR.'/profiler.plib';

if (array_key_exists('action', $_REQUEST)) $action=$_REQUEST['action'];
else $action='none';


/** Extract the start date from the input form.
 * @return A Unix timestamp containing the start date, or -1 in case of failure
 */
function getStartDate() {
   $startdate = fetchFrom('REQUEST', 'startdate', '%s');
   $m = preg_match('/^\d{4}-\d{2}-\d{2}$/', $startdate);
   if (empty($startdate) || $m == 0) {
       $year = fetchFrom('REQUEST', 'start_year', '%d');
       $month = fetchFrom('REQUEST', 'start_month', '%d');
       $day = fetchFrom('REQUEST', 'start_day', '%d');

       $start = strtotime(sprintf('%02d/%02d/%04d', $month, $day, $year));
   } else {
       $start = strtotime($startdate);
   }
   if ($start == FALSE) {
      return -1;
   } else {
      return $start;
   }
}

/** Extract the end date from the input form.
 * @return A Unix timestamp containing the end date, or -1 in case of failure
 */
function getStopDate() {
   $stopdate = fetchFrom('REQUEST', 'stopdate', '%s');
   $m = preg_match('/^\d{4}-\d{2}-\d{2}$/', $stopdate);
   if (empty($stopdate) || $m == 0) {
       $year = fetchFrom('REQUEST', 'stop_year', '%d');
       $month = fetchFrom('REQUEST', 'stop_month', '%d');
       $day = fetchFrom('REQUEST', 'stop_day', '%d');

       $end = strtotime(sprintf('%02d/%02d/%04d', $month, $day, $year));
   } else {
       $end = strtotime($stopdate);
   }
   if ($end == FALSE) {
      return -1;
   } else {
      return $end;
   }
}


/** Show a matrix containing constituencies on the vertical axis and
 * incident types horizontally. The values of the cells contain the number
 * of incidents in the reporting period.
 */
function showMatrix($start, $stop, $showempty) {
   global $db;

   $constituencies = getConstituencies();
   $types = getIncidentTypes();
   $startdate = Date('Y-m-d', $start);
   $stopdate = Date('Y-m-d', $stop);
   $data = array();

   /* Initialize array with 0 values if the user wants to see 
    * them. 
    */
   if ($showempty == 'on') {
       foreach ($constituencies as $c) {
           foreach ($types as $t) {
               $data[$c['label']][$t] = 0;
           }
       }
   }

   $out = '<form method="post">'.LF;
   $out .= t('<input type="checkbox" name="showempty" %s/>'.LF, array(
      '%s' => ($showempty == 'on' ? 'CHECKED' : '')
   ));
   $out .= _('Show empty lines');
   $out .= t('<input type="hidden" name="startdate" value="%s"/>'.LF, array(
      '%s' => urlencode($startdate)
   ));
   $out .= t('<input type="hidden" name="stopdate" value="%s"/>'.LF, array(
      '%s' => urlencode($stopdate)
   ));
   $out .= '<input type="hidden" name="report" value="1"/>'.LF;
   $out .= '<input type="hidden" name="action" value="query"/>'.LF;
   $out .= '<input type="submit" value="Go"/>'.LF;
   $out .= '</form>'.LF;

   $out .= '<H2>'._('Amount of incidents per incident type per Constituency matrix').'</H2>';

   $out .= '<p>';
   $out .= _('Report generated on ').date('r').'.<br/>'.LF;
   $out .= t(_('Printing incidents from %startdate until %stopdate.<br/>'), array(
      '%startdate'=>htmlentities($startdate),
      '%stopdate'=>htmlentities($stopdate)));
   $out .= '</p>'.LF;
   $out .= '<table class="horizontal">'.LF;
   $out .= '<tr>'.LF;
   $out .= '<td>&nbsp;</td>'.LF;
   $type_sums = array();
   foreach ($types as $t_id=>$t_label) {
       $out .= t('<td>%l</td>'.LF, array('%l'=>htmlentities($t_label)));
       $type_sums[$t_label] = 0;
   }
   $out .= '<td>'._('Sum').'</td>'.LF;
   $out .= '</tr>'.LF;

   $res = pg_query_params($db, "
       SELECT c.label AS cons_l, t.label AS type_l, COUNT(DISTINCT i.id) AS c
           FROM constituencies c, incident_types t, incidents i,
                incident_addresses a
          WHERE i.id = a.incident
            AND a.constituency = c.id
            AND i.type = t.id
            AND date_trunc('day', i.created) >= to_timestamp($1, 'YYYY-MM-DD') 
            AND date_trunc('day', i.created) <= to_timestamp($2, 'YYYY-MM-DD')
       GROUP BY c.label, t.label
       ORDER BY c.label, t.label", array(
           $startdate,
           $stopdate)
   );
   if ($res === FALSE) {
       airt_msg(_('Error in').' stats.php:'.__LINE__);
       airt_msg(pg_last_error());
       exit(reload());
   }
   while (($row = db_fetch_next($res)) !== FALSE) {
       $cons = $row['cons_l'];
       $type = $row['type_l'];
       $data[$cons][$type] = $row['c'];
   }
   foreach ($data as $cons=>$counts) {
       $rowsum = 0;
       $out .= '<tr>'.LF;
       $out .= t('<td>%c</td>'.LF, array('%c'=>htmlentities($cons)));
       foreach ($types as $t_id => $t_label) {
           if ( array_key_exists($t_label, $counts) ) {
               $n = $counts[$t_label];
               $type_sums[$t_label] += $n;
           } else {
              $n = 0;
           }
           $out .= t('<td>%n</td>'.LF, array('%n'=>htmlentities($n)));
           $rowsum += $n;
       }
       $out .= t('<td>%s</td>'.LF, array('%s'=>$rowsum));
       $out .= '</tr>'.LF;
   }
   $out .= '<tr style="background-color:lightgray">'.LF;
   $out .= '<td>'._('Sum').'</td>'.LF;
   $sum = 0;
   foreach ($types as $t_id => $t_label) {
      $sum += $type_sums[$t_label];
      $out .= t('<td>%s</td>'.LF, array('%s'=>htmlentities($type_sums[$t_label])));
   }
   $out .= t('<td>%s</td>'.LF, array('%s'=>htmlentities($sum)));
   $out .= '</tr>'.LF;

   $out .= '</table>'.LF;

   $out .= '<H2>'._('Incidents without IP addresses').'</h2>';
   $res = pg_query_params("SELECT i.id
      FROM incidents i
      LEFT JOIN incident_addresses a ON i.id = a.incident
      WHERE date_trunc('day', i.created) >= to_timestamp($1, 'YYYY-MM-DD') 
        AND date_trunc('day', i.created) <= to_timestamp($2, 'YYYY-MM-DD')
      GROUP BY i.id
      HAVING COUNT (ip) = 0", array(
         $startdate,
         $stopdate)
   );
   if ($res === FALSE) {
       airt_msg(t_('Error in').' stats.php:'.__LINE__);
       exit(reload());
   }

   $out .= '<table class="horizontal">'.LF;
   $out .= '<tr>'.LF;
   $out .= '   <th>'._('Incident ID').'</th>'.LF;
   $out .= '   <th>'._('Type').'</th>'.LF;
   $out .= '   <th>'._('Status').'</th>'.LF;
   $out .= '   <th>'._('State').'</th>'.LF;
   $out .= '   <th>'._('Date').'</th>'.LF;
   $out .= '</tr>'.LF;
   while ($row = db_fetch_next($res)) {
      $i = getIncident($row['id']);
      $out .= '<tr>'.LF;
      $out .= '<td>'.htmlentities(normalize_incidentid($row['id'])).'</td>'.LF;
      $out .= '<td>'.htmlentities(getIncidentTypeLabelByID($i['type'])).'</td>'.LF;
      $out .= '<td>'.htmlentities(getIncidentStatusLabelByID($i['status'])).'</td>'.LF;
      $out .= '<td>'.htmlentities(getIncidentStateLabelByID($i['status'])).'</td>'.LF;
      $out .= '<td>'.($i['incidentdate'] == 0 ? _('Unknown') : date('d-M-Y H:i:s', $i['incidentdate'])).'</td>'.LF;
      $out .= '</tr>'.LF;
   }
   $out .= '</table>'.LF;
   print $out;
}

function printStatsInputForm() {
  $months=array(
    1=>_('January'),
    2=>_('February'),
    3=>_('March'),
    4=>_('April'),
    5=>_('May'),
    6=>_('June'),
    7=>_('July'),
    8=>_('August'),
    9=>_('September'),
    10=>_('October'),
    11=>_('November'),
    12=>_('December'),
  );

  $year = Date('Y');
  pageHeader(_('Incident statistics'), array(
     'menu'=>'incidents',
     'submenu'=>'reports'));
  $out = '<P>'._('Please select the reporting period of which you would like to see statistics. (note; the start date and the end date are included in the
report.').'</P>';

  $out .= '<form action="'.BASEURL.'/stats.php" method="POST">'.LF;
  $out .= '<table>'.LF;
  $out .= '<tr>'.LF;
  $out .= '   <td>'._('Start date (day-month-year)').'</td>'.LF;
  $out .= '   <td>'.LF;
  $out .= '   <select name="start_day">'.LF;
  $out .= '      <option value="-1">'._('Select day').'</option>'.LF;
  for ($i=1; $i<32; $i++) {
     $out .= sprintf('<option value="%02d">%02d</option>', $i, $i);
  }
  $out .= '   </select>'.LF;
  $out .= '   &nbsp;-&nbsp;'.LF;
  $out .= '   <select name="start_month">'.LF;
  $out .= '      <option value="-1">'._('Select month').'</option>'.LF;
  for ($i=1; $i<13; $i++) {
    $out .= sprintf('<option value="%02d">%s</option>', $i, $months[$i]);
  }
  $out .= '   </select>'.LF;
  $out .= '   &nbsp;-&nbsp;'.LF;
  $out .= '   <input type="text" size="5" name="start_year" value="'.$year.'">'.LF;
  $out .= '   </td>'.LF;
  $out .= '</tr>'.LF;

  $out .= '<tr>'.LF;
  $out .= '   <td>'._('End date (day-month-year)').'</td>'.LF;
  $out .= '   <td>'.LF;
  $out .= '   <select name="stop_day">'.LF;
  $out .= '      <option value="-1">'._('Select day').'</option>'.LF;
  for ($i=1; $i<32; $i++) {
     $out .= sprintf('<option value="%02d">%02d</option>', $i, $i);
  }
  $out .= '   </select>'.LF;
  $out .= '   &nbsp;-&nbsp;'.LF;
  $out .= '   <select name="stop_month">'.LF;
  $out .= '      <option value="-1">'._('Select month').'</option>'.LF;
  for ($i=1; $i<13; $i++) {
    $out .= sprintf('<option value="%02d">%s</option>', $i, $months[$i]);
  }
  $out .= '   </select>'.LF;
  $out .= '   &nbsp;-&nbsp;'.LF;
  $out .= '   <input type="text" size="5" name="stop_year" value="'.$year.'">'.LF;
  $out .= '   </td>'.LF;
  $out .= '</tr>'.LF;
  $out .= '</table>'.LF;

  $out .= _('Report to generate').LF;
  $out .= '<select name="report">'.LF;
  $out .= '<option value="0" SELECTED>--- Select report ---</option>'.LF;
  $out .= '<option value="1">'.
     _('Amount per incident type per constituency').'</option>'.LF;
  $out .= '<option value="2">'.
     _('Created/open amount per constituency').'</option>'.LF;
  $out .= '</select>'.LF;

  $out .= '<p><input type="submit" name="action" value="'._('Show statistics').'">'.LF;
  $out .= '</form>'.LF;
  print $out;
}

function printreport1($start, $stop) {
   $startdate = date('r', $start);
   $stopdate = date('r', $stop);
   $showempty = fetchFrom('REQUEST', 'showempty');
   showMatrix($start, $stop, $showempty);
   pageFooter();
   return;
}

function printreport2($start, $stop) {
   $out = '<H2>'._('Created/open amount of incidents per constituency').'</H2>';
   $out .= _('Report generated on ').date('r').'.<br/>'.LF;
   $out .= t(_('Printing incidents from %startdate until %enddate.').
      '<br/>'.LF, array(
      '%startdate'=>Date('d-M-Y', $start),
      '%enddate'=>Date('d-M-Y', $stop)));
   $out .= '<p/>'.LF;
   $const = getconstituencies();
   $q = q('select c.id as constituencyid, count(i.id) as count
      from incidents i
      left join incident_addresses ia on ia.incident = i.id
      left join constituencies c on ia.constituency = c.id
      where created between \'%begin\' and \'%end\'
      group by c.id', array(
         '%begin'=>Date('d-M-Y', $start),
         '%end'=>Date('d-M-Y', $stop+86400)));
   $res = db_query($q);
   $out .= '<table border="1">'.LF;
   $out .= '<tr>'.LF;
   $out .= '   <th>&nbsp;</th>'.LF;
   $out .= '   <th colspan="2">'._('Incidents').'</th>'.LF;
   $out .= '</tr>'.LF;
   $out .= '<tr>'.LF;
   $out .= '   <th>'._('Constituency').'</th>'.LF;
   $out .= '   <th>'._('created').'</th>'.LF;
   $out .= '   <th>'._('open').'</th>'.LF;
   $out .= '</tr>'.LF;

   $const_sums=array('created'=>0, 'open'=>0);
   while ($row = db_fetch_next($res)) {
      if (empty($row['constituencyid'])) continue;
      $o = getOpenIncidentsByConstituency($row['constituencyid']);
      $out .= '<tr>'.LF;
      $out .= t('<td>%cons</td>', array(
         '%cons'=>$const[$row['constituencyid']]['label'])).LF;
      $out .= t('<td>%created</td>', array(
         '%created'=>$row['count'])).LF;
      $out .= t('<td>%open</td>', array(
         '%open'=>sizeof($o))).LF;
      $out .= '</tr>'.LF;
      $const_sums['created'] += $row['count'];
      $const_sums['open'] += sizeof($o);
   }
   $out .= '<tr style="background-color:lightgray">'.LF;
   $out .= '<td>'._('Sum').'</td>'.LF;
   foreach ($const_sums as $sum) {
      $out .= t('<td>%s</td>'.LF, array('%s'=>htmlentities($sum)));
   }
   $out .= '</tr>'.LF;
   $out .= '</table>'.LF;

   print $out;
   pageFooter();
   return;
}

/*****************************************************************************/
switch ($action) {
   case 'none':
    printStatsInputForm();
      break;
   case _('Show statistics'):
   case 'query':
      pageHeader('AIRT statistics');
      $start = getStartDate();
      $stop = getStopDate();
      if ($start == -1 || $stop == -1) {
         airt_msg(_('Invalid date format.'));
         exit(reload());
      }

      switch (fetchFrom('POST', 'report', '%d')) {
         case 0:
            reload();
            break;
         case 1:
            printreport1($start, $stop);
            break;
         case 2:
            printreport2($start, $stop);
            break;
         default:
            reload();
      }
      break;

   default:
      airt_msg(_('Unknown action'));
      exit(reload());
}
