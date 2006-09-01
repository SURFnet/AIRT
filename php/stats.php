<?php
/* $Id$
 * $URL$
 * vim: syntax=php tabstop=3 shiftwidth=3

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

if (array_key_exists('action', $_REQUEST)) $action=$_REQUEST['action'];
else $action='none';


/** Extract the start date from the input form.
 * @return A Unix timestamp containing the start date, or -1 in case of failure
 */
function getStartDate() {
  $year = fetchFrom('REQUEST', 'start_year', '%d');
  $month = fetchFrom('REQUEST', 'start_month', '%d');
  $day = fetchFrom('REQUEST', 'start_day', '%d');

  $start = strtotime(sprintf('%02d/%02d/%04d', $month, $day, $year));
  if ($start == FALSE) {
    return -1;
  } else {
    return $start;
  }
}

/** Extract the end date from the input form.
 * @return A Unix timestamp containing the end date, or -1 in case of failure
 */
function getEndDate() {
  $year = fetchFrom('REQUEST', 'stop_year', '%d');
  $month = fetchFrom('REQUEST', 'stop_month', '%d');
  $day = fetchFrom('REQUEST', 'stop_day', '%d');

  $end = strtotime(sprintf('%02d/%02d/%04d', $month, $day, $year));
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
function showMatrix($start, $end) {
  $constituencies = getConstituencies();
  $types = getIncidentTypes();

  $out = '<H2>'._('Incident type/Constituency matrix').'</H2>';

  $out .= '<p>';
  $out .= t(_('Printing from %startdate until %enddate.'), array(
    '%startdate'=>Date('d-M-Y', $start),
    '%enddate'=>Date('d-M-Y', $end)));
  $out .= '</p>'.LF;
  $out .= '<table border="1" cellpadding="3">'.LF;
  $out .= '<tr>'.LF;
  $out .= '<td>&nbsp;</td>'.LF;
  foreach ($types as $t=>$label) {
    $out .= t('<th>%t</th>'.LF, array('%t'=>$label));
    $typesum[$label] = 0;
  }
  $out .= '<td><I>'._('Sum').'</I></td>'.LF;
  $out .= '</tr>'.LF;
  foreach ($constituencies as $consid=>$c) {
    $constsum=0;
    $out .= '<tr>'.LF;
    $out .= t('<th>%c</th>'.LF, array('%c'=>$c['label']));
    foreach ($types as $id=>$label) {
      $res = db_query(q('SELECT COUNT(distinct i.id) AS count
         FROM incidents i
         LEFT JOIN incident_addresses a ON (i.id = a.incident)
         WHERE type = %type
         AND a.constituency = %constituency
         AND i.created BETWEEN \'%start\' AND \'%stop\'', array(
            '%type'=>$id, 
            '%constituency'=>$consid,
            '%start'=>Date('d-M-Y', $start),
            '%stop'=>Date('d-M-Y', $end))));
      $row = db_fetch_next($res);
      $out .= '<td>'.$row['count'].'</td>';
      $typesum[$label] += $row['count'];
      $constsum += $row['count'];
      db_free_result($res);
    }
    $out .= '<td><I>'.$constsum.'</I></td>'.LF;
    $out .= '</tr>'.LF;
  }
  $out .= '<tr>'.LF;
  $out .= '<td>'._('Sum').'</td>'.LF;
  $sum = 0;
  foreach ($types as $id=>$label) {
    $out .= '<td><I>'.$typesum[$label].'</I></td>'.LF;
    $sum += $typesum[$label];
  }
  $out .= '<td><I><B>'.$sum.'</B></I></td>'.LF;
  $out .= '<tr><td/>'.LF;
  foreach ($types as $t=>$label) {
    $out .= t('<th>%t</th>'.LF, array('%t'=>$label));
    $typesum[$label] = 0;
  }
  $out .= '</tr>'.LF;
  $out .= '</table>'.LF;

  $out .= '<H2>'._('Incidents without IP addresses').'</h2>';
  $res = db_query(q('SELECT i.id
     FROM incidents i
     LEFT JOIN incident_addresses a ON i.id = a.incident
     WHERE i.created BETWEEN \'%start\' AND \'%stop\'
     GROUP BY i.id
     HAVING COUNT (ip) = 0', array(
            '%start'=>Date('d-M-Y', $start),
            '%stop'=>Date('d-M-Y', $end))));
  $out .= '<table border="1" cellpadding="3">'.LF;
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
    $out .= '<td>'.normalize_incidentid($row['id']).'</td>'.LF;
    $out .= '<td>'.getIncidentTypeLabelByID($i['type']).'</td>'.LF;
    $out .= '<td>'.getIncidentStatusLabelByID($i['status']).'</td>'.LF;
    $out .= '<td>'.getIncidentStateLabelByID($i['status']).'</td>'.LF;
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
  pageHeader(_('Incident statistics'));
  $out = '<P>'._('Please select the reporting period of which you would like to see statistics. (note; the start date and the end date are included in the
report.').'</P>';

  $out .= '<form action="'.$_SERVER[PHP_SELF].'" method="POST">'.LF;
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

  $out .= '<p><input type="submit" name="action" value="'._('Show statistics').'">'.LF;
  $out .= '</form>'.LF;
  print $out;
}

/*****************************************************************************/
switch ($action) {
	case 'none':
    printStatsInputForm();
		break;
	case _('Show statistics'):
		pageHeader('AIRT statistics');

    $start = getStartDate();
    if ($start == -1) {
      print 'Invalid date format of start date.';
      break;
    }
    $stop = getEndDate();
    if ($stop == -1) {
      print 'Invalid date format of end date.';
      break;
    }

    $startdate = date('r', $start);
    $enddate = date('r', $stop);
		$out = '<p>'._("Statistics from $startdate to $enddate.").'</p>';
      showmatrix($start, $stop);
		pageFooter();
		break;
	default:
		die('Unknown action');
}
?>
