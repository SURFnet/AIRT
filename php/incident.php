<?php
/*
 * AIRT: APPLICATION FOR INCIDENT RESPONSE TEAMS
 * Copyright (C) 2004	Tilburg University, The Netherlands

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
require_once '/etc/airt/airt.cfg';
require_once LIBDIR.'/airt.plib';
require_once LIBDIR.'/database.plib';
require_once LIBDIR.'/constituency.plib';
require_once LIBDIR.'/incident.plib';
require_once LIBDIR.'/history.plib';
require_once LIBDIR.'/user.plib';

if (array_key_exists("action", $_REQUEST)) $action=$_REQUEST["action"];
else $action="list";

$SELF="incident.php";

function choice($label, $value, $default)
{
    if ($value == $default)
        return sprintf("<OPTION value='%s' SELECTED>%s</OPTION>\n",
            $value, $label);
    else
        return sprintf("<OPTION value='%s'>%s</OPTION>\n",
            $value, $label);
}


function footer()
{
    printf("<P><a href=\"%s?action=new\">Create new incident</a>", $SELF);
    printf("&nbsp;|&nbsp");
    printf("<a href=\"%s?action=list\">List incidents</a>", $SELF);
}


function showBasicIncidentData($type, $state, $status) {
	echo <<<EOF
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
    $constituency = $name = $email = $type = $state = $states = "";
    if (array_key_exists("active_ip", $_SESSION))
        $address = $_SESSION["active_ip"];
    if (array_key_exists("constituency_id", $_SESSION))
        $constituency = $_SESSION["constituency_id"];
    if (array_key_exists("current_name", $_SESSION))
        $name = $_SESSION["current_name"];
    if (array_key_exists("current_email", $_SESSION))
        $email = $_SESSION["current_email"];

	showBasicIncidentData($type, $state, $status);
	echo <<<EOF
<h3>Affected IP addresses</h3>
<table cellpadding="4">
<tr>
    <td>Hostname or IP address</td>
    <td><input type="text" size="30" name="address" value="$address"></td>
</tr>
<tr>
    <td>Constituency</td>
    <td>
EOF;
        showConstituencySelection("constituency", $constituency);
        echo <<<EOF
    </td>
</tr>
</table>

<h3>Affected users</h3>
<table>
<tr>
    <td>User's name</td>
    <td><input type="text" size="30" value="$name" name="name"></td>
</tr>
<tr>
    <td>User's email</td>
    <td><input type="text" size="30" value="$email" name="email"></td>
</tr>
</table>

EOF;
} // showIncidentForm



function showEditForm() {

	$incident = getIncident($_SESSION["incidentid"]);
	$type = $incident["type"];
	$state = $incident["state"];
	$status = $incident["status"];

    if (array_key_exists("active_ip", $_SESSION))
        $address = $_SESSION["active_ip"];
    if (array_key_exists("constituency_id", $_SESSION))
        $constituency = $_SESSION["constituency_id"];
	
	echo <<<EOF
<form action="$SELF" method="POST">
EOF;
	showBasicIncidentData($type, $state, $status);

	echo <<<EOF
<input type="submit" name="action" value="Update">
</form>
<HR>
<h3>Affected IP addresses</h3>
<table cellpadding="4">
EOF;
	foreach ($incident["ips"] as $address) {
		printf("
<tr>
	<td><a href=\"search.php?action=search&hostname=%s\">%s</a></td>
	<td>%s</td>
	<td><a href=\"$SELF?action=deleteip&ip=%s\">remove</a></td>
</tr>
	",
		urlencode($address),
		$address,
		$address==""?"Unknown":gethostbyaddr(gethostbyname($address)),
		urlencode($address)
		);
	}
	echo <<<EOF
	</table>
	<p/>
	Enter IP address or hostname to add to this incident.

	<P/>
	<form action="$SELF" method="POST">
	<input type="hidden" name="action" value="addip">
	<table cellpadding=4>
	<tr>
		<td>IP Address</td>
		<td><input type="text" name="ip" size="30" value="$address"></td>
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
	<td>anr/td>
	<td><a href=\"mailto:%s\">%s</a></td>
	<td>%s %s</td>
	<td><a href=\"$SELF?action=deleteuser&userid=%s\">remove</a></td>
</tr>
		", $u["email"],
		   $u["email"],
		   $u["lastname"],
		   $u["firstname"],
		   urlencode($incidentid),
		   urlencode($user)
		);
	}

	if (array_key_exists("current_userid", $_SESSION)) {
		$userid = $_SESSION["current_userid"];
		$u = getUserByUserID($userid);
		if (sizeof($u) > 0)
		{
			$lastname = $u[0]["lastname"];
			$email = $u[0]["email"];
		} else {
			$userid = "";
		}
	} else { 
		$userid = ""; 
	}

	echo <<<EOF
	</table>
	<p/>

	<form action="$SELF" method="POST">
	<input type="hidden" name="action" value="adduser">
EOF;
	if ( $userid == "" ) 
		echo "No selected user.";
	else {
		$u = getUserByUserId($userid);
		printf("Selected user: %s (%s)", 
			$u["lastname"],
			$u["email"]);
		echo <<<EOF
		<input type="submit" value="Add">
EOF;
	}
			
	echo <<<EOF
	</form>
EOF;
} // showeditform



switch ($action)
{
    //--------------------------------------------------------------------
    case "edit":
        if (array_key_exists("incidentid", $_REQUEST))
			$incidentid=$_REQUEST["incidentid"];
        else die("Missing information(1).");
		$_SESSION["incidentid"] = $incidentid;

		pageHeader("Edit incident");
		showEditForm();
		break;

    //---------------------------------------------------------------
    case "New incident":
    case "new":
        PageHeader("New Incident");
        echo <<<EOF
<form action="$SELF" method="POST">
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
                $state,
                $status,
                $type
            )
        ) or die("Unable to execute query 3.");
        db_free_result($res);
		addIncidentComment("Incident created", "", "", $conn);

        $res = db_query($conn,
            "select nextval('incident_addresses_sequence') as iaid")
        or die("Unable to execute query 4.");
        $row = db_fetch_next($res);
        $iaid = $row["iaid"];
        db_free_result($res);

        $res = db_query($conn, sprintf(
            "insert into incident_addresses
             (id, incident, ip, hostname, constituency, added, addedby)
             values
             (%s, %s, %s, %s, %s, CURRENT_TIMESTAMP, %s)",
                $iaid,
                $incidentid,
                db_masq_null($address),
                db_masq_null(gethostbyaddr($address)),
				db_masq_null($constituency),
                $_SESSION["userid"]
            )
        ) or die("Unable to execute query 5.");
        db_free_result($res);
		addIncidentComment(sprintf("IP address %s added to incident.",
			$address), "", "", $conn);

        $res = db_query($conn, "end transaction");
        db_close($conn);

        if ($sendmail == "on") Header("Location: standard.php");
		else Header("Location: $SELF");
        break;


    //--------------------------------------------------------------------
    case "list":
        pageHeader("Incident overview");
        $conn = db_connect(DBDB, DBUSER, DBPASSWD)
        or die("Unable to connect to database.");

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
             AND      i.id = a.incident
			 AND      s2.label in ('open', 'stalled')
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
            while ($row = db_fetch_next($res))
            {   
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
	<td><a href="$SELF?action=edit&incidentid=$id">edit</a></td>
    <td>$incidentid</td>
	<td>$constituency</td>
    <td>$hostline</td>
    <td>$status</td>
    <td>$state</td>
    <td>$type</td>
    <td>$updated</td>
	<td><a href="$SELF?action=history&incidentid=$id">history</a></td>
</tr>
EOF;
            } // while
            echo "</table>";
            db_free_result($res);
            db_close($conn);
        } // else

        echo <<<EOF
<p>
<form action="$SELF" method="POST">
<input type="submit" name="action" value="New incident">
</form>
EOF;
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
		Header(sprintf("Location: $SELF?action=edit&incidentid=%s",
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

		Header(sprintf("Location: $SELF?action=edit&incidentid=%s",
			urlencode($incidentid)));
		break;


    //--------------------------------------------------------------------
	case "adduser":
		echo "To be implemented.";
		break;

    //--------------------------------------------------------------------
    case "close":
		echo "To be implemented.";
        break;

    //--------------------------------------------------------------------
    case "history":
        if (array_key_exists("incidentid", $_REQUEST))
			$incidentid=$_REQUEST["incidentid"];
        else die("Missing information(1).");
		$_SESSION["incidentid"] = $incidentid;

		pageHeader("Incident history");
		showIncidentHistory($incidentid);

		echo <<<EOF
<p>
<form action="$SELF" method="post">
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
		pageFooter();
        break;

    //--------------------------------------------------------------------
	case "addcomment":
		if (array_key_exists("comment", $_REQUEST)) 
			$comment = $_REQUEST["comment"];
		else die ("Missing information.");

		addIncidentComment($comment);

		Header("Location: $SELF?action=history&incidentid=$_SESSION[incidentid]");
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
			$state,
			$status,
			$type));

		Header("Location: $SELF");
		break;

    //--------------------------------------------------------------------
    default:
        die("Unknown action");
}

?>
