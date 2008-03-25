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
 * roleassignments.php -- manage roles
 * 
 * $Id$
 */
 exit; // not in production yet
 require_once 'config.plib';
 require_once LIBDIR.'/airt.plib';
 require_once LIBDIR.'/database.plib';
 
 $SELF = "roleassignments.php";

 if (array_key_exists("action", $_REQUEST)) $action=$_REQUEST["action"];
 else $action = "list";

 switch ($action)
 {
    //-----------------------------------------------------------------
    case "list":
        pageHeader("Role assignments");
        $conn = db_connect(DBDB, DBUSER, DBPASSWD)
        or die("Unable to connect to database.");

        $res = db_query($conn,
            "SELECT   label, id
             FROM     roles
             ORDER BY label")
        or die("Unable to execute query.");

        echo "Please select a role to edit the assigned users:<P>";
        while($row=db_fetch_next($res))
        {
            $id = $row["id"];
            $label = $row["label"];
            echo "<a href=\"$SELF?action=edit&roleid=$id\">$label</a><P>";
        }
        db_free_result($res);

        db_close($conn);
        pageFooter();
        break;
        
    //-----------------------------------------------------------------
    case "edit":
        if (array_key_exists("roleid", $_GET)) $roleid=$_GET["roleid"];
        else die("Missing information.");

        pageHeader("Edit role assignments");

        $conn = db_connect(DBDB, DBUSER, DBPASSWD)
        or die("Unable to connect to database.");

        $res = db_query($conn, 
            "SELECT label
             FROM   roles
             WHERE  id=$roleid")
        or die("Unable to execute query 1.");

        if (db_num_rows($res) == 0)
        {
            die("Invalid role.");
        }

        $row = db_fetch_next($res);
        $rolelabel = $row["label"];
        db_free_result($res);
        
        echo "<h3>Users currently assigned to role $rolelabel</h3>";
        
        $res = db_query($conn,
            "SELECT u.id, login, lastname, firstname, email, phone
             FROM   role_assignments ra, users u
             WHERE  ra.role=$roleid
             AND    ra.userid = u.id")
        or die("Unable to execute query(2).");

        if (db_num_rows($res) == 0)
        {
            echo "<I>No assigned users.</I>";
        } 
        else
        {
            echo "<table border=1 cellpadding=4>";
            while ($row = db_fetch_next($res))
            {
                $login = $row["login"];
                $lastname = $row["lastname"];
                $firstname = $row["firstname"];
                $email = $row["email"];
                $phone = $row["phone"];
                $id = $row["id"];

                printf("<tr>
                            <td>%s (%s, %s)</td>
                            <td><a href=\"mailto:%s\">%s</a></td>
                            <td>%s</td>
                            <td><a
                            href=\"$SELF?action=remove&role=%s&user=%s\">Remove</a></td>
                        </tr>",
                        $login, $lastname, $firstname,
                        $email, $email,
                        $phone, $roleid, $id);
            }
            echo "</table>";
        }

        db_free_result($res);
        $res = db_query($conn,
            "SELECT  u.id, login, lastname, firstname
             FROM    users u
             WHERE   NOT u.id IN (
                SELECT userid
                FROM   role_assignments
                WHERE  role=$roleid
             )")
        or die("Unable to execute query(3).");

        if (db_num_rows($res) > 0)
        {
            echo <<<EOF
<P>
<FORM action="$SELF" method="POST">
Add user(s) to role: 
<SELECT MULTIPLE name="userid[]">

EOF;
        while ($row = db_fetch_next($res))
        {
            $login     = $row["login"];
            $lastname  = $row["lastname"];
            $firstname = $row["firstname"];
            $id        = $row["id"];

            printf("<option value=\"$id\">$login ($lastname, ".
            "$firstname)</option>\n");
        }
        echo <<<EOF
</SELECT>
<input type="hidden" name="roleid" value="$roleid">
<input type="hidden" name="action" value="assignuser">
<input type="submit" value="Assign">
</FORM>
EOF;
        } 
        else
        {   
            echo "<P><I>No unassigned users.</I>";
        }
        db_close($conn);
        pageFooter();
        break;

    //-----------------------------------------------------------------
    case "assignuser":
        if (array_key_exists("roleid", $_POST)) $roleid=$_POST["roleid"];
        else die("Missing information (1).");
        if (array_key_exists("userid", $_POST)) $userid=$_POST["userid"];
        else die("Missing information (2).");

        $conn = db_connect(DBDB, DBUSER, DBPASSWD)
        or die("Unable to connect to database.");
        
        foreach ($userid as $key=>$value)
        {
            $res=db_query($conn,"
                INSERT INTO role_assignments
                (id, role, userid)
                VALUES
                (nextval('role_assignments_sequence'), $roleid, $value)")
            or die("Unable to execute query");
        }
        db_close($conn);
        Header("Location: $SELF");
        break;

    //-----------------------------------------------------------------
    case "remove":
        if (array_key_exists("role", $_GET)) $roleid=$_GET["role"];
        else die("Missing information (1).");
        if (array_key_exists("user", $_GET)) $userid=$_GET["user"];
        else die("Missing information (2).");

        $conn = db_connect(DBDB, DBUSER, DBPASSWD)
        or die("Unable to connect to database.");

        $res = db_query($conn,
            "DELETE FROM role_assignments
             WHERE  userid=$userid
             AND    role=$roleid")
        or die("Unable to execute query");
        db_close($conn);
        Header("Location: $SELF");

        break;
    //-----------------------------------------------------------------
    default:
        die("Unknown action: $strip_tags(action)");
 } // switch

?>
 
