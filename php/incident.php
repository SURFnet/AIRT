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


function showIncidentForm($id="")
{
    $constituency = $name = $email = $type = $state = $states = "";
    if (array_key_exists("active_ip", $_SESSION))
        $address = $_SESSION["active_ip"];
    if (array_key_exists("constituency_id", $_SESSION))
        $constituency = $_SESSION["constituency_id"];
    if (array_key_exists("current_name", $_SESSION))
        $name = $_SESSION["current_name"];
    if (array_key_exists("current_email", $_SESSION))
        $email = $_SESSION["current_email"];
    if ($id == "")
    {
        echo <<<EOF
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
<tr>
    <td>User's name</td>
    <td><input type="text" size="30" value="$name" name="name"></td>
</tr>
<tr>
    <td>User's email</td>
    <td><input type="text" size="30" value="$email" name="email"></td>
</tr>
<tr>
    <td>Incident type</td>
    <td>
EOF;
        showIncidentTypeSelection("type");
        echo <<<EOF
    </td>
</tr>
<tr>
    <td>Incident state</td>
    <td>
EOF;
        showIncidentStateSelection("state");
        echo <<<EOF
    </td>
</tr>
<tr>
    <td>Incident status</td>
    <td>
EOF;
        showIncidentStatusSelection("status");
        echo <<<EOF
    </td>
</tr>
</table>
EOF;
    } // new incident
} // showIncidentForm

switch ($action)
{
    //--------------------------------------------------------------------
    case "edit":
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
</form>
EOF;
        break;

    //--------------------------------------------------------------------
    case "Add":
        if (array_key_exists("address", $_POST)) $address=$_POST["address"];
        else $address="";
        if (array_key_exists("constituency", $_POST)) 
            $constituency=$_POST["constituency"];
        else $constituency="";
        if (array_key_exists("type", $_POST)) $type=$_POST["type"];
        else $type="";
        if (array_key_exists("state", $_POST)) $state=$_POST["state"];
        else $state="";
        if (array_key_exists("status", $_POST)) $status=$_POST["status"];
        else $status="";
        
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

        $res = db_query($conn,
            "select nextval('incident_addresses_sequence') as iaid")
        or die("Unable to execute query 4.");
        $row = db_fetch_next($res);
        $iaid = $row["iaid"];
        db_free_result($res);

        $res = db_query($conn, sprintf(
            "insert into incident_addresses
             (id, incident, ip, added, addedby)
             values
             (%s, %s, %s, CURRENT_TIMESTAMP, %s)",
                $iaid,
                $incidentid,
                db_masq_null("$address"),
                $_SESSION["userid"]
            )
        ) or die("Unable to execute query 5.");
        db_free_result($res);

        $res = db_query($conn, "end transaction");
        db_close($conn);

        Header("Location: $SELF");
        break;

    //--------------------------------------------------------------------
    case "update":
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
                      c1.login  as creator, 
                      c2.login  as updater, 
                      s1.label  as state, 
                      s2.label  as status, 
                      t.label   as type,
                      a.ip      as ip
             FROM     incidents i, credentials c1, credentials c2,
                      incident_states s1, incident_status s2, 
                      incident_types t, incident_addresses a
             WHERE    i.creator = c1.userid
             AND      i.updatedby = c2.userid
             AND      i.state = s1.id
             AND      i.status = s2.id
             AND      i.type = t.id
             AND      i.id = a.incident
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
    <td>Incident ID</td>
    <td>IP address</td>
    <td>Last updated</td>
    <td>Status</td>
    <td>State</td>
    <td>Type</td>
</tr>
EOF;
            $count = 0;
            while ($row = db_fetch_next($res))
            {   
                $id      = $row["incidentid"];
                $ip      = $row["ip"];
                $updated = Date("d M Y", $row["updated"]);
                $status  = $row["status"];
                $state   = $row["state"];
                $type    = $row["type"];
                $incidentid = encode_incidentid($id);

                echo <<<EOF
<tr>
    <td>$incidentid</td>
    <td>$ip</td>
    <td>$updated</td>
    <td>$status</td>
    <td>$state</td>
    <td>$type</td>
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
    case "close":
        break;

    //--------------------------------------------------------------------
    case "history":
        break;
    //--------------------------------------------------------------------
    default:
        die("Unknown action");
}

?>
