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
 * search.php - Search for additional information on host
 * 
 * $Id$
 */

 require '../lib/liberty.plib';
 require '../lib/api.plib';
 require '../lib/userfunctions.plib';

 if (array_key_exists("action", $_REQUEST)) $action=$_REQUEST["action"];
 else $action = "none";

 $SELF = "search.php";

 switch ($action)
 {
    case "none":
        pageHeader("IP address search:");
        echo <<<EOF
<form action="$SELF" method="POST">
<input type="hidden" name="action" value="search">
<table width="100%" bgcolor="#DDDDDD" border=0 cellpadding=2>
<tr>
    <td>IP address:</td>
    <td>
        <input type="text" size="40" name="hostname">
        <input type="submit" value="Search">
    </td>
</tr>
</table>
EOF;
        pageFooter();
        break;
        
    // ------------------------------------------------------------------
    case "search":
        if (array_key_exists("hostname", $_REQUEST)) 
            $hostname = $_REQUEST["hostname"];
        else die("Missing information.");

        // normalize to IP address
        $ip = gethostbyname($hostname);

        // get FQDN
        $hostname = gethostbyaddr($ip);

        // call user-supplied categorization routine
        $constituency = categorize($ip, $hostname);

        pageHeader("Detailed information for host $hostname");

        // call user-defined search function. Must print in unformatted layout
        // additional info about hostname needed to make a decision.
        search_info($ip, $hostname, $constituency);


        // include constuency
        $con = AIR_getConstituencyByName($constituency);

        echo "<h2>Constituency</h2>";
        printf("
        <pre>
Constituency     %s
Contact point    %s
Email            <a href=\"mailto:%s\">%s</a>
Telephone        %s
        </pre>", 
            $constituency,
            $con->getContactName(),
            $con->getContactEmail(),
            $con->getContactEmail(),
            $con->getContactPhone());
         
        printf("<P><a href=\"%s/incident.php?action=new&hostname=%s&".
               "constituency=%s\">Create new incident</a>",
            BASEURL, urlencode($hostname), urlencode($constituency));

        pageFooter();
        
        break;
    default:
        die("Unknown action.");
 } // switch
?>
