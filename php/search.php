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
 
 require_once 'config.plib';
 require_once LIBDIR.'/airt.plib';
 require_once LIBDIR.'/incident.plib';
 require_once LIBDIR.'/constituency.plib';

 if (defined('CUSTOM_FUNCTIONS')) require_once CUSTOM_FUNCTIONS;

 if (array_key_exists("action", $_REQUEST)) $action=$_REQUEST["action"];
 else $action = "none";

 $SELF = "search.php";

 function ShowSearch()
 {
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
 }

 switch ($action)
 {
    case "none":
        pageHeader("IP address search:");
        showSearch();
        pageFooter();
        break;
        
    // ------------------------------------------------------------------
    case "search":
		unset($_SESSION["current_name"]);
		unset($_SESSION["current_name"]);
		unset($_SESSION["current_name"]);
		unset($_SESSION["current_name"]);

        if (array_key_exists("hostname", $_REQUEST)) 
            $hostname = $_REQUEST["hostname"];
        else die("Missing information.");

        // normalize to IP address
        $ip = gethostbyname($hostname);

        // get FQDN
        $hostname = gethostbyaddr($ip);

        // call user-supplied categorization routine. Returns the id of the
        // constituency
        $networkid = categorize($ip);
		if (defined('CUSTOM_FUNCTIONS'))
			$networkid = custom_categorize($ip, $networkid);

        // get addl info
        $networks = getNetworks();
        $constituencies = getConstituencies();
		
        $network = $networks[$networkid]["network"];
        $netmask = $networks[$networkid]["netmask"];
        $netname = $networks[$networkid]["label"];
        $consid  = $networks[$networkid]["constituency"];
        $conslabel = $constituencies[$consid]["label"];
        $consname  = $constituencies[$consid]["name"];

        // update active IP address
        $_SESSION["active_ip"] = $ip;
        $_SESSION["constituency_id"] = $consid;

        pageHeader("Detailed information for host $hostname");
        
        echo <<<EOF
Search results for the following host:
<PRE>
    
    IP Address          : $ip
    Hostname            : $hostname
    Network             : $netname ($network/$netmask)
    Constituency        : $consname
</PRE>

<H2>Constituency Contacts</H2>
EOF;
    showConstituencyContacts($consid);

        // call user-defined search function. Must print in unformatted layout
        // additional info about hostname needed to make a decision.
        echo "<HR>";
		if (defined('CUSTOM_FUNCTIONS')) {
			search_info($ip, $networkid);
			echo "<HR>";
		}


        // include previous incidents
		// TODO
        echo <<<EOF
<h2>Previous incidents</h2>
	
<I>Under construction</I>
EOF;
		
		echo <<<EOF
<h2>Link address to incident</h2>
EOF;
		
        // create new incident
        $count = showOpenIncidentSelection("incidentid");
        if ($count == 0) echo "<I>No previous incidents</I><P>";

        echo <<<EOF
<form action="incident.php" method="POST">
<input type="hidden" name="ip" value="ip">
EOF;
        if ($count>0)
        {
            echo <<<EOF
<input type="submit" name="action" value="Link to incident">
EOF;
        }
        echo <<<EOF
<input type="submit" name="action" value="New incident">
</form>
<P>
<HR>
<H2>New Search</H2>
EOF;
        showSearch();
        pageFooter();
        
        break;
    default:
        die("Unknown action.");
 } // switch
?>
