<?php
/*
 * AIR: APPLICATION FOR INCIDENT RESPONSE
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
 * colorstate.php -- colorstate plugin for AIR
 * 
 * $Id$
 */
require '../lib/air.plib';
require '../lib/database.plib';
 
$SELF = "colorstate.php";

if (array_key_exists("action", $_REQUEST)) $action=$_REQUEST["action"];
else $action = "new";

function getColorState()
{
    $conn = @db_connect(DBNAME, DBUSER, DBPASSWD)
    or die("Unable to connect to database.");

    $res = @db_query($conn, "
        SELECT *
        FROM   colorstates
        WHERE  active=1")
    or die("Unable to query database: ".db_errormessage());

    if (db_num_rows($res) == 0)
        $row = array("colorstate"=>"Unknown", "url"=>"");
    else
        $row = db_fetch_next($res);

    db_close($conn);
    return $row;
}

switch ($action)
{
    // -------------------------------------------------------------------
    case "image":
        $row = getColorState();
        $url = $row["url"];
        printf("<img src=\"%s\">", $url);
        break;

    // -------------------------------------------------------------------
    case "label":
        $row = getColorState();
        $colorstate = $row["colorstate"];
        printf($colorstate);
        break;

    // -------------------------------------------------------------------
    case "new":
        pageHeader("Color states");

        $row = getColorState();
        $current = $row["colorstate"];
        printf("The current colorstate is <B>%s</B><P>", $current);

        $conn = @db_connect(DBNAME, DBUSER, DBPASSWD)
        or die("Unable to connect to database.");

        $res = @db_query($conn, 
            "SELECT *
             FROM   colorstates
             ORDER BY created");
        echo <<<EOF
<form action="$SELF" method="post">
<input type="hidden" name="action" value="activate">
<table width="100%">
EOF;
        $count = 0;
        while ($row = db_fetch_next($res))
        {
            $colorstate = $row["colorstate"];
            $url = $row["url"];
            $id = $row["id"];
            $bgcolor = ($count++ % 2 == 0) ? "#DDDDDD":"#FFFFFF";

            if ($colorstate == $current)
                $active = "CHECKED";
            else 
                $active = "";
            echo <<<EOF
<tr bgcolor='$bgcolor'
    <td><input type="radio" name="id" value="$id" $active></td>
    <td>$colorstate</td>
    <td>$url</td>
    <td><a href="$SELF?action=delete&id=$id">Delete</a></td>
</tr>
EOF;
        }

        echo "</table>";
        if ($count) echo "<input type=\"submit\" value=\"Activate\">";
        echo <<<EOF
</form>
<P>
<HR>
<B>Add color state</B>
<form action="$SELF" method="POST">
<table>
<tr>
    <td>Color state (one descriptive word)</td>
    <td><input type="text" name="colorstate" size="20"></td>
</tr>
<tr>
    <td>URL of image (if any)</td>
    <td><input type="text" name="url" size="20"></td>
</tr>
</table>
<input type="submit" value="Add">
<input type="hidden" name="action" value="add">
</form>
EOF;
    pageFooter();
    break;

    // -------------------------------------------------------------------
    case "add":
        if (array_key_exists("url", $_REQUEST)) $url=$_REQUEST["url"];
        else die("Missing information (1).");

        if (array_key_exists("colorstate", $_REQUEST)) 
            $colorstate=$_REQUEST["colorstate"];
        else die("Missing information (2).");

        $conn = @db_connect(DBNAME, DBUSER, DBPASSWD)
        or die("Unable to connect to database.");

        $now = Date("Y-m-d H:i:s");
        $res = @db_query($conn, sprintf("
            INSERT INTO colorstates
            (id, url, colorstate, created, createdby)
            VALUES
            (nextval('colorstates_seq'), %s, %s, %s, %s)",
            db_masq_null($url),
            db_masq_null($colorstate),
            db_masq_null($now),
            $_SESSION["userid"]))
        or die("Unable to add color state:".db_errormessage());

        Header("Location: $SELF");

        break;
    // -------------------------------------------------------------------
    case "delete":
        if (array_key_exists("id", $_REQUEST)) $id=$_REQUEST["id"];
        else die("Missing information (1).");

        $conn = @db_connect(DBNAME, DBUSER, DBPASSWD)
        or die("Unable to connect to database.");

        $res = @db_query($conn, sprintf("
            DELETE FROM colorstates
            WHERE id=%s",
            $id))
        or die("Unable to delete color state:".db_errormessage());

        Header("Location: $SELF");
        break;
    // -------------------------------------------------------------------
    case "activate":
        if (array_key_exists("id", $_REQUEST)) $id=$_REQUEST["id"];
        else die("Missing information (1).");

        $conn = @db_connect(DBNAME, DBUSER, DBPASSWD)
        or die("Unable to connect to database.");

        $res = @db_query($conn, "UPDATE colorstates SET active=0")
        or die("Unable to query: ".db_errormessage());
        
        db_free_result($res);

        $res = @db_query($conn, "
            UPDATE colorstates 
            SET active=1
            WHERE id=$id")
        or die("Unable to query: ".db_errormessage());

        db_close($conn);
        Header("Location: $SELF");
        break;
} // switch
?>
 
