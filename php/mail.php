<?php
/* $Id$
 *
 * Handle incoming mail
 *
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
 */
 $public = 1;
 require '../lib/liberty.plib';
 require '../lib/pgsql.plib';

 $SELF="$BASEURL/mail.php";
 session_start();
 if (array_key_exists("action", $_REQUEST)) $action=$_REQUEST["action"];
 else $action="none";

 switch ($action)
 {
    case "none":
        pageHeader("Incoming messages");

        $conn = db_connect(RTNAME, RTUSER, RTPASSWD)
        or die("unable to connect to database: ".db_errormessage());

        $res = db_query($conn, 
            "SELECT   t.id, u.emailaddress, t.subject, t.created
             FROM     tickets t, users u, queues q
             WHERE    t.creator = u.id
             AND      t.queue = q.id
             AND      q.name = '".LIBERTYQUEUE."'
             ORDER BY t.created")
        or die("Unable to query database: ".db_errormessage());

        printf("<TABLE WIDTH=\"100%%\" BORDER=\"1\">\n");
        while ($row = db_fetch_next($res))
        {
            $requestor = $row["emailaddress"];
            $subject   = $row["subject"];
            $created   = $row["created"];
            $id        = $row["id"];

            printf("<TR valign='top'>\n");
            printf("<TD><B><a href='%s?action=show&id=%s'>%s</a></B><BR>
                        <small>%s</small></TD>\n", 
                $SELF, $id, $subject, $requestor);
            printf("<TD NOWRAP>%s</TD>\n", $created);
            printf("</TR>");

        } // while
        printf("</TABLE>\n");

        db_close($conn);

        pageFooter();
        break;
    
    // --------------------------------------------------------------------
    
    case "show":
        pageHeader("Message details");
        if (array_key_exists("id", $_REQUEST)) $id = $_REQUEST["id"];
        else die("Missing parameter.");

        $conn = db_connect(RTNAME, RTUSER, RTPASSWD)
        or die("unable to connect to database: ".db_errormessage());

        $res = db_query($conn,
            "SELECT   u.emailaddress, u.realname, t.subject, 
               extract (epoch from t.created) as created
             FROM     tickets t, users u
             WHERE    t.id = '$id'
             AND      u.id = t.creator")
        or die("Unable to query database: ".db_errormessage());
        
        echo <<<EOF
<P>
<form action="$SELF" method="POST">
<table width="100%" bgcolor="#DDDDDD" border=0 cellpadding=2>
<tr>
    <td>IP address:</td>
    <td>
        <input type="text" size="40" name="hostname">
        <input type="submit" value="Apply">
    </td>
</tr>

<tr>
    <td>Action</td>
    <td>
        <input type="radio" name="action" value="search">Search</input>
        <input type="radio" name="action" value="new">New incident</input>
        <input type="radio" name="action" value="ignore">Ignore message</input>
    </td>
</tr>

</table>
</form>

<table>
<tr>
    <td>From:</td>
    <td>$name &lt;$from&gt;</td>
</tr>
<tr>
    <td>Subject:</td>
    <td>$subject:</td>
</tr>
<tr>
    <td>Date:</td>
    <td>$created</td>
</tr>
</table>
EOF;

        // show ticket information
        if ($row = db_fetch_next($res))
        {
            $from = $row["emailaddress"];
            $name = $row["realname"];
            $subject = $row["subject"];
            $created = Date("r",$row["created"]);
            
        }
        db_free_result($res);

        // get actual email 
        $query = sprintf("
            SELECT a.headers, a.content
            FROM   attachments a, transactions t
            WHERE  t.ticket = '%s'
            AND    a.transactionid = t.id
            ", $id);
        $res = db_query($conn, $query)
        or die("Unable to query database: ".db_errormessage());

        while ($row = db_fetch_next($res))
        {
            printf("<PRE>%s</PRE>", $row["content"]);
        }

        db_close($conn);

        echo <<<EOF
<form action="$SELF" method="POST">
<div width="100%" style="background-color: #DDDDDD">
Incident ID:
    <input type="text" size="40" name="incidentid">
    <input type="submit" value="Add to incident">
</div>
<input type="hidden" name="action" value="associate">
</form>
EOF;

        pageFooter();
        break;

    // --------------------------------------------------------------------

    case "search":
        if (array_key_exists("hostname", $_REQUEST))
            $hostname = $_REQUEST["hostname"];
        else die("Missing information");

        Header(
            sprintf("Location: https://liberty.uvt.nl/cert/search.php?ip=%s&action=search",
            urlencode($hostname)));
        break;

    default:
        die("Unknown action.");
 } // switch

?>
