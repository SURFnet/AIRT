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
 * constituency_contacts.php -- manage constituency contacts
 * 
 * $Id$
 */
 require_once '/etc/airt/airt.cfg';
 require_once LIBDIR.'/airt.plib';
 require_once LIBDIR.'/database.plib';
 
 $SELF = "constituency_contacts.php";

 if (array_key_exists("action", $_REQUEST)) $action=$_REQUEST["action"];
 else $action = "list";

 switch ($action)
 {
    //-----------------------------------------------------------------
    case "list":
        pageHeader("Constituency contacts");
        $conn = db_connect(DBDB, DBUSER, DBPASSWD)
        or die("Unable to connect to database.");

        $res = db_query($conn,
            "SELECT   id, label, name
             FROM     constituencies
             ORDER BY label")
        or die("Unable to execute query.");

        echo "Please select a constituency to edit assign contacts:<P>";
        while($row=db_fetch_next($res))
        {
            $id = $row["id"];
            $label = $row["label"];
            $name = $row["name"];
            echo "<a href=\"$SELF?action=edit&id=$id\">$label - $name</a><P>";
        }
        db_free_result($res);

        db_close($conn);
        pageFooter();
        break;
        
    //-----------------------------------------------------------------
    case "edit":
        if (array_key_exists("id", $_GET)) $id=$_GET["id"];
        else die("Missing information.");

        pageHeader("Edit constituency assignments");

        $conn = db_connect(DBDB, DBUSER, DBPASSWD)
        or die("Unable to connect to database.");

        $res = db_query($conn, 
            "SELECT label, name
             FROM   constituencies
             WHERE  id=$id")
        or die("Unable to execute query 1.");

        if (db_num_rows($res) == 0)
        {
            die("Invalid constituency.");
        }

        $row = db_fetch_next($res);
        $label = $row["label"];
        $name  = $row["name"];
        db_free_result($res);
        
        echo "<h3>Current contacts of constituency $label</H3>";
        
        $res = db_query($conn,
            "SELECT cr.userid, login, lastname, firstname, email, phone
             FROM   constituency_contacts cc, users u, credentials cr
             WHERE  cc.constituency=$id
             AND    cc.userid = u.id
             AND    cc.userid = cr.userid")
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
                $userid = $row["userid"];

                printf("<tr>
                            <td>%s (%s, %s)</td>
                            <td><a href=\"mailto:%s\">%s</a></td>
                            <td>%s</td>
                            <td><a
                            href=\"$SELF?action=remove&cons=%s&user=%s\">Remove</a></td>
                        </tr>",
                        $login, $lastname, $firstname,
                        $email, $email,
                        $phone, $id, $userid);
            }
            echo "</table>";
        }

        db_free_result($res);
        $res = db_query($conn,
            "SELECT c.userid, login, lastname, firstname
             FROM    users u, credentials c
             WHERE   u.id = c.userid
             AND     NOT u.id IN (
                SELECT userid
                FROM   constituency_contacts
                WHERE  constituency=$id
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
            $userid    = $row["userid"];

            printf("<option value=\"$userid\">$login ($lastname, ".
            "$firstname)</option>\n");
        }
        echo <<<EOF
</SELECT>
<input type="hidden" name="cons" value="$id">
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

        echo <<<EOF
<P><HR>
<a href="$SELF">Select another constituency</a> &nbsp;|&nbsp;
<a href="maintenance.php">Settings</a>
EOF;
        pageFooter();
        break;

    //-----------------------------------------------------------------
    case "assignuser":
        if (array_key_exists("cons", $_POST)) $cons=$_POST["cons"];
        else die("Missing information (1).");
        if (array_key_exists("userid", $_POST)) $userid=$_POST["userid"];
        else die("Missing information (2).");

        $conn = db_connect(DBDB, DBUSER, DBPASSWD)
        or die("Unable to connect to database.");
        
        foreach ($userid as $key=>$value)
        {
            $res=db_query($conn,"
                INSERT INTO constituency_contacts
                (id, constituency, userid)
                VALUES
                (nextval('role_assignments_sequence'), $cons, $value)")
            or die("Unable to execute query");
        }
        db_close($conn);
        Header("Location: $SELF?action=edit&id=$cons");
        break;

    //-----------------------------------------------------------------
    case "remove":
        if (array_key_exists("cons", $_GET)) $cons=$_GET["cons"];
        else die("Missing information (1).");
        if (array_key_exists("user", $_GET)) $userid=$_GET["user"];
        else die("Missing information (2).");

        $conn = db_connect(DBDB, DBUSER, DBPASSWD)
        or die("Unable to connect to database.");

        $res = db_query($conn,
            "DELETE FROM constituency_contacts
             WHERE  userid=$userid
             AND    constituency=$cons")
        or die("Unable to execute query");
        db_close($conn);
        Header("Location: $SELF?action=edit&id=$cons");

        break;
    
    //-----------------------------------------------------------------
    default:
        die("Unknown action: $action");
 } // switch

?>
 
