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
 * netblocks.php -- manage net blocks
 * 
 * $Id$
 */
 require_once 'config.plib';
 require_once LIBDIR.'/airt.plib';
 require_once LIBDIR.'/database.plib';
 require_once LIBDIR.'/constituency.plib';

 if (array_key_exists("action", $_REQUEST)) $action=$_REQUEST["action"];
 else $action = "list";

 function show_form($id="") {
    $label = "";
    $action = "add";
    $submit = "Add!";
	$constituency = "";
	$netmask = "";
	$network = "";

    if ($id != "") {
        $networks = getNetworks();
        if (array_key_exists($id, $networks))
        {
            $row = $networks["$id"];
            $action = "update";
            $submit = "Update!";
            $network = $row["network"];
            $netmask = $row["netmask"];
            $label   = $row["label"];
            $constituency = $row["constituency"];
        }
    }
    echo <<<EOF
<form action="$_SERVER[PHP_SELF]" method="POST">
<input type="hidden" name="action" value="$action">
<input type="hidden" name="id" value="$id">
<table>
<tr>
    <td>Network Address</td>
    <td><input type="text" size="30" name="network" value="$network"></td>
</tr>
<tr>
    <td>Netmask</td>
    <td><input type="text" size="30" name="netmask" value="$netmask"></td>
</tr>
<tr>
    <td>Label</td>
    <td><input type="text" size="30" name="label" value="$label"></td>
</tr>
<tr>
    <td>Constituency</td>
    <td>
EOF;
        showConstituencySelection("constituency", $constituency);
    echo <<<EOF
</table>
<p>
<input type="submit" value="$submit">
</form>
EOF;
 }

 /* very ugly function that is a first attempt to sort networks for the
  * overview. Currently this is done by comparing the individual network bytes
  * of the network address; the largest one comes first. 
  * TODO: improve this code to make it IPv4-independent, and maybe take
  * netmasks in account too. Also; there has to be a more elegant way of
  * coding this.
  */
 function airt_netsort($a, $b) {
	 $ea = explode('.', $a['network']);
	 $eb = explode('.', $b['network']);

	 if ($ea[0] > $eb[0]) return 1;
	 else if ($ea[0] < $eb[0]) return -1;
	 else {
		 if ($ea[1] > $eb[1]) return 1;
		 else if ($ea[1] < $eb[1]) return -1;
		 else {
			 if ($ea[2] > $eb[2]) return 1;
			 else if ($ea[2] < $eb[2]) return -1;
			 else {
				 if ($ea[3] > $eb[3]) return 1;
				 else if ($ea[3] < $eb[3]) return -1;
				 else return 0;
			 }
		 }
	 }
 }

 switch ($action)
 {
    // --------------------------------------------------------------
    case "list":
        pageHeader("Networks");

        echo <<<EOF
<table cellpadding="3">
<tr>
    <td><B>Network</B></td>
    <td><B>Netmask</B></td>
    <td><B>Label</B></td>
    <td><B>Constituency</B></td>
    <td><B>Edit</B></td>
    <td><B>Delete</B></td>

</tr>
EOF;

        $networklist = getNetworks();
		usort($networklist, "airt_netsort");
        $constituencies = getConstituencies();

        $count=0;
        foreach ($networklist as $nid=>$data) {
			$id = $data["id"];
            $network      = $data["network"];
            $netmask      = $data["netmask"];
            $label        = $data["label"];
            $constituency = $data["constituency"];
            $constituency_name  = $constituencies["$constituency"]["name"];
            $color = ($count++%2==0?"#FFFFFF":"#DDDDDD");
            echo <<<EOF
<tr valign="top" bgcolor="$color">
    <td>$network</td>
    <td>$netmask</td>
    <td>$label</td>
    <td><a href="constituencies.php?action=edit&cons=$constituency"
        >$constituency_name</a></td>
    <td><a href="$_SERVER[PHP_SELF]?action=edit&id=$id"><small>edit</small></td>
    <td><a href="$_SERVER[PHP_SELF]?action=delete&id=$id"><small>delete</small></td>
</tr>
EOF;
        } // while $row
        echo "</table>";

        echo "<h3>New network</h3>";
        show_form("");

        break;

    //-----------------------------------------------------------------
    case "edit":
        if (array_key_exists("id", $_GET)) $id=$_GET["id"];
        else die("Missing information.");

        pageHeader("Edit Network");
        show_form($id);
        pageFooter();
        break;

    //-----------------------------------------------------------------
    case "add":
    case "update":
        if (array_key_exists("id", $_POST)) $id=$_POST["id"];
        else $id="";
        if (array_key_exists("network", $_POST)) $network=$_POST["network"];
        else die("Missing information (1).");
        if (array_key_exists("netmask", $_POST)) $netmask=$_POST["netmask"];
        else die("Missing information (2).");
        if (array_key_exists("label", $_POST)) $label=$_POST["label"];
        else die("Missing information (3).");
        if (array_key_exists("constituency", $_POST)) 
            $constituency=$_POST["constituency"];
        else die("Missing information (4).");

        if ($action=="add")
        {
            $conn = db_connect(DBDB, DBUSER, DBPASSWD)
            or die("Unable to connect to database.");

            $res = db_query($conn, sprintf("
                INSERT INTO networks
                (id, network, netmask, label, constituency)
                VALUES
                (nextval('networks_sequence'), %s, %s, %s, %s)",
                    db_masq_null($network),
                    db_masq_null($netmask),
                    db_masq_null($label),
                    $constituency
                )
            ) or die("Unable to excute query.");

            db_close($conn);
            Header("Location: $_SERVER[PHP_SELF]");
        }

        else if ($action=="update")
        {
            if ($id=="") die("Missing information (5).");
            $conn = db_connect(DBDB, DBUSER, DBPASSWD)
            or die("Unable to connect to database.");

            $res = db_query($conn, sprintf("
                UPDATE networks
                SET    network=%s,
                       netmask=%s,
                       label=%s,
                       constituency=%s
                WHERE id=%s",
                    db_masq_null($network),
                    db_masq_null($netmask),
                    db_masq_null($label),
                    $constituency,
                    $id
                )
            ) or die("Unable to excute query.");

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
            DELETE FROM networks
            WHERE  id='$id'")
        or die("Unable to execute query.");

        db_close($conn);
        Header("Location: $_SERVER[PHP_SELF]");
        
        break;
    //-----------------------------------------------------------------
    default:
        die("Unknown action: $action");
 } // switch

?>
 
