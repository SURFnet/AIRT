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
 require '../lib/liberty.plib';
 require '../lib/rt.plib';

 $SELF="$BASEURL/mail.php";

 if (array_key_exists("action", $_REQUEST)) $action=$_REQUEST["action"];
 else $action="none";

 switch ($action)
 {
    case "none":
        pageHeader("Incoming messages");
        $msgs = RT_getNewTicketIds(LIBERTYQUEUE);

        printf("<TABLE WIDTH=\"100%%\" BORDER=\"1\">\n");
        foreach ($msgs as $a => $index)
        {
            $msg       = RT_getTicketById($index);
            $created   = $msg["created"];
            $subject   = $msg["subject"];
            $sender_id = $msg["creator"];

            $sender    = RT_getUserById($sender_id);
            $sender_name = $sender["realname"];
            
            printf("<TR valign='top'>\n");
            printf("<TD><B><a href='%s?action=show&id=%s'>%s</a></B><BR>
                        <small>%s</small></TD>\n", 
                $SELF, $index, $subject, $sender_name);
            printf("<TD NOWRAP>%s</TD>\n", $created);
            printf("</TR>");
        }
        printf("</TABLE>\n");

        pageFooter();
        break;
    
    // --------------------------------------------------------------------
    
    case "show":
        pageHeader("Message details");
        if (array_key_exists("id", $_REQUEST)) $id = $_REQUEST["id"];
        else die("Missing parameter.");

        echo <<<EOF
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
        <input type="radio" name="action" value="search" CHECKED>Search</input>
        <input type="radio" name="action" value="new">New incident</input>
        <input type="radio" name="action" value="ignore">Ignore message</input>
        <input type="hidden" name="id" value="$id">
    </td>
</tr>

</table>
</form>
EOF;
       
        // show ticket summary
        $ticket = RT_getTicketById($id);
        if (count($ticket) == 0) 
        {
            printf("Unable to retrieve message.");
            return;
        }
        $userid  = $ticket["creator"];
        $subject = $ticket["subject"];
        $created = $ticket["created"];

        $creator = RT_getUserById($userid);
        $from    = $creator["emailaddress"];
        $name    = $creator["realname"];

        echo <<<EOF
<table cellpadding="2">
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
   
        // show message body
        $attachmentids = RT_getAttachmentsOfTicket($id);
        foreach ($attachmentids as $index => $i)
        {
            $attach = RT_getAttachmentById($i);
            $body    = $attach["content"];
            printf("<PRE>%s</PRE>", $body);
        }

        // allow association with ticket if not already associated

        pageFooter();
        break;

    // --------------------------------------------------------------------
    case "search":
        if (array_key_exists("hostname", $_REQUEST))
            $hostname = $_REQUEST["hostname"];
        else die("Missing information");

        Header(
            sprintf("Location: search.php?hostname=%s&action=search",
            urlencode($hostname)));
        break;

    // --------------------------------------------------------------------
    case "ignore":
        if (array_key_exists("id", $_REQUEST)) 
            $id = $_REQUEST["id"];
        else die("Missing information");

        $now = Date("Y-m-d H:i:s");
        $conn = db_connect(RTNAME, RTUSER, RTPASSWD)
        or die("unable to connect to database: ".db_errormessage());

        // update ticket status
        $res = db_query($conn, sprintf("
            UPDATE tickets
            SET    status = 'rejected',
                   lastupdatedby = %s,
                   lastupdated = '%s'
            WHERE  id = '%s'", 
            $_SESSION["userid"], $now, $id))
        or die("Unable to update status: ".db_errormessage());

        pg_free_result($res);

        // add transaction
        $res = db_query($conn, sprintf("
            INSERT INTO transactions
            (effectiveticket, ticket, type, field, oldvalue, newvalue,
             creator, created)
            VALUES
            ('%s', '%s', 'Status', 'Status', 'new', 'rejected', %s, '%s')",
            $id, $id, $_SESSION["userid"], $now))
        or die("Unable to insert transaction: ".db_errormessage());

        Header("Location: $SELF");
        break;

    // --------------------------------------------------------------------
    case "new":
        if (array_key_exists("hostname", $_REQUEST))
            $hostname = $_REQUEST["hostname"];
        else die("Missing information.");

        $page = sprintf("incident.php?action=new&hostname=%s",
            urlencode($hostname));
        Header(sprintf("Location: %s/%s", BASEURL, $page));
        break;

    // --------------------------------------------------------------------
    case "associate":
        if (array_key_exists("ticketid", $_REQUEST))
            $ticketid = $_REQUEST["ticketid"];
        else die("Missing information.");
        
        if (array_key_exists("incidentid", $_REQUEST))
            $incidentid = decode_incidentid(
                normalize_incidentid($_REQUEST["incidentid"])
            );
        else die("Missing information.");

        printf("TODO");

        break;

    // --------------------------------------------------------------------

    default:
        die("Unknown action.");
 } // switch

?>
