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
 * constituencies.php -- manage constituency data
 * 
 * $Id$
 */
 require '../lib/air.plib';
 require '../lib/constituency.plib';
 
 $SELF = "constituencies.php";

 if (array_key_exists("action", $_REQUEST)) $action=$_REQUEST["action"];
 else $action = "list";

 switch ($action)
 {
    // --------------------------------------------------------------
    case "list":
        pageHeader("Constituencies");
        echo <<<EOF
<table width="100%" border="1">
<tr>
    <th>Name</th>
    <th>Security Entry Point</th>
    <th>Email</th>
    <th>Phone</th>
    <th>Edit</th>
</tr>
EOF;
        $constituencies = AIR_getConstituencies();
        $count = 0;
        foreach ($constituencies as $i => $tuple)
        {
            $id  = $tuple["id"];
            $name = $tuple["name"];
            $contact_email = $tuple["contact_email"];
            $contact_name  = $tuple["contact_name"];
            $contact_phone = $tuple["contact_phone"];
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
        pageFooter();
        break;

    // --------------------------------------------------------------
    case "edit":
        PageHeader("Edit");
        if (array_key_exists("id", $_REQUEST))
            $id = $_REQUEST["id"];
        else die("Missing information.");

        $constituency = AIR_getConstituencyById($id);
        $name          = $constituency->getName();
        $description   = $constituency->getDescription();
        $contact_name  = $constituency->getContactName();
        $contact_email = $constituency->getContactEmail();
        $contact_phone = $constituency->getContactPhone();

        // note: break missing on purpose!

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
    <td>Constituency name:</td>
    <td><input type="text" name="name" size="30" value="$name"></td>
</tr>
<tr>
    <td>Description:</td>
    <td><input type="text" name="description" size="50" 
        value="$description">
    </td>
<tr>
    <td>Security Entry Point (SEP):</td>
    <td><input type="text" name="contact_name" size="50" 
        value="$contact_name">
    </td>
</tr>
<tr>
    <td>SEP email address:</td>
    <td><input type="text" name="contact_email" size="50"
        value="$contact_email">
    </td>
</tr>
<tr>
    <td>SEP phone:</td>
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

        $now = Date("Y-m-d H:i:s");

        if ($action == "add")
        {
            $constituency = new AIR_Constituency();
            $constituency->setName($name);
            $constituency->setDescription($description);
            $constituency->setContactEmail($contact_email);
            $constituency->setContactName($contact_name);
            $constituency->setContactPhone($contact_phone);
            $constituency->setCreated($now);
            $constituency->setCreatedBy($_SESSION["userid"]);

            AIR_addConstituency($constituency);
        }

        else if ($action == "update")
        {
            $constituency = AIR_getConstituencyById($id);
            $constituency->setName($name);
            $constituency->setDescription($description);
            $constituency->setContactEmail($contact_email);
            $constituency->setContactName($contact_name);
            $constituency->setContactPhone($contact_phone);

            AIR_updateConstituency($constituency);
        }

        Header(sprintf("Location: %s/%s?action=list", BASEURL, $SELF));

        break;
    default:
        die("Unknown action: $action");
 } // switch

?>
 
