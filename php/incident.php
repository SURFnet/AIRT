<?php
/*
 * AIRT: APPLICATION FOR INCIDENT RESPONSE TEAMS
 * Copyright (C) 2004	Kees Leune <kees@uvt.nl>

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
require_once "../lib/airt.plib";
require_once "../lib/incident.plib";
require_once "../lib/constituency.plib";
require_once "../lib/rt.plib";
require_once "../lib/history.plib";
require_once "../lib/userfunctions.plib";

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


switch ($action)
{
    //--------------------------------------------------------------------
    case "edit":
        if (array_key_exists("id", $_REQUEST)) 
            $id=$_REQUEST["id"];
        else die("Missing information (1).");

        // set active incident id
        $_SESSION["active_incidentid"] = normalize_incidentid($id);

        $incident = AIR_getIncidentById($id);
        if ($incident->getId() == -1) die("Unknown ID: $id");
        

        $ip            = $incident->getIp();
        $constituency  = $incident->getConstituency();
        $rtid          = $incident->getRTId();
        $status        = $incident->getStatus();
        $category      = $incident->getCategory();
        $user_email    = $incident->getUserEmail();
        $user_name     = $incident->getUserName();
        $state         = $incident->getstate();
        $hostname      = gethostbyaddr($ip);

        if ($constituency=="") 
            $constituency = categorize($ip, gethostbyaddr($ip));
        
        $_SESSION["active_ip"] = $ip;

        // no break on purpose

    //---------------------------------------------------------------
    case "new":
        pageHeader("New/edit ticket");
        if ($hostname == "") 
            if (array_key_exists("hostname", $_REQUEST))
            {
                $hostname = $_REQUEST["hostname"];
                $ip = gethostbyname($hostname);
                $hostname = gethostbyaddr($ip);
            }
            else 
            {
                $hostname = "";
                $ip = "";
            }

        if ($constituency == "") 
            if (array_key_exists("constituency", $_REQUEST))
                $constituency = constituency_to_id($_REQUEST["constituency"]);
            else $constituency = "";
       
        if ($category == "")
            if (array_key_exists("category", $_REQUEST))
                $category = $_REQUEST["category"];
            else $category = "";

        echo <<<EOF
<form method="post" action="$SELF">
<input type="hidden" name="id" value="$id">
<table cellpadding=3>

<tr>
    <td>IP address</td>
    <td><input type="text" name="ip" size="40" value="$ip"></td>
</tr>

<tr>
    <td>Consituency</td>
    <td>
    
    <select name="constituency">
EOF;

        echo choice("--- Choose consituency ---", "", $constituency);
        $cons = AIR_getConstituencies();
        foreach ($cons as $i => $c)
            echo choice($c["description"], $c["id"], $constituency);

        echo <<<EOF
        </select>
    </td>
</tr>

<tr>
    <td>Full name of user</td>
    <td><input type="text" name="user_name" size="40" value="$user_name"></td>
</tr>

<tr>
    <td>Email address of user</td>
    <td><input type="text" name="user_email" size="40" value="$user_email"></td>
</tr>

<tr>
    <td>Ticket status</td>
    <td><select name="status">
EOF;
        echo choice("--- Choose status ---", "", $status);
        echo choice("Open", "open", $status);
        echo choice("Closed", "closed", $status);
        echo <<<EOF
        </select>
    </td>
</tr>

<tr>
    <td>Category</td>
    <td><select name="category">
EOF;
        echo choice("--- Choose category ---", "", $category);
        echo choice("System compromise (virus, etc)", "compromise", $category);
        echo choice("Spam", "spam", $category);
        echo choice("Portscan", "portscan", $category);
        echo choice("Active hacking", "hack", $category);
        echo <<<EOF
        </select>
    </td>
</tr>

<tr>
    <td>Ticket state</td>
    <td><select name="state">
EOF;
        echo choice("--- Choose state ---", "", $state);
        echo choice("Acknowledged", "acknowledged", $state);
        echo choice("Block requested", "blockrequest", $state);
        echo choice("Blocked", "blocked", $state);
        echo choice("Unblock requested", "unblockrequest", $state);
        echo choice("Unblocked", "unblocked", $state);
        echo choice("Forwarded", "forwarded", $state);
        echo <<<EOF
        </select>
        <input type="hidden" name="state_before" value="$state">
    </td>
</tr>

</table>

<P>
EOF;

        if ($action == "new")
            echo <<<EOF
<input type="submit" value="Create">
<input type="hidden" name="action" value="add">
EOF;
        else
            echo <<<EOF
<input type="submit" value="Update">
<input type="hidden" name="action" value="update">
EOF;

        echo "</form>";
        pageFooter();


        break;

    //--------------------------------------------------------------------
    case "add":
        // ip: required
        if (array_key_exists("ip", $_POST)) $ip = $_POST["ip"];
        else die("Missing information (2).");
        $ip = gethostbyname($ip);

        // status: default to open
        if (array_key_exists("status", $_POST)) $status = $_POST["status"];
        else die("Missing information (3).");
        if ($status=="") $status="open";

        // category: required
        if (array_key_exists("category", $_POST))
            $category = $_POST["category"];
        else die("Missing information (4).");
        if ($category=="") die("Incomplete information (4).");

        // user_name: optional
        if (array_key_exists("user_name", $_POST))
            $user_name=$_POST["user_name"];

        // user_email: optional
        if (array_key_exists("user_email", $_POST))
            $user_email=$_POST["user_email"];

        // constituency: optional
        if (array_key_exists("constituency", $_POST))
            $constituency=$_POST["constituency"];

        // state: optional
        if (array_key_exists("state", $_POST))
            $state=$_POST["state"];


        $now = Date("Y-m-d H:i:s");
        $incident = new AIR_Incident();
        $incident->setIp($ip);
        $incident->setStatus($status); 
        $incident->setState($state);
        $incident->setCategory($category);
        $incident->setUserName($user_name);
        $incident->setUserEmail($user_email);
        $incident->setConstituency($constituency);
        $incident->setCreated($now);
        $incident->setCreator($_SESSION["userid"]);
        $id = AIR_addIncident($incident);
        
        // set active incident id
        $_SESSION["active_incidentid"] = normalize_incidentid($id);
        $_SESSION["active_ip"] = $ip;

        Header(sprintf("Location: %s/%s?action=list",
            BASEURL, $SELF));

        break;

    //--------------------------------------------------------------------
    case "update":
        // incidentid: required
        if (array_key_exists("id", $_POST)) $incidentid = $_REQUEST["id"];
        else die("Missing information (1).");

        // ip: required
        if (array_key_exists("ip", $_POST)) $ip = $_POST["ip"];
        else die("Missing information (2).");
        $ip = gethostbyname($ip);

        // status: required
        if (array_key_exists("status", $_POST)) $status = $_POST["status"];
        else die("Missing information (3).");

        // category: required
        if (array_key_exists("category", $_POST))
            $category = $_POST["category"];
        else die("Missing information (4).");
        if ($category=="") die("Incomplete information (4).");

        // user_name: optional
        if (array_key_exists("user_name", $_POST))
            $user_name=trim($_POST["user_name"]);

        // user_email: optional
        if (array_key_exists("user_email", $_POST))
            $user_email=trim($_POST["user_email"]);

        // fac: optional
        if (array_key_exists("constituency", $_POST))
            $constituency=$_POST["constituency"];

        // state: optional
        if (array_key_exists("state", $_POST))
            $state=$_POST["state"];

        // set active incident id
        $_SESSION["active_incidentid"] = normalize_incidentid($id);
        $_SESSION["active_ip"] = $ip;

        $id = normalize_incidentid($id);
        $now = Date("Y-m-d H:i:s");
        $incident = new AIR_Incident();
        $incident->setId($incidentid);
        $incident->setIp($ip);
        $incident->setStatus($status); 
        $incident->setState($state);
        $incident->setCategory($category);
        $incident->setUserName($user_name);
        $incident->setUserEmail($user_email);
        $incident->setConstituency($constituency);
        $incident->setLastUpdated($now);
        $incident->setLastUpdatedBy($_SESSION["userid"]);

        Air_updateIncident($incident);
        
        $event = new AIR_Event();
        $event->setType("updateincident");
        $event->setField("id", $incidentid);
        $event->setField("ip", $ip);
        $event->setField("category", $category);
        $event->setField("constituency", $constituency);
        $event->setField("user_email", $user_email);
        $event->setField("user_name", $user_name);
        $event->setField("state", $state);
        $event->setField("status", $status);
        triggerEvent($event);

        Header(sprintf("Location: %s/%s?action=list",
            BASEURL, $SELF));

        break;

    //--------------------------------------------------------------------
    case "list":
        pageHeader("Incident overview");
        $incidents = AIR_getIncidents();
        if (count($incidents) == 0)
        {
            echo "No incidents defined.";
            pageFooter();
            exit;
        }

        echo <<<EOF
<table width='100%'>
<tr>
    <td></td>
    <td align='center'><small>Click to edit</small></td>
    <td></td>
    <td align='center'><small>Click to search</small></td>
</tr>
EOF;
        $count = 0;
        foreach ($incidents as $index => $incident)
        {
            // get status
            $status       = $incident["status"];
            if ($status == "closed") continue;

            $id           = normalize_incidentid($incident["id"]);
            $ip           = $incident["ip"];
            $category     = $incident["category"];
            $date_created = $incident["created"];


            // get state
            $state        = $incident["state"];
            if ($state == "") $state = "New";

            // get constitution
            $con = AIR_getConstituencyById($incident["constituency"]);
            if ($con->getId() == -1) $constituency = "Unknown";
            else $constituency = $con->getName();

            // check if incident is overdue
            if ($state == "new" && ((time() - $row["date_part"]) > WARN_UNACK))
                $flags = "*";
            else
                $flags = "&nbsp;";
                
            if ($count++%2==0) $bgcolor="#FFFFFF"; else $bgcolor="#DDDDDD";
            printf("<tr bgcolor=$bgcolor>
                    <td>%s</td>
                    <td><a href=\"$SELF?action=edit&id=%s\">%s</a></td>
                    <td>%s</td>
                    <td><a href=\"search.php?action=search&hostname=%s\">%s</a></td>
                    <td>%s</td>
                    <td>%s</td>
                    <td>%s</td>
                    <td>%s</td>
                    <td>%s</td>
                </tr>\n",
                $flags,
                urlencode($id), $id, $constituency, $ip, 
                gethostbyaddr($ip), $category, $state,
                $date_created,
                sprintf("<small><a 
                    href=\"$SELF?action=history&id=%s\">history</a></small>", 
                    urlencode($id)),

                sprintf("<small><a 
                    href=\"$SELF?action=close&id=%s\">close</a></small>",
                    urlencode($id))
            );
        } // foreach
        echo "</table>\n";
        printf("<P><small><i>%s %s selected.</small><P>",
            $count, $count==1?"record":"records");

        printf("<a href=\"%s?action=new&ip=%s\">New incident</a>",
            $SELF, urlencode($_SESSION["active_ip"]));
        pageFooter();
        break;
        
    //--------------------------------------------------------------------
    case "close":
        if (array_key_exists("id", $_REQUEST)) $id=$_REQUEST["id"];
        else die("Missing information (1).");

        $incident = AIR_getIncidentById(decode_incidentid($id));
        $incident->setStatus("closed");
        AIR_updateIncident($incident);
        
        Header(sprintf("Location: %s/%s?action=list",
            BASEURL, $SELF));

        break;

    //--------------------------------------------------------------------
    case "history":
        if (array_key_exists("id", $_REQUEST)) $id=$_REQUEST["id"];
        else die("Missing information (1).");

        pageHeader("Ticket history");

        echo "<h2>Basic information of incident $id</h2>";
        $incident = AIR_getIncidentById($id);
        if ($incident->id == -1) die("Unknown incident");
        $constituency = AIR_getConstituencyById($incident->getConstituency());

        printf("
<table cellpadding='3'>
<tr>
    <td>Incident number:</td>
    <td>%s</td>
</tr>
<tr>
    <td>IP address:</td>
    <td>%s</td>
</tr>
<tr>
    <td>Hostname:</td>
    <td>%s</td>
</tr>
<tr>
    <td>Constituency:</td>
    <td>%s</td>
</tr>
<tr>
    <td>Full name of user:</td>
    <td>%s</td>
</tr>
<tr>
    <td>Email address of user:</td>
    <td>%s</td>
</tr>
<tr>
    <td>Category:</td>
    <td>%s</td>
</tr>
</table>", 
        encode_incidentid($incident->getId()),
        $incident->getIp(),
        gethostbyaddr($incident->getIp()), 
        $constituency->getName(),
        $incident->getUserName(),
        $incident->getUserEmail(), 
        $incident->getStatus(),
        $incident->getCategory());

        echo "<h2>Activity</h2>";
        echo "<table>";
        $history = AIR_getHistory($incident->getId());
        $count = 0;
        foreach ($history as $key=>$tuple)
        {
            switch ($tuple["type"])
            {
                case "create":
                    $info = "Incident created";
                    break;
                case "state":
                    $info = "State changed to ".$tuple["newvalue"];
                    break;
                case "close":
                    $info = "Incident closed,";
                    break;
                default:
                    $info = $tuple["newvalue"];

            }
            $user = RT_getUserById($tuple["createdby"]);
            printf("<tr cellpadding=3 bgColor='%s'>
                <td>%s</td><td>%s</td><td>%s</td></tr>",
                $count++%2==0 ? "#DDDDDD": "#FFFFFF",
                $tuple["created"],
                $user["realname"],
                $info
                );
        }
        echo "</table>";


        echo "<h2>Messages</h2>";
        $incident = AIR_getIncidentById($id);
        $rtid = $incident->rtid;
        if ($rtid != "")
        {
            $ticketids = RT_getTicketIdsByEffectiveId($rtid);
            foreach ($ticketids as $key => $value)
            {
                $ticket = RT_getTicketById($value);
                $attachments = RT_getAttachmentsOfTicket($value);

                $creator = RT_getUserById($ticket["creator"]);
                printf("<div width='100%%' style='background-color: #DDDDDD'>");
                printf("Received: %s from %s &lt;%s&gt;<BR>",
                    $ticket["created"], $creator["realname"],
                    $creator["emailaddress"]);
                printf("</div>");
                foreach ($attachments as $k => $v)
                {
                    $a = RT_getAttachmentById($v);
                    printf("<PRE>%s</PRE>", $a["content"]);
                }
            }
        }

        pageFooter();
        break;
    //--------------------------------------------------------------------
    default:
        die("Unknown action");
}

?>
