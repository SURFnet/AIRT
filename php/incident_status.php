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
 * incident_status.php -- manage incident_status
 * 
 * $Id$
 */
 require_once 'config.plib';
 require_once LIBDIR.'/airt.plib';
 require_once LIBDIR.'/database.plib';
 
 if (array_key_exists("action", $_REQUEST)) $action=$_REQUEST["action"];
 else $action = "list";

 function show_form($id="") {
    $label     = '';
    $desc      = '';
    $isdefault = 'f';
    $action    = "add";
    $submit    = "Add!";

    if ($id != "") {
        $conn = db_connect(DBDB, DBUSER, DBPASSWD)
        or die("Unable to connect to database.");

        $res = db_query($conn, "
        SELECT label, descr, isdefault
        FROM   incident_status
        WHERE  id = '$id'")
        or die("Unable to query database.");

        if (db_num_rows($res) > 0) {
           $row = db_fetch_next($res);
           $action    = "update";
           $submit    = "Update!";
           $label     = $row['label'];
           $desc      = $row['descr'];
           $isdefault = $row['isdefault'];
        }
        db_close($conn);
    }
    if ($isdefault=='t') {
       $isdefault = 'CHECKED';
    } else {
       $isdefault = '';
    }
    echo <<<EOF
<form action="$_SERVER[PHP_SELF]" method="POST">
<input type="hidden" name="action" value="$action">
<input type="hidden" name="id" value="$id">
<table>
<tr>
    <td>Label</td>
    <td><input type="text" size="30" name="label" value="$label"></td>
</tr>
<tr>
    <td>Description</td>
    <td><input type="text" size="50" name="desc" value="$desc"></td>
</tr>
<tr>
    <td>Entry is default</td>
    <td><input type="checkbox" name="isdefault" value="1" $isdefault></td>
</tr>
</table>
<p>
<input type="submit" value="$submit">
</form>
EOF;
 }

 switch ($action) {
    // --------------------------------------------------------------
    case "list":
        pageHeader("Incident status");
        $conn = db_connect(DBDB, DBUSER, DBPASSWD)
        or die("Unable to connect to database.");

        $res = db_query($conn,
            "SELECT   id, label, descr, isdefault
             FROM     incident_status
             ORDER BY label")
        or die("Unable to execute query 1");

        echo <<<EOF
<table cellpadding="3">
<tr>
    <td><B>Label</B></td>
    <td><B>Description</B></td>
    <td><B>Is default</B></td>
    <td><B>Edit</B></td>
    <td><B>Delete</B></td>

</tr>
EOF;
        $count=0;
        while ($row = db_fetch_next($res)) {
            $label     = $row["label"];
            $id        = $row["id"];
            $desc      = $row['descr'];
            $isdefault = $row['isdefault']=='t'? 'Yes':'';
            $color = ($count++%2==0?"#FFFFFF":"#DDDDDD");
            echo <<<EOF
<tr valign="top" bgcolor="$color">
    <td>$label</td>
    <td>$desc</td>
    <td>$isdefault</td>
    <td><a href="$_SERVER[PHP_SELF]?action=edit&id=$id">edit</a></td>
    <td><a href="$_SERVER[PHP_SELF]?action=delete&id=$id">delete</a></td>
</tr>
EOF;
        } // while $row
        echo "</table>";

        db_free_result($res);
        db_close($conn);

        echo "<h3>New incident status</h3>";
        show_form("");

        break;

    //-----------------------------------------------------------------
    case "edit":
        if (array_key_exists("id", $_GET)) $id=$_GET["id"];
        else die("Missing information.");

        pageHeader("Edit incident status");
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
        if (array_key_exists("desc", $_POST)) $desc=$_POST["desc"];
        else die("Missing information (2).");
        if (array_key_exists("isdefault", $_POST)) {
          $isdefault = 't';
        } else {
          $isdefault = 'f';
        }

        $conn = db_connect(DBDB, DBUSER, DBPASSWD)
           or die("Unable to connect to database.");

        if ($isdefault=='t') {
          // The new/updated record is default, so all others are not.
          $q = "UPDATE incident_status
                SET isdefault = 'f'";
          $res = db_query($conn, $q) or die("Unable to execute query 4.");
        }

        // Insert or update the current status record.
        if ($action=="add") {
            $res = db_query($conn, sprintf("
                INSERT INTO incident_status
                (id, label, descr, isdefault)
                VALUES
                (nextval('incident_status_sequence'), %s, %s, %s)",
                    db_masq_null($label),
                    db_masq_null($desc),
                    db_masq_null($isdefault)))
            or die("Unable to execute query 2.");

            db_close($conn);
            Header("Location: $_SERVER[PHP_SELF]");
        } else if ($action=="update") {
            if ($id=="") die("Missing information (3).");
            $res = db_query($conn, sprintf("
                UPDATE incident_status
                set label=%s,
                    descr=%s,
                    isdefault=%s
                WHERE id=%s",
                    db_masq_null($label),
                    db_masq_null($desc),
                    db_masq_null($isdefault),
                    $id))
            or die("Unable to execute query  3.");

            db_close($conn);
            Header("Location: $_SERVER[PHP_SELF]");
        }
        break;

    //-----------------------------------------------------------------
    case "delete":
        if (array_key_exists("id", $_GET)) $id=$_GET["id"];
        else die("Missing information.");

        $conn = db_connect(DBDB, DBUSER, DBPASSWD)
        or die("Unable to connect to database.");

        $res = db_query($conn, "
            DELETE FROM incident_status
            WHERE  id='$id'")
        or die("Unable to execute query 4.");

        db_close($conn);
        Header("Location: $_SERVER[PHP_SELF]");

        break;
    //-----------------------------------------------------------------
    default:
        die("Unknown action: $action");
 } // switch

?>
