<?php
/* $Id$ 
 * index.php - Index page for UvT-CERT
 *
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
 * index.php - AIR console
 * $Id$
 */
require "../lib/database.plib";
require "../lib/air.plib";
require "../lib/rt.plib";

pageHeader("AIR Control Center");

$new = RT_countNewMessages(LIBERTYQUEUE);

function getColorState()
{
    $f = @fopen(
        "https://liberty.uvt.nl/liberty/colorstate.php?action=label",
        "r");
    if ($f) 
        $threat = fgets($f);
    else 
        $theat = "";
    @fclose($f);

    return $threat;
}

$filename=sprintf("/var/lib/cert/last_%s.txt", $_SESSION["username"]);
if (file_exists($filename))
{
  $f = fopen($filename, "r");
  $last = fgets($f);
  fclose($f);
  printf("<small>$last</small>");
}
/*
echo "<P>";
echo "The current UvT-CERT color state is: <B>".getColorState()."</B>";
echo "<P>";
*/
echo "<HR>";
?>


<table width="100%">
<tr>
    <td><B>Common tasks</B></td>
    <td><B>Maintenance tasks</B></td>
</tr>

<tr valign="top">
    <td>
<a href="mail.php">Incoming messages</a> (<?php echo $new; ?> new)

<P>

<a href="incident.php">Incident management</a>

<P>

<a href="search.php">IP Address lookup</a>

<P>

<?php
    $conn = db_connect(RTNAME, RTUSER, RTPASSWD)
    or die("Unable to connect to database.");

    $res = db_query($conn, "
        SELECT *
        FROM   URLs
        ORDER BY created")
    or die("Unable to query database.");

    while ($row = db_fetch_next($res))
    {
        $url = $row["url"];
        $description = $row["description"];
        printf("<a href=\"%s\">%s</a><p>",
            $url, $description);
    }
    db_close($conn);
?>

<td>

<a href="constituencies.php">Edit constituencies</a>

<P>

<a href="links.php">Edit links</a>

<P>
</td>

</table>

<P>

<a onclick="return confirm('Are you sure that you want to log out?')" 
   href="logout.php">Logout</a>

<?php
    pageFooter();
?>
