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
 * search.php - Search for additional information on host
 * 
 * $Id$
 */
 
 require_once '/etc/airt/airt.cfg';
 require_once LIBDIR.'/airt.plib';
 require_once LIBDIR.'/incident.plib';
 require_once LIBDIR.'/constituency.plib';
 require_once LIBDIR.'/userfunctions.plib';

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

        // call user-supplied categorization routine. Returns the id of the
        // constituency
        $networkid = custom_categorize($ip, categorize($ip));

        // get addl info
        $networks = getNetworks();
        $constituencies = getConstituencies();

        $network = $networks[$networkid]["network"];
        $netmask = $networks[$networkid]["netmask"];
        $netname = $networks[$networkid]["label"];
        $conslabel = $constituencies[$networks[$networkid]["constituency"]]
            ["label"];
        $consname  = $constituencies[$networks[$networkid]["constituency"]]
            ["name"];

        // update active IP address
        $_SESSION["active_ip"] = $ip;

        pageHeader("Detailed information for host $hostname");
        
        echo <<<EOF
Search results for the following host:
<PRE>
    
    IP Address          : $ip
    Hostname            : $hostname
    Network             : $netname ($network/$netmask)
    Constituency        : $consname ($conslabel)
</PRE>
EOF;

        // call user-defined search function. Must print in unformatted layout
        // additional info about hostname needed to make a decision.
        echo "<HR>";
        search_info($ip, $networkid);
        echo "<HR>";


        // include previous incidents
        echo "<h2>Previous incidents</h2>";

        // create new incident
        echo <<<EOF
<form action="incident.php" method="POST">
<input type="hidden" name="ip" value="ip">
<input type="hidden" name="ip" value="ip">
<input type="submit" name="link" value="Link to incident">
EOF;
        showOpenIncidentSelection("incidentid");
        echo <<<EOF
<input type="submit" name="new" value="New incident">
EOF;

        pageFooter();
        
        break;
    default:
        die("Unknown action.");
 } // switch
?>
