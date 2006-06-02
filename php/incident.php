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
require_once LIBDIR.'/constituency.plib';
require_once LIBDIR.'/incident.plib';
require_once LIBDIR.'/history.plib';
require_once LIBDIR.'/user.plib';
require_once LIBDIR.'/mailtemplates.plib';

function updateCheckboxes() {
   // Used to compile some JavaScript that is needed in many places.
   global $toggle;

   $sortkey = fetchFrom('REQUEST','sortkey');
   defaultTo($sortkey,'incidentid');
   
   $page = fetchFrom('REQUEST','page','%d');
   defaultTo($page,1);

   defaultTo($toggle,0);

   $filter = fetchFrom('REQUEST','filter[]');
   defaultTo($filter,array('state'=>-1, 'status'=>-1));

   $urlsuffix=strtr(
     '&page=%p&filter[state]=%sf&filter[status]=%stf&sortkey=%sk&toggle=%t',
     array(
      '%p'=>$page,
      '%t'=>$toggle,
      '%sf'=>$filter['state'],
      '%stf'=>$filter['status'],
      '%sk'=>$sortkey));
   $output = "<SCRIPT Language=\"JavaScript\">\n";
   $output .= "function updateCheckboxes() {\n";
   $output .= "   if (!(document.jsform.email.value == '')) {\n";
   $output .= "      document.jsform.addifmissing.checked = true;\n";
   $output .= "      if (typeof document.jsform.sendmail != \"undefined\") document.jsform.sendmail.checked = true;\n";
   $output .= "   } else {\n";
   $output .= "      document.jsform.addifmissing.checked = false;\n";
   $output .= "   }\n";
   $output .= "}\n";
   $output .= "function checkAll() {\n";
   $output .= "   window.location = '$_SERVER[PHP_SELF]?action=toggle$urlsuffix';\n";
   $output .= "}\n";

   $output .= "</SCRIPT>\n";
   return $output;
} // updateCheckboxes


function formatIncidentBulkForm(&$check) {
   $constituency = $name = $email = $type = $state = $status
     = $addressrole = "";

   if (array_key_exists("active_ip", $_SESSION)) {
      $address = $_SESSION["active_ip"];
   } else {
      $address = "";
   }
   $address = fetchFrom('SESSION','active_ip');

   if (array_key_exists("constituency_id", $_SESSION)) {
      $constituency = $_SESSION["constituency_id"];
   }
   if (array_key_exists("current_email", $_SESSION)) {
      $email = $_SESSION["current_email"];
   }

   if (defined('CUSTOM_FUNCTIONS') && function_exists('custom_default_addressrole')) {
      $addressrole = custom_default_addressrole($address);
   }
   $output =  formatBasicIncidentData($type, $state, $status);
   $output .= '<hr/>'.LF;
   $output .= '<h3>'._('Affected IP addresses').'</h3>'.LF;
   $output .= "<table cellpadding=\"4\">\n";
   $output .= "<tr>\n";
   $output .= "  <td valign=\"top\">hostname or ip address</td>\n";
   $output .= "  <td><textarea cols=\"30\" rows=\"15\" name=\"addresses\"></textarea>\n";
   $output .= "</tr>\n";
   $output .= "<tr>\n";
   $output .= t("<td>addressrole</td><td>%addressrole</td>\n",array('%addressrole'=>getAddressRolesSelection('addressrole', $addressrole)));
   $output .= "</tr>\n";

   $output .= "<tr>\n";
   $output .= "  <td>constituency</td>\n";
   $output .= "  <td>".getConstituencySelection("constituency", $constituency)."</td>\n";
   $output .= "</tr>\n";
   $output .= "</table>\n";
 
   $output .= "<p/>\n";

   return $output;
} // show IncidentBulkForm


function formatIncidentForm(&$check) {
   $constituency = $name = $email = $type = $state = $status 
     = $addressrole = '';

   $address      = fetchFrom('SESSION','active_ip');
   $constituency = fetchFrom('SESSION','constituency_id');
   $email        = fetchFrom('SESSION','current_email');

   if (defined('CUSTOM_FUNCTIONS') &&
             function_exists('custom_default_addressrole')) {
      $addressrole = custom_default_addressrole($address);
   }

   $output =  formatBasicIncidentData($type, $state, $status);
   $output .= '<hr/>'.LF;
   $output .= '<h3>'._('Affected IP addresses').'</h3>'.LF;
   $output .= '<table cellpadding=4>'.LF;
   $output .= '<tr>'.LF;
   $output .= '  <td>'._('Hostname or IP address').'</td>'.LF;
   $output .= t('  <td><input type="text" size=30 name="address" '.
                'value="%address">%addressrole</td>'.LF, array(
      '%address'=>$address,
      '%addressrole'=>getAddressRolesSelection('addressrole', $addressrole)
   ));
   $output .= '</tr>'.LF;
   $output .= '<tr>'.LF;
   $output .= '  <td>'._('Constituency').'</td>'.LF;
   $output .= '  <td>'.getConstituencySelection('constituency',
       $constituency).'</td>'.LF;
   $output .= '</tr>'.LF;
   $output .= '</table>'.LF;
 
   $output .= '<hr/>'.LF;
   $output .= '<h3>'._('Affected users').'</h3>'.LF;
   $output .= '<table bgcolor="#dddddd" cellpadding=2 border=0>'.LF;
   $output .= '<tr>'.LF;
   $output .= '  <td>'._('E-mail address of user').':</td>'.LF;
   $output .= '  <td><input onChange="updateCheckboxes()" type="text" '.
              'size=40 name="email" value="'.$email.'"></td>'.LF;
   $output .= '  <td><a href="help.php?topic=incident-adduser">'.
              _('help').'</td>'.LF;
   $output .= '</tr>'.LF;
   $output .= '</table>'.LF;

   if ($email != '') {
      $check = true;
   }
   $output .= t('<input type="checkbox" name="addifmissing" %checked>'.LF,
      array('%checked'=>($check == false) ? '' : 'checked'));
   $output .= '  '._('If checked, create user if email address unknown').LF;

   $output .= '<p/>'.LF;

   return $output;
} // show Incidentform


/* Return a formatted string representing an HTML form for editing incident
 * details.
 */
function formatEditForm() {
   $incident = getincident(fetchFrom('SESSION','incidentid'));
   $type    = $incident['type'];
   $state   = $incident['state'];
   $status  = $incident['status'];
	$logging = $incident['logging'];

   $address = fetchFrom('SESSION','active_ip');
   $constituency = fetchFrom('SESSION','constituency_id');

   // Basic incident data block.
   $output = '<form action="'.$_SERVER['PHP_SELF'].'" method="post">'.LF;
   $output .= '<hr/>'.LF;
   $output .= '<h3>'._('Basic incident data').'</h3>'.LF;
   $output .= formatBasicIncidentData($type, $state, $status, $logging);
   $output .= '<input type="submit" name="action" value="update">'.LF;
   $output .= '</form>'.LF;

   // Affected IP addresses block. First the header part.
   $output .= '<hr/>'.LF;
   $output .= '<h3>'._('Affected IP addresses').'</h3>'.LF;
   $output .= '<table cellpadding=4>'.LF;
   $output .= '<tr>'.LF;
   $output .= '   <td>'._('IP Address').'</td>'.LF;
   $output .= '   <td>'._('Hostname').'</td>'.LF;
   $output .= '   <td>'._('Constituency').'</td>'.LF;
   $output .= '   <td>'._('Role in incident').'</td>'.LF;
   $output .= '   <td>'._('Edit').'</td>'.LF;
   $output .= '   <td>'._('Remove').'</td>'.LF;
   $output .= '</tr>'.LF;
   $conslist = getConstituencies();
   // Then the IP address list.
   foreach ($incident['ips'] as $address) {
      $output .= '<tr>'.LF;
      $output .= sprintf(
        '  <td><a href="search.php?action=search&q=%s">%s</a></td>'.LF,
         urlencode($address['ip']),
         $address['ip']);
      $_SESSION['active_ip'] = $address['ip'];
      $output .= sprintf(
         '  <td>%s</td>'.LF,
         $address['hostname']==''?_('Unknown'):
                       @gethostbyaddr(@gethostbyname($address['ip'])));
      $cons = getConstituencyIDbyNetworkID(categorize($address['ip']));
      $output .= sprintf('  <td>%s</td>'.LF, $conslist[$cons]['label']);
      $output .= t('  <td>%addressrole</td>'.LF,
         array(
            '%addressrole'=>getAddressRoleByID($address['addressrole'])));
      $output .= sprintf('  <td><a href="'.$_SERVER['PHP_SELF'].
                         '?action=editip&ip=%s">'._('edit').'</a></td>'.LF,
         urlencode($address['ip']));
      $output .= t('  <td><a href="'.$_SERVER['PHP_SELF'].
                   '?action=deleteip&ip=%ip&addressrole=%addressrole">'.
                   _('remove').'</a></td>'.LF,
         array(
            '%ip'=>urlencode($address['ip']),
            '%addressrole'=>urlencode($address['addressrole'])));
      $output .= '</tr>'.LF;
   }
   $output .= '</table>'.LF;
   // And lastly, the IP address footer.
   $output .= '<p/>'.LF;
   $output .= '<form action="'.$_SERVER['PHP_SELF'].'" method="POST">'.LF;
   $output .= '<input type="hidden" name="action" value="addip">'.LF;
   $output .= '<table bgColor="#DDDDDD" cellpadding=2>'.LF;
   $output .= '<tr>'.LF;
   $output .= '  <td>'._('IP Address').'</td>'.LF;
   $output .= '  <td><input type="text" name="ip" size=40></td>'.LF;
   $output .= t('<td>%addressrole</td>', array(
      '%addressrole' => getAddressRolesSelection('addressrole')
   ));
   $output .= '  <td><input type="submit" value="'._('Add').'"></td>'.LF;
   $output .= '</tr>'.LF;
   $output .= '</table>'.LF;
   $output .= '</form>'.LF;

   // Affected users block.
   $output .= '<hr/>'.LF;
   $output .= '<h3>'._('Affected users').'</h3>'.LF;
   $output .= '<form>'.LF;
   $output .= t('<input type="hidden" name="incidentid" value="%incidentid">',
      array('%incidentid'=>$incident['incidentid']));
   $output .= '<table cellpadding=4>'.LF;
   // re-initialise active users
   $_SESSION['current_name'] = '';
   $_SESSION['current_email'] = '';
   foreach ($incident['users'] as $user) {
      $u = getUserByUserId($user);
      if ($_SESSION['current_email'] == '') {
         $_SESSION['current_email'] = $u['email'];
      } else {
         $_SESSION['current_email'] .= ','.$u['email'];
      }
      if ($u['firstname'] == '' && $u['lastname'] == '') {
         $name = $u['email'];
      } else {
         $name = $u['firstname'].' '.$u['lastname'];
      }
      if ($_SESSION['current_name'] == '') {
         $_SESSION['current_name'] = $name;
      } else {
         $_SESSION['current_name'] .= ','.$name;
      }
      $output .= t('<tr >'.LF);
      $output .= t('  <td><input type="checkbox" name="agenda[]" value="%userid"></td>', array('%userid'=>$user));
      $output .= t('  <td>%email</td>', array('%email'=>$u['email'])).LF;
      $output .= '</tr>'.LF;
   }
   $output .= '</table>'.LF;
   $output .= '<input type="submit" name="action" value="Mail">'.LF;
   $output .= '<input type="submit" name="action" value="Remove">'.LF;
   $output .= '</form>'.LF;
   $output .= '<p/>'.LF;
   // Affected user list footer. First get som data.
   $userid = fetchFrom('SESSION','current_userid');
   if ($userid!='') {
      $u = getUserByUserID($userid);    // Cannot cope with empty userids.
      if (sizeof($u) > 0) {
         $lastname = $u[0]['lastname'];
         $email = $u[0]['email'];
      }
   }
   $email = fetchFrom('SESSION','current_email');
   // Then build the form.
   $output .= '<form name="jsform" action="'.$_SERVER['PHP_SELF'].
              ' method="POST">'.LF;
   $output .= '  <input type="hidden" name="action" value="adduser">'.LF;
   $output .= '  <table bgColor="#DDDDDD" cellpadding=2 border=0>'.LF;
   $output .= '  <tr>'.LF;
   $output .= '    <td>'._('Email address of user').':</td>'.LF;
   $output .= '    <td><input onChange="updateCheckboxes()" type="text" '.
              'size=40 name="email" value="'.$email.'"></td>'.LF;
   $output .= '    <td><input type="submit" value="'._('Add').'"></td>'.LF;
   $output .= '    <td><a href="help.php?topic=incident-adduser">'.
              _('help').'</td>'.LF;
   $output .= '  </tr>'.LF;
   $output .= '  </table>'.LF;
   $output .= t('  <input onChange="updateCheckboxes()" type="checkbox" name="addifmissing" %checked>', array(
      '%checked'=>($email=='')?'':'CHECKED')).LF;
   $output .= '  '._('If checked, create user if email address unknown').LF;
   $output .= '<hr/>'.LF;
   $output .= '</form>'.LF;

   return $output;
} // formatEditForm


/* format the filter block in the page header */
function formatFilterBlock() {
   $filter = fetchFrom('REQUEST','filter[]');
# TODO These defaults need to come from the database.
   defaultTo($filter, array('state'=>-1, 'status'=>-1));

   $out = t(
      '<FORM method="POST">'.LF.
      '<table>'.LF.
      '<tr>'.LF.
      '   <td>'._('Status').'</td>'.LF.
      '   <td>'.
             getIncidentStatusSelection('filter[status]', $filter['status'],
             array(-1=>_('Do not filter'))).
      '   </td>'.LF.
      '</tr>'.LF.
      '<tr>'.LF.
      '   <td>'._('State').'</td>'.LF.
      '   <td>'.
             getIncidentStateSelection('filter[state]', $filter['state'],
             array(-1=>_('Do not filter'))).
      '   </td>'.LF.
      '</tr>'.LF.
      '<tr>'.LF.
      '  <td></td>'.LF.
      '  <td><INPUT TYPE="submit" VALUE="'._('Filter the List').'"></td>'.LF.
      '</tr>'.LF.
      '</table>'.LF.
      '</FORM>'.LF);
   return $out;
}// formatFilterBlock


/* format the details block in the page header */
function formatDetailBlock() {
   $out = t(
      '<form method="post">'.LF.
      '<table>'.LF.
      '<tr>'.LF.
      '  <td>'._('Incident #').'</td>'.LF.
      '  <td><INPUT TYPE="text" name="incidentid" size="7">'.LF.
      '</tr><tr>'.LF.
      '  <td></td>'.LF.
      '  <td><INPUT TYPE="submit" name="action" value="'.
            _('Show Details').'"></td>'.LF.
      '</tr>'.LF.
      '</table>'.LF.
      '</form>'.LF);
   return $out;
}

/* format the create incident(s) block in the page header */
function formatCreateIncidentBlock() {
   $out = t(
      '<form method="post">'.LF.
      '<INPUT TYPE="submit" Name="action" VALUE="'.
         _('Create New Incident').'"><p>'.LF.
      '<INPUT TYPE="submit" Name="action" VALUE="'.
         _('Create Many Incidents').'">'.LF.
      '</form>'.LF
   );
   return $out;
}


/* return a string containing the list overview page header, which contains
 * a few blocks horizontally next to each other.
 */
function formatListOverviewHeader() {
   // A style expression for the vertical separation lines between the
   // blocks.
   $style = 'style="border-right-width:1px; border-right-style:solid; '.
            'padding-left:10px; padding-right:10px"';

   $out = t(
      '<table>'.LF.
      '<tr valign="top">'.LF.
      '   <td %style>%filters</td>'.LF.
      '   <td %style>%details</td>'.LF.
      '   <td style="padding-left:10px">%createIncident</td>'.LF.
      '</tr>'.LF.
      '</table>'.LF,
      array(
         '%style'=>$style,
         '%filters'=>formatFilterBlock(),
         '%details'=>formatDetailBlock(),
         '%createIncident'=>formatCreateIncidentBlock()));

   return $out;
}// formatListOverviewHeader


/* format a pager line. Take the total number of incidents, the current page,
 * and the number of incidents per page as input
 */
function formatPagerLine($page, $numincidents, $pagesize=PAGESIZE) {
   global $sortkey;
   global $filter;

   if ($numincidents < $pagesize) {
      return '';
   }

   $urlprefix = '&sortkey='.$sortkey.
                '&filter[status]='.$filter['status'].
                '&filter[state]='.$filter['state'];
   $out = '';
   $numpages = (int) ceil($numincidents / $pagesize);

   if ($page == 1) {
      $out .= '<strong>'._('Previous').'</strong>&nbsp;';
   } else {
      $out .= t('<a href="%url?page=%prev%urlprefix">'.
                 _('Previous').'</a>&nbsp;',
                array(
         '%urlprefix'=>$urlprefix,
         '%url'=>$_SERVER['PHP_SELF'],
         '%prev'=>($page-1)));
   }
   for ($i = 1; $i <= $numpages; $i++) {
      if ($i == $page) {
         $out .= "<strong>$i</strong>&nbsp;";
      } else {
         $out .= t('<a href="%url?page=%i%urlprefix">'.$i.'</a>&nbsp;',
                 array(
                   '%i'=>$i,
                   '%urlprefix'=>$urlprefix,
                   '%url' => $_SERVER['PHP_SELF']));
      }
   }
   if ($page == $numpages) {
      $out .= '<strong>'._('Next').'</strong>&nbsp;';
   } else {
      $out .= t('<a href="%url?page=%next%urlprefix">'._('Next').'</a>&nbsp;',
              array(
               '%urlprefix'=>$urlprefix,
               '%url'=>$_SERVER['PHP_SELF'],
               '%next'=>($page+1)));
   }
   return $out;
}


/* return an HTML-formatted overview of open incidents */
function formatListOverviewBody() {
   global $sortkey;
   global $filter;
   global $toggle;

   $filter = fetchFrom('REQUEST','filter[]');
   // NOTE: the $filter itself has a default, but if somebody manipulates
   // the $filter and e.g. removes the 'status' index, things go wrong here.
   // It looks secure, but it breaks.
   defaultTo($filter,
             array('status'=>-1, 'state'=>-1));

   $sortkey = fetchFrom('REQUEST','sortkey');
   defaultTo($sortkey,'incidentid');
   
   $page = fetchFrom('REQUEST','page','%d');
   defaultTo($page,1);

   $statuses = getIncidentStatus();
   if (array_key_exists($filter['status'], $statuses) && 
      $filter['status'] >= 0) {
      $sqlfilter = " AND s1.label = '".$statuses[$filter['status']]."'";
   } else {
      $sqlfilter = '';
   }

   $states = getIncidentStates();
   if (array_key_exists($filter['state'], $states) && 
      $filter['state'] >= 0) {
      $sqlfilter .= " AND s2.label = '".$states[$filter['state']]."'";
   }

   switch ($sortkey) {
      case 'incidentid':
         $sqlfilter .= ' ORDER BY incidentid';
         break;
      case 'constituency':
         $sqlfilter .= ' ORDER BY constituency';
         break;
      case 'hostname':
         $sqlfilter .= ' ORDER BY hostname';
         break;
      case 'status':
         $sqlfilter .= ' ORDER BY status';
         break;
      case 'state':
         $sqlfilter .= ' ORDER BY state';
         break;
      case 'type':
         $sqlfilter .= ' ORDER BY type';
         break;
      case 'lastupdated':
         $sqlfilter .= ' ORDER BY updated';
         break;
      default:
         // Hmmm... should not happen. Manual intervention in the URL?
         $sqlfilter .= ' ORDER BY incidentid';
         $sortkey = 'incidentid';
   }

   $incidents = getOpenIncidents($sqlfilter);
   if (sizeof($incidents) == 0) {
        return '<I>'._('No incidents').'</I>';
   }

   // Now produce the list header.
   $out = t(
      '<form name="listform" action="%url" method="POST">'.LF.
      '<INPUT TYPE="hidden" name="action" value="massupdate">'.LF.
      '<table width="100%">'.LF.'<tr>'.LF,
      array('%url'=>$_SERVER['PHP_SELF']));

   // 'Select all' checkbox. No <th> but <td> for visual alignment reasons.
   $out .= t(
      '   <td><input type="checkbox" %checked onChange="checkAll()"></td>'.LF,
      array('%url'=>$_SERVER['PHP_SELF'],
            '%checked'=>($toggle==1)?"CHECKED":""));

   // 'Incident ID' column header. Clickable if not the current sort key.
   if ($sortkey == 'incidentid') {
      $out .= t('   <th>'._('Incident ID').'</th>'.LF);
   } else {
      $out .= t('   <th><a href="%url?sortkey=incidentid&filter[status]=%sf'.
                '&filter[state]=%stf&page=1">%heading</a></th>'.LF, 
         array('%url'=>$_SERVER['PHP_SELF'],
               '%sf'=>$filter['status'],
               '%heading'=>_('Incident ID'),
               '%stf'=>$filter['state']));
   }

   // 'Constituency' column header. Clickable if not the current sort key.
   if ($sortkey == 'constituency') {
      $out .= t('   <th>'._('Constituency').'</th>'.LF);
   } else {
      $out .= t('   <th><a href="%url?sortkey=constituency&filter[status]=%sf'.
                '&filter[state]=%stf&page=1">%heading</a></th>'.LF, 
         array('%url'=>$_SERVER['PHP_SELF'],
               '%sf'=>$filter['status'],
               '%heading'=>_('Constituency'),
               '%stf'=>$filter['state']));
   }

   // 'Hostname' column header. Clickable if not the current sort key.
   if ($sortkey == 'hostname') {
      $out .= t('   <th>'._('Host name').'</th>'.LF);
   } else {
      $out .= t('   <th><a href="%url?sortkey=hostname&filter[status]=%sf'.
                '&filter[state]=%stf&page=1">%heading</a></th>'.LF, 
         array('%url'=>$_SERVER['PHP_SELF'],
               '%sf'=>$filter['status'],
               '%heading'=>_('Host name'),
               '%stf'=>$filter['state']));
   }

   // 'Status' column header. Clickable if not the current sort key.
   if ($sortkey == 'status') {
      $out .= t('   <th>'._('Status').'</th>'.LF);
   } else {
      $out .= t('   <th><a href="%url?sortkey=status&filter[status]=%sf'.
                '&filter[state]=%stf&page=1">%heading</a></th>'.LF, 
         array('%url'=>$_SERVER['PHP_SELF'],
               '%sf'=>$filter['status'],
               '%heading'=>_('Status'),
               '%stf'=>$filter['state']));
   }

   // 'State' column header. Clickable if not the current sort key.
   if ($sortkey == 'state') {
      $out .= t('   <th>'._('State').'</th>'.LF);
   } else {
      $out .= t('   <th><a href="%url?sortkey=state&filter[status]=%sf'.
                '&filter[state]=%stf&page=1">%heading</a></th>'.LF, 
         array('%url'=>$_SERVER['PHP_SELF'],
               '%sf'=>$filter['status'],
               '%heading'=>_('State'),
               '%stf'=>$filter['state']));
   }

   // 'Type' column header. Clickable if not the current sort key.
   if ($sortkey == 'type') {
      $out .= t('   <th>'._('Type').'</th>'.LF);
   } else {
      $out .= t('   <th><a href="%url?sortkey=type&filter[status]=%sf'.
                '&filter[state]=%stf&page=1">%heading</a></th>'.LF, 
         array('%url'=>$_SERVER['PHP_SELF'],
               '%sf'=>$filter['status'],
               '%heading'=>_('Type'),
               '%stf'=>$filter['state']));
   }

   // 'Last updated' column header. Clickable if not the current sort key.
   if ($sortkey == 'lastupdated') {
      $out .= t('   <th>'._('Last updated').'</th>'.LF);
   } else {
      $out .= t('   <th><a href="%url?sortkey=lastupdated&filter[status]=%sf'.
                '&filter[state]=%stf&page=1">%heading</a></th>'.LF, 
         array('%url'=>$_SERVER['PHP_SELF'],
               '%sf'=>$filter['status'],
               '%heading'=>_('Last updated'),
               '%stf'=>$filter['state']));
   }

   $out .= t('</tr>'.LF);

   // Prepare to run over the list of incidents that need display.
   $count = 0;
   $conslist = getConstituencies();
   foreach ($incidents as $id=>$data) {
      if ($count < PAGESIZE*($page-1) || $count >= PAGESIZE*($page)) {
         $count++;
         continue;
      }
      $hostline= $data['hostname'];
      $addresses = getAddressesForIncident($id);
      $constituency = $conslist[$addresses[0]['constituency']]['label'];

      $out .= t('<tr bgcolor="%color">'.LF.
         '   <td>'.
         '<input type="checkbox" name="massincidents[]" %check value="%id">'.
         '</td>'.LF.
         '   <td>'.
         '<a href="%url?action=details&incidentid=%id">%incidentid</a>'.
         '</td>'.LF.
         '   <td>%constituency</td>'.LF.
         '   <td>%hostline</td>'.LF.
         '   <td>%status</td>'.LF.
         '   <td>%state</td>'.LF.
         '   <td>%type</td>'.LF.
         '   <td>%updated</td>'.LF.
         '</tr>'.LF, array(
            '%color'=> ($count++%2 == 1) ? '#FFFFFF' : '#DDDDDD',
            '%url' => $_SERVER['PHP_SELF'],
            '%id' => $id,
            '%incidentid' => encode_incidentid($id),
            '%constituency' => $constituency,
            '%hostline' => $hostline,
            '%status' => $data['status'],
            '%state' => $data['state'],
            '%type' => $data['type'],
            '%check' => ($toggle == 1) ? 'CHECKED' : '',
            '%updated' => Date('d M Y', $data['updated'])));
   } // foreach

   $out .= '</table><p>'.LF;
   $out .= '<div align="center">'.
           formatPagerLine($page, sizeof($incidents)).
           '</div>'.LF;
   return $out;
} // formatQueueOverviewBody


/* Returns a string which contains the incident overview list footer, two
 * blocks of menus/buttons.
 */
function formatListOverviewFooter() {
   // A style expression for the vertical separation lines between the
   // blocks.
   $style = 'style="border-right-width:1px; border-right-style:solid; '.
            'padding-left:10px; padding-right:10px"';

   $updatetable = t(
      '<table>'.LF.
      '<tr>'.LF.
      '  <td>'._('New State').'</td>'.LF.
      '  <td>'.getIncidentStateSelection('massstate', 'null',
                          array('null'=>_('Leave Unchanged'))).'</td>'.LF.
      '</tr>'.LF.
      '<tr>'.LF.
      '  <td>'._('New Status').'</td>'.LF.
      '  <td>'.getIncidentStatusSelection('massstatus', 'null',
                          array('null'=>_('Leave Unchanged'))).'</td>'.LF.
      '</tr>'.LF.
      '<tr>'.LF.
      '  <td>'._('New Type').'</td>'.LF.
      '  <td>'.getIncidentTypeSelection('masstype', 'null',
                          array('null'=>_('Leave Unchanged'))).'</td>'.LF.
      '</tr>'.LF.
      '<tr>'.LF.
      '  <td>&nbsp;</td>'.LF.
      '  <td><input type="submit" value="'._('Update All Selected').'"></td>'.LF.
      '</tr>'.LF.
      '</table>'.LF);

   $actiontable = t(
      '<table>'.LF.
      '<tr>'.LF.
      '  <td>'._('Bulk mail').'</td>'.LF.
      '  <td>'.getMailTemplateSelection('template', 'null',
                   array(_('Do not send mail')=>'')).'</td>'.LF.
      '</tr>'.LF.
      '<tr>'.LF.
      '  <td>&nbsp;</td>'.LF.
      '  <td><input type="submit" name="action" value="'._('Prepare').'"></td>'.LF.
      '</tr>'.LF.
      '</table>'.LF);

   $out = t(
      '<table>'.LF.
      '<tr valign="top">'.LF.
      '  <td %style>%updatetable</td>'.LF.
      '  <td style="padding-left:10px">%actiontable</td>'.LF.
      '</tr>'.LF.
      '</table>'.LF.
      '</form>', array(
         '%actiontable'=>$actiontable,
         '%updatetable'=>$updatetable,
         '%style'=>$style
      ));
   return $out;
}

/** User interface component for editing external incident ids.
 * \param [in] $incidentid  Identifier of the incident
 *
 * \return false on failure, true on success.
 */
function edit_externalids($incidentid='') {
   if (!is_numeric($incidentid)) {
      return false;
   }
   pageHeader(t('External incident identifiers of %id', array(
      '%id'=>normalize_incidentid($incidentid))));

   $incident = getIncident($incidentid);
   $out = '<h2>Basic incident data</h2>';
   $out .= '<table>';
   $out .= t('<tr><td>Incident ID</td><td>%incidentid</td></tr>', array(
      '%incidentid'=>normalize_incidentid($incidentid)));
   $out .= t('<tr><td>Type</td><td>%type</td></tr>', array(
      '%type'=>getIncidentTypeDescr($incident['type'])));
   $out .= t('<tr><td>Status</td><td>%status</td></tr>', array(
      '%status'=>getIncidentStatusDescr($incident['status'])));
   $out .= t('<tr><td>State</td><td>%state</td></tr>', array(
      '%state'=>getIncidentStateDescr($incident['state'])));
   $out .= t('<tr valign="top"><td>Logging</td><td><pre>%logging</pre></td></tr>', array(
      '%logging'=>htmlentities($incident['logging'])));
   $out .= '</table>';
   $out .= t('<a href="%url?action=details&incidentid=%id">Back to details</a>',
      array('%url'=>$_SERVER['PHP_SELF'], '%id'=>urlencode($incidentid)));

   $out .= '<h2>External identifiers</h2>';
   foreach (getExternalIncidentIDS($incidentid) as $extid) {
      $out .= t('<a href="%url?action=delete_extid&incidentid=%id&extid=%extid">Remove</a> ', array(
         '%url'=>$_SERVER['PHP_SELF'], 
         '%id' => $incidentid, 
         '%extid' => urlencode($extid)));
      $out .= $extid."<br/>\n";
   }
   $out .= '<p/><form>';
   $out .= t('<input type="hidden" name="incidentid" value="%id">', array(
      '%id'=>urlencode($incidentid)));
   $out .= 'New identifer: <input type="text" name="extid" size="25">';
   $out .= '<input type="submit" name="action" value="Add external identifier">';
   $out .= '</form>';
   print $out;
   pageFooter();
}


///// PAGE GENERATION STARTS HERE //////////////////////////////////////////

$action = fetchFrom('REQUEST','action');
defaultTo($action,'list');

switch ($action) {

  //--------------------------------------------------------------------
  case _('Prepare'):
     // Send bulk mail for the selected incidents.
     $massincidents = fetchFrom('REQUEST','massincidents[]');
     if (empty($massincidents)) {
        // Nothing selected, show list again.
        Header("Location: $_SERVER[PHP_SELF]");
        break;
     }
     $agenda = implode(',', $massincidents);

     $template = fetchFrom('REQUEST','template');
     defaultTo($template,_('Do not send mail'));
     if ($template==_('Do not send mail')) {
        // No template selected, show list again.
        Header("Location: $_SERVER[PHP_SELF]");
        break;
     }

     Header("Location: mailtemplates.php?action=prepare&".
               "template=$template&agenda=$agenda");
     break;

  //--------------------------------------------------------------------
  case _('Show Details'):
  case 'details':
    $incidentid = fetchFrom('REQUEST','incidentid','%d');
    print updateCheckboxes();

    /* Prevent cross site scripting in incidentid. */
    $norm_incidentid = normalize_incidentid($incidentid);
    $incidentid = decode_incidentid($norm_incidentid);
    if (!getIncident($incidentid)) {
      pageHeader(_('Invalid incident'));
      printf(_('Requested incident (%s) does not exist.'),
             $norm_incidentid);
      pageFooter();
      exit;
    }
    $_SESSION['incidentid'] = $incidentid;

    pageHeader(_('Incident details: ').$norm_incidentid);
    $output = '<div class="externalids" width="100%">';
    $output .= t('(<a href="%url?action=edit_extid&'.
                 'incidentid=%incidentid">'._('Edit').'</a>) ',
            array(
       '%url'=>$_SERVER['PHP_SELF'], '%incidentid'=>urlencode($incidentid)));
    $output .= _('External identifiers').': ';
    $output .= implode(',', getExternalIncidentIDs($incidentid));
    $output .= '</div>';
    $output .= formatEditForm();
    $output .= '<hr/>'.LF;
    $output .= '<h3>'._('History').'</h3>'.LF;
    generateEvent('historyshowpre', array('incidentid'=>$incidentid));
    $output .= formatIncidentHistory($incidentid);
    generateEvent('historyshowpost', array('incidentid'=>$incidentid));

    $output .= '<p/>'.LF;
    $output .= '<form action="'.$_SERVER['PHP_SELF'].'" method="post">'.LF;
    $output .= '<input type="hidden" name="action" value="addcomment">'.LF;
    $output .= '<table bgcolor="#DDDDDD" border=0 cellpadding=2>'.LF;
    $output .= '<tr>'.LF;
    $output .= '  <td>'._('New comment').': </td>'.LF;
    $output .= '  <td><input type="text" size=45 name="comment"></td>'.LF;
    $output .= '  <td><input type="submit" value="'._('Add').'"></td>'.LF;
    $output .= '</tr>'.LF;
    $output .= '</table>'.LF;
    $output .= '</form>'.LF;

    print $output;
    break;

    //---------------------------------------------------------------
    case _('Create New Incident'):
    case 'new':
      PageHeader(_('New Incident'));
      $check = false;
      $output = updateCheckboxes();
      $output .= '<form name="jsform" action="'.$_SERVER['PHP_SELF'].
                 '" method="POST">'.LF;

      $output .= formatIncidentForm($check);

      $output .= '<input type="submit" name="action" value="'._('Add').'">'.LF;
      $output .= t('<input type="checkbox" name="sendmail" %checked>'.LF,
         array('%checked'=>($check==false)?'':'CHECKED'));
      $output .= _('Check to prepare mail.').LF;
      $output .= '</form>'.LF;
      print $output;
      break;
    //--------------------------------------------------------------------
    case _('Create Many Incidents'):
      PageHeader("Bulk incidents");
      $check = false;
      $output = updateCheckboxes();
      $output .= "<form name=\"jsform\" action=\"$_SERVER[PHP_SELF]\" method=\"POST\">\n";

      $output .= formatIncidentBulkForm($check);

      $output .= "<input type=\"submit\" name=\"action\" value=\"Addbulk\">\n";
           
      $output .= "</form>\n";
      print $output;
      break;


    //---------------------------------------------------------------------
    case "Addbulk":
      $addresses = $constituency = $type = $state = $status = $email =
		   $addressrole = $logging = '';
     
      if (array_key_exists("addressrole", $_POST)) {
         $addressrole=$_POST["addressrole"];
      }
     
      if (array_key_exists("constituency", $_POST)) {
        $constituency=$_POST["constituency"];
      }
      if (array_key_exists("type", $_POST)) {
        $type=$_POST["type"];
      }
      if (array_key_exists("state", $_POST)) {
        $state=$_POST["state"];
      }
      if (array_key_exists("status", $_POST)) {
        $status=$_POST["status"];
      }
      if (array_key_exists("logging", $_POST)) {
        $logging=trim($_POST["logging"]);
      }

      if (array_key_exists("addresses", $_POST)) {
         $addresses=$_POST["addresses"];      
         $addresslist = split("\r?\n",$addresses);

         foreach($addresslist as $address) {
        
            // make sure we have an IP address here
            $address = @gethostbyname($address);
            if($address) {
               $incidentid = createIncident($state,$status,$type,$logging);
               addIPtoIncident($address,$incidentid,$addressrole);
	    }
         }
      }
      Header("Location: $_SERVER[PHP_SELF]");
      break;

    //---------------------------------------------------------------------
    case "Add":
      // Create a new incident from edit form.
      $address = $constituency = $type = $state = $status = $email =
		  $addressrole = $logging = '';
      $addifmissing = $sendmail = 'off';

      $addressrole  = fetchFrom('POST','addressrole');
      $address      = fetchFrom('POST','address');
      $address = @gethostbyname($address);
      $constituency = fetchFrom('POST','constituency');
      $type         = fetchFrom('POST','type');
      $state        = fetchFrom('POST','state');
      $status       = fetchFrom('POST','status');
      $sendmail     = fetchFrom('POST','sendmail');
      $email        = fetchFrom('POST','email');
      if ($email!='') {
# NOTE: this may be a list of addresss; looks not good.
         $_SESSION['current_email'] = trim(strtolower($email));
      }
      $addif        = fetchFrom('POST','addifmissing');
      $logging      = fetchFrom('POST','logging');

      $incidentid   = createIncident($state,$status,$type,$logging);
      addIPtoIncident($address,$incidentid,$addressrole);

      if ($email != '') {
         foreach (explode(',', $email) as $addr) {
            $addr = trim($addr);
            $user = getUserByEmail($addr);
            if (!$user) {
               if ($addif == 'on') {
                  addUser(array('email'=>$addr));
                  $user = getUserByEmail($addr);
                  addUserToIncident($user['id'], $incidentid);
               } else {
                  pageHeader(_('Unable to add user to incident.'));
                  printf('<p>'.LF.
_('The e-mail address specified in the incident data entry form (%s) is unknown
to AIRT and you chose not to add it to the database.').'</p>'.LF.'<p>'.
_('The incident has been created, however no users have been associated with
it.').'</p>'.LF.'<p><a href="'.$_SERVER['PHP_SELF'].'">'.
_('Continue').'...</a>'.LF,
                     $addr);
                  pageFooter();
                  exit;
               }
           } else addUserToIncident($user['id'], $incidentid);
         }
      }

      if ($sendmail == 'on') Header('Location: mailtemplates.php');
      else Header("Location: $_SERVER[PHP_SELF]");
        break;

   //--------------------------------------------------------------------
   case 'toggle':
      // Flip the column of check boxes.
      $toggle = fetchFrom('REQUEST','toggle');
      defaultTo($toggle,0);
      $toggle = ($toggle == 0) ? 1 : 0;
      // Break omitted on purpose.

    //--------------------------------------------------------------------
    case 'list':
      pageHeader(_('Incident Overview'));
      print updateCheckboxes();

      generateEvent('incidentlistpre');
      print formatListOverviewHeader();
      print '<hr/>';
      print formatListOverviewBody();
      print '<hr/>';
      print formatListOverviewFooter();

      generateEvent('incidentlistpost');
      pageFooter();
      break;

   //--------------------------------------------------------------------
   case 'addip':
      if (array_key_exists('incidentid', $_SESSION)) {
         $incidentid = $_SESSION['incidentid'];
      } else {
         airt_error('PARAM_MISSING', 'incident.php:'.__LINE__);
         Header("Location: $_SERVER[PHP_SELF]");
         return;
      }
      if (array_key_exists('ip', $_POST)) {
         $ip = gethostbyname($_POST['ip']);
      } else {
         airt_error('PARAM_MISSING', 'incident.php:'.__LINE__);
         Header("Location: $_SERVER[PHP_SELF]");
         return;
      }
      if (array_key_exists('addressrole', $_POST)) {
         $addressrole = $_POST['addressrole'];
      } else {
         airt_error('PARAM_MISSING', 'incident.php:'.__LINE__);
         Header("Location: $_SERVER[PHP_SELF]");
         return;
      }
      generateEvent('addiptoincident', array(
         'incidentid' => $incidentid,
         'ip'         => $ip,
         'addressrole'=> $addressrole
      ));
      if (trim($ip) != '') {
         addIpToIncident(trim($ip), $incidentid, $addressrole);
         addIncidentComment(t(
           _('IP address %ip added to incident with role %role'),
           array(
            '%ip'=>$ip,
            '%role'=>getAddressRoleByID($addressrole))
         ));
      }

      header(sprintf('Location: %s?action=details&incidentid=%s',
         $_SERVER['PHP_SELF'],
         urlencode($incidentid)));
      break;

    //--------------------------------------------------------------------
   case 'editip':
      if (array_key_exists('incidentid', $_SESSION)) {
         $incidentid = $_SESSION['incidentid'];
      } else {
         die(_('Missing information').' (1).');
      }
      if (array_key_exists('ip', $_GET)) {
         $ip = $_GET['ip'];
      } else {
         die(_('Missing information').' (2).');
      }

      pageHeader(_('IP address details'));
      printf(editIPform($incidentid,$ip));
      pageFooter();
      break;

    //--------------------------------------------------------------------
   case 'updateip':
      // Rough sanity check of data.
      if (array_key_exists('id', $_POST)) {
         $id = $_POST['id'];
      } else {
         airt_error('PARAM_MISSING', 'incident.php:'.__LINE__);
         return;
      }
      if (array_key_exists('constituency', $_POST)) {
         $constituency = $_POST['constituency'];
      } else {
         airt_error('PARAM_MISSING', 'incident.php:'.__LINE__);
         return;
      }
      if (array_key_exists('ip', $_POST)) {
         $ip = $_POST['ip'];
      } else {
         airt_error('PARAM_MISSING', 'incident.php:'.__LINE__);
         return;
      }
      if (array_key_exists('incidentid', $_POST)) {
         $incidentid = $_POST['incidentid'];
      } else {
         airt_error('PARAM_MISSING', 'incident.php:'.__LINE__);
         return;
      }
      if (array_key_exists('addressrole', $_POST)) {
         $addressrole = $_POST['addressrole'];
      } else {
         airt_error('PARAM_MISSING', 'incident.php:'.__LINE__);
         return;
      }

      // Update the IP details.
      updateIPofIncident($id, $constituency, $addressrole);

      // Fetch all constituencies and address roles for name lookup.
      $constituencies = getConstituencies();
      $constLabel = $constituencies[$constituency]['label'];

      $addressroles = getAddressRoles();
      $addressRoleLabel = $addressroles[$addressrole];

      // Generate comment and event.
      addIncidentComment(t(
         _('Details of IP address %ip updated; const=%const, addressrole=%role'),
         array(
           '%ip'=>$ip,
           '%const'=>$constLabel,
           '%role'=>$addressRoleLabel)
      ));
      generateEvent('updateipdetails', array(
         'incidentid' => $incidentid,
         'ip'         => $ip,
         'constituency' => $constituency,
         'addressrole' => $addressrole
      ));

      header(sprintf('Location: %s?action=details&incidentid=%s',
         $_SERVER['PHP_SELF'],
         urlencode($incidentid)));
      break;

    //--------------------------------------------------------------------
   case 'deleteip':
      if (array_key_exists('incidentid', $_SESSION)) {
         $incidentid = $_SESSION['incidentid'];
      } else {
         airt_error('PARAM_MISSING', 'incident.php:'.__LINE__);
         Header('Location: '.$_SERVER['PHP_SELF']);
         return;
      }
      if (array_key_exists('ip', $_GET)) {
         $ip = $_GET['ip'];
      } else {
         airt_error('PARAM_MISSING', 'incident.php:'.__LINE__);
         Header('Location: '.$_SERVER['PHP_SELF']);
         return;
      }
      if (array_key_exists('addressrole', $_GET)) {
         $addressrole = $_GET['addressrole'];
      } else {
         airt_error('PARAM_MISSING', 'incident.php:'.__LINE__);
         Header('Location: '.$_SERVER['PHP_SELF']);
         return;
      }

      removeIpFromIncident($ip, $incidentid, $addressrole);
      addIncidentComment(t(
         _('IP address %address (%role) removed from incident.'),
         array(
            '%address'=>$ip,
            '%role'=>getAddressRolebyID($addressrole)
         )));

      generateEvent('removeipfromincident', array(
         'incidentid' => $incidentid,
         'ip'         => $ip,
         'addressrole'=> $addressrole
      ));
      header(sprintf('Location: %s?action=details&incidentid=%s',
         $_SERVER['PHP_SELF'],
         urlencode($incidentid)));
      break;

    //--------------------------------------------------------------------
   case 'adduser':
      if (array_key_exists('email', $_REQUEST)) {
         $email = validate_input($_REQUEST['email']);
      } else {
         die(_('Missing information').' (1).');
      }
      if (array_key_exists("addifmissing", $_REQUEST)) {
         $add = validate_input($_REQUEST["addifmissing"]);
      } else {
         $add = 'off';
      }
      $incidentid = $_SESSION["incidentid"];
      if ($incidentid == '') {
         die(_('Missing information').' (2).');
      }

      $id = getUserByEmail($email);
      if (!$id) {
         if ($add == 'on') {
            addUser(array("email"=>$email));
            $id = getUserByEmail($email);
         } else {
            printf(_('Unknown email address. User not added.'));
            exit();
         }
      }

      $user = getUserByUserID($id['id']);
      addUserToIncident($id['id'], $incidentid);
      addIncidentComment(sprintf(_('User %s added to incident.'),
                                 $user['email']));

      Header(sprintf("Location: %s?action=details&incidentid=%s",
         $_SERVER['PHP_SELF'],
         urlencode($incidentid)));

      break;
   //--------------------------------------------------------------------
   case 'deluser':
      if (array_key_exists("incidentid", $_SESSION)) {
         $incidentid = $_SESSION["incidentid"];
      } else {
         die(_('Missing information').' (1).');
      }
      if (array_key_exists("userid", $_GET)) {
         $userid = $_GET["userid"];
      } else {
         die(_('Missing information').' (2).');
      }

      removeUserFromIncident($userid, $incidentid);
      $user = getUserByUserID($userid);
      addIncidentComment(sprintf(_('User %s removed from incident.'), 
         $user["email"]));

      header(sprintf('Location: %s?action=details&incidentid=%s',
         $_SERVER['PHP_SELF'],
         urlencode($incidentid)));
      break;

   //--------------------------------------------------------------------
   case 'addcomment':
      if (array_key_exists("comment", $_REQUEST)) {
         $comment = $_REQUEST["comment"];
      } else {
         die (_('Missing information').'.');
      }

      addIncidentComment($comment);
      generateEvent("incidentcommentadd", array(
         "comment"=>$comment,
         "incidentid"=>$_SESSION['incidentid']
      ));

      Header(sprintf('Location: %s?action=details&incidentid=%s',
        $_SERVER[PHP_SELF],
        $_SESSION['incidentid']));
      break;

    //--------------------------------------------------------------------
   case 'Update':
   case 'update':
      if (array_key_exists("incidentid", $_SESSION)) {
         $incidentid = $_SESSION["incidentid"];
      } else {
         die(_('Missing information').'.');
      }
      if (array_key_exists("state", $_POST)) {
         $state = $_POST["state"];
      } else {
         die(_('Missing information').' (2).');
      }
      if (array_key_exists("status", $_POST)) {
         $status = $_POST["status"];
      } else {
         die(_('Missing information').' (3).');
      }
      if (array_key_exists("type", $_POST)) {
         $type = $_POST["type"];
      } else {
         die(_('Missing information').' (4).');
      }
      if (array_key_exists("logging", $_POST)) {
         $logging = trim($_POST["logging"]);
      } else {
         die(_('Missing information').' (5).');
      }

      generateEvent("incidentupdate", array(
         "incidentid" => $incidentid,
         "state" => $state,
         "status" => $status,
         "type" => $type
      ));

      updateIncident($incidentid,$state,$status,$type,$logging);

      addIncidentComment(sprintf(_(
        'Incident updated: state=%s, status=%s type=%s'), 
         getIncidentStateLabelByID($state),
         getIncidentStatusLabelByID($status),
         getIncidentTypeLabelByID($type)));

      Header("Location: $_SERVER[PHP_SELF]");
      break;

    //--------------------------------------------------------------------
   case 'showstates':
      generateEvent('pageHeader',
         array('title' => _('Available incident states')));
      $res = db_query('SELECT label, descr
         FROM   incident_states
         ORDER BY label')
      or die(_('Unable to query incident states.'));
      $output = "<script language=\"JavaScript\">\n";
      $output .= "window.resizeTo(800,500);\n";
      $output .= "</script>";
      $output .= "<table>\n";
      while ($row = db_fetch_next($res)) {
         $output .= "<tr>\n";
         $output .= "  <td>$row[label]</td>\n";
         $output .= "  <td>$row[descr]</td>\n";
         $output .= "</tr>\n";
      }
      $output .= "</table>\n";
      print $output;
      break;

   //--------------------------------------------------------------------
   case 'showtypes':
      generateEvent('pageHeader',
         array('title' => _('Available incident types')));
      $res = db_query('SELECT label, descr
         FROM   incident_types
         ORDER BY label')
      or die(_('Unable to query incident types.'));
      $output = "<script language=\"JavaScript\">\n";
      $output .= "window.resizeTo(800,500);\n";
      $output .= "</script>";

      $output .= "<table>\n";
      while ($row = db_fetch_next($res)) {
         $output .= "<tr>\n";
         $output .= "  <td>$row[label]</td>\n";
         $output .= "  <td>$row[descr]</td>\n";
         $output .= "</tr>\n";
      }
      $output .= "</table>\n";
      print $output;
      break;

    //--------------------------------------------------------------------
   case 'showstatus':
      generateEvent('pageHeader',
         array('title' => _('Available incident statuses')));

      $res = db_query('
         SELECT label, descr
         FROM   incident_status
         ORDER BY label')
      or die(_('Unable to query incident statuses.'));
      $output = "<script language=\"JavaScript\">\n";
      $output .= "window.resizeTo(800,500);\n";
      $output .= "</script>";
      $output .= "<table>\n";
      while ($row = db_fetch_next($res)) {
         $output .= "<tr>\n";
         $output .= "  <td>$row[label]</td>\n";
         $output .= "  <td>$row[descr]</td>\n";
         $output .= "</tr>\n";
      }
      $output .= "</table>\n";
      print $output;
      break;

   //--------------------------------------------------------------------
   case 'massupdate':
      // massincidents may be absent, this is how HTML checkboxes work.
      if (array_key_exists('massincidents', $_POST)) {
         $massIncidents = $_POST['massincidents'];
      } else {
         // Nothing checked, nothing to do; disregard command.
         Header("Location: $_SERVER[PHP_SELF]");
      }
      if (array_key_exists('massstate', $_POST)) {
         $massState = $_POST['massstate'];
         if ($massState=='null') {
            $massState = '';
         }
      } else {
         $massState = '';
      }
      if (array_key_exists('massstatus', $_POST)) {
         $massStatus = $_POST['massstatus'];
         if ($massStatus=='null') {
            $massStatus = '';
         }
      } else {
         $massStatus = '';
      }
      if (array_key_exists('masstype', $_POST)) {
         $massType = $_POST['masstype'];
         if ($massType=='null') {
            $massType = '';
         }
      } else {
         $massType = '';
      }

      updateIncidentList($massIncidents,$massState,$massStatus,$massType);

      Header("Location: $_SERVER[PHP_SELF]");
      break;

   //--------------------------------------------------------------------
   case 'Mail':
      if (array_key_exists('agenda', $_REQUEST)) {
         $agenda = $_REQUEST['agenda'];
      } else {
         airt_msg(_(
           'USER ERROR: Must select one or more recipients for mail.'));
         Header("Location: $_SERVER[PHP_SELF]?action=details&incidentid=$_SESSION[incidentid]");
         return;
      }
      Header("Location: mailtemplates.php?to=".urlencode(implode(',',$agenda)));
      break;
   //--------------------------------------------------------------------
   case 'Remove':
      if (array_key_exists('incidentid', $_REQUEST)) {
         $incidentid = $_REQUEST['incidentid'];
      } else {
         airt_error('PARAM_MISSING', 'incident.php:'.__LINE__);
         Header("Location: $_SERVER[PHP_SELF]");
         return;
      }
      if (array_key_exists('agenda', $_REQUEST)) {
         $agenda = $_REQUEST['agenda'];
      } else {
         airt_error('PARAM_MISSING', 'incident.php:'.__LINE__);
         Header("Location: $_SERVER[PHP_SELF]");
         return;
      }
      foreach ($agenda as $userid) {
         $user = getUserByUserId($userid);
         removeUserFromIncident($userid, $incidentid);
         addIncidentComment(sprintf(_('User %s removed from incident.'), 
            $user["email"]));
      }
      Header("Location: $_SERVER[HTTP_REFERER]");
      break;
   //--------------------------------------------------------------------
   case 'edit_extid':
      if (array_key_exists('incidentid', $_REQUEST)) {
         $incidentid = $_REQUEST['incidentid'];
      } else {
         airt_error('PARAM_MISSING', 'incident.php:'.__LINE__);
         Header("Location: $_SERVER[PHP_SELF]");
         return;
      }
      edit_externalids($incidentid);
      break;

   //--------------------------------------------------------------------
   case 'delete_extid':
      if (array_key_exists('incidentid', $_REQUEST)) {
         $incidentid = $_REQUEST['incidentid'];
      } else {
         airt_error('PARAM_MISSING', 'incident.php:'.__LINE__);
         Header("Location: $_SERVER[PHP_SELF]");
         return;
      }
      if (array_key_exists('extid', $_REQUEST)) {
         $extid = $_REQUEST['extid'];
      } else {
         airt_error('PARAM_MISSING', 'incident.php:'.__LINE__);
         Header("Location: $_SERVER[PHP_SELF]");
         return;
      }
      deleteExternalIncidentIDs($incidentid, $extid);
      Header("Location: $_SERVER[PHP_SELF]?action=edit_extid&incidentid=".
         urlencode($incidentid));
      break;
   //--------------------------------------------------------------------
   case 'Add external identifier':
   case 'add_extid':
      if (array_key_exists('incidentid', $_REQUEST)) {
         $incidentid = $_REQUEST['incidentid'];
      } else {
         airt_error('PARAM_MISSING', 'incident.php:'.__LINE__);
         Header("Location: $_SERVER[PHP_SELF]");
         return;
      }
      if (array_key_exists('extid', $_REQUEST)) {
         $extid = $_REQUEST['extid'];
      } else {
         airt_error('PARAM_MISSING', 'incident.php:'.__LINE__);
         Header("Location: $_SERVER[PHP_SELF]");
         return;
      }
      addExternalIncidentIDs($incidentid, $extid);
      Header("Location: $_SERVER[PHP_SELF]?action=edit_extid&incidentid=".
         urlencode($incidentid));
      break;

   //--------------------------------------------------------------------
   default:
      die(_('Unknown action'));
}

?>
