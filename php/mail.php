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
 require '../lib/pgsql.plib';

 $SELF="$BASEURL/mail.php";

 if (array_key_exists("action", $_SESSION)) $action=$_SESSION["action"];
 else $action="none";

 switch ($action)
 {
    case "none":
        $conn = db_connect(RTNAME, RTUSER, RTPASS)
        or die("unable to connect to database: ".db_errormsg());

        $res = db_query($conn, 
            "SELECT   u.name, a.subject, a.created
             FROM     tickets t, attachments a, users u
             WHERE    t.id = a.id
             AND      t.creator = u.id
             AND      queue = 0
             ORDER BY t.created")
        or die("Unable to query database: ".db_errormsg());

        printf("<TABLE>\n");
        while ($row = db_fetch_next($res))
        {
            $requestor = $row["name"];
            $subject   = $row["subject"];
            $created   = $row["created"];

            printf("<TR>\n");
            printf("<TD>%s</TD>\n", $requestor);
            printf("<TD>%s</TD>\n", $subject);
            printf("<TD>%s</TD>\n", $created);
            printf("</TR>");

        } // while
        printf("</TABLE>\n");

        db_close($conn);
        break;
    default:
        die("Unknown action.");
 } // switch

?>
