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
        break;

    //--------------------------------------------------------------------
    case "new":
        if (array_key_exists("hostname", $_REQUEST))
            $hostname = $_REQUEST["hostname"];
        else die("Missing information.");

        $hostname = trim(gethostbyname($hostname));
        $hostname = gethostbyaddr($hostname);

        // TODO
        printf("New hostname: $hostname");
        break;

    //--------------------------------------------------------------------
    case "add":
        break;

    //--------------------------------------------------------------------
    case "update":
        break;

    //--------------------------------------------------------------------
    case "list":
        pageHeader("Incident overview");

        echo "<table width='100%'>\n";
        echo "<tr>
                <td></td>
                <td align='center'><small>Click to edit</small></td>
                <td></td>
                <td align='center'><small>Click to search</small></td>
            </tr>";

        $conn = db_connect(RTNAME, RTUSER, RTPASSWD)
        or die("Unable to connect to database.");

        $res = db_query($conn, "
            SELECT   t.id, t.status, extract (epoch from t.created) as created
            FROM     tickets t, queues q
            WHERE    t.queue = q.id
            AND      q.name  = '".LIBERTYQUEUE."'
            AND      t.status in ('open', 'new')
            ORDER BY t.created
            ")
        or die("Unable to query incidents.");

        $count = 0;
        while ($row = db_fetch_next($res))
        {
            $status   = $row["status"];
            $created  = Date("d-M-Y", $row["created"]);
            $ticketid = $row["id"];

            $res2 = db_query($conn, "
                SELECT f.name, v.content
                FROM   ticketcustomfieldvalues v, customfields f
                WHERE  v.ticket = $ticketid
                AND    f.id = v.customfield
                ")
            or die("Unable to retrieve field");

            $VALUES = array();
            while ($row2 = db_fetch_next($res2))
                $VALUES[$row2["name"]] = $row2["content"];

            printf("
                <tr bgcolor=\"%s\">
                    <td>%s</td>
                    <td>%s</td>
                    <td>%s</td>
                    <td>%s</td>
                    <td>%s</td>
                    <td>%s</td>
                </tr>",
                $count++%2 ? "#DDDDDD" : "#FFFFFF",
                $VALUES["IncidentID"],
                $VALUES["Constituency"],
                $VALUES["IPAddress"],
                $VALUES["Category"],
                $status,
                $created);
            pg_free_result($res2);
        } // while
        echo "</table>";
        db_close($conn);

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
