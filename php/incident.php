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

function updateCheckboxes() {
   $output = "<SCRIPT Language=\"JavaScript\">\n";
   $output .= "function updateCheckboxes() {\n";
   $output .= "   if (!(document.jsform.email.value == '')) {\n";
   $output .= "      document.jsform.addifmissing.checked = true;\n";
   $output .= "      if (typeof document.jsform.sendmail != \"undefined\") document.jsform.sendmail.checked = true;\n";
   $output .= "   } else {\n";
   $output .= "      document.jsform.addifmissing.checked = false;\n";
   $output .= "   }\n";
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
   $output .= "   <td>Role in incident</td>";
   $output .= "   <td>Edit</td>";
   $output .= "   <td>Remove</td>";
   $output .= "</tr>";

   foreach ($incident['ips'] as $address) {
      $output .= "<tr>\n";
      $output .= sprintf("  <td><a href=\"search.php?action=search&hostname=%s\">%s</a></td>\n",
         urlencode($address['ip']), $address['ip']);
      $_SESSION['active_ip'] = $address['ip'];
      $output .= sprintf("  <td>%s</td>\n",
         $address['hostname']==""?"Unknown":@gethostbyaddr(@gethostbyname($address['ip'])));
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
   $output .= "<table cellpadding=\"4\">\n";
   $output .= "<tr>\n";
   $output .= "   <td>Email address</td>\n";
   $output .= "   <td>Mail from template</td>\n";
   $output .= "   <td>Remove from incident</td>\n";
   $output .= "</tr>\n";
   $count = 0;
   foreach ($incident["users"] as $user) {
      $u = getUserByUserId($user);
      $output .= t('<tr >'."\n");
      $output .= t('  <td>%email</td>', array('%email'=>$u['email']))."\n";
      $output .= '  <td><a href="standard.php">Select template</a></td>';
      $output .= t('  <td><a href="%url">Remove</a></td>', array(
         '%url'=>"$_SERVER[PHP_SELF]?action=deluser&userid=".urlencode($u['id'])
      ));
      $output .= "</tr>\n";
   }
   $output .= "</table>\n";
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

switch ($action) {
  //--------------------------------------------------------------------
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
    $output = formatEditForm();
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
         $user = getUserByEmail($email);
         if (!$user) {
            if ($addif == "on") {
               addUser(array("email"=>$email));
               $user = getUserByEmail($email);
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

      if ($sendmail == "on") Header("Location: standard.php");
      else Header("Location: $_SERVER[PHP_SELF]");
        break;


    //--------------------------------------------------------------------
    case "list":
      pageHeader("Incident overview");
      # $conn = db_connect(DBDB, DBUSER, DBPASSWD)
      # or die("Unable to connect to database.");

      if (array_key_exists("filter", $_POST)) {
         $filter = $_POST["filter"];
      } else {
         $filter = 1;
      }

      generateEvent("incidentlistpre");
      echo <<<EOF
<table cellpadding="3">
<tr>
   <td>
Enter incident number
   </td>
   <td>
<FORM action="$_SERVER[PHP_SELF]" method="POST">
<INPUT TYPE="hidden" name="action" value="details">
<INPUT TYPE="input" name="incidentid" size="14">
<INPUT TYPE="submit" value="Details">
</FORM>
   </td>
</tr>

<tr>
   <td>
Select incident status
   </td>
   <td>
<FORM action="$_SERVER[PHP_SELF]" method="POST">
<INPUT TYPE="hidden" name="action" value="list">
 <SELECT name="filter">
EOF;
      echo choice("open", 1, $filter);
      echo choice("stalled", 2, $filter);
      echo choice("open or stalled", 3, $filter);
      echo choice("closed", 4, $filter);
      echo choice("all", 5, $filter);
      echo <<<EOF
</SELECT>
<INPUT TYPE="submit" VALUE="Ok">
</FORM>
   </td>
</tr>
</table>
EOF;

        echo <<<EOF
<p>
<form action="$_SERVER[PHP_SELF]" method="POST">
<input type="submit" name="action" value="New incident">
</form>
EOF;

      switch ($filter) {
         case 1: $sqlfilter = "AND s2.label = 'open'";
            break;
         case 2: $sqlfilter = "AND s2.label = 'stalled'";
            break;
         case 3: $sqlfilter = "AND s2.label IN ('open', 'stalled')";
            break;
         case 4: $sqlfilter = "AND s2.label = 'closed'";
            break;
         case 5: $sqlfilter = "AND s2.label IN ('open', 'stalled', 'closed')";
            break;
         default:
            $sqlfilter="";
      }

      $res = db_query(
        "SELECT   i.id      as incidentid, 
                  extract(epoch from i.created) as created,
                  extract(epoch from i.updated) as updated,
                  u1.login  as creator, 
                  u2.login  as updater, 
                  s1.label  as state, 
                  s2.label  as status, 
                  c3.label  as constituency,
                  t.label   as type,
                  a.ip      as ip,
                  a.hostname as hostname
         FROM     incidents i, users u1, users u2,
                  incident_states s1, incident_status s2, 
                  incident_types t, incident_addresses a, 
                  constituencies c3
         WHERE    i.creator = u1.id
         AND      i.updatedby = u2.id
         AND      a.constituency = c3.id
         AND      i.state = s1.id
         AND      i.status = s2.id
         AND      i.type = t.id
         AND      i.id = a.incident $sqlfilter
         ORDER BY i.id")
        or die("Unable to execute query (1)");

        if (db_num_rows($res) == 0) {
           echo "<I>No incidents.</I>";
        } else {
           echo <<<EOF
<form action="$_SERVER[PHP_SELF]" method="POST">
<INPUT TYPE="hidden" name="action" value="massupdate">
<table width='100%'>
<tr>
    <td>&nbsp;</td>
    <th>Incident ID</th>
    <th>Consituency</th>
    <th>Hostname</th>
    <th>Status</th>
    <th>State</th>
    <th>Type</th>
    <th>Last updated</th>
</tr>
EOF;
           $count = 0;
           while ($row = db_fetch_next($res)) {
              $id      = $row["incidentid"];
              $ip      = $row["ip"];
              $hostname= $row["hostname"];
              $hostname2=@gethostbyaddr($ip);
              $updated = Date("d M Y", $row["updated"]);
              $status  = $row["status"];
              $state   = $row["state"];
              $type    = $row["type"];
              $constituency = $row["constituency"];
              $incidentid = encode_incidentid($id);

              if ($hostname == $hostname2) {
                 $hostline = $hostname;
              } else {
                 $hostline = "$hostname **";
              }

              $color = ($count++%2 == 1) ? '#FFFFFF' : '#DDDDDD';
              echo <<<EOF
<tr bgcolor='$color'>
   <td><input type="checkbox" name="massincidents[]" value="$id"></td>
   <td><a href="$_SERVER[PHP_SELF]?action=details&incidentid=$id">$incidentid</a></td>
   <td>$constituency</td>
    <td>$hostline</td>
    <td>$status</td>
    <td>$state</td>
    <td>$type</td>
    <td>$updated</td>
</tr>
EOF;
           } // while

           echo "</table><p>\n";
           // Create block below the incident list that allows mass updates.
           echo "<table>\n";
           echo "<tr><td>New State</td><td>";
           echo getIncidentStateSelection(
              'massstate',
              'null',
              array('null'=>'Leave Unchanged'));
           echo "</td></tr>\n";
           echo "<tr><td>New Status</td><td>";
           echo getIncidentStatusSelection(
              'massstatus',
              'null',
              array('null'=>'Leave Unchanged'));
         echo "</td></tr>\n";
         echo "<tr><td>&nbsp;</td><td>";
         echo "<input type=\"submit\" value=\"Update All Selected\">";
         echo "</td></tr>\n";
         echo "</table>\n";

         echo "</form>";
         printf("<P><I>$count incidents displayed.</I><P>");
         db_free_result($res);
         # db_close($conn);
        } // else

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
      # $conn = db_connect(DBDB, DBUSER, DBPASSWD)
      # or die("Unable to connect to database.");
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
      # db_close($conn);
      break;

   //--------------------------------------------------------------------
   case "showtypes":
      generateEvent('pageHeader', array('title' => 'Available incident types'));
      # $conn = db_connect(DBDB, DBUSER, DBPASSWD)
      # or die("Unable to connect to database.");
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
      # db_close($conn);
      break;

    //--------------------------------------------------------------------
   case "showstatus":
      generateEvent('pageHeader', array('title' => 'Available incident statuses'));

      # $conn = db_connect(DBDB, DBUSER, DBPASSWD)
      # or die("Unable to connect to database.");
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
      # db_close($conn);
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

      updateIncidentList($massIncidents,$massState,$massStatus);

      Header("Location: $_SERVER[PHP_SELF]");
      break;

   //--------------------------------------------------------------------
   default:
      die("Unknown action");
}

?>
