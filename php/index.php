<?php
/* $Id$ 
 * index.php - Index page for UvT-CERT
 *
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
 * index.php - AIR console
 * $Id$
 */
require_once '/usr/local/etc/airt/airt.cfg';
require_once LIBDIR."/airt.plib";
require_once LIBDIR."/database.plib";

pageHeader("AIRT Control Center");

$filename=sprintf(STATEDIR."/last_%s.txt", $_SESSION["username"]);
if (file_exists($filename))
{
  $f = fopen($filename, "r");
  $lastlogin = fgets($f, 255);
  fclose($f);
  printf("<small>$lastlogin</small>");
}
echo "<HR>";
?>


<B>Main tasks</B>

<P>

<a href="incident.php">Incident management</a> (Work in progress)

<P>

<a href="search.php">IP Address lookup</a>

<P>

<a href="standard.php">Mail templates</a>

<P>

<a href="maintenance.php">Edit settings</a>

<P>

<?php
    $conn = db_connect(DBDB, DBUSER, DBPASSWD)
    or die("Unable to connect to database.");

    $res = db_query($conn, "
        SELECT *
        FROM   urls
        ORDER BY created")
    or die("Unable to query database.");

    while ($row = db_fetch_next($res))
    {
        $url = $row["url"];
        $description = $row["label"];
        printf("<a href=\"%s\">%s</a><p>",
            $url, $description);
    }
    db_close($conn);
?>

<P>

<a onclick="return confirm('Are you sure that you want to log out?')" 
   href="logout.php">Logout</a>

<?php
    pageFooter();
?>
