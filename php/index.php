<?php
/* $Id$ 
 * index.php - Index page for UvT-CERT
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
 *
 * index.php - Liberty console
 * $Id$
 */
require "../lib/liberty.plib";
require "../lib/rt.plib";

pageHeader("Liberty Control Center");

$new = RT_countNewMessages(LIBERTYQUEUE);

$filename=sprintf("/var/lib/cert/last_%s.txt", $_SESSION["username"]);
if (file_exists($filename))
{
  $f = fopen($filename, "r");
  $last = fgets($f);
  fclose($f);
  printf("<small>$last</small>");
  echo "<P>&nbsp;<P>";
}
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

<a href="ports.php">Common TCP/UDP port lookup</a>

<P>

<a href="contacten_extern.php">Externe contactpunten</a>

<P>

<a href="dienstlijst.php">De dienstlijst</a>

<P>

<a href="contactlijst.php">De UvT-CERT telefoonnummers</a>

</td>


<td>

<a href="constituencies.php">Edit constituencies</a>

</td>

</table>

<P>

<a onclick="return confirm('Are you sure that you want to log out?')" 
   href="logout.php">Logout</a>

<?php
    pageFooter();
?>
