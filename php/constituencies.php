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
 * constituencies.php -- manage constituency data
 * 
 * $Id$
 */
 require_once '/etc/airt/airt.cfg';
 require_once LIBDIR.'/airt.plib';
 require_once LIBDIR.'/database.plib';
 
 $SELF = "constituencies.php";

 if (array_key_exists("action", $_REQUEST)) $action=$_REQUEST["action"];
 else $action = "list";

 function show_form($id="")
 {
    $label = $description = "";
    $action = "add";
    $submit = "Add!";

    if ($id != "")
    {
        $conn = db_connect(DBDB, DBUSER, DBPASSWD)
        or die("Unable to connect to database.");

        $res = db_query($conn, "
        SELECT label, name
        FROM   constituencies c
        WHERE  c.id = '$id'")
        or die("Unable to query database.");

        if (db_num_rows($res) > 0)
        {
            $row = db_fetch_next($res);
            $action = "update";
            $submit = "Update!";
            $label = $row["label"];
            $description = $row["name"];
        }
        db_close($conn);
    }
    echo <<<EOF
<form action="$SELF" method="POST">
<input type="hidden" name="action" value="$action">
<input type="hidden" name="consid" value="$id">
<table>
<tr>
    <td>Label</td>
    <td><input type="text" size="30" name="label" value="$label"></td>
</tr>
<tr>
    <td>Description</td>
    <td><input type="text" size="30" name="description" value="$description">
        </td>
</tr>
</table>
<p>
<input type="submit" value="$submit">
EOF;
    if ($action=="update")
        echo "<input type=\"submit\" name=\"action\" value=\"Delete\">";

    echo "</form>";
 }

 switch ($action)
 {
    // --------------------------------------------------------------
    case "list":
        pageHeader("Constituencies");
        $conn = db_connect(DBDB, DBUSER, DBPASSWD)
        or die("Unable to connect to database.");

        $res = db_query($conn,
            "SELECT   id, label, name
             FROM     constituencies
             ORDER BY label")
        or die("Unable to execute query 1");

        echo <<<EOF
<table width="100%" cellpadding="3">
<tr>
    <th>&nbsp;</th>
    <th>Label</th>
    <th>Description</th>
    <th>Netblocks</th>
</tr>
EOF;
        $count=0;
        while ($row = db_fetch_next($res))
        {
            $label = $row["label"];
            $name  = $row["name"];
            $consid = $row["id"];
            $color = ($count++%2==0?"#FFFFFF":"#DDDDDD");
            echo <<<EOF
<tr valign="top" bgcolor="$color">
    <td><a href="$SELF?action=edit&cons=$consid">edit</a></td>
    <td>$label</td>
    <td>$name</td>
    <td>
EOF;
            $res2 = db_query($conn,
                "SELECT network, netmask
                 FROM   networks
                 WHERE  constituency = $consid
                 ORDER BY network")
            or die("Unable to excute query 2");

            while ($row2 = db_fetch_next($res2))
            {
                $network = $row2["network"];
                $netmask = $row2["netmask"];

                echo "$network / $netmask<BR>";
            }
            printf("<a href='$SELF?action=editblocks&cons=%s'>edit
            networks</a>", $consid);

            db_free_result($res2);

            echo <<<EOF
</td>
</tr>
EOF;
        } // while $row
        echo "</table>";

        db_free_result($res);
        db_close($conn);

        echo "<h3>New constituency</h3>";
        show_form("");

        break;

    //-----------------------------------------------------------------
    case "edit":
        if (array_key_exists("cons", $_GET)) $cons=$_GET["cons"];
        else die("Missing information.");

        pageHeader("Edit constituency");
        show_form($cons);
        pageFooter();
        break;

    //-----------------------------------------------------------------
    case "add":
    case "update":
        if (array_key_exists("consid", $_POST)) $consid=$_POST["consid"];
        else $consid="";
        if (array_key_exists("label", $_POST)) $label=$_POST["label"];
        else die("Missing information (1).");
        if (array_key_exists("description", $_POST)) 
            $description=$_POST["description"];
        else die("Missing information (2).");

        if ($action=="add")
        {
            $conn = db_connect(DBDB, DBUSER, DBPASSWD)
            or die("Unable to connect to database.");

            $res = db_query($conn, sprintf("
                INSERT INTO constituencies
                (id, label, name)
                VALUES
                (nextval('constituencies_sequence'), %s, %s)",
                    db_masq_null($label),
                    db_masq_null($description)))
            or die("Unable to excute query.");

            db_close($conn);
            Header("Location: $SELF");
        }

        else if ($action=="update")
        {
            if ($consid=="") die("Missing information (3).");
            $conn = db_connect(DBDB, DBUSER, DBPASSWD)
            or die("Unable to connect to database.");

            $res = db_query($conn, sprintf("
                UPDATE constituencies
                set  label=%s,
                     name=%s
                WHERE id=%s",
                    db_masq_null($label),
                    db_masq_null($description),
                    $consid))
            or die("Unable to excute query.");

            db_close($conn);
            Header("Location: $SELF");
        }

        break;

    //-----------------------------------------------------------------
    case "Delete":
        if (array_key_exists("cons", $_GET)) $cons=$_GET["cons"];
        else die("Missing information.");

        $conn = db_connect(DBDB, DBUSER, DBPASSWD)
        or die("Unable to connect to database.");

        $res = db_query($conn, "
            DELETE FROM constituencies
            WHERE  id='$cons'")
        or die("Unable to execute query.");

        db_close($conn);
        Header("Location: $SELF");
        
        break;
    //-----------------------------------------------------------------
    default:
        die("Unknown action: $action");
 } // switch

?>
 
