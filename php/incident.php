<?php
/*
 * LIBERTY: INCIDENT RESPONSE SUPPORT FOR END-USERS
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
require "../lib/liberty.plib";
require "../lib/database.plib";
require "../lib/userfunctions.plib";

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

        $id = normalize_incidentid($id);

        $conn = db_connect(DBNAME, DBUSER, DBPASSWD)
        or die("Unable to connect to database.");

        $r = db_query($conn, sprintf("
            SELECT * 
            FROM incidents
            WHERE id='%s'",
                decode_incidentid($id)))
        or die("Unable to query database.");

        if (db_num_rows($r) == 0)
            die("Unknown incident id");

        $row           = db_fetch_next($r);
        $ip            = $row["ip"];
        $constituency  = $row["constituency"];
        $rtid          = $row["rtid"];
        $status        = $row["status"];
        $category      = $row["category"];
        $user_email    = $row["user_email"];
        $user_name     = $row["user_name"];
        $state         = $row["state"];
        $hostname      = gethostbyaddr($ip);

        if ($constituency=="") 
            $constituency = categorize($ip, gethostbyaddr($ip));

        db_free_result($r);
        db_close($conn);

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
        $conn = db_connect(DBNAME, DBUSER, DBPASSWD)
        or die("Unable to connect to database.");

        $res = db_query($conn,
            "SELECT * 
             FROM   constituencies
             ORDER BY name")
        or die("Unable to query constituencies");

        while ($row = db_fetch_next($res))
        {
            $id = $row["id"];
            $descr = $row["description"];
            echo choice($descr, $id, $constituency);
        }
        db_close($conn);

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

        $conn = db_connect(DBNAME, DBUSER, DBPASSWD)
        or die("Unable to connect to database.");

        $now = Date("Y-m-d H:i:s");
        $query = sprintf("
            INSERT INTO incidents
            (id, ip, status, state, category, user_name, user_email, 
             constituency, created, creator, lastupdatedby, lastupdated )
            VALUES
            (nextval('incidentid_seq'), '%s', '%s', '%s', '%s', %s, %s, 
                %s, '%s', %s, %s, '%s')",
                $ip, 
                $status, 
                $state,
                $category,
                $user_name==""?"NULL":"'$user_name'",
                $user_email==""?"NULL":"'$user_email'",
                $constituency, 
                $now, 
                $_SESSION["userid"],
                $_SESSION["userid"],
                $now
                );
        $r = db_query($conn, $query)
        or die("Unable to insert incident.");
        db_close($conn);

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

        $id = normalize_incidentid($id);

        $conn = db_connect(DBNAME, DBUSER, DBPASSWD)
        or die("Unable to connect to database.");

        session_start();
        $query = sprintf("
            UPDATE incidents
            SET    ip = '%s',
                   status = '%s',
                   category = '%s',
                   user_name = %s,
                   user_email = %s,
                   constituency = %s,
                   lastupdatedby = '%s',
                   lastupdated = '%s'
            WHERE  id = '%s'",
            $ip, 
            $status, 
            $category,
            $user_name    == "" ? "NULL" : "'$user_name'",
            $user_email   == "" ? "NULL" : "'$user_email'",
            $constituency == "" ? "NULL" : $constituency,
            $_SESSION["userid"],
            Date("Y-m-d H:i:s"),
            decode_incidentid($incidentid));
        $r = db_query($conn, $query)
        or die("Unable to update incident.");
        db_close($conn);     
        
        Header(sprintf("Location: %s/%s?action=list",
            BASEURL, $SELF));

        break;

    //--------------------------------------------------------------------
    case "list":
        pageHeader("Incident overview");
        $conn = db_connect(DBNAME, DBUSER, DBPASSWD)
        or die("Unable to connect to database.");

        $r = db_query($conn, "SELECT 
            i.id, ip, status, category, c.name as constituency,
            extract(epoch from i.created) 
            FROM incidents i, constituencies c
            WHERE status='open'
            AND   i.constituency = c.id
            order by id")
        or die("Unable to query incidents.");

        echo "<table width='100%'>\n";
        echo "<tr>
                <td></td>
                <td align='center'><small>Click to edit</small></td>
                <td></td>
                <td align='center'><small>Click to search</small></td>
            </tr>";
        $count = 0;
        while ($row = pg_fetch_array($r))
        {
            $id           = normalize_incidentid($row["id"]);
            $ip           = $row["ip"];
            $constituency = $row["constituency"];
            $status       = $row["status"];
            $category     = $row["category"];
            $date_created = Date("d M Y", $row["date_part"]);

            if ($count++%2==0) $bgcolor="#FFFFFF"; else $bgcolor="#DDDDDD";

            // check if incident is overdue
            if ($state == "" && ((time() - $row["date_part"]) > WARN_UNACK))
                $flags = "*";
            else
                $flags = "&nbsp;";
                
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
        }
        db_close($conn);
        echo "</table>\n";
        printf("<P><small><i>%s %s selected.</small><P>",
            $count, $count==1?"record":"records");

        pageFooter();
        break;
        
    //--------------------------------------------------------------------
    case "close":
        break;

    //--------------------------------------------------------------------
    case "history":
        break;

    //--------------------------------------------------------------------
    case "comment":
        break;
    
    //--------------------------------------------------------------------
    default:
        die("Unknown action");
}

?>
