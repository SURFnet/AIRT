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
 * incident_types.php -- manage incident_types
 * 
 * $Id$
 */
 require_once '/etc/airt/airt.cfg';
 require_once LIBDIR.'/airt.plib';
 require_once LIBDIR.'/database.plib';
 
 $SELF = "incident_types.php";

 if (array_key_exists("action", $_REQUEST)) $action=$_REQUEST["action"];
 else $action = "list";

 function show_form($id="")
 {
    $label = "";
    $action = "add";
    $submit = "Add!";

    if ($id != "")
    {
        $conn = db_connect(DBDB, DBUSER, DBPASSWD)
        or die("Unable to connect to database.");

        $res = db_query($conn, "
        SELECT label
        FROM   incident_types
        WHERE  id = '$id'")
        or die("Unable to query database.");

        if (db_num_rows($res) > 0)
        {
            $row = db_fetch_next($res);
            $action = "update";
            $submit = "Update!";
            $label = $row["label"];
        }
        db_close($conn);
    }
    echo <<<EOF
<form action="$SELF" method="POST">
<input type="hidden" name="action" value="$action">
<input type="hidden" name="id" value="$id">
<table>
<tr>
    <td>Label</td>
    <td><input type="text" size="30" name="label" value="$label"></td>
</tr>
</table>
<p>
<input type="submit" value="$submit">
</form>
EOF;
 }

 switch ($action)
 {
    // --------------------------------------------------------------
    case "list":
        pageHeader("Incident types");
        $conn = db_connect(DBDB, DBUSER, DBPASSWD)
        or die("Unable to connect to database.");

        $res = db_query($conn,
            "SELECT   id, label
             FROM     incident_types
             ORDER BY label")
        or die("Unable to execute query 1");

        echo <<<EOF
<table cellpadding="3">
<tr>
    <td><B>Label</B></td>
    <td><B>Edit</B></td>
    <td><B>Delete</B></td>
    
</tr>
EOF;
        $count=0;
        while ($row = db_fetch_next($res))
        {
            $label = $row["label"];
            $id    = $row["id"];
            $color = ($count++%2==0?"#FFFFFF":"#DDDDDD");
            echo <<<EOF
<tr valign="top" bgcolor="$color">
    <td>$label</td>
    <td><a href="$SELF?action=edit&id=$id">edit</a></td>
    <td><a href="$SELF?action=delete&id=$id">delete</a></td>
</tr>
EOF;
        } // while $row
        echo "</table>";

        db_free_result($res);
        db_close($conn);

        echo "<h3>New incident state</h3>";
        show_form("");

        break;

    //-----------------------------------------------------------------
    case "edit":
        if (array_key_exists("id", $_GET)) $id=$_GET["id"];
        else die("Missing information.");

        pageHeader("Edit incident state");
        show_form($id);
        pageFooter();
        break;

    //-----------------------------------------------------------------
    case "add":
    case "update":
        if (array_key_exists("id", $_POST)) $id=$_POST["id"];
        else $id="";
        if (array_key_exists("label", $_POST)) $label=$_POST["label"];
        else die("Missing information (1).");

        if ($action=="add")
        {
            $conn = db_connect(DBDB, DBUSER, DBPASSWD)
            or die("Unable to connect to database.");

            $res = db_query($conn, sprintf("
                INSERT INTO incident_types
                (id, label)
                VALUES
                (nextval('incident_types_sequence'), %s)",
                    db_masq_null($label)))
            or die("Unable to excute query.");

            db_close($conn);
            Header("Location: $SELF");
        }

        else if ($action=="update")
        {
            if ($id=="") die("Missing information (3).");
            $conn = db_connect(DBDB, DBUSER, DBPASSWD)
            or die("Unable to connect to database.");

            $res = db_query($conn, sprintf("
                UPDATE incident_types
                set  label=%s
                WHERE id=%s",
                    db_masq_null($label),
                    $id))
            or die("Unable to excute query.");

            db_close($conn);
            Header("Location: $SELF");
        }

        break;

    //-----------------------------------------------------------------
    case "delete":
        if (array_key_exists("id", $_GET)) $id=$_GET["id"];
        else die("Missing information.");

        $conn = db_connect(DBDB, DBUSER, DBPASSWD)
        or die("Unable to connect to database.");

        $res = db_query($conn, "
            DELETE FROM incident_types
            WHERE  id='$id'")
        or die("Unable to execute query.");

        db_close($conn);
        Header("Location: $SELF");
        
        break;
    //-----------------------------------------------------------------
    default:
        die("Unknown action: $action");
 } // switch

?>
 
