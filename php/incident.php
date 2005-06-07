<?php
/*
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

if (array_key_exists("action", $_REQUEST)) $action=$_REQUEST["action"];
else $action="list";

function showBasicIncidentData($type, $state, $status) {
   echo <<<EOF
<hr>
<h3>Basic incident data</h3>
<table>
<tr>
    <td>Incident type</td>
    <td>
EOF;
        showIncidentTypeSelection("type", $type);
        echo <<<EOF
    </td>
</tr>
<tr>
    <td>Incident state</td>
    <td>
EOF;
        showIncidentStateSelection("state", $state);
        echo <<<EOF
    </td>
</tr>
<tr>
    <td>Incident status</td>
    <td>
EOF;
        showIncidentStatusSelection("status", $status);
        echo <<<EOF
    </td>
</tr>
</table>
EOF;
}

function showIncidentForm() {
    $constituency = $name = $email = $type = $state = $status = "";

    if (array_key_exists("active_ip", $_SESSION))
        $address = $_SESSION["active_ip"];
   else $address = "";

    if (array_key_exists("constituency_id", $_SESSION))
        $constituency = $_SESSION["constituency_id"];
      
    if (array_key_exists("current_email", $_SESSION))
        $email = $_SESSION["current_email"];

   showbasicincidentdata($type, $state, $status);
   echo <<<eof
<hr>
<h3>affected ip addresses</h3>
<table cellpadding="4">
<tr>
    <td>hostname or ip address</td>
    <td><input type="text" size="30" name="address" value="$address"></td>
</tr>
<tr>
    <td>constituency</td>
    <td>
eof;
        showconstituencyselection("constituency", $constituency);
      $email = $_SESSION['current_email'];
        echo <<<eof
    </td>
</tr>
</table>

<hr>
<h3>affected users</h3>

<table bgcolor="#dddddd" cellpadding="2" border="0">
<tr>
   <td>email address of user:</td>
   <td><input type="text" size="40" name="email" value="$email"></td>
   <td><a href="help.php?topic=incident-adduser">help</td>
</tr>
</table>
<input type="checkbox" name="addifmissing">
   if checked, create user if email address unknown
<p>
<hr>
eof;
} // showincidentform



function showeditform() {
   $incident = getincident($_SESSION["incidentid"]);
   $type = $incident["type"];
   $state = $incident["state"];
   $status = $incident["status"];

    if (array_key_exists("active_ip", $_SESSION))
        $address = $_SESSION["active_ip"];
    if (array_key_exists("constituency_id", $_SESSION))
        $constituency = $_SESSION["constituency_id"];
   
   echo <<<EOF
<form action="$_SERVER[PHP_SELF]" method="post">
EOF;
   showBasicIncidentData($type, $state, $status);

   echo <<<EOF
<input type="submit" name="action" value="update">
</form>
<HR>
<h3>Affected IP addresses</h3>
<table cellpadding="4">
EOF;
   foreach ($incident['ips'] as $address) {
      printf("
<tr>
   <td><a href=\"search.php?action=search&hostname=%s\">%s</a></td>
   <td>%s</td>
   <td><a href=\"$_SERVER[PHP_SELF]?action=deleteip&ip=%s\">remove</a></td>
</tr>
   ",
      urlencode($address),
      $address,
      $address==""?"Unknown":@gethostbyaddr(@gethostbyname($address)),
      urlencode($address)
      );
   }

   echo <<<EOF
</table>
<p/>

<P/>
<form action="$_SERVER[PHP_SELF]" method="POST">
<input type="hidden" name="action" value="addip">
<table bgColor="#DDDDDD" cellpadding="2">
<tr>
   <td>IP Address</td>
   <td><input type="text" name="ip" size="40"></td>
   <td><input type="submit" value="Add">
   </td>
</tr>
</table>
</form>
EOF;

   echo <<<EOF
<HR>
<h3>Affected users</h3>
<table cellpadding="4">
EOF;
   foreach ($incident["users"] as $user) {
      $u = getUserByUserId($user);
      printf("
<tr>
   <td>%s</td>
   <td><a href=\"mailto:%s\">%s</a></td>
   <td>%s, %s</td>
   <td><a href=\"$_SERVER[PHP_SELF]?action=deluser&userid=%s\">remove</a></td>
</tr>
      ", $u["userid"],
          $u["email"],
         $u["email"],
         $u["lastname"],
         $u["firstname"],
         urlencode($u["id"])
      );
   }

   if (array_key_exists("current_userid", $_SESSION)) {
      $userid = $_SESSION["current_userid"];
      $u = getUserByUserID($userid);
      if (sizeof($u) > 0) {
         $lastname = $u[0]["lastname"];
         $email = $u[0]["email"];
      } else $userid = "";
   } else $userid = ""; 

   if (array_key_exists('current_email', $_SESSION))
      $email = $_SESSION['current_email'];
   else $email='';

   echo <<<EOF
</table>
<p/>

<form action="$_SERVER[PHP_SELF]" method="POST">
<input type="hidden" name="action" value="adduser">

<table bgColor="#DDDDDD" cellpadding="2" border="0">
<tr>
   <td>Email address of user:</td>
   <td><input type="text" size="40" name="email" value="$email"></td>
   <td><input type="submit" value="Add"></td>
   <td><a href="help.php?topic=incident-adduser">help</td>
</tr>
</table>
<input type="checkbox" name="addifmissing">
If checked, create user if email address unknown
</form>
EOF;
         
} // showeditform



switch ($action) {
    //--------------------------------------------------------------------
    case "details":
        if (array_key_exists("incidentid", $_REQUEST))
         $incidentid=$_REQUEST["incidentid"];
        else die("Missing information(1).");

      $norm_incidentid = normalize_incidentid($incidentid);
      $incidentid = decode_incidentid($norm_incidentid);

      if (!getIncident($incidentid)) {
         pageHeader("Invalid incident");
         printf("Requested incident ($norm_incidentid) does not exist.",
            $norm_incidentid);
         pageFooter();
         exit;
      }

      $_SESSION["incidentid"] = $incidentid;

      pageHeader("Incident details: $norm_incidentid");
      showEditForm();

      echo <<<EOF
<hr>
<h3>History</h3>
EOF;
      generateEvent("historyshowpre", array("incidentid"=>$incidentid));
      showIncidentHistory($incidentid);
      generateEvent("historyshowpost", array("incidentid"=>$incidentid));

      echo <<<EOF
<p>
<form action="$_SERVER[PHP_SELF]" method="post">
<input type="hidden" name="action" value="addcomment">
<table bgcolor="#DDDDDD" border=0 cellpadding=2>
<tr>
    <td>New comment: </td>
   <td><input type="text" size="45" name="comment"></td>
   <td><input type="submit" value="Add"></td>
</tr>
</table>
</form>
EOF;

      break;

    //---------------------------------------------------------------
    case "New incident":
    case "new":
        PageHeader("New Incident");
        echo <<<EOF
<form action="$_SERVER[PHP_SELF]" method="POST">
EOF;
        showIncidentForm();
        echo <<<EOF
<input type="submit" name="action" value="Add">
      <input type="checkbox" name="sendmail">
      Check to prepare mail.
</form>
EOF;
        break;

    //--------------------------------------------------------------------
    case "Add":
        if (array_key_exists("address", $_POST)) $address=$_POST["address"];
        else $address="";
      // make sure we have an IP address here
      $address = @gethostbyname($address);
        if (array_key_exists("constituency", $_POST)) 
            $constituency=$_POST["constituency"];
        else $constituency="";
        if (array_key_exists("type", $_POST)) $type=$_POST["type"];
        else $type="";
        if (array_key_exists("state", $_POST)) $state=$_POST["state"];
        else $state="";
        if (array_key_exists("status", $_POST)) $status=$_POST["status"];
        else $status="";
      if (array_key_exists("sendmail", $_POST)) $sendmail=$_POST["sendmail"];
      else $sendmail="off";
      if (array_key_exists("email", $_POST)) {
         $email=strtolower($_POST["email"]);
         $_SESSION['current_email'] = $email;
      }
      else $email="";
      if (array_key_exists("addifmissing", $_POST))
         $addif=$_POST["addifmissing"];
      else $addif="off";

		// TODO: move all db_connets to libraries
      $conn = db_connect(DBDB, DBUSER, DBPASSWD)
      or die("Unable to connect to database.");

        $res = db_query($conn, "begin transaction")
        or die("Unable to execute query 1.");
        db_free_result($res);

        $res = db_query($conn, 
            "select nextval('incidents_sequence') as incidentid")
        or die("Unable to execute query 2.");
        $row = db_fetch_next($res);
        $incidentid = $row["incidentid"];
        $_SESSION["incidentid"] = $incidentid;
        db_free_result($res);

        $res = db_query($conn, sprintf(
            "insert into incidents
             (id, created, creator, updated, updatedby, state, status, type)
             values
             (%s, CURRENT_TIMESTAMP, %s, CURRENT_TIMESTAMP, %s, %s, %s, %s)",
                $incidentid,
                $_SESSION["userid"],
                $_SESSION["userid"],
                $state == "" ? 'NULL' : $state ,
                $status == "" ? 'NULL' : $status ,
                $type == "" ? 'NULL' : $type
            )
        ) or die("Unable to execute query 3.");
        db_free_result($res);
        addIncidentComment("Incident created", "", "", $conn);
        addIncidentComment(sprintf("state=%s, status=%s, type=%s",
           getIncidentStateLabelByID($state),
           getIncidentStatusLabelByID($status),
           getIncidentTypeLabelById($type)), "", "", $conn);

        $res = db_query($conn,
            "select nextval('incident_addresses_sequence') as iaid")
        or die("Unable to execute query 4.");
        $row = db_fetch_next($res);
        $iaid = $row["iaid"];
        db_free_result($res);

      $hostname = @gethostbyaddr($address);
        $res = db_query($conn, sprintf(
            "insert into incident_addresses
             (id, incident, ip, hostname, constituency, added, addedby)
             values
             (%s, %s, %s, %s, %s, CURRENT_TIMESTAMP, %s)",
                $iaid,
                $incidentid,
                db_masq_null($address),
                db_masq_null($hostname),
            db_masq_null($constituency),
                $_SESSION["userid"]
            )
        ) or die("Unable to execute query 5.");
        db_free_result($res);
      addIncidentComment(sprintf("IP address %s added to incident.",
         $address), "", "", $conn);

        $res = db_query($conn, "end transaction");
        db_close($conn);

      generateEvent("newincident", array(
         "incidentid" => $incidentid,
         "ip"         => $address,
         "hostname"   => $hostname,
         "state"      => $state,
         "status"     => $status,
         "type"       => $type
      ));


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
		  // TODO: move all db_connets to libraries
        $conn = db_connect(DBDB, DBUSER, DBPASSWD)
        or die("Unable to connect to database.");

      if (array_key_exists("filter", $_POST)) $filter = $_POST["filter"];
      else $filter = 1;

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
      echo <<<EOF
</SELECT>
<INPUT TYPE="submit" VALUE="Ok">
</FORM>
   </td>
</tr>
</table>


EOF;
      switch ($filter) {
         case 1: $sqlfilter = "AND s2.label = 'open'";
            break;
         case 2: $sqlfilter = "AND s2.label = 'stalled'";
            break;
         case 3: $sqlfilter = "AND s2.label IN ('open', 'stalled')";
            break;
         default:
            $sqlfilter="";
      }

// TODO: move queries to library
        $res = db_query($conn,
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
             AND      i.id = a.incident ".$sqlfilter."
             ORDER BY i.id")
        or die("Unable to execute query (1)");

        if (db_num_rows($res) == 0)
        {
            echo "<I>No incidents.</I>";
        }
        else
        {
            echo <<<EOF
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

            if ($hostname == $hostname2) 
               $hostline = $hostname;
            else
               $hostline = "$hostname **";

            $color = ($count++%2 == 1) ? '#FFFFFF' : '#DDDDDD';
                echo <<<EOF
<tr bgcolor='$color'>
   <td><a href="$_SERVER[PHP_SELF]?action=details&incidentid=$id">details</a></td>
    <td>$incidentid</td>
   <td>$constituency</td>
    <td>$hostline</td>
    <td>$status</td>
    <td>$state</td>
    <td>$type</td>
    <td>$updated</td>
</tr>
EOF;
            } // while
            echo "</table>";
         printf("<P><I>$count incidents displayed.</I><P>");
            db_free_result($res);
            db_close($conn);
        } // else

        echo <<<EOF
<p>
<form action="$_SERVER[PHP_SELF]" method="POST">
<input type="submit" name="action" value="New incident">
</form>
EOF;
      generateEvent("incidentlistpost");
        pageFooter();
        break;

    //--------------------------------------------------------------------
   case "addip":
        if (array_key_exists("incidentid", $_SESSION))
         $incidentid = $_SESSION["incidentid"];
      else die("Missing information (1).");
        if (array_key_exists("ip", $_POST)) $ip = gethostbyname($_POST["ip"]);
      else die("Missing information (2).");

      if (trim($ip) != "") {
         addIpToIncident(trim($ip), $incidentid);
         addIncidentComment(sprintf("IP address %s added to incident.",
         $ip));
      }

      generateEvent("addiptoincident", array(
         "incidentid" => $incidentid,
         "ip"         => $ip
      ));
      Header(sprintf("Location: $_SERVER[PHP_SELF]?action=details&incidentid=%s",
         urlencode($incidentid)));
      break;

    //--------------------------------------------------------------------
   case "deleteip":
        if (array_key_exists("incidentid", $_SESSION))
         $incidentid = $_SESSION["incidentid"];
      else die("Missing information (1).");
        if (array_key_exists("ip", $_GET)) $ip = $_GET["ip"];
      else die("Missing information (2).");

      removeIpFromIncident($ip, $incidentid);
      addIncidentComment(sprintf("IP address %s removed from incident.", 
         $ip));

      generateEvent("remoteipfromincident", array(
         "incidentid" => $incidentid,
         "ip"         => $ip
      ));
      Header(sprintf("Location: $_SERVER[PHP_SELF]?action=details&incidentid=%s",
         urlencode($incidentid)));
      break;


    //--------------------------------------------------------------------
   case "adduser":
      if (array_key_exists("email", $_REQUEST))
         $email = validate_input($_REQUEST["email"]);
      else die("Missing information (1).");
      if (array_key_exists("addifmissing", $_REQUEST))
         $add = validate_input($_REQUEST["addifmissing"]);
      else $add = 'off';
      $incidentid = $_SESSION["incidentid"];
      if ($incidentid == '') die("Missing information (2).");

      $id = getUserByEmail($email);
      if (!$id) {
         if ($add == 'on') {
            addUser(array("email"=>$email));
            $id = getUserByEmail($email);
         }
         else {
            printf("Unknown email address. User not added.");
            exit();
         }
      }
      
      $user = getUserByUserID($id["id"]);
      addUserToIncident($id["id"], $incidentid);
      addIncidentComment(sprintf("User %s added to incident.",
         $user["email"]));
      
      Header(sprintf("Location: $_SERVER[PHP_SELF]?action=details&incidentid=%s",
         urlencode($incidentid)));
      
      break;
    //--------------------------------------------------------------------
   case "deluser":
        if (array_key_exists("incidentid", $_SESSION))
         $incidentid = $_SESSION["incidentid"];
      else die("Missing information (1).");
        if (array_key_exists("userid", $_GET)) $userid = $_GET["userid"];
      else die("Missing information (2).");

      removeUserFromIncident($userid, $incidentid);
      $user = getUserByUserID($userid);
      addIncidentComment(sprintf("User %s removed from incident.", 
         $user["email"]));

      Header(sprintf("Location: $_SERVER[PHP_SELF]?action=details&incidentid=%s",
         urlencode($incidentid)));
      break;

    //--------------------------------------------------------------------
   case "addcomment":
      if (array_key_exists("comment", $_REQUEST)) 
         $comment = $_REQUEST["comment"];
      else die ("Missing information.");

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
      if (array_key_exists("incidentid", $_SESSION)) 
         $incidentid = $_SESSION["incidentid"];
      else die("Missing information.");
      if (array_key_exists("state", $_POST))
         $state = $_POST["state"];
      else die("Missing information (2).");
      if (array_key_exists("status", $_POST))
         $status = $_POST["status"];
      else die("Missing information (3).");
      if (array_key_exists("type", $_POST))
         $type = $_POST["type"];
      else die("Missing information (4).");

      generateEvent("incidentupdate", array(
         "incidentid" => $incidentid,
         "state" => $state,
         "status" => $status,
         "type" => $type
      ));

		// TODO: move all db_connets to libraries
      $conn = db_connect(DBDB, DBUSER, DBPASSWD)
      or die("Unable to connect to database.");
      $res = db_query($conn, sprintf("
         UPDATE incidents
         SET    state=%s,
               status=%s,
               type=%s,
               updated=CURRENT_TIMESTAMP,
               updatedby=%s
         WHERE  id = %s",
         $state, $status, $type, $_SESSION["userid"], $incidentid))
      or die("Unable to update incident.");
      db_close($conn);

      addIncidentComment(sprintf("Incident updated: state=%s, ".
         "status=%s type=%s", 
         getIncidentStateLabelByID($state),
         getIncidentStatusLabelByID($status),
         getIncidentTypeLabelByID($type)));

      Header("Location: $_SERVER[PHP_SELF]");
      break;

    //--------------------------------------------------------------------
    default:
        die("Unknown action");
}

?>
