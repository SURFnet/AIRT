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
 * constituencies.php -- manage constituency data
 * 
 * $Id$
 */
 require '../lib/liberty.plib';
 require '../lib/database.plib';
 
 $SELF = "constituencies.php";

 if (array_key_exists("action", $_REQUEST)) $action=$_REQUEST["action"];
 else $action = "list";

 switch ($action)
 {
    // --------------------------------------------------------------
    case "list":
        pageHeader("Constituencies");
        $conn = db_connect(RTNAME, RTUSER, RTPASSWD)
        or die("Unable to connect to database.");

        $res = db_query($conn, "
            SELECT   * 
            FROM     constituencies 
            ORDER BY name")
        or die("Unable to query database.");

        echo <<<EOF
<table width="100%" border="1">
<tr>
    <th>Name</th>
    <th>Contact</th>
    <th>Email</th>
    <th>Phone</th>
    <th>Edit</th>
</tr>
EOF;
        $count = 0;
        while ($row = db_fetch_next($res))
        {
            $id  = $row["id"];
            $name = $row["name"];
            $contact_email = $row["contact_email"];
            $contact_name  = $row["contact_name"];
            $contact_phone = $row["contact_phone"];
            $bgcolor = ($count++%2==0 ? "#DDDDDD" : "#FFFFFF");

            echo <<<EOF
<tr bgcolor="$bgcolor">
    <td>$name</td>
    <td>$contact_name</td>
    <td>$contact_email</td>
    <td>$contact_phone</td>
    <td><small><a href="$SELF?action=edit&id=$id">edit</a></small></td>
</tr>
EOF;
        }
        echo <<<EOF
</table>
<P>
<a href="$SELF?action=new">New constituency</a>
EOF;
        db_close($conn);
        pageFooter();
        break;

    // --------------------------------------------------------------
    case "edit":
        PageHeader("Edit");
        if (array_key_exists("id", $_REQUEST))
            $id = $_REQUEST["id"];
        else die("Missing information.");

        $conn = db_connect(RTNAME, RTUSER, RTPASSWD)
        or die("Unable to connect to database.");

        $res = db_query($conn,
            "SELECT *
             FROM   constituencies
             WHERE  id=$id")
        or die("Unable to query database.");

        if (db_num_rows($res) > 0)
        {
            $row = db_fetch_next($res);
            $name = $row["name"];
            $description = $row["description"];
            $contact_name = $row["contact_name"];
            $contact_email = $row["contact_email"];
            $contact_phone = $row["contact_phone"];
        }
        db_free_result($res);
        db_close($conn);


    // --------------------------------------------------------------
    case "new":
        echo "<form action=\"$SELF\" method=\"post\">";
        if ($action == "new") 
        {
            $next = "add";
            pageHeader("New constituency");
        }
        else 
        {
            $next = "update";
            echo "<input type=\"hidden\" name=\"id\" value=\"$id\">";
        }
        echo <<<EOF
<input type="hidden" name="action" value="$next">
<table>
<tr>
    <td>constituency name:</td>
    <td><input type="text" name="name" size="30" value="$name"></td>
</tr>
<tr>
    <td>Description:</td>
    <td><input type="text" name="description" size="50" 
        value="$description">
    </td>
<tr>
    <td>Contact person/institute:</td>
    <td><input type="text" name="contact_name" size="50" 
        value="$contact_name">
    </td>
</tr>
<tr>
    <td>Contact email address:</td>
    <td><input type="text" name="contact_email" size="50"
        value="$contact_email">
    </td>
</tr>
<tr>
    <td>Contact phone:</td>
    <td><input type="text" name="contact_phone" size="50"
        value="$contact_phone">
    </td>
</tr>
</table>
<p>
<input type="submit" value="$next!">
</form>
EOF;
        break;

        
    // --------------------------------------------------------------
    case "add":
    case "update":
        // required fields
        if (array_key_exists("name", $_REQUEST))
            $name = $_REQUEST["name"];
        else die("Missing constituency name");

        // optional fields
        if (array_key_exists("description", $_REQUEST))
            $description = $_REQUEST["description"];
        else $description = "";
        
        if (array_key_exists("contact_email", $_REQUEST))
            $contact_email = $_REQUEST["contact_email"];
        else $contact_email = "";
        
        if (array_key_exists("contact_phone", $_REQUEST))
            $contact_phone = $_REQUEST["contact_phone"];
        else $contact_phone = "";
        
        if (array_key_exists("contact_name", $_REQUEST))
            $contact_name = $_REQUEST["contact_name"];
        else $contact_name = "";

        if (array_key_exists("id", $_REQUEST))
            $id = $_REQUEST["id"];
        else $id = "";

        $conn = db_connect(RTNAME, RTUSER, RTPASSWD)
        or die("Unable to connect to database.");

        $now = Date("Y-m-d H:i:s");

        if ($action == "add")
            $res = db_query($conn, sprintf("
                INSERT INTO constituencies
                (id, name, description, contact_email, contact_name,
                 contact_phone, created, createdby)
                VALUES
                (nextval('constituencies_seq') , '%s', '%s', '%s', '%s', 
                 '%s', '%s', %s)",
                $name,
                $description,
                $contact_email,
                $contact_name,
                $contact_phone,
                $now,
                $_SESSION["userid"]))
            or die("Unable to add constituency.");
        else if ($action == "update")
            $res = db_query($conn, sprintf("
                UPDATE constituencies
                SET    name = '%s',
                       description = '%s',
                       contact_email = '%s',
                       contact_name  = '%s',
                       contact_phone = '%s'
                WHERE  id = %s", 
                $name,
                $description,
                $contact_email,
                $contact_name,
                $contact_phone,
                $id
                ))
            or die("Unable to update constituency.");

        db_close($conn);
        Header(sprintf("Location: %s/%s?action=list", BASEURL, $SELF));

        break;
    default:
        die("Unknown action: $action");
 } // switch

?>
 
