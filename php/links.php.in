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
 * links.php -- manage links
 * 
 * $Id$
 */
 require_once '@ETCPATH@/airt.cfg';
 require_once LIBDIR.'/airt.plib';
 require_once LIBDIR.'/database.plib';
 
 $SELF = "links.php";

 if (array_key_exists("action", $_REQUEST)) $action=$_REQUEST["action"];
 else $action = "list";

 switch ($action)
 {
    // --------------------------------------------------------------
    case "list":
        pageHeader("Links");
        $conn = db_connect(DBDB, DBUSER, DBPASSWD)
        or die("Unable to connect to database.");

        $res = db_query($conn, "
            SELECT *
            FROM   urls
            ORDER BY created")
        or die("Unable to reqtrieve URLs");

        if (db_num_rows($res) == 0)
        {
            echo "<I>No links defined.</I>";
        }
        else
        {   
            echo "<table width=\"100%\">";
            $count=0;
            while ($row = db_fetch_next($res))
            {
                printf("<tr bgcolor='%s'>\n",
                    $count++%2==0 ? "#DDDDDD" : "#FFFFFF");
                printf("<td>\n");
                printf("<a href=\"%s\">%s</a>", 
                    $row["url"], $row["label"]);
                printf("</td>\n");
                printf("<td><a href=\"%s/%s?action=edit&id=%s\">edit</a></td>",
                    BASEURL, $SELF, urlencode($row["id"]));
                printf("<td><a href=\"%s/%s?action=delete&id=%s\">delete</a>
                        </td>",
                    BASEURL, $SELF, urlencode($row["id"]));
                printf("</tr>\n");
            }
            printf("</table>");
        }

        echo <<<EOF
<HR>
<BR><B>Add new URL</B><BR>
<form action="$SELF" method="POST">
<input type="hidden" name="action" value="add">
<table>
<tr>
    <td>URL</td>
    <td><input type="text" name="url" size="50"></td>
</tr>
<tr>
    <td>Description</td>
    <td><input type="text" name="description" size="50"></td>
</tr>
</table>
<input type="submit" value="Add">
</form>
EOF;
        db_close($conn);
        pageFooter();
        break;

    // --------------------------------------------------------------
    case "add":
        if (array_key_exists("url", $_REQUEST)) $url = $_REQUEST["url"]
        or die("Missing information (1).");

        if (array_key_exists("description", $_REQUEST))
            $description = $_REQUEST["description"]
        or die("Missing information (2).");

        $conn = db_connect(DBDB, DBUSER, DBPASSWD)
        or die("Unable to connect to database.");

        $now = Date("Y-m-d H:i:s");
        $res = db_query($conn, sprintf("
            INSERT INTO urls
            (id, url, label, created, createdby)
            VALUES
            (nextval('urls_sequence'), %s, %s, '%s', %s)",
            db_masq_null($url),
            db_masq_null($description),
            $now,
            $_SESSION["userid"]))
        or die("Unable to insert URL");

        db_close($conn);
        Header("Location: $SELF");
        break;

    // --------------------------------------------------------------
    case "delete":
        if (array_key_exists("id", $_REQUEST)) $id = $_REQUEST["id"]
        or die("Missing information (1).");


        $conn = db_connect(DBDB, DBUSER, DBPASSWD)
        or die("Unable to connect to database.");

        $res = db_query($conn, sprintf("
            DELETE FROM urls
            WHERE ID=%s", $id))
        or die("Unable to delete URL");

        db_close($conn);
        Header("Location: $SELF");
        break;

    // --------------------------------------------------------------
    case "edit":
        if (array_key_exists("id", $_REQUEST))
            $id = $_REQUEST["id"]
        or die("Missing information (3).");
        
        $conn = db_connect(DBDB, DBUSER, DBPASSWD)
        or die("Unable to connect to database.");

        $res = db_query($conn, sprintf("
            SELECT url, label
            FROM   urls
            WHERE  id=%s", $id))
        or die("Unable to retrieve URL");

        if (db_num_rows($res) == 0) die("Incorrect row id");

        pageHeader("Edit link");
        $row = db_fetch_next($res);
        db_close($conn);

        $url = $row["url"];
        $description = $row["label"];

        echo <<<EOF
<form action="$SELF" method="POST">
<input type="hidden" name="action" value="update">
<input type="hidden" name="id" value="$id">
<table>
<tr>
    <td>URL</td>
    <td><input type="text" name="url" size="50" value="$url"></td>
</tr>
<tr>
    <td>Description</td>
    <td><input type="text" name="description" size="50"
         value="$description"></td>
</tr>
</table>
<input type="submit" value="Update">
</form>
EOF;
        break;

    // --------------------------------------------------------------
    case "update":
        if (array_key_exists("url", $_REQUEST)) $url = $_REQUEST["url"]
        or die("Missing information (1).");

        if (array_key_exists("description", $_REQUEST))
            $description = $_REQUEST["description"]
        or die("Missing information (2).");

        if (array_key_exists("id", $_REQUEST))
            $id = $_REQUEST["id"]
        or die("Missing information (3).");
        
        $conn = db_connect(DBDB, DBUSER, DBPASSWD)
        or die("Unable to connect to database.");

        $res = db_query($conn, sprintf("
            UPDATE URLs
            SET    label=%s,
                   url=%s
            WHERE  id=%s", 
            db_masq_null($description),
            db_masq_null($url),
            $id))
        or die("Unable to update URL");
        db_close($conn);

        Header("Location: $SELF");
        break;
    // --------------------------------------------------------------
    default:
        die("Unknown action: $action");
} // switch
?>
 
