<?php
/* vim:syntax=php shiftwidth=3 tabstop=3
 *
 * AIRT: APPLICATION FOR INCIDENT RESPONSE TEAMS
 * Copyright (C) 2004   Tilburg University, The Netherlands

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
   global $toggle;

   if (array_key_exists('sortkey', $_REQUEST)) {
      $sortkey = $_REQUEST['sortkey'];
   } else {
      $sortkey = 'incidentid';
   }
   if (array_key_exists('page', $_REQUEST)) {
      $page = $_REQUEST['page'];
   } else {
      $page = 1;
   }
   if (!isset($toggle)) {
      $toggle = 0;
   }
   if (array_key_exists('filter', $_REQUEST)) {
      $filter = $_REQUEST['filter'];
   } else {
      $filter = array();
      $filter['state'] = -1;
      $filter['status'] = -1;
   }
   $urlsuffix=strtr("&page=%page&filter[state]=%sf&filter[status]=%stf&sortkey=%sk&toggle=%t", array(
      '%page'=>$page,
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
}

function formatIncidentForm(&$check) {
   $constituency = $name = $email = $type = $state = $status = $addressrole = "";

   if (array_key_exists("active_ip", $_SESSION)) {
      $address = $_SESSION["active_ip"];
   } else {
      $address = "";
   }
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
   $output .= "<hr/>\n";
   $output .= "<h3>affected ip addresses</h3>\n";
   $output .= "<table cellpadding=\"4\">\n";
   $output .= "<tr>\n";
   $output .= "  <td>hostname or ip address</td>\n";
   $output .= t("  <td><input type=\"text\" size=\"30\" name=\"address\" value=\"%address\">%addressrole</td>\n", array(
      '%address'=>$address,
      '%addressrole'=>getAddressRolesSelection('addressrole', $addressrole)
   ));
   $output .= "</tr>\n";
   $output .= "<tr>\n";
   $output .= "  <td>constituency</td>\n";
   $output .= "  <td>".getConstituencySelection("constituency", $constituency)."</td>\n";
   $output .= "</tr>\n";
   $output .= "</table>\n";
 
   $output .= "<hr/>\n";
   $output .= "<h3>affected users</h3>\n";
   $output .= "<table bgcolor=\"#dddddd\" cellpadding=\"2\" border=\"0\">";
   $output .= "<tr>\n";
   $output .= "  <td>email address of user:</td>\n";
   $output .= "  <td><input onChange=\"updateCheckboxes()\" type=\"text\" size=\"40\" name=\"email\" value=\"$email\"></td>\n";
   $output .= "  <td><a href=\"help.php?topic=incident-adduser\">help</td>\n";
   $output .= "</tr>\n";
   $output .= "</table>\n";

   if ($email != '') {
      $check = true;
   }
   $output .= t("<input type=\"checkbox\" name=\"addifmissing\" %checked>",
      array('%checked'=>($check == false) ? '' : 'checked'));
   $output .= "  if checked, create user if email address unknown\n";

   $output .= "<p/>\n";

   return $output;
} // showincidentform

/* return a formatted string representing an HTML form for editing incident
 * details 
 */
function formatEditForm() {
   $incident = getincident($_SESSION["incidentid"]);
   $type = $incident['type'];
   $state = $incident['state'];
   $status = $incident['status'];
	$logging = $incident['logging'];

   if (array_key_exists("active_ip", $_SESSION)) {
      $address = $_SESSION["active_ip"];
   }
   if (array_key_exists("constituency_id", $_SESSION)) {
      $constituency = $_SESSION["constituency_id"];
   }

   $output = "<form action=\"$_SERVER[PHP_SELF]\" method=\"post\">\n";
   $output .= "<hr/>\n";
   $output .= "<h3>Basic incident data</h3>\n";
   $output .= formatBasicIncidentData($type, $state, $status, $logging);
   $output .= "<input type=\"submit\" name=\"action\" value=\"update\">\n";
   $output .= "</form>";

   $output .= "<hr/>\n";
   $output .= "<h3>Affected IP addresses</h3>\n";
   $output .= "<table cellpadding=\"4\">\n";
   $output .= "<tr>";
   $output .= "   <td>IP Address</td>";
   $output .= "   <td>Hostname</td>";
   $output .= "   <td>Constituency</td>";
   $output .= "   <td>Role in incident</td>";
   $output .= "   <td>Edit</td>";
   $output .= "   <td>Remove</td>";
   $output .= "</tr>";
   $conslist = getConstituencies();

   foreach ($incident['ips'] as $address) {
      $output .= "<tr>\n";
      $output .= sprintf("  <td><a href=\"search.php?action=search&hostname=%s\">%s</a></td>\n",
         urlencode($address['ip']), $address['ip']);
      $_SESSION['active_ip'] = $address['ip'];
      $output .= sprintf("  <td>%s</td>\n",
         $address['hostname']==""?"Unknown":@gethostbyaddr(@gethostbyname($address['ip'])));

      $cons = getConstituencyIDbyNetworkID(categorize($address['ip']));

      $output .= sprintf("  <td>%s</td>\n", $conslist[$cons]['label']);
      $output .= t("  <td>%addressrole</td>\n", array(
         '%addressrole'=>getAddressRoleByID($address['addressrole'])));
      $output .= sprintf("  <td><a href=\"$_SERVER[PHP_SELF]?action=editip&ip=%s\">edit</a></td>\n",
         urlencode($address['ip']));
      $output .= t("  <td><a href=\"$_SERVER[PHP_SELF]?action=deleteip&ip=%ip&addressrole=%addressrole\">remove</a></td>\n", array(
         '%ip'=>urlencode($address['ip']),
         '%addressrole'=>urlencode($address['addressrole'])));
      $output .= "</tr>\n";
   }
   $output .= "</table>\n";
   $output .= "<p/>";
   $output .= "<form action=\"$_SERVER[PHP_SELF]\" method=\"POST\">\n";
   $output .= "<input type=\"hidden\" name=\"action\" value=\"addip\">\n";
   $output .= "<table bgColor=\"#DDDDDD\" cellpadding=\"2\">\n";
   $output .= "<tr>\n";
   $output .= "  <td>IP Address</td>\n";
   $output .= "  <td><input type=\"text\" name=\"ip\" size=\"40\"></td>\n";
   $output .= t('<td>%addressrole</td>', array(
      '%addressrole' => getAddressRolesSelection('addressrole')
   ));
   $output .= "  <td><input type=\"submit\" value=\"Add\"></td>\n";
   $output .= "</tr>\n";
   $output .= "</table>\n";
   $output .= "</form>\n";

   $output .= "<hr/>\n";
   $output .= "<h3>Affected users</h3>\n";
   $output .= '<form>';
   $output .= t('<input type="hidden" name="incidentid" value="%incidentid"',
      array('%incidentid'=>$incident['incidentid']));
   $output .= "<table cellpadding=\"4\">\n";

   // re-initialise active users
   $_SESSION['current_name'] = '';
   $_SESSION['current_email'] = '';
   foreach ($incident["users"] as $user) {
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
      $output .= t('<tr >'."\n");
      $output .= t('  <td><input type="checkbox" name="agenda[]" value="%userid"></td>', array('%userid'=>$user));
      $output .= t('  <td>%email</td>', array('%email'=>$u['email']))."\n";
      $output .= "</tr>\n";
   }
   $output .= "</table>\n";
   $output .= '<input type="submit" name="action" value="Mail">';
   $output .= '<input type="submit" name="action" value="Remove"';
   $output .= '</form>';
   $output .= "<p/>\n";
   if (array_key_exists("current_userid", $_SESSION)) {
      $userid = $_SESSION["current_userid"];
      $u = getUserByUserID($userid);
      if (sizeof($u) > 0) {
         $lastname = $u[0]["lastname"];
         $email = $u[0]["email"];
      } else {
        $userid = "";
      }
   } else {
     $userid = "";
   }
   if (array_key_exists('current_email', $_SESSION)) {
      $email = $_SESSION['current_email'];
   } else {
     $email='';
   }

   $output .= "<form name=\"jsform\" action=\"$_SERVER[PHP_SELF]\" method=\"POST\">\n";
   $output .= "  <input type=\"hidden\" name=\"action\" value=\"adduser\">\n";
   $output .= "  <table bgColor=\"#DDDDDD\" cellpadding=\"2\" border=\"0\">\n";
   $output .= "  <tr>\n";
   $output .= "    <td>Email address of user:</td>\n";
   $output .= "    <td><input onChange=\"updateCheckboxes()\" type=\"text\" size=\"40\" name=\"email\" value=\"$email\"></td>\n";
   $output .= "    <td><input type=\"submit\" value=\"Add\"></td>\n";
   $output .= "    <td><a href=\"help.php?topic=incident-adduser\">help</td>\n";
   $output .= "  </tr>\n";
   $output .= "  </table>\n";
   $output .= t('  <input onChange="updateCheckboxes()" type="checkbox" name="addifmissing" %checked>', array(
      '%checked'=>($email=='')?'':'CHECKED'))."\n";
   $output .= "  If checked, create user if email address unknown\n";

   $output .= "<hr/>\n";
   $output .= "</form>\n";

   return $output;
} // formatEditForm

if (array_key_exists("action", $_REQUEST)) {
  $action=$_REQUEST["action"];
} else {
  $action="list";
}

/* format the filter block */
function formatFilterBlock() {
   if (array_key_exists('filter', $_REQUEST)) {
      $filter = $_REQUEST['filter'];
   } else {
      $filter = array();
      $filter['state'] = -1;
      $filter['status'] = -1;
   }

   $out = t(
      "<FORM method=\"POST\">\n".
      "<table cellpadding=\"3\">\n".
      "<tr>\n".
      "   <td>Status</td>\n".
      "   <td>".
             getIncidentStatusSelection("filter[status]", $filter['status'],
             array("-1"=>"Do not filter")).
      "   </td>\n".
      "</tr>\n".
      "<tr>\n".
      "   <td>State</td>\n".
      "   <td>".
             getIncidentStateSelection("filter[state]", $filter['state'],
             array("-1"=>"Do not filter")).
      "   </td>\n".
      "</tr>\n".
      "</table>\n".
      "<INPUT TYPE=\"submit\" VALUE=\"Filter\">\n".
      "<INPUT TYPE=\"submit\" Name=\"action\" VALUE=\"New incident\">\n".
      "</FORM>\n");
   return $out;
}

function formatDetailBlock() {
   $out = t(
      "<form method=\"post\">".
      "Incident number ".
      "<INPUT TYPE=\"input\" name=\"incidentid\" size=\"14\">\n".
      "<INPUT TYPE=\"submit\" name=\"action\" value=\"Details\">\n".
      "</form>\n");
   return $out;
}

/* return a string containing the list overview header page
 */
function formatListOverviewHeader() {
   $out = t(
      "<table style=\"border-right:1px\">".
      "<tr valign=\"top\">".
      "   <td>%filters</td>".
      "   <td>%details</td>".
      "</tr>".
      "</table>", array(
         '%filters'=>formatFilterBlock(),
         '%details'=>formatDetailBlock()));

   return $out;
}

/* format a pager line. Take the total number of incidents, the current page,
 * and the number of incidents per page as input
 */
function formatPagerLine($page, $numincidents, $pagesize=PAGESIZE) {
   global $sortkey;
   global $filter;

   $urlprefix="&sortkey=$sortkey&filter[status]=$filter[status]&filter[state]=$filter[state]";
   if ($numincidents < $pagesize) {
      return '';
   }
   $out = '';
   $numpages = (int) ceil($numincidents / $pagesize);
   if ($page == 1) {
      $out .= '<strong>Previous</strong>&nbsp;';
   } else {
      $out .= t("<a href=\"%url?page=%prev$urlprefix\">Previous</a>&nbsp;", array(
         '%url'=>$_SERVER['PHP_SELF'],
         '%prev'=>($page-1)));
   }
   for ($i = 1; $i <= $numpages; $i++) {
      if ($i == $page) {
         $out .= "<strong>$i</strong>&nbsp;";
      } else {
         $out .= t("<a href=\"%url?page=$i$urlprefix\">$i</a>&nbsp;", array(
            '%url' => $_SERVER['PHP_SELF']));
      }
   }
   if ($page == $numpages) {
      $out .= '<strong>Next</strong>&nbsp;';
   } else {
      $out .= t("<a href=\"%url?page=%next$urlprefix\">Next</a>&nbsp;", array(
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

   if (array_key_exists('filter', $_REQUEST)) {
      $filter = $_REQUEST['filter'];
   } else {
      $filter = array();
      $filter['status'] = -1;
      $filter['state']= -1;
   }
   if (array_key_exists('sortkey', $_REQUEST)) {
      $sortkey = $_REQUEST['sortkey'];
   } else {
      $sortkey = 'incidentid';
   }
   if (array_key_exists('page', $_REQUEST)) {
      $page = $_REQUEST['page'];
   } else {
      $page = 1;
   }

   $statuses = getIncidentStatus();
   if (array_key_exists($filter['status'], $statuses) && 
      $filter['status'] >= 0) {
      $sqlfilter = " AND s1.label = '".$statuses[$filter['status']]."'";
   } else {
      $sqlfilter = " AND s1.label = 'open'";
   }
   $states = getIncidentStates();
   if (array_key_exists($filter['state'], $states) && 
      $filter['state'] >= 0) {
      $sqlfilter .= " AND s2.label = '".$states[$filter['state']]."'";
   }

   switch ($sortkey) {
      case 'incidentid': $sqlfilter .= " ORDER BY incidentid";
         break;
      case "constituency": $sqlfilter .= " ORDER BY constituency";
         break;
      case "hostname": $sqlfilter .= " ORDER BY hostname";
         break;
      case "status": $sqlfilter .= " ORDER BY status";
         break;
      case "state": $sqlfilter .= " ORDER BY state";
         break;
      case "type": $sqlfilter .= " ORDER BY type";
         break;
      case "lastupdated": $sqlfilter .= " ORDER BY updated";
         break;
   }

   $incidents = getOpenIncidents($sqlfilter);
   if (sizeof($incidents) == 0) {
        return "<I>No incidents.</I>";
   }
   $out = t(
      "<form name=\"listform\" action=\"%url\" method=\"POST\">\n".
      "<INPUT TYPE=\"hidden\" name=\"action\" value=\"massupdate\">\n".
      "<table width=\"100%\">\n".
      "<tr>\n".
      "   <td><input type=\"checkbox\" %checked onChange=\"checkAll()\"></td>\n", 
          array('%url'=>$_SERVER['PHP_SELF'],
                '%checked'=>($toggle==1)?"CHECKED":""));
   $out .= t("   <th>Incident ID\n");
   if ($sortkey == 'incidentid') {
      $out .=  "";
   } else {
      $out .= t("<a href=\"%url?sortkey=incidentid&filter[status]=%sf&filter[state]=%stf&page=%p\">*</a>", 
         array('%url'=>$_SERVER['PHP_SELF'], '%sf'=>$filter['status'],
         '%p'=>$page, '%stf'=>$filter['state']));
   }
   $out .= t("</th>\n");

   $out .= t("   <th>Consituency");
   if ($sortkey == 'constituency') {
      $out .=  "";
   } else {
      $out .= t("<a href=\"%url?sortkey=constituency&filter[status]=%sf&filter[state]=%stf&page=%p\">*</a>",
         array('%url'=>$_SERVER['PHP_SELF'], '%sf'=>$filter['status'], '%stf'=>$filter['state'], '%p'=>$page));
   }
   $out .= t("</th>\n");

   $out .= t("   <th>Hostname");
   if ($sortkey == 'hostname') {
      $out .=  "";
   } else {
      $out .= t("<a href=\"%url?sortkey=hostname&filter[status]=%sf&filter[state]=%stf&page=%p\">*</a>",
         array('%url'=>$_SERVER['PHP_SELF'], '%sf'=>$filter['status'], '%stf'=>$filter['state'],
         '%p'=>$page));
   }
   $out .= t("</th>\n");

   $out .= t("   <th>Status");
   if ($sortkey == 'status') {
      $out .=  "";
   } else {
      $out .= t("<a href=\"%url?sortkey=status&filter[status]=%sf&filter[state]=%stf&page=%p\">*</a>", 
         array('%url'=>$_SERVER['PHP_SELF'], '%sf'=>$filter['status'], '%stf'=>$filter['state'],
         '%p'=>$page));
   }
   $out .= t("</th>\n");

   $out .= t("   <th>State");
   if ($sortkey == 'state') {
      $out .=  "";
   } else {
      $out .= t("<a href=\"%url?sortkey=state&filter[status]=%sf&filter[state]=%stf&page=%p\">*</a>", 
         array('%url'=>$_SERVER['PHP_SELF'], '%sf'=>$filter['status'],
         '%stf'=>$filter['state'], '%p'=>$page));
   }
   $out .= t("</th>\n");

   $out .= t("   <th>Type");
   if ($sortkey == 'type') {
      $out .=  "";
   } else {
      $out .= t("<a href=\"%url?sortkey=type&filter[status]=%sf&filter[state]=%stfpage=%p\">*</a>", 
         array('%url'=>$_SERVER['PHP_SELF'], '%sf'=>$filter['status'],
         '%stf'=>$filter['state'], '%p'=>$page));
   }
   $out .= t("</th>\n");

   $out .= t("   <th>Last updated");
   if ($sortkey == 'lastupdate') {
      $out .=  "";
   } else {
      $out .= t("<a href=\"%url?sortkey=lastupdate&filter[status]=%sf&filter[state]=%stf&page=%p\">*</a>", 
         array('%url'=>$_SERVER['PHP_SELF'], '%sf'=>$filter['status'],
         '%stf'=>$filter['state'], '%p'=>$page));
   }
   $out .= t("</th>\n");
   $out .= t("</tr>\n");

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

      $out .= t("<tr bgcolor=\"%color\">\n".
         "   <td>\n".
         "   <input type=\"checkbox\" name=\"massincidents[]\" %check value=\"%id\"></td>\n".
         "   </td>\n".
         "   <td>\n".
         "   <a href=\"%url?action=details&incidentid=%id\">%incidentid</a>\n".
         "   </td>\n".
         "   <td>%constituency</td>\n".
         "   <td>%hostline</td>\n".
         "   <td>%status</td>\n".
         "   <td>%state</td>\n".
         "   <td>%type</td>\n".
         "   <td>%updated</td>\n".
         "</tr>", array(
            '%color'=> ($count++%2 == 1) ? '#FFFFFF' : '#DDDDDD',
            '%url' => $_SERVER['PHP_SELF'],
            '%id' => $id,
            '%incidentid' => encode_incidentid($id),
            '%constituency' => $constituency,
            '%hostline' => $hostline,
            '%status' => $data['status'],
            '%state' => $data['state'],
            '%type' => $data['type'],
            '%check' => ($toggle == 1) ? "CHECKED" : "",
            '%updated' => Date('d M Y', $data['updated'])));
   } // foreach

   $out .= "</table><p>\n";
   $out .= "<div align=\"center\">".
           formatPagerLine($page, sizeof($incidents)).
           "</div>";
   return $out;
} // formatQueueOverviewBody


function formatListOverviewFooter() {
   $updatetable = t("<table>\n".
      "<tr><td>New State</td><td>".
      getIncidentStateSelection('massstate', 'null',
         array('null'=>'Leave Unchanged')).
      "</td></tr>\n".
      "<tr><td>New Status</td><td>".
      getIncidentStatusSelection('massstatus', 'null',
         array('null'=>'Leave Unchanged')).
      "</td></tr>\n".
      "<tr><td>New Type</td><td>".
      getIncidentTypeSelection('masstype', 'null',
         array('null'=>'Leave Unchanged')).
      "</td></tr>\n".
      "<tr><td>&nbsp;</td><td>".
      "<input type=\"submit\" value=\"Update All Selected\">".
      "</td></tr>\n".
      "</table>\n");

   $actiontable = t("<table>\n".
      "<tr>\n".
      "<td>Bulk mail</td>\n".
      "<td>".getMailTemplateSelection('template', 'null',
         array('Do not send mail'=>''))."</td>\n".
      "</tr>\n".
      "<tr>\n".
      "<td>&nbsp;</td>\n".
      "<td><input type=\"submit\" name=\"action\" value=\"Apply\"></td>\n".
      "</tr>\n".
      "</table>\n");

   $out = t("<table>\n".
      "<tr valign=\"top\">\n".
      "<td>%updatetable</td>\n".
      "<td>%actiontable</td>\n".
      "</tr>\n".
      "</table>\n".
      "</form>", array(
      '%actiontable'=>$actiontable,
      '%updatetable'=>$updatetable
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
   pageHeader(t('Additional incident identifiers of %id', array(
      '%id'=>normalize_incidentid($id))));

   $incident = getIncident($incidentid);
   $out = '<h2>Basic incident data</h2>';
   $out .= '<table>';
   $out .= t('<tr><td>Incident ID</td><td>%incidentid</td></tr>', array(
      '%incidentid'=>normalize_incidentid($id)));
   $out .= t('<tr><td>Type</td><td>%type</td></tr>', array(
      '%type'=>getIncidentTypeDescr($incident['type'])));
   $out .= t('<tr><td>Status</td><td>%status</td></tr>', array(
      '%status'=>getIncidentStatusDescr($incident['status'])));
   $out .= t('<tr><td>State</td><td>%state</td></tr>', array(
      '%state'=>getIncidentStateDescr($incident['state'])));
   $out .= t('<tr><td>Logging</td><td><pre>%logging</pre></td></tr>', array(
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


switch ($action) {
  //--------------------------------------------------------------------
  case "Apply":
     if (array_key_exists('massincidents', $_REQUEST)) {
        $massincidents = $_REQUEST['massincidents'];
     } else {
       break;
     }
     $agenda = implode(',', $massincidents);

     if (array_key_exists('template', $_REQUEST)) {
        if ($_REQUEST['template'] != 'Do not send mail') {
           Header("Location: mailtemplates.php?action=prepare&template=$_REQUEST[template]&agenda=$agenda");
           break;
        }
     }

     Header("Location: $_SERVER[PHP_SELF]");
     break;

  //--------------------------------------------------------------------
  case "Details":
  case "details":
    if (array_key_exists("incidentid", $_REQUEST)) {
      $incidentid=$_REQUEST["incidentid"];
    } else {
      die("Missing information(1).");
    }

    print updateCheckboxes();

    /* prevent cross site scripting in incidentid */
    $norm_incidentid = normalize_incidentid($incidentid);
    $incidentid = decode_incidentid($norm_incidentid);

    if (!getIncident($incidentid)) {
      pageHeader("Invalid incident");
      printf("Requested incident ($norm_incidentid) does not exist.", $norm_incidentid);
      pageFooter();
      exit;
    }
    $_SESSION["incidentid"] = $incidentid;

    pageHeader("Incident details: $norm_incidentid");
    $output = '<div class="externalids" width="100%">';
    $output .= t('(<a href="%url?action=edit_extid&incidentid=%incidentid">Edit</a>) ', array(
       '%url'=>$_SERVER["PHP_SELF"], '%incidentid'=>urlencode($incidentid)));
    $output .= 'Additional identifiers: ';
    $output .= implode(',', getExternalIncidentIDs($incidentid));
    $output .= '</div>';
    $output .= formatEditForm();
    $output .= "<hr/>\n";
    $output .= "<h3>History</h3>\n";

    generateEvent("historyshowpre", array("incidentid"=>$incidentid));
    $output .= formatIncidentHistory($incidentid);
    generateEvent("historyshowpost", array("incidentid"=>$incidentid));

    $output .= "<p/>\n";
    $output .= "<form action=\"$_SERVER[PHP_SELF]\" method=\"post\">\n";
    $output .= "<input type=\"hidden\" name=\"action\" value=\"addcomment\">\n";
    $output .= "<table bgcolor=\"#DDDDDD\" border=\"0\" cellpadding=\"2\">\n";
    $output .= "<tr>\n";
    $output .= "  <td>New comment: </td>\n";
    $output .= "  <td><input type=\"text\" size=\"45\" name=\"comment\"></td>\n";
    $output .= "  <td><input type=\"submit\" value=\"Add\"></td>\n";
    $output .= "</tr>\n";
    $output .= "</table>\n";
    $output .= "</form>\n";

    print $output;
    break;

    //---------------------------------------------------------------
    case "New incident":
    case "new":
      PageHeader("New Incident");
      $check = false;
      $output = updateCheckboxes();
      $output .= "<form name=\"jsform\" action=\"$_SERVER[PHP_SELF]\" method=\"POST\">\n";
      $output .= formatIncidentForm($check);
      $output .= "<input type=\"submit\" name=\"action\" value=\"Add\">\n";
      $output .= t("<input type=\"checkbox\" name=\"sendmail\" %checked>\n",
         array('%checked'=>($check==false)?'':'CHECKED'));
      $output .= "Check to prepare mail.\n";
      $output .= "</form>\n";
      print $output;
      break;

    //--------------------------------------------------------------------
    case "Add":
      $address = $constituency = $type = $state = $status = $email =
		$addressrole = $logging = '';
      $addifmissing = $sendmail = 'off';
      if (array_key_exists("address", $_POST)) {
        $address=$_POST["address"];
      }
      if (array_key_exists("addressrole", $_POST)) {
         $addressrole=$_POST["addressrole"];
      }
      // make sure we have an IP address here
      $address = @gethostbyname($address);
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
      if (array_key_exists("sendmail", $_POST)) {
        $sendmail=$_POST["sendmail"];
      }
      if (array_key_exists("email", $_POST)) {
         $email=trim(strtolower($_POST["email"]));
         $_SESSION['current_email'] = $email;
      }
      if (array_key_exists("addifmissing", $_POST)) {
         $addif=$_POST["addifmissing"];
      }
      if (array_key_exists("logging", $_POST)) {
         $logging=trim($_POST["logging"]);
      }

      $incidentid   = createIncident($state,$status,$type,$logging);
      addIPtoIncident($address,$incidentid,$addressrole);

      if ($email != "") {
         foreach (explode(',', $email) as $addr) {
            $addr = trim($addr);
            $user = getUserByEmail($addr);
            if (!$user) {
               if ($addif == "on") {
                  addUser(array("email"=>$addr));
                  $user = getUserByEmail($addr);
                  addUserToIncident($user["id"], $incidentid);
               } else {
                  pageHeader("Unable to add user to incident.");
                  echo <<<EOF
<p>The e-mail address specified in the incident data entry form is unknown
and you chose not to add it to the database.</p>

<p>The incident has been created, however no users have been associated with
it.</p>

<p><a href="$_SERVER[PHP_SELF]">Continue...</a>
EOF;
                  pageFooter();
                  exit;
               }
           } else addUserToIncident($user["id"], $incidentid);
         }
      }

      if ($sendmail == "on") Header("Location: mailtemplates.php");
      else Header("Location: $_SERVER[PHP_SELF]");
        break;

   //--------------------------------------------------------------------
   case 'toggle':
      if (array_key_exists('toggle', $_REQUEST)) {
         $toggle = $_REQUEST['toggle'];
      } else {
         $toggle = 0;
      }
      $toggle = ($toggle == 0) ? 1 : 0;
      // break omitted on purpose

    //--------------------------------------------------------------------
    case 'list':
      pageHeader("Incident overview");
      print updateCheckboxes();

      generateEvent("incidentlistpre");
      print formatListOverviewHeader();
      print "<hr/>";
      print formatListOverviewBody();
      print "<hr/>";
      print formatListOverviewFooter();

      generateEvent("incidentlistpost");
      pageFooter();
      break;

   //--------------------------------------------------------------------
   case "addip":
      if (array_key_exists("incidentid", $_SESSION)) {
         $incidentid = $_SESSION["incidentid"];
      } else {
         airt_error('PARAM_MISSING', 'incident.php:'.__LINE__);
         Header("Location: $_SERVER[PHP_SELF]");
         return;
      }
      if (array_key_exists("ip", $_POST)) {
         $ip = gethostbyname($_POST["ip"]);
      } else {
         airt_error('PARAM_MISSING', 'incident.php:'.__LINE__);
         Header("Location: $_SERVER[PHP_SELF]");
         return;
      }
      if (array_key_exists("addressrole", $_POST)) {
         $addressrole = $_POST['addressrole'];
      } else {
         airt_error('PARAM_MISSING', 'incident.php:'.__LINE__);
         Header("Location: $_SERVER[PHP_SELF]");
         return;
      }
      generateEvent("addiptoincident", array(
         "incidentid" => $incidentid,
         "ip"         => $ip,
         "addressrole"=> $addressrole
      ));
      if (trim($ip) != "") {
         addIpToIncident(trim($ip), $incidentid, $addressrole);
         addIncidentComment(t('IP address %ip added to incident with role %role', array(
            '%ip'=>$ip,
            '%role'=>getAddressRoleByID($addressrole))));
      }

      Header(sprintf("Location: $_SERVER[PHP_SELF]?action=details&incidentid=%s",
         urlencode($incidentid)));
      break;

    //--------------------------------------------------------------------
   case "editip":
      if (array_key_exists("incidentid", $_SESSION)) {
         $incidentid = $_SESSION["incidentid"];
      } else {
         die("Missing information (1).");
      }
      if (array_key_exists("ip", $_GET)) {
         $ip = $_GET["ip"];
      } else {
         die("Missing information (2).");
      }

      pageHeader("IP address details");
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
      addIncidentComment(t('Details of IP address %ip updated; const=%const, addressrole=%role', array(
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

      Header(sprintf('Location: %s?action=details&incidentid=%s',
         $_SERVER['PHP_SELF'],
         urlencode($incidentid)));
      break;

    //--------------------------------------------------------------------
   case "deleteip":
      if (array_key_exists("incidentid", $_SESSION)) {
         $incidentid = $_SESSION["incidentid"];
      } else {
         airt_error('PARAM_MISSING', 'incident.php:'.__LINE__);
         Header("Location: $_SERVER[PHP_SELF]");
         return;
      }
      if (array_key_exists("ip", $_GET)) {
         $ip = $_GET["ip"];
      } else {
         airt_error('PARAM_MISSING', 'incident.php:'.__LINE__);
         Header("Location: $_SERVER[PHP_SELF]");
         return;
      }
      if (array_key_exists("addressrole", $_GET)) {
         $addressrole = $_GET["addressrole"];
      } else {
         airt_error('PARAM_MISSING', 'incident.php:'.__LINE__);
         Header("Location: $_SERVER[PHP_SELF]");
         return;
      }

      removeIpFromIncident($ip, $incidentid, $addressrole);
      addIncidentComment(t('IP address %address (%role) removed from incident.', array(
         '%address'=>$ip,
         '%role'=>getAddressRolebyID($addressrole))));

      generateEvent("removeipfromincident", array(
         "incidentid" => $incidentid,
         "ip"         => $ip,
         "addressrole"=> $addressrole
      ));
      Header(sprintf("Location: $_SERVER[PHP_SELF]?action=details&incidentid=%s",
         urlencode($incidentid)));
      break;

    //--------------------------------------------------------------------
   case "adduser":
      if (array_key_exists("email", $_REQUEST)) {
         $email = validate_input($_REQUEST["email"]);
      } else {
         die("Missing information (1).");
      }
      if (array_key_exists("addifmissing", $_REQUEST)) {
         $add = validate_input($_REQUEST["addifmissing"]);
      } else {
         $add = 'off';
      }
      $incidentid = $_SESSION["incidentid"];
      if ($incidentid == '') {
         die("Missing information (2).");
      }

      $id = getUserByEmail($email);
      if (!$id) {
         if ($add == 'on') {
            addUser(array("email"=>$email));
            $id = getUserByEmail($email);
         } else {
            printf("Unknown email address. User not added.");
            exit();
         }
      }

      $user = getUserByUserID($id["id"]);
      addUserToIncident($id["id"], $incidentid);
      addIncidentComment(sprintf("User %s added to incident.", $user["email"]));

      Header(sprintf("Location: $_SERVER[PHP_SELF]?action=details&incidentid=%s",
         urlencode($incidentid)));

      break;
   //--------------------------------------------------------------------
   case "deluser":
      if (array_key_exists("incidentid", $_SESSION)) {
         $incidentid = $_SESSION["incidentid"];
      } else {
         die("Missing information (1).");
      }
      if (array_key_exists("userid", $_GET)) {
         $userid = $_GET["userid"];
      } else {
         die("Missing information (2).");
      }

      removeUserFromIncident($userid, $incidentid);
      $user = getUserByUserID($userid);
      addIncidentComment(sprintf("User %s removed from incident.", 
         $user["email"]));

      Header(sprintf("Location: $_SERVER[PHP_SELF]?action=details&incidentid=%s",
         urlencode($incidentid)));
      break;

   //--------------------------------------------------------------------
   case "addcomment":
      if (array_key_exists("comment", $_REQUEST)) {
         $comment = $_REQUEST["comment"];
      } else {
         die ("Missing information.");
      }

      addIncidentComment($comment);
      generateEvent("incidentcommentadd", array(
         "comment"=>$comment,
         "incidentid"=>$_SESSION['incidentid']
      ));

      Header("Location: $_SERVER[PHP_SELF]?action=details&incidentid=$_SESSION[incidentid]");
      break;

    //--------------------------------------------------------------------
   case "Update":
   case "update":
      if (array_key_exists("incidentid", $_SESSION)) {
         $incidentid = $_SESSION["incidentid"];
      } else {
         die("Missing information.");
      }
      if (array_key_exists("state", $_POST)) {
         $state = $_POST["state"];
      } else {
         die("Missing information (2).");
      }
      if (array_key_exists("status", $_POST)) {
         $status = $_POST["status"];
      } else {
         die("Missing information (3).");
      }
      if (array_key_exists("type", $_POST)) {
         $type = $_POST["type"];
      } else {
         die("Missing information (4).");
      }
      if (array_key_exists("logging", $_POST)) {
         $logging = trim($_POST["logging"]);
      } else {
         die("Missing information (5).");
      }

      generateEvent("incidentupdate", array(
         "incidentid" => $incidentid,
         "state" => $state,
         "status" => $status,
         "type" => $type
      ));

      updateIncident($incidentid,$state,$status,$type,$logging);

      addIncidentComment(sprintf("Incident updated: state=%s, ".
         "status=%s type=%s", 
         getIncidentStateLabelByID($state),
         getIncidentStatusLabelByID($status),
         getIncidentTypeLabelByID($type)));

      Header("Location: $_SERVER[PHP_SELF]");
      break;

    //--------------------------------------------------------------------
   case "showstates":
      generateEvent('pageHeader', array('title' => 'Available incident states'));
      $res = db_query("SELECT label, descr
         FROM   incident_states
         ORDER BY label")
      or die("Unable to query incident states.");
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
   case "showtypes":
      generateEvent('pageHeader', array('title' => 'Available incident types'));
      $res = db_query("SELECT label, descr
         FROM   incident_types
         ORDER BY label")
      or die("Unable to query incident types.");
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
   case "showstatus":
      generateEvent('pageHeader', array('title' => 'Available incident statuses'));

      $res = db_query('
         SELECT label, descr
         FROM   incident_status
         ORDER BY label')
      or die("Unable to query incident statuses.");
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
   case "massupdate":
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
         airt_error('PARAM_MISSING', 'incident.php:'.__LINE__);
         Header("Location: $_SERVER[PHP_SELF]");
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
         addIncidentComment(sprintf("User %s removed from incident.", 
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
      die("Unknown action");
}

?>
